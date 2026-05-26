<?php
// view/client/conseils.php
$pageTitle = "Conseils Santé";
include "partials/header.php";
$conseils = getAllConseils($cnx, true);
?>
<style>
.conseils-page{padding-top:110px;min-height:80vh;}
.conseil-card{background:var(--cream);border-radius:20px;padding:2rem;margin-bottom:1.2rem;box-shadow:var(--shadow,0 4px 20px rgba(0,0,0,.07));max-width:820px;margin-left:auto;margin-right:auto;}
.conseil-titre{font-family:var(--font-display);font-size:1.35rem;margin:0 0 .4rem;color:var(--green-dark);}
.conseil-meta{font-size:.8rem;color:var(--gold);margin-bottom:.8rem;display:flex;gap:1rem;flex-wrap:wrap;}
.conseil-body{color:var(--fg);line-height:1.75;font-size:.97rem;}
.conseil-produit{display:inline-flex;align-items:center;gap:.3rem;background:var(--green-light,#74c69d);color:var(--green-dark);padding:.15rem .6rem;border-radius:20px;font-size:.78rem;font-weight:600;}
.navbar {
  background: transparent !important;  /* solid green background */
  backdrop-filter: none !important;
  border-bottom: 1px solid rgba(255,255,255,0.15);
}
.navbar.scrolled {
  background: #1a4a2e !important;
    backdrop-filter: none !important;
  border-bottom: 1px solid rgba(255,255,255,0.15);
}

body.dark .navbar {
  background: #0a1a0a !important;
}
.navbar .nav-links a,
.navbar .nav-links2 a {
  color: rgba(20, 58, 31, 0.95) !important;
  text-shadow: none !important;
}
.navbar.scrolled .nav-links a,
.navbar.scrolled .nav-links2 a {
  color: rgba(255, 255, 255, 0.95) !important;
}
.navbar .nav-logo {
  color: #1a4a2e !important;
  text-shadow: none !important;
}
.navbar.scrolled .nav-logo {
  color: #ffffff !important;
  text-shadow: none !important;
}
</style>

<main class="conseils-page container">
  <div class="section-header fade-in">
    <p class="section-label">Nutrition &amp; Santé</p>
    <h1 class="section-title">Conseils de notre <em>Nutritionniste</em></h1>
    <div class="section-divider"></div>
    <p class="section-sub">Des conseils d'experts pour une alimentation saine et adaptée à vos besoins</p>
  </div>

  <?php if (empty($conseils)): ?>
    <div style="text-align:center;padding:4rem 2rem;color:var(--muted);">
      <div style="font-size:3.5rem;margin-bottom:1rem;">🥗</div>
      <p>Aucun conseil disponible pour l'instant. Revenez bientôt !</p>
    </div>
  <?php endif; ?>

  <?php foreach ($conseils as $i => $c): ?>
  <div class="conseil-card fade-in" style="--delay:<?= $i * 0.08 ?>s">
    <h2 class="conseil-titre"><?= htmlspecialchars($c['titre']) ?></h2>
    <div class="conseil-meta">
      <span>🥗 <?= htmlspecialchars($c['nutri_nom']) ?></span>
      <?php if (!empty($c['produit_nom'])): ?>
        <span class="conseil-produit">🌿 <?= htmlspecialchars($c['produit_nom']) ?></span>
      <?php endif; ?>
      <span>📅 <?= date('d/m/Y', strtotime($c['created_at'])) ?></span>
    </div>
    <div class="conseil-body"><?= nl2br(htmlspecialchars($c['contenu'])) ?></div>
  </div>
  <?php endforeach; ?>
</main>

<?php include "partials/footer.php"; ?>
