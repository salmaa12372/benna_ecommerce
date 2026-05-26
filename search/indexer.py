#!/usr/bin/env python3
"""
search/indexer.py
Pipeline NLP — génère products_index.json + benna_search.js
Usage :
    python3 indexer.py              # depuis la DB
    python3 indexer.py --from-json  # depuis data/products_raw.json
"""

import json, re, math, os, sys, unicodedata

# ══════════════════════════════════════════════════════════════
#  CONFIG DB  ← modifiez ici
# ══════════════════════════════════════════════════════════════
DB_CONFIG = {
    "host":     "127.0.0.1",
    "port":     3306,
    "user":     "root",
    "password": "",           # ← votre mot de passe XAMPP
    "database": "db_benna",   # ← nom de votre base
}


# ══════════════════════════════════════════════════════════════
#  CHEMINS
# ══════════════════════════════════════════════════════════════
DIR        = os.path.dirname(os.path.abspath(__file__))
DATA_DIR   = os.path.join(DIR, "data")
OUTPUT_DIR = os.path.join(DIR, "output")
RAW_JSON   = os.path.join(DATA_DIR,   "products_raw.json")
INDEX_JSON = os.path.join(OUTPUT_DIR, "products_index.json")
SEARCH_JS  = os.path.join(OUTPUT_DIR, "benna_search.js")

os.makedirs(DATA_DIR,   exist_ok=True)
os.makedirs(OUTPUT_DIR, exist_ok=True)

# ══════════════════════════════════════════════════════════════
#  STOPWORDS FR + EN
# ══════════════════════════════════════════════════════════════
STOPWORDS = {
    "le","la","les","un","une","des","de","du","et","en","au","aux",
    "est","sont","avec","pour","par","sur","dans","qui","que","ou",
    "the","a","an","and","or","is","are","in","on","at","of","to",
    "it","for","by","with","sans","tres","bien","plus","très","this",
    "ce","se","sa","son","ses","ma","mon","mes","notre","votre",
}

# ══════════════════════════════════════════════════════════════
#  NORMALISATION
# ══════════════════════════════════════════════════════════════
def normalize(text: str) -> str:
    """Minuscules + suppression accents + alphanumeric seulement."""
    text = text.lower()
    text = unicodedata.normalize("NFD", text)
    text = "".join(c for c in text if unicodedata.category(c) != "Mn")
    text = re.sub(r"[^a-z0-9\s]", " ", text)
    return re.sub(r"\s+", " ", text).strip()

def tokenize(text: str) -> list[str]:
    return [w for w in normalize(text).split() if w not in STOPWORDS and len(w) > 1]

# ══════════════════════════════════════════════════════════════
#  CHARGEMENT DES DONNÉES
# ══════════════════════════════════════════════════════════════
def load_from_db() -> list[dict]:
    import pymysql
    cnx = pymysql.connect(**DB_CONFIG, charset="utf8mb4")
    cur = cnx.cursor(pymysql.cursors.DictCursor)
    cur.execute("""
        SELECT p.id, p.nom, p.description, p.regime, p.prix, p.stock,
               p.est_actif, p.est_bestseller, p.est_nouveau,
               c.nom AS categorie
        FROM produits p
        LEFT JOIN categories c ON p.categorie_id = c.id
        WHERE p.est_actif = 1
          AND (p.description != 'box' OR p.description IS NULL)
    """)
    rows = cur.fetchall()
    cur.close(); cnx.close()
    return [dict(r) for r in rows]

def load_from_json() -> list[dict]:
    with open(RAW_JSON, encoding="utf-8") as f:
        return json.load(f)

# ══════════════════════════════════════════════════════════════
#  CONSTRUCTION DU DOCUMENT pour chaque produit
#  Pondération : nom × 3, régime × 2, description × 1
# ══════════════════════════════════════════════════════════════
def build_document(p: dict) -> str:
    nom  = p.get("nom", "")    or ""
    desc = p.get("description","") or ""
    reg  = p.get("regime","")  or ""
    cat  = p.get("categorie","") or ""
    return f"{nom} {nom} {nom} {reg} {reg} {cat} {desc}"

# ══════════════════════════════════════════════════════════════
#  TF-IDF
# ══════════════════════════════════════════════════════════════
def build_tfidf(products: list[dict]):
    """Retourne (vocab, idf_dict, tfidf_matrix)."""
    docs = [tokenize(build_document(p)) for p in products]

    # Vocabulaire
    vocab = sorted(set(w for doc in docs for w in doc))
    word2idx = {w: i for i, w in enumerate(vocab)}
    N = len(docs)

    # DF
    df = [0] * len(vocab)
    for doc in docs:
        for w in set(doc):
            if w in word2idx:
                df[word2idx[w]] += 1

    # IDF (lissé)
    idf = [math.log((N + 1) / (df[i] + 1)) + 1 for i in range(len(vocab))]

    # TF-IDF par document (sparse : seulement les poids > 0)
    tfidf_matrix = []
    for doc in docs:
        total = len(doc)
        if total == 0:
            tfidf_matrix.append({})
            continue
        tf_raw = {}
        for w in doc:
            tf_raw[w] = tf_raw.get(w, 0) + 1
        sparse = {}
        for w, cnt in tf_raw.items():
            idx = word2idx.get(w)
            if idx is not None:
                val = round((cnt / total) * idf[idx], 5)
                if val > 0:
                    sparse[w] = val
        tfidf_matrix.append(sparse)

    return vocab, {w: round(idf[i], 5) for i, w in enumerate(vocab)}, tfidf_matrix

