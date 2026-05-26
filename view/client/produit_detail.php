<?php
// view/client/produit_detail.php
$extraCss  = 'stylePro.css';

session_start();
include_once __DIR__ . "/../../config/app.php";
if (!isset($_GET['id'])) { header("Location: " . BASE . "/view/client/produits.php"); exit(); }

$pageTitle = "Produit";
include "partials/header.php";
$produit = getProduitById($cnx, (int)$_GET['id']);
if (!$produit) { header("Location: " . BASE . "/view/client/produits.php"); exit(); }
$pageTitle = htmlspecialchars($produit['nom']);

// ── Check if current client has ordered & received this product (can leave avis) ──
$canLeaveAvis  = false;
$alreadyReviewed = false;
$avisSuccess   = '';
$avisError     = '';

if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'client') {
    // Has the client received a delivered order containing this product?
    $stmtCheck = $cnx->prepare("
    SELECT COUNT(*) FROM commande_details ci
    JOIN commandes c ON c.id = ci.commande_id
    WHERE c.user_id = ? AND ci.produit_id = ? AND c.statut = 'livre'
");
    $stmtCheck->execute([$_SESSION['user_id'], $produit['id']]);
    $canLeaveAvis = (int)$stmtCheck->fetchColumn() > 0;

    // Has the client already left an avis for this product?
    $stmtAlready = $cnx->prepare("SELECT id FROM avis WHERE user_id = ? AND produit_id = ?");
    $stmtAlready->execute([$_SESSION['user_id'], $produit['id']]);
    $alreadyReviewed = (bool)$stmtAlready->fetch();

    // ── Handle avis form submission ──
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_avis'])) {
        $note       = (int)($_POST['note'] ?? 0);
        $commentaire = trim($_POST['commentaire'] ?? '');

        if ($note < 1 || $note > 5) {
            $avisError = 'Veuillez sélectionner une note entre 1 et 5 étoiles.';
        } elseif (!$canLeaveAvis) {
            $avisError = 'Vous devez avoir commandé et reçu ce produit pour laisser un avis.';
        } elseif ($alreadyReviewed) {
            $avisError = 'Vous avez déjà laissé un avis pour ce produit.';
        } else {
            $stmtIns = $cnx->prepare("
                INSERT INTO avis (user_id, produit_id, note, commentaire, valide, created_at)
                VALUES (?, ?, ?, ?, 0, NOW())
            ");
            $stmtIns->execute([$_SESSION['user_id'], $produit['id'], $note, $commentaire ?: null]);
            $avisSuccess     = 'Votre avis a été soumis et sera publié après validation. Merci !';
            $alreadyReviewed = true;
        }
    }
}

// Produits similaires
$req = $cnx->prepare("
    SELECT p.*, c.nom AS cat_nom,
           CASE WHEN p.categorie_id = :c THEN 1 ELSE 0 END AS priorite
    FROM produits p
    LEFT JOIN categories c ON c.id = p.categorie_id
    WHERE p.id != :id AND p.est_actif = 1
    ORDER BY priorite DESC, RAND()
    LIMIT 20
");
$req->execute([':c' => $produit['categorie_id'], ':id' => $produit['id']]);
$similaires = $req->fetchAll();
?>
<style>
.detail-page { padding-top: 50px; min-height: 80vh; }
.detail-page .container { padding-top: 0; }
main.detail-page { margin-top: 0; }
.detail-grid{display:grid;grid-template-columns:1fr 1fr;gap:3rem;margin:2rem 0 3rem;align-items:start;}
@media(max-width:780px){.detail-grid{grid-template-columns:1fr;}}
.detail-img{border-radius:20px;overflow:hidden;box-shadow:0 8px 40px rgba(0,0,0,.12);}
.detail-img img{width:100%;aspect-ratio:1;object-fit:contain;display:block;}
.detail-info h1{font-family:var(--font-display);font-size:2rem;margin:0 0 .5rem;color:var(--green-dark);}
.detail-price{font-family:var(--font-display);font-size:1.8rem;color:var(--green);margin:.5rem 0 1rem;}
.detail-desc{color:var(--muted);line-height:1.75;font-size:.97rem;margin-bottom:1.5rem;}
.badges-row{display:flex;flex-wrap:wrap;gap:.4rem;margin-bottom:1.2rem;}
.dbadge{display:inline-flex;align-items:center;gap:.3rem;padding:.25rem .8rem;border-radius:20px;font-size:.78rem;font-weight:700;}
.dbadge-green{background:#d1fae5;color:#065f46;}
.dbadge-blue{background:#dbeafe;color:#1e40af;}
.dbadge-gold{background:#fef3c7;color:#92400e;}
.dbadge-red{background:#fee2e2;color:#991b1b;}
.stock-ok{color:var(--green);font-weight:600;font-size:.9rem;}
.stock-ko{color:var(--terracotta);font-weight:600;font-size:.9rem;}
.add-btn{width:100%;padding:1rem 1.5rem;border:none;border-radius:14px;background:linear-gradient(135deg,var(--green),var(--green-dark));color:white;font-family:var(--font-display);font-size:1.1rem;font-weight:600;cursor:pointer;transition:.2s;margin-top:1rem;}
.add-btn:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(45,106,79,.35);}
.add-btn:disabled{background:#ccc;cursor:not-allowed;transform:none;}
.qty-row{display:flex;align-items:center;gap:.7rem;margin:.8rem 0;}
.qty-ctrl{width:36px;height:36px;border:2px solid var(--green);background:none;border-radius:50%;cursor:pointer;font-size:1.1rem;color:var(--green);font-weight:700;display:flex;align-items:center;justify-content:center;transition:.2s;}
.qty-ctrl:hover{background:var(--green);color:white;}
#qty-val{width:44px;text-align:center;font-family:var(--font-display);font-size:1.1rem;font-weight:700;border:none;background:transparent;}

/* Valeurs nutritives */
.nutri-table{width:100%;border-collapse:collapse;font-size:.9rem;margin-top:.5rem;}
.nutri-table th{text-align:left;padding:.5rem .8rem;background:var(--green-dark);color:white;border-radius:4px 4px 0 0;}
.nutri-table td{padding:.45rem .8rem;border-bottom:1px solid var(--border);}
.nutri-table tr:last-child td{border-bottom:none;}
.nutri-table tr:nth-child(even) td{background:var(--cream);}

/* Allergènes */
.allerg-list{display:flex;flex-wrap:wrap;gap:.3rem;margin-top:.5rem;}
.allerg-tag{padding:.2rem .7rem;border-radius:20px;background:#fee2e2;color:#991b1b;font-size:.75rem;font-weight:700;}

/* Avis */
.avis-section{margin:3rem 0;}
.avis-card{background:var(--cream);border-radius:16px;padding:1.2rem 1.5rem;margin-bottom:.8rem;box-shadow:0 2px 8px rgba(0,0,0,.05);}
.avis-header{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.3rem;}
.avis-auteur{font-family:var(--font-display);font-size:.95rem;}
.avis-stars{color:var(--gold);font-size:1.1rem;}
.avis-date{font-size:.78rem;color:var(--muted);}
.avis-txt{font-size:.9rem;color:var(--muted);margin-top:.4rem;line-height:1.6;}
.no-avis{color:var(--muted);font-size:.9rem;text-align:center;padding:2rem;}

/* ── Leave-an-avis form ── */
.avis-form-wrap {
    background: var(--cream, #f9f6f0);
    border: 1px solid var(--border, #d8e6c8);
    border-radius: 20px;
    padding: 1.8rem 2rem;
    margin-bottom: 2rem;
}
.avis-form-wrap h3 {
    font-family: var(--font-display);
    font-size: 1.2rem;
    margin: 0 0 1rem;
    color: var(--green-dark);
}
/* Star rating - custom JavaScript version */
.star-rating {
    display: flex;
    flex-direction: row-reverse;
    justify-content: flex-end;
    gap: 6px;
    margin-bottom: 1rem;
}
.star {
    font-size: 2rem;
    color: #d1d5db;
    cursor: pointer;
    transition: color 0.2s ease, transform 0.1s;
    user-select: none;
}
.star:hover,
.star:hover ~ .star {
    color: var(--gold, #d4a820) !important;
}
.star.active {
    color: var(--gold, #d4a820);
}
.avis-form-wrap textarea:focus {
    border-color: rgba(74,222,128,.5);
    box-shadow: 0 0 0 3px rgba(74,222,128,.1);
}
.avis-submit-btn {
    margin-top: .8rem;
    padding: .75rem 2rem;
    border: none;
    border-radius: 12px;
    background: var(--green);
    color: white;
    font-weight: 700;
    font-size: .9rem;
    cursor: pointer;
    transition: .2s;
}
.avis-submit-btn:hover { opacity: .87; transform: translateY(-1px); }
.alert-ok  { background:#d1fae5; color:#065f46; border:1px solid #6ee7b7; border-radius:12px; padding:.8rem 1.2rem; margin-bottom:1rem; font-size:.9rem; }
.alert-err { background:#fee2e2; color:#991b1b; border:1px solid #fca5a5; border-radius:12px; padding:.8rem 1.2rem; margin-bottom:1rem; font-size:.9rem; }

/* Similaires */
.similaires{margin:3rem 0;}
.similaires h2{font-family:var(--font-display);font-size:1.5rem;margin-bottom:1.2rem;color:var(--green-dark);}
.sim-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:1rem;}
.sim-card{background:var(--cream);border-radius:16px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.05);text-decoration:none;color:var(--fg);transition:.2s;}
.sim-card:hover{transform:translateY(-4px);box-shadow:0 8px 24px rgba(0,0,0,.1);}
.sim-card img{width:100%;height:130px;object-fit:cover;}
.sim-body{padding:.9rem;}
.sim-name{font-family:var(--font-display);font-size:.95rem;margin:0 0 .2rem;}
.sim-price{color:var(--green);font-weight:700;font-size:.9rem;}

/* Skeleton */
.sim-skeleton{pointer-events:none;}
.skel-img img {
    max-width: 100%;
    max-height: 100%;
    width: auto;
    height: auto;
    object-fit: contain;
}
.skel-img{width:100%;height:130px;background:linear-gradient(90deg,#e8ede9 25%,#d4e0d6 50%,#e8ede9 75%);background-size:200% 100%;animation:shimmer 1.4s infinite;object-fit: contain;}
.skel-line{height:12px;border-radius:6px;background:linear-gradient(90deg,#e8ede9 25%,#d4e0d6 50%,#e8ede9 75%);background-size:200% 100%;animation:shimmer 1.4s infinite;}
@keyframes shimmer{0%{background-position:200% 0}100%{background-position:-200% 0}}
.sim-card-ready{opacity:0;transform:translateY(10px);transition:opacity .35s ease,transform .35s ease;}
.sim-card-ready.visible{opacity:1;transform:translateY(0);}
</style>

<section class="hero-shop" style="width:100%;height:9px;margin-bottom:0px;padding-top:30px;"></section>
<main class="detail-page container">

  <!-- BREADCRUMB -->
  <div style="font-size:.85rem;color:var(--muted);margin-bottom:.5rem;">
    <a href="<?= BASE ?>/view/client/home.php" style="color:var(--green);text-decoration:none;">Accueil</a>
    &rsaquo; <a href="<?= BASE ?>/view/client/produits.php" style="color:var(--green);text-decoration:none;">Boutique</a>
    &rsaquo; <?= htmlspecialchars($produit['nom']) ?>
  </div>

  <div class="detail-grid">

    <!-- IMAGE -->
    <div class="detail-img">
      <img src="<?= BASE ?>/public/uploads/produits/pics/bg_final/<?= $produit['id'] ?>.jpg"
           alt="<?= htmlspecialchars($produit['nom']) ?>"
           onerror="this.src='https://placehold.co/600x600/e8f0e3/2c5e2e?text=Benna'"/>
    </div>

    <!-- INFOS -->
    <div class="detail-info">

      <?php if ($produit['cat_nom']): ?>
      <p style="color:var(--gold);font-size:.82rem;text-transform:uppercase;letter-spacing:.1em;margin-bottom:.3rem;">
        <?= htmlspecialchars($produit['cat_nom']) ?>
      </p>
      <?php endif; ?>

      <h1><?= htmlspecialchars($produit['nom']) ?></h1>

      <?php if ($produit['note_moyenne'] > 0): ?>
      <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.3rem;">
        <span style="color:var(--gold);font-size:1.1rem;">
          <?= str_repeat('★', round($produit['note_moyenne'])) ?>
          <?= str_repeat('☆', 5 - round($produit['note_moyenne'])) ?>
        </span>
        <span style="color:var(--muted);font-size:.88rem;">
          <?= number_format($produit['note_moyenne'], 1) ?>/5 (<?= $produit['nb_avis'] ?> avis)
        </span>
      </div>
      <?php endif; ?>

      <div class="detail-price"><?= number_format($produit['prix'], 3) ?> TND</div>

      <?php if (!empty($produit['regime'])): ?>
      <div class="badges-row">
        <?php foreach (array_filter(array_map('trim', explode(',', $produit['regime']))) as $r): ?>
          <span class="dbadge dbadge-green"><?= htmlspecialchars($r) ?></span>
        <?php endforeach; ?>
        <?php if ($produit['est_bestseller']): ?>
          <span class="dbadge dbadge-gold">Bestseller</span>
        <?php endif; ?>
        <?php if ($produit['est_nouveau']): ?>
          <span class="dbadge dbadge-blue">Nouveau</span>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <p class="detail-desc"><?= nl2br(htmlspecialchars($produit['description'] ?? '')) ?></p>

      <p>
        <?php if ($produit['stock'] > 0): ?>
          <span class="stock-ok">En stock (<?= $produit['stock'] ?> disponibles)</span>
        <?php else: ?>
          <span class="stock-ko">Rupture de stock</span>
        <?php endif; ?>
      </p>

      <!-- Add to cart -->
      <?php if (isset($_SESSION['user_id'])): ?>
        <?php if ($produit['stock'] > 0): ?>
          <form action="<?= BASE ?>/controller/panier_controller.php?action=add" method="POST">
            <input type="hidden" name="produit_id" value="<?= $produit['id'] ?>"/>
            <div class="qty-row">
              <button type="button" class="qty-ctrl" onclick="changeQty(-1)">−</button>
              <input type="number" name="quantite" id="qty-val" value="1" min="1" max="<?= $produit['stock'] ?>"/>
              <button type="button" class="qty-ctrl" onclick="changeQty(1)">+</button>
              <span style="color:var(--muted);font-size:.85rem;">unité(s)</span>
            </div>
            <button type="submit" class="add-btn">Ajouter au panier</button>
          </form>
        <?php else: ?>
          <button class="add-btn" disabled>Rupture de stock</button>
        <?php endif; ?>
      <?php else: ?>
        <a href="<?= BASE ?>/view/client/signup.php">
          <button class="add-btn">Connectez-vous pour commander</button>
        </a>
      <?php endif; ?>

      <!-- Allergènes -->
      <?php if (!empty($produit['allergenes'])): ?>
      <div style="margin-top:1.5rem;">
        <p style="font-family:var(--font-display);font-size:.9rem;color:var(--green-dark);margin-bottom:.3rem;">Allergènes :</p>
        <div class="allerg-list">
          <?php foreach ($produit['allergenes'] as $a): ?>
            <span class="allerg-tag"><?= htmlspecialchars($a['icone']) ?> <?= htmlspecialchars($a['nom']) ?></span>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- Valeurs nutritives -->
      <?php if ($produit['calories'] > 0): ?>
      <div style="margin-top:1.5rem;">
        <p style="font-family:var(--font-display);font-size:.9rem;color:var(--green-dark);margin-bottom:.5rem;">Valeurs nutritives (par portion)</p>
        <table class="nutri-table">
          <thead><tr><th>Nutriment</th><th>Quantité</th></tr></thead>
          <tbody>
            <tr><td>Calories</td><td><?= $produit['calories'] ?> kcal</td></tr>
            <tr><td>Protéines</td><td><?= number_format($produit['proteines'], 1) ?> g</td></tr>
            <tr><td>Glucides</td><td><?= number_format($produit['glucides'], 1) ?> g</td></tr>
            <tr><td>Lipides</td><td><?= number_format($produit['lipides'], 1) ?> g</td></tr>
          </tbody>
        </table>
      </div>
      <?php endif; ?>

    </div><!-- /detail-info -->
  </div><!-- /detail-grid -->

  <!-- ═══════════════════════════════════════
       AVIS CLIENTS
  ═══════════════════════════════════════ -->
  <div class="avis-section">
    <div class="section-header" style="margin-bottom:1.5rem;">
      <p class="section-label">Communauté</p>
      <h2 class="section-title">Avis <em>Clients</em></h2>
      <div class="section-divider"></div>
    </div>

    <!-- ── Leave an avis form ── -->
    <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'client'): ?>

      <?php if (!empty($avisSuccess)): ?>
        <div class="alert-ok">✅ <?= htmlspecialchars($avisSuccess) ?></div>
      <?php elseif (!empty($avisError)): ?>
        <div class="alert-err">⚠️ <?= htmlspecialchars($avisError) ?></div>
      <?php endif; ?>

      <?php if ($canLeaveAvis && !$alreadyReviewed): ?>
      <div class="avis-form-wrap">
        <h3>⭐ Laisser votre avis</h3>
        <form method="POST">
          <div style="margin-bottom:.5rem;font-size:.82rem;color:var(--muted);">Votre note</div>
          <div class="star-rating" id="starRating">
                      <span class="star" data-value="1">★</span>
                      <span class="star" data-value="2">★</span>
                      <span class="star" data-value="3">★</span>
                      <span class="star" data-value="4">★</span>
                      <span class="star" data-value="5">★</span>
                      <input type="hidden" name="note" id="ratingValue" value="0">
            </div>
          <div style="margin-bottom:.6rem;font-size:.82rem;color:var(--muted);">Votre commentaire (facultatif)</div>
          <textarea name="commentaire" placeholder="Partagez votre expérience avec ce produit…"></textarea>
          <button type="submit" name="submit_avis" class="avis-submit-btn">Soumettre mon avis →</button>
        </form>
      </div>
      <?php elseif ($alreadyReviewed): ?>
        <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:14px;padding:1rem 1.4rem;margin-bottom:1.5rem;font-size:.9rem;color:#166534;">
          ✅ Vous avez déjà soumis un avis pour ce produit. Merci pour votre retour !
        </div>
      <?php elseif (!$canLeaveAvis): ?>
        <div style="background:#fefce8;border:1px solid #fde68a;border-radius:14px;padding:1rem 1.4rem;margin-bottom:1.5rem;font-size:.88rem;color:#92400e;">
          💡 Achetez et recevez ce produit pour pouvoir laisser un avis.
        </div>
      <?php endif; ?>

    <?php elseif (!isset($_SESSION['user_id'])): ?>
      <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:14px;padding:1rem 1.4rem;margin-bottom:1.5rem;font-size:.9rem;color:#166534;">
        <a href="<?= BASE ?>/view/client/signup.php" style="color:var(--green);font-weight:700;">Connectez-vous</a>
        pour laisser un avis après avoir commandé ce produit.
      </div>
    <?php endif; ?>

    <!-- Existing reviews -->
    <?php if (!empty($produit['avis'])): ?>
      <?php foreach ($produit['avis'] as $a): ?>
      <div class="avis-card">
        <div class="avis-header">
          <span class="avis-auteur"><?= htmlspecialchars($a['auteur']) ?></span>
          <span class="avis-stars">
            <?= str_repeat('★', $a['note']) ?><?= str_repeat('☆', 5 - $a['note']) ?>
          </span>
        </div>
        <div class="avis-date"><?= date('d/m/Y', strtotime($a['created_at'])) ?></div>
        <?php if ($a['commentaire']): ?>
          <div class="avis-txt"><?= htmlspecialchars($a['commentaire']) ?></div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="no-avis">
        Soyez le premier à laisser un avis sur ce produit !<br/>
        <span style="font-size:.82rem;">Achetez ce produit, puis laissez votre avis ci-dessus.</span>
      </div>
    <?php endif; ?>
  </div>

  <!-- SIMILAIRES -->
  <div class="similaires" id="sim-section">
    <h2>Vous aimerez aussi</h2>
    <div class="sim-grid" id="sim-grid">
      <?php for ($i = 0; $i < 6; $i++): ?>
      <div class="sim-card sim-skeleton">
        <div class="skel-img" style="object-fit: contain;"></div>
        <div class="sim-body">
          <div class="skel-line" style="width:80%"></div>
          <div class="skel-line" style="width:50%;margin-top:.4rem"></div>
        </div>
      </div>
      <?php endfor; ?>
    </div>
  </div>

</main>

<script>
const SIMILAR_PRODUCTS = <?= json_encode(array_map(fn($s) => [
  'id'        => $s['id'],
  'nom'       => $s['nom'],
  'prix'      => $s['prix'],
  'categorie' => $s['cat_nom'] ?? '',
  'regime'    => $s['regime']  ?? '',
], $similaires)) ?>;

const CURRENT = {
  id:        <?= (int)$produit['id'] ?>,
  categorie: <?= json_encode($produit['cat_nom'] ?? '') ?>,
  regime:    <?= json_encode($produit['regime']  ?? '') ?>,
  prix:      <?= (float)$produit['prix'] ?>,
};
const BASE_URL = <?= json_encode(BASE) ?>;

function buildGraph(products) {
  const graph = {};
  products.forEach(p => { graph[p.id] = []; });
  for (let i = 0; i < products.length; i++) {
    for (let j = i + 1; j < products.length; j++) {
      const a = products[i], b = products[j];
      const sameCat = a.categorie && b.categorie && a.categorie === b.categorie;
      const regA = (a.regime || '').split(',').map(r => r.trim()).filter(Boolean);
      const regB = (b.regime || '').split(',').map(r => r.trim()).filter(Boolean);
      if (sameCat || regA.some(r => regB.includes(r))) {
        graph[a.id].push(b.id);
        graph[b.id].push(a.id);
      }
    }
  }
  return graph;
}

function dfsRecommend(pool, graph, max = 6) {
  const map = {};
  pool.forEach(p => { map[p.id] = p; });
  const score = p => {
    let s = 0;
    if (p.categorie === CURRENT.categorie) s += 40;
    const regCur = (CURRENT.regime || '').split(',').map(r => r.trim()).filter(Boolean);
    const regP   = (p.regime   || '').split(',').map(r => r.trim()).filter(Boolean);
    s += regCur.filter(r => regP.includes(r)).length * 20;
    const ratio = p.prix / (CURRENT.prix || 1);
    if (ratio > 0.4 && ratio < 2.5) s += 15;
    return s;
  };
  const visited = new Set([CURRENT.id]);
  const results = [];
  const stack   = [{ id: CURRENT.id, depth: 0 }];
  while (stack.length && results.length < max * 2) {
    const { id, depth } = stack.pop();
    if (depth > 4) continue;
    const neighbours = (graph[id] || []).slice().sort((a, b) => score(map[b] || {}) - score(map[a] || {}));
    for (const nid of neighbours) {
      if (visited.has(nid)) continue;
      visited.add(nid);
      const p = map[nid];
      if (p) { results.push({ product: p, score: score(p) }); stack.push({ id: nid, depth: depth + 1 }); }
    }
  }
  return results.sort((a, b) => b.score - a.score).slice(0, max);
}

function renderCards(items) {
  const grid = document.getElementById('sim-grid');
  grid.innerHTML = '';
  if (!items.length) {
    grid.innerHTML = '<p style="color:var(--muted);font-size:.9rem;padding:1rem 0;">Aucune recommandation disponible.</p>';
    return;
  }
  items.forEach(({ product: p }, i) => {
    const a = document.createElement('a');
    a.className = 'sim-card sim-card-ready';
    a.href = BASE_URL + '/view/client/produit_detail.php?id=' + p.id;
    a.innerHTML = `
      <img src="${BASE_URL}/public/uploads/produits/pics/bg_final/${p.id}.jpg"
           alt="${p.nom.replace(/"/g,'&quot;')}"
           onerror="this.src='https://placehold.co/200x130/e8f0e3/2c5e2e?text=Benna'"/>
      <div class="sim-body">
        <div class="sim-name">${p.nom}</div>
        <div class="sim-price">${parseFloat(p.prix).toFixed(3)} TND</div>
      </div>`;
    grid.appendChild(a);
    setTimeout(() => a.classList.add('visible'), 80 * i);
  });
}

function loadRecommendations() {
  const pool = SIMILAR_PRODUCTS.filter(p => p.id != CURRENT.id);
  if (!pool.length) {
    document.getElementById('sim-grid').innerHTML =
      '<p style="color:var(--muted);font-size:.9rem;padding:1rem 0;">Aucune recommandation disponible.</p>';
    return;
  }
  renderCards(dfsRecommend(pool, buildGraph([CURRENT, ...pool])));
}

if ('IntersectionObserver' in window) {
  const obs = new IntersectionObserver(entries => {
    if (entries[0].isIntersecting) { loadRecommendations(); obs.disconnect(); }
  }, { threshold: 0.1 });
  obs.observe(document.getElementById('sim-section'));
} else {
  loadRecommendations();
}

function changeQty(delta) {
  const input = document.getElementById('qty-val');
  const max   = parseInt(input.max) || 99;
  input.value = Math.min(max, Math.max(1, parseInt(input.value) + delta));
}

// Custom star rating with click-to-deselect
(function() {
    const container = document.getElementById('starRating');
    if (!container) return;
    
    const stars = container.querySelectorAll('.star');
    const ratingInput = document.getElementById('ratingValue');
    let currentRating = 0;

    function setRating(value) {
        currentRating = value;
        ratingInput.value = value;
        stars.forEach(star => {
            const starVal = parseInt(star.dataset.value);
            if (value > 0 && starVal <= value) {
                star.classList.add('active');
            } else {
                star.classList.remove('active');
            }
        });
    }

    stars.forEach(star => {
        star.addEventListener('click', function(e) {
            const clickedValue = parseInt(this.dataset.value);
            if (currentRating === clickedValue) {
                setRating(0);  // Deselect
            } else {
                setRating(clickedValue);
            }
        });

        star.addEventListener('mouseenter', function() {
            const hoverValue = parseInt(this.dataset.value);
            stars.forEach(s => {
                const sv = parseInt(s.dataset.value);
                if (sv <= hoverValue) {
                    s.style.color = 'var(--gold, #d4a820)';
                } else {
                    s.style.color = '';
                }
            });
        });

        star.addEventListener('mouseleave', function() {
            stars.forEach(s => {
                if (s.classList.contains('active')) {
                    s.style.color = '';
                } else {
                    s.style.color = '';
                }
            });
            // Re-apply active colors after mouse leave
            if (currentRating > 0) {
                stars.forEach(s => {
                    const sv = parseInt(s.dataset.value);
                    if (sv <= currentRating) {
                        s.style.color = 'var(--gold, #d4a820)';
                    } else {
                        s.style.color = '';
                    }
                });
            } else {
                stars.forEach(s => s.style.color = '');
            }
        });
    });

    // Initialize empty
    setRating(0);
})();
</script>

<?php include "partials/footer.php"; ?>