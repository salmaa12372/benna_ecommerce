<?php
// view/client/home.php

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . "/../../config/app.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../config/auth.php";

require_login();

$user = current_user();
if (!$user) {
    session_destroy();
    header('Location: ' . BASE . '/view/client/signup.php');
    exit();
}

$userId      = (int)$_SESSION['user_id'];
$userInitial = mb_strtoupper(mb_substr($user['nom'], 0, 1));
$cartCount   = cart_count($userId);
$vipSub      = active_vip($userId);

$stmtStats = $cnx->prepare("SELECT COUNT(*) AS total_orders, COALESCE(SUM(total),0) AS total_spent FROM commandes WHERE user_id=?");
$stmtStats->execute([$userId]);
$stats = $stmtStats->fetch();

$stmtOrders = $cnx->prepare("SELECT id,total,statut,date_commande FROM commandes WHERE user_id=? ORDER BY date_commande DESC LIMIT 3");
$stmtOrders->execute([$userId]);
$recentOrders = $stmtOrders->fetchAll();

$products = $cnx->query("
    SELECT p.id,p.nom,p.description,p.prix,p.image,p.est_bestseller,p.est_nouveau,p.stock,
           COALESCE(AVG(a.note),0) AS note_moyenne
    FROM produits p
    LEFT JOIN avis a ON a.produit_id=p.id AND a.valide=1
    WHERE p.est_actif=1
    GROUP BY p.id
    ORDER BY p.est_bestseller DESC,p.est_nouveau DESC,p.created_at DESC
    LIMIT 4
")->fetchAll();

$conseils = $cnx->query("
    SELECT c.id,c.titre,c.type,
           LEFT(c.contenu,115) AS contenu_preview,
           COALESCE(u.nom,'Dr. Sana Ben Ali') AS nutri_nom
    FROM conseils c
    LEFT JOIN users u ON u.id=c.nutritionniste_id
    WHERE c.public=1
    ORDER BY c.created_at DESC LIMIT 3
")->fetchAll();

// ── Products the client bought & received but hasn't reviewed yet ──────────
$stmtPendingAvis = $cnx->prepare("
    SELECT DISTINCT p.id, p.nom, p.prix
    FROM commande_details ci
    JOIN commandes c ON c.id = ci.commande_id
    JOIN produits p ON p.id = ci.produit_id
    LEFT JOIN avis a ON a.produit_id = p.id AND a.user_id = :uid
    WHERE c.user_id = :uid2 AND c.statut = 'livre' AND a.id IS NULL
    ORDER BY c.date_commande DESC
    LIMIT 4
");
$stmtPendingAvis->execute([':uid' => $userId, ':uid2' => $userId]);
$pendingAvis = $stmtPendingAvis->fetchAll();

// ── My recent reviews ────────────────────────────────────────────────────
$stmtMyAvis = $cnx->prepare("
    SELECT a.note, a.commentaire, a.valide, a.created_at, p.nom AS produit, p.id AS produit_id
    FROM avis a
    JOIN produits p ON p.id = a.produit_id
    WHERE a.user_id = ?
    ORDER BY a.created_at DESC
    LIMIT 3
");
$stmtMyAvis->execute([$userId]);
$myAvis = $stmtMyAvis->fetchAll();

$statutInfo = [
    'en_attente'     => ['icon'=>'🕐','label'=>'En attente',    'color'=>'#b45309','bg'=>'#fef3c7'],
    'confirmee'      => ['icon'=>'✅','label'=>'Confirmée',      'color'=>'#065f46','bg'=>'#d1fae5'],
    'en_preparation' => ['icon'=>'👨‍🍳','label'=>'En préparation','color'=>'#1e40af','bg'=>'#dbeafe'],
    'expedie'        => ['icon'=>'📦','label'=>'Expédiée',       'color'=>'#6d28d9','bg'=>'#ede9fe'],
    'en_livraison'   => ['icon'=>'🚚','label'=>'En livraison',   'color'=>'#0891b2','bg'=>'#cffafe'],
    'livre'          => ['icon'=>'🎉','label'=>'Livrée',         'color'=>'#16a34a','bg'=>'#dcfce7'],
    'annulee'        => ['icon'=>'❌','label'=>'Annulée',        'color'=>'#dc2626','bg'=>'#fee2e2'],
];

function getBadgeHtml(array $p): string {
    if (!empty($p['est_bestseller'])) return '<span class="product-badge badge-gold">Bestseller</span>';
    if (!empty($p['est_nouveau']))    return '<span class="product-badge badge-terra">Nouveau</span>';
    return '';
}
function conseilIcon(?string $t): string {
    return match($t) {
        'recette'          => '🍽️',
        'plan_alimentaire' => '📋',
        'recommandation'   => '⭐',
        default            => '🥗',
    };
}

$pageTitle = 'Mon Espace';
include "partials/header.php";
?>
<style>
.navbar {
  background: transparent !important;
  backdrop-filter: none !important;
  border-bottom: 1px solid rgba(255,255,255,0.15);
}
.navbar.scrolled {
  background: #379b61e3 !important;
  backdrop-filter: none !important;
}
body.dark .navbar { background: #0a1a0a !important; }
.navbar .nav-links a, .navbar .nav-links2 a { color: rgba(13, 73, 30, 0.95) !important; }
body.dark .navbar .nav-links a, body.dark .navbar .nav-links2 a { color:rgba(255, 255, 255, 0.95) !important; }
.navbar.scrolled .nav-links a, .navbar.scrolled .nav-links2 a { color: rgba(255,255,255,0.95) !important; }
.navbar .nav-logo { color: #1a4a2e !important; }
body.dark .navbar .nav-logo { color: #b7d6c4 !important; }
.navbar.scrolled .nav-logo { color: #ffffff !important; }

.dash-hero {
    background: linear-gradient(135deg, var(--green-dark), var(--green));
    border-radius: 24px;
    padding: 2rem;
    margin-bottom: 2rem;
    color: white;
}
.dash-hero-inner { display:flex; align-items:center; gap:1.5rem; flex-wrap:wrap; }
.dash-avatar {
    width:80px; height:80px; border-radius:50%;
    background:rgba(255,255,255,0.2);
    display:flex; align-items:center; justify-content:center;
    font-size:2rem; font-weight:bold;
}
.dash-welcome h1 { font-family:var(--font-display); font-size:1.8rem; margin:0; }
.dash-welcome p  { margin:.3rem 0 0; opacity:.9; }
.dash-badges  { display:flex; gap:.8rem; flex-wrap:wrap; margin-left:auto; }
.dash-badge   { background:rgba(255,255,255,0.2); padding:.3rem 1rem; border-radius:20px; font-size:.8rem; }
.dash-badge.gold { background:rgba(255,215,0,0.3); color:#ffd700; }

.stats-section { margin-bottom:2rem; }
.stats-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:1rem; }
.stat-card { background:var(--card); border-radius:16px; padding:1rem; text-align:center; border:1px solid var(--border); }
.stat-num  { font-size:1.8rem; font-weight:bold; color:var(--green); }
.stat-lbl  { font-size:.75rem; color:var(--muted); text-transform:uppercase; }

.quick-section { margin-bottom:2rem; }
.quick-grid  { display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:1rem; }
.quick-card  {
    background:var(--card); border-radius:16px; padding:1rem; text-align:center;
    text-decoration:none; border:1px solid var(--border); transition:all .3s;
}
.quick-card:hover { transform:translateY(-3px); box-shadow:0 4px 12px rgba(0,0,0,.1); }
.qc-title { display:block; font-weight:bold; color:var(--fg); margin-top:.5rem; }
.qc-lbl   { font-size:.7rem; color:var(--muted); }

.home-section { margin-bottom:2rem; }

.vip-banner {
    background:linear-gradient(135deg,#fef3c7,#fde68a);
    border-radius:20px; padding:2rem;
    display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;
}
.vip-banner-text h3 { margin:0 0 .3rem; color:#92400e; }
.vip-banner-text p  { margin:0; color:#b45309; }
.vip-banner-btn     { background:#92400e; color:white; padding:.8rem 1.5rem; border-radius:40px; text-decoration:none; font-weight:bold; }

.products-grid {
    display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:1.5rem;
}
.product-card { background:var(--card); border-radius:16px; overflow:hidden; border:1px solid var(--border); transition:all .3s; }
.product-card:hover { transform:translateY(-5px); box-shadow:0 8px 24px rgba(0,0,0,.1); }
.product-img  { position:relative; height:200px; overflow:hidden; }
.product-img img { width:100%; height:100%; object-fit:cover; }
.product-img-overlay { position:absolute; inset:0; background:linear-gradient(to top,rgba(0,0,0,.3),transparent); }
.product-badge { position:absolute; top:10px; left:10px; padding:.2rem .8rem; border-radius:20px; font-size:.7rem; font-weight:bold; }
.badge-gold  { background:#ffd700; color:#1a4a2e; }
.badge-terra { background:#c8684a; color:white; }
.product-body { padding:1rem; }
.product-name-shine { font-family:var(--font-display); font-size:1rem; margin:0 0 .3rem; }
.product-footer { display:flex; justify-content:space-between; align-items:center; margin-top:.8rem; }
.product-price { font-weight:bold; color:var(--green); font-size:1rem; }
.pc-add { background:var(--green); border:none; border-radius:50%; width:36px; height:36px; cursor:pointer; font-size:1rem; transition:all .3s; }
.pc-add:hover { transform:scale(1.1); }

.view-all-wrapper { text-align:center; margin-top:2rem; }
.view-all-trigger { display:inline-block; padding:.8rem 2rem; background:var(--green); color:white; border-radius:40px; text-decoration:none; font-weight:bold; }

.orders-table { width:100%; border-collapse:collapse; }
.orders-table th, .orders-table td { padding:.8rem; text-align:left; border-bottom:1px solid var(--border); }
.orders-empty { text-align:center; padding:2rem; color:var(--muted); }
.statut-pill { display:inline-flex; align-items:center; gap:.3rem; padding:.2rem .8rem; border-radius:20px; font-size:.75rem; font-weight:bold; }

/* ── Avis section ── */
.avis-section-home { margin-bottom:2rem; }
.avis-pending-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(200px,1fr)); gap:1rem; margin-bottom:1.5rem; }
.avis-pending-card {
    background:var(--card); border:1px solid var(--border); border-radius:16px;
    padding:1rem; display:flex; flex-direction:column; gap:.5rem;
    transition:.2s;
}
.avis-pending-card:hover { transform:translateY(-3px); box-shadow:0 4px 14px rgba(0,0,0,.08); }
.avis-pending-card img { width:100%; height:90px; object-fit:cover; border-radius:10px; }
.avis-pending-name { font-family:var(--font-display); font-size:.92rem; font-weight:600; }
.avis-pending-btn {
    display:block; text-align:center; padding:.45rem .8rem; border-radius:10px;
    background:var(--green); color:white; font-size:.78rem; font-weight:700;
    text-decoration:none; transition:.15s; margin-top:auto;
}
.avis-pending-btn:hover { opacity:.85; }

.my-avis-list { display:flex; flex-direction:column; gap:.7rem; }
.my-avis-item {
    background:var(--card); border:1px solid var(--border); border-radius:14px;
    padding:.9rem 1.1rem; display:flex; justify-content:space-between; align-items:flex-start; gap:1rem; flex-wrap:wrap;
}
.my-avis-stars { color:var(--gold); font-size:1rem; }
.my-avis-prod  { font-family:var(--font-display); font-size:.92rem; font-weight:600; }
.my-avis-text  { font-size:.84rem; color:var(--muted); margin-top:.2rem; line-height:1.5; }
.my-avis-date  { font-size:.72rem; color:var(--muted); white-space:nowrap; }
.avis-status   { display:inline-block; padding:.15rem .55rem; border-radius:20px; font-size:.68rem; font-weight:700; }
.avis-status.pending  { background:#fef3c7; color:#92400e; }
.avis-status.approved { background:#d1fae5; color:#065f46; }

.floating-cart {
    position:fixed; bottom:20px; right:20px;
    background:var(--green); width:60px; height:60px; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    text-decoration:none; box-shadow:0 4px 12px rgba(0,0,0,.2); transition:all .3s;
}
.floating-cart:hover { transform:scale(1.1); }
.floating-cart-icon  { font-size:1.5rem; }
.floating-cart-count {
    position:absolute; top:-5px; right:-5px;
    background:#ff4444; color:white; border-radius:50%;
    width:22px; height:22px; display:flex; align-items:center; justify-content:center;
    font-size:.7rem; font-weight:bold;
}
.conseils-body  { padding:1rem; }
.conseil-tag    { font-size:.7rem; color:var(--gold); margin-bottom:.3rem; }
.conseil-text   { font-size:.85rem; color:var(--muted); line-height:1.5; }
.conseil-author { font-size:.75rem; font-style:italic; margin-top:.5rem; }
</style>



<div class="home-page">

  <!-- HERO -->
  <div class="dash-hero">
    <div class="dash-hero-inner">
      <div class="dash-avatar"><?= htmlspecialchars($userInitial) ?></div>
      <div class="dash-welcome">
        <h1>Bonjour, <em><?= htmlspecialchars(explode(' ', $user['nom'])[0]) ?></em></h1>
        <p>Bienvenue dans votre espace — <?= date('d/m/Y') ?></p>
      </div>
      <div class="dash-badges">
        <?php if ($vipSub): ?>
          <span class="dash-badge gold">♛ VIP <?= ucfirst($vipSub['niveau']) ?> · jusqu'au <?= date('d/m/Y', strtotime($vipSub['date_fin'])) ?></span>
        <?php endif; ?>
        <span class="dash-badge"><?= $stats['total_orders'] ?> commandes</span>
        <span class="dash-badge">🗓 Membre depuis <?= date('Y', strtotime($user['created_at'])) ?></span>
      </div>
    </div>
  </div>

  <!-- STATS -->
  <div class="stats-section">
    <div class="container">
      <div class="stats-grid">
        <div class="stat-card"><div class="stat-num"><?= $stats['total_orders'] ?></div><div class="stat-lbl">Commandes</div></div>
        <div class="stat-card"><div class="stat-num"><?= number_format($stats['total_spent'],0) ?></div><div class="stat-lbl">TND dépensés</div></div>
        <div class="stat-card"><div class="stat-num"><?= $cartCount ?></div><div class="stat-lbl">Au panier</div></div>
        <div class="stat-card">
          <div class="stat-num" style="font-size:1.1rem;"><?= $vipSub ? ucfirst($vipSub['niveau']) : 'Free' ?></div>
          <div class="stat-lbl">Abonnement</div>
        </div>
      </div>
    </div>
  </div>

  <!-- QUICK LINKS -->
  <div class="quick-section">
    <div class="container">
      <div class="quick-grid">
        <a href="<?= BASE ?>/view/client/produits.php" class="quick-card">
          <span class="qc-title"> Boutique</span><span class="qc-lbl">Tous les produits</span>
        </a>
        <a href="<?= BASE ?>/view/client/mes_commandes.php" class="quick-card">
          <span class="qc-title"> Commandes</span><span class="qc-lbl">Suivi en temps réel</span>
        </a>
        <a href="<?= BASE ?>/view/client/panier.php" class="quick-card">
          <span class="qc-title"> Mon Panier</span><span class="qc-lbl"><?= $cartCount ?> article(s)</span>
        </a>
        <a href="<?= BASE ?>/view/client/conseils.php" class="quick-card">
          <span class="qc-title"> Conseils</span><span class="qc-lbl">Nutrition & Santé</span>
        </a>
        <a href="<?= BASE ?>/view/client/vip.php" class="quick-card" style="<?= $vipSub ? 'border-color:var(--gold)' : '' ?>">
          <span class="qc-title"> Club VIP</span>
          <span class="qc-lbl"><?= $vipSub ? 'Actif · '.ucfirst($vipSub['niveau']) : 'Rejoindre' ?></span>
        </a>
        <a href="<?= BASE ?>/view/client/profil.php" class="quick-card">
          <span class="qc-title"> Profil</span><span class="qc-lbl">Paramètres</span>
        </a>
        <a href="<?= BASE ?>/view/client/mes_reclamations.php" class="quick-card">
          <span class="qc-title"> Réclamations</span><span class="qc-lbl">Suivi SAV</span>
        </a>
      </div>
    </div>
  </div>

  <!-- VIP UPSELL -->
  <?php if (!$vipSub): ?>
  <div class="home-section">
    <div class="container">
      <div class="vip-banner fade-in">
        <div class="vip-banner-text">
          <h3>Rejoindre le Club VIP Benna</h3>
          <p>Accédez à Dr. Sana, plans alimentaires personnalisés et jusqu'à 30% de réduction.</p>
        </div>
        <a href="<?= BASE ?>/view/client/vip.php" class="vip-banner-btn">Découvrir les offres →</a>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- RECENT ORDERS -->
  <div class="home-section">
    <div class="container">
      <div class="section-header fade-in" style="text-align:left;margin-bottom:20px;">
        <p class="section-label">Activité récente</p>
        <h2 class="section-title" style="font-size:1.8rem;">Mes dernières <em>Commandes</em></h2>
        <div class="section-divider"></div>
      </div>
      <div style="background:var(--card);border:1px solid var(--border);border-radius:16px;overflow:hidden;box-shadow:var(--shadow);">
        <?php if (!empty($recentOrders)): ?>
          <table class="orders-table">
            <thead>
              <tr><th>#</th><th>Date</th><th>Total</th><th>Statut</th><th></th></tr>
            </thead>
            <tbody>
              <?php foreach ($recentOrders as $o):
                $s = $statutInfo[$o['statut']] ?? $statutInfo['en_attente'];
              ?>
              <tr>
                <td><strong>#<?= $o['id'] ?></strong></td>
                <td><?= date('d/m/Y', strtotime($o['date_commande'])) ?></td>
                <td><strong><?= number_format($o['total'],3) ?> TND</strong></td>
                <td><span class="statut-pill" style="color:<?= $s['color'] ?>;background:<?= $s['bg'] ?>;"><?= $s['icon'] ?> <?= $s['label'] ?></span></td>
                <td><a href="<?= BASE ?>/view/client/mes_commandes.php" style="color:var(--green);font-size:.85rem;">Voir →</a></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <div class="orders-empty">
            <p>📭 Aucune commande pour l'instant.</p>
            <a href="<?= BASE ?>/view/client/produits.php" style="color:var(--green);font-size:.9rem;">Découvrir nos produits →</a>
          </div>
        <?php endif; ?>
      </div>
      <?php if (!empty($recentOrders)): ?>
        <div style="text-align:center;margin-top:16px;">
          <a href="<?= BASE ?>/view/client/mes_commandes.php" style="color:var(--green);font-size:.88rem;font-weight:600;">Voir toutes mes commandes →</a>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- ═══════════════════════════════════════
       AVIS — leave a review & my reviews
  ═══════════════════════════════════════ -->
  <div class="home-section avis-section-home">
    <div class="container">
      <div class="section-header fade-in" style="text-align:left;margin-bottom:20px;">
        <p class="section-label">Votre voix compte</p>
        <h2 class="section-title" style="font-size:1.8rem;">Mes <em>Avis</em></h2>
        <div class="section-divider"></div>
      </div>

      <?php if (!empty($pendingAvis)): ?>
        <p style="font-size:.88rem;color:var(--muted);margin-bottom:.8rem;">
          Vous avez <strong style="color:var(--green);"><?= count($pendingAvis) ?></strong> produit<?= count($pendingAvis) > 1 ? 's' : '' ?> livré<?= count($pendingAvis) > 1 ? 's' : '' ?> en attente d'un avis :
        </p>
        <div class="avis-pending-grid">
          <?php foreach ($pendingAvis as $prd): ?>
          <div class="avis-pending-card">
            <img src="<?= BASE ?>/public/uploads/produits/pics/bg_final/<?= $prd['id'] ?>.jpg"
                 alt="<?= htmlspecialchars($prd['nom']) ?>"
                 onerror="this.src='https://placehold.co/200x90/e8f0e3/2c5e2e?text=Benna'">
            <div class="avis-pending-name"><?= htmlspecialchars($prd['nom']) ?></div>
            <div style="font-size:.78rem;color:var(--muted);"><?= number_format($prd['prix'],3) ?> TND</div>
            <a href="<?= BASE ?>/view/client/produit_detail.php?id=<?= $prd['id'] ?>#laisser-avis"
               class="avis-pending-btn"> Donner mon avis</a>
          </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p style="font-size:.88rem;color:var(--muted);margin-bottom:1rem;">
          Aucun produit en attente d'avis.
          <a href="<?= BASE ?>/view/client/produits.php" style="color:var(--green);font-weight:600;">Commander maintenant →</a>
        </p>
      <?php endif; ?>

      <?php if (!empty($myAvis)): ?>
        <p style="font-weight:600;font-size:.9rem;margin-bottom:.7rem;margin-top:1.2rem;">Mes derniers avis</p>
        <div class="my-avis-list">
          <?php foreach ($myAvis as $av): ?>
          <div class="my-avis-item">
            <div style="flex:1;">
              <div class="my-avis-prod">
                <a href="<?= BASE ?>/view/client/produit_detail.php?id=<?= $av['produit_id'] ?>"
                   style="color:var(--green-dark);text-decoration:none;"><?= htmlspecialchars($av['produit']) ?></a>
              </div>
              <div class="my-avis-stars">
                <?= str_repeat('★', (int)$av['note']) ?><?= str_repeat('☆', 5-(int)$av['note']) ?>
              </div>
              <?php if ($av['commentaire']): ?>
                <div class="my-avis-text"><?= htmlspecialchars(mb_substr($av['commentaire'],0,120)) ?><?= mb_strlen($av['commentaire']) > 120 ? '…' : '' ?></div>
              <?php endif; ?>
            </div>
            <div style="text-align:right;">
              <div class="my-avis-date"><?= date('d/m/Y', strtotime($av['created_at'])) ?></div>
              <div class="avis-status <?= $av['valide'] ? 'approved' : 'pending' ?>" style="margin-top:.4rem;">
                <?= $av['valide'] ? '✅ Publié' : '⏳ En attente' ?>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <div style="text-align:center;margin-top:1rem;">
          <a href="<?= BASE ?>/view/client/profil.php#mes-avis" style="color:var(--green);font-size:.88rem;font-weight:600;">Voir tous mes avis →</a>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- FEATURED PRODUCTS -->
  <div class="home-section" style="background:var(--cream-dark);padding:56px 0;">
    <div class="container">
      <div class="section-header fade-in">
        <p class="section-label">Notre Collection</p>
        <h2 class="section-title">Produits <em>Signature</em></h2>
        <div class="section-divider"></div>
        <p class="section-sub">Ingrédients purs, confectionnés selon les recettes ancestrales tunisiennes</p>
      </div>
      <div class="products-grid">
        <?php foreach ($products as $i => $p):
      $imgSrc = BASE . '/public/uploads/produits/pics/bg_final/' . $p['id'] . '.jpg';

        ?>
        <div class="product-card slide-in-left" style="transition-delay:<?= $i*0.1 ?>s">
          <div class="product-img">
            <img src="<?= $imgSrc ?>" 
     alt="<?= htmlspecialchars($p['nom']) ?>" 
     loading="lazy"
     onerror="this.src='https://placehold.co/400x400/e8f4ea/2a5c35?text=Benna'"/>
            <div class="product-img-overlay"></div>
            <?= getBadgeHtml($p) ?>
          </div>
          <div class="product-body">
            <h3 class="product-name-shine"><?= htmlspecialchars($p['nom']) ?></h3>
            <p><?= htmlspecialchars(mb_substr($p['description'] ?? '',0,88)) ?>…</p>
            <?php if ($p['note_moyenne'] > 0): ?>
              <div style="color:var(--gold);font-size:.9rem;margin-bottom:8px;">
                <?= str_repeat('★',(int)round($p['note_moyenne'])) ?>
                <span style="font-size:.75rem;color:var(--muted);">(<?= number_format($p['note_moyenne'],1) ?>)</span>
              </div>
            <?php endif; ?>
            <div class="product-footer">
              <span class="product-price"><?= number_format($p['prix'],3) ?> TND</span>
              <?php if (($p['stock']??0) <= 0): ?>
                <span style="font-size:.72rem;color:var(--red);font-weight:600;">Rupture</span>
              <?php else: ?>
                <form action="<?= BASE ?>/controller/panier_controller.php?action=add" method="POST" style="display:inline;">
                  <input type="hidden" name="produit_id" value="<?= (int)$p['id'] ?>"/>
                  <input type="hidden" name="quantite" value="1"/>
                  <button class="pc-add" type="submit" title="Ajouter au panier">🛒</button>
                </form>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="view-all-wrapper">
        <a href="<?= BASE ?>/view/client/produits.php" class="view-all-trigger">Voir tous les produits →</a>
      </div>
    </div>
  </div>

  <!-- CONSEILS -->
  <?php if (!empty($conseils)): ?>
  <div class="home-section">
    <div class="container">
      <div class="section-header fade-in">
        <p class="section-label">Expertise</p>
        <h2 class="section-title">Conseils de notre <em>Nutritionniste</em></h2>
        <div class="section-divider"></div>
      </div>
      <div class="products-grid">
        <?php foreach ($conseils as $i => $c): ?>
        <div class="product-card fade-in" style="--delay:<?= $i*0.12 ?>s">
          <div class="product-body conseils-body">
            <p class="conseil-tag"><?= conseilIcon($c['type']??'') ?> Conseil nutrition</p>
            <h3 class="product-name-shine" style="font-size:1.1rem;"><?= htmlspecialchars($c['titre']) ?></h3>
            <p class="conseil-text"><?= htmlspecialchars($c['contenu_preview']) ?>…</p>
            <p class="conseil-author">— <?= htmlspecialchars($c['nutri_nom']) ?></p>
            <a href="<?= BASE ?>/view/client/conseils.php?id=<?= $c['id'] ?>" style="font-size:.82rem;color:var(--green);margin-top:6px;display:inline-block;">Lire la suite →</a>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>

</div><!-- /home-page -->

<a href="<?= BASE ?>/view/client/panier.php" class="floating-cart" aria-label="Panier">
  <span class="floating-cart-icon">🛒</span>
  <span class="floating-cart-count"><?= $cartCount ?></span>
</a>

<?php include "partials/footer.php"; ?>