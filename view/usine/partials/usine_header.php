<?php
// view/usine/partials/usine_header.php
if (session_status() === PHP_SESSION_NONE) session_start();
include_once __DIR__ . "/../../../config/database.php";
include_once __DIR__ . "/../../../config/app.php";
include_once __DIR__ . "/../../../controller/traitement.php";

if (!in_array($_SESSION['role'] ?? '', ['usine', 'admin'])) {
    header("Location: " . BASE . "/view/client/signup.php"); exit();
}
$cur = basename($_SERVER['PHP_SELF']);



?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Benna — Usine | <?= htmlspecialchars($pageTitle ?? '') ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<link rel="stylesheet" href="<?= BASE ?>/public/css/dashboard.css"/>

</head>
<body>

<aside class="sidebar" id="sidebar">
    <a href="<?= BASE ?>/view/usine/dashboard.php" class="sidebar-logo-link">
    <div class="sidebar-logo">
        <img src="<?= BASE ?>/view/pics/logo.png" alt="Benna" class="benna-logo-img"
             onerror="this.style.display='none'"/>

        <div>
            <span class="sidebar-logo-text">Benna</span>
            <span class="sidebar-logo-sub">Unité de Production</span>
        </div>
    </div>
</a>

    <nav class="sidebar-nav">
        <div class="sidebar-section">Général</div>
        <a href="<?= BASE ?>/view/usine/dashboard.php" class="sidebar-item <?= $cur==='dashboard.php'?'active':'' ?>">
            <i class="fas fa-chart-bar"></i> Tableau de bord
        </a>
        <div class="sidebar-section">Stock &amp; Production</div>
        <a href="<?= BASE ?>/view/usine/stock.php" class="sidebar-item <?= $cur==='stock.php'?'active':'' ?>">
            <i class="fas fa-warehouse"></i> Gestion du stock
        </a>
        <a href="<?= BASE ?>/view/usine/production.php" class="sidebar-item <?= $cur==='production.php'?'active':'' ?>">
            <i class="fas fa-industry"></i> Ordres de production
        </a>
        <div class="sidebar-section">Commandes</div>
        <a href="<?= BASE ?>/view/usine/commandes.php" class="sidebar-item <?= $cur==='commandes.php'?'active':'' ?>">
            <i class="fas fa-box"></i> Commandes à préparer
        </a>
        <a href="<?= BASE ?>/view/usine/reclamations.php" class="sidebar-item <?= $cur==='reclamations.php'?'active':'' ?>">
            <i class="fas fa-flag"></i> Réclamations reçues
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="user-avatar">🏭</div>
        <div>
            <div class="user-name"><?= htmlspecialchars($_SESSION['nom']) ?></div>
            <div class="user-role">Usine / Production</div>
        </div>
        <a href="<?= BASE ?>/controller/auth_controller.php?action=logout" class="logout-btn" title="Déconnexion">
            <i class="fas fa-sign-out-alt"></i>
        </a>
    </div>
</aside>

<main class="main-content">
<?php if (!empty($_SESSION['success'])): ?>
  <div id="flashMsg" class="flash success">
    <i class="fas fa-check-circle"></i>
    <?= htmlspecialchars($_SESSION['success']) ?>
  </div>
  <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['error'])): ?>
  <div id="flashMsg" class="flash error">
    <i class="fas fa-exclamation-triangle"></i>
    <?= htmlspecialchars($_SESSION['error']) ?>
  </div>
  <?php unset($_SESSION['error']); ?>
<?php endif; ?>


</script>