# ══════════════════════════════════════════════════════════════
#  CONSTRUCTION DE L'INDEX JSON
# ══════════════════════════════════════════════════════════════
def build_index(products: list[dict]) -> dict:
    vocab, idf, tfidf_matrix = build_tfidf(products)

    entries = []
    for i, p in enumerate(products):
        entries.append({
            "id":          int(p["id"]),
            "nom":         p.get("nom", ""),
            "prix":        float(p.get("prix", 0)),
            "stock":       int(p.get("stock", 0)),
            "regime":      p.get("regime", "") or "",
            "categorie":   p.get("categorie", "") or "",
            "bestseller":  bool(p.get("est_bestseller", False)),
            "nouveau":     bool(p.get("est_nouveau", False)),
            # Vecteur TF-IDF sparse (seulement les mots présents)
            "vec":         tfidf_matrix[i],
        })

    return {
        "version":   2,
        "generated": __import__("datetime").datetime.utcnow().isoformat() + "Z",
        "count":     len(entries),
        "idf":       idf,
        "products":  entries,
    }

# ══════════════════════════════════════════════════════════════
#  GÉNÉRATION DE benna_search.js
#  Moteur de recherche côté navigateur — cosinus TF-IDF
# ══════════════════════════════════════════════════════════════
JS_TEMPLATE = r"""
/* benna_search.js — généré automatiquement par indexer.py */
/* NE PAS MODIFIER MANUELLEMENT */

(function (global) {
  "use strict";

  let _index  = null;   // données chargées depuis products_index.json
  let _ready  = false;
  let _queue  = [];     // callbacks en attente

  /* ── Normalisation (miroir Python) ── */
  function normalize(str) {
    return str.toLowerCase()
      .normalize("NFD").replace(/[\u0300-\u036f]/g, "")
      .replace(/[^a-z0-9\s]/g, " ")
      .replace(/\s+/g, " ").trim();
  }

  const STOPWORDS = new Set([
    "le","la","les","un","une","des","de","du","et","en","au","aux",
    "est","sont","avec","pour","par","sur","dans","qui","que","ou",
    "the","a","an","and","or","is","are","in","on","at","of","to",
    "it","for","by","with","sans","tres","bien","plus","tres","this",
    "ce","se","sa","son","ses","ma","mon","mes","notre","votre",
  ]);

  function tokenize(text) {
    return normalize(text).split(" ").filter(w => w.length > 1 && !STOPWORDS.has(w));
  }

  /* ── Cosinus sparse ── */
  function cosine(qVec, dVec) {
    let dot = 0, n1 = 0, n2 = 0;
    for (const [w, v] of Object.entries(qVec)) {
      dot += v * (dVec[w] || 0);
      n1  += v * v;
    }
    for (const v of Object.values(dVec)) n2 += v * v;
    return (n1 && n2) ? dot / (Math.sqrt(n1) * Math.sqrt(n2)) : 0;
  }

  /* ── Vecteur TF-IDF pour la requête ── */
  function queryVec(tokens) {
    const idf = _index.idf;
    const tf  = {};
    tokens.forEach(t => { tf[t] = (tf[t] || 0) + 1; });
    const total = tokens.length;
    const vec   = {};
    for (const [t, cnt] of Object.entries(tf)) {
      if (idf[t] !== undefined) {
        vec[t] = (cnt / total) * idf[t];
      }
    }
    return vec;
  }

  /* ── Recherche principale ── */
  function search(query, opts = {}) {
    if (!_ready || !query.trim()) return [];
    const { limit = 12, minScore = 0.01 } = opts;
    const tokens = tokenize(query);
    if (!tokens.length) return [];
    const qv = queryVec(tokens);

    const scored = _index.products
      .map(p => ({ p, score: cosine(qv, p.vec) }))
      .filter(x => x.score >= minScore)
      .sort((a, b) => b.score - a.score)
      .slice(0, limit)
      .map(x => ({ ...x.p, _score: Math.round(x.score * 1000) / 1000 }));

    return scored;
  }

  /* ── Chargement de l'index ── */
  function load(baseUrl) {
    const url = (baseUrl || "") + "/search/output/products_index.json?v=" + Date.now();
    fetch(url)
      .then(r => r.json())
      .then(data => {
        _index = data;
        _ready = true;
        _queue.forEach(fn => fn());
        _queue = [];
      })
      .catch(err => console.error("[BennaSearch] load error:", err));
  }

  function onReady(fn) {
    _ready ? fn() : _queue.push(fn);
  }

  /* ── Export ── */
  global.BennaSearch = { load, search, onReady, isReady: () => _ready };

})(window);
"""

# ══════════════════════════════════════════════════════════════
#  MAIN
# ══════════════════════════════════════════════════════════════
if __name__ == "__main__":
    from_json = "--from-json" in sys.argv

    print("🔄 Chargement des produits", "depuis JSON" if from_json else "depuis la DB", "...")
    products = load_from_json() if from_json else load_from_db()
    print(f"   ✅ {len(products)} produits chargés")

    # Sauvegarder le raw JSON (backup)
    with open(RAW_JSON, "w", encoding="utf-8") as f:
        json.dump(products, f, ensure_ascii=False, indent=2, default=str)
    print(f"   💾 Backup : {RAW_JSON}")

    print("🧠 Construction de l'index TF-IDF ...")
    index = build_index(products)

    with open(INDEX_JSON, "w", encoding="utf-8") as f:
        json.dump(index, f, ensure_ascii=False, separators=(",", ":"))
    size_kb = os.path.getsize(INDEX_JSON) / 1024
    print(f"   ✅ Index généré : {INDEX_JSON} ({size_kb:.1f} KB)")

    with open(SEARCH_JS, "w", encoding="utf-8") as f:
        f.write(JS_TEMPLATE.strip())
    print(f"   ✅ Moteur JS   : {SEARCH_JS}")

    print(f"\n🎉 Terminé — {index['count']} produits indexés.")
