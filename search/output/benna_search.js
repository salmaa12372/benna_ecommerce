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