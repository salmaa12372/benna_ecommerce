<?php
// view/nutritionniste/partials/nutri_header.php
if (session_status() === PHP_SESSION_NONE) session_start();
include_once __DIR__ . "/../../../config/database.php";
include_once __DIR__ . "/../../../config/app.php";
include_once __DIR__ . "/../../../controller/traitement.php";

if (!in_array($_SESSION['role'] ?? '', ['nutritionniste','admin'])) {
    header("Location: " . BASE . "/view/client/signup.php"); exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Benna — Nutritionniste | <?= htmlspecialchars($pageTitle ?? '') ?></title> 
   <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<link rel="stylesheet" href="<?= BASE ?>/public/css/dashboard.css"/>
</head>
<body>
<aside class="sidebar">
  <?php $cur = basename($_SERVER['PHP_SELF']); ?>
            <a href="<?= BASE ?>/view/nutritionniste/dashboard.php" class="sidebar-logo-link">
              <div class="sidebar-logo">
        <img src="<?= BASE ?>/view/pics/logo.png" alt="Benna" class="benna-logo-img"
             onerror="this.style.display='none'"/>

        <div>
            <span class="sidebar-logo-text">Benna</span>
            <span class="sidebar-logo-sub">Nutritionniste</span>
        </div>
        </div>
    </a>

    <nav class="sidebar-nav">
        <div class="sidebar-section">Général</div>

        <a href="<?= BASE ?>/view/nutritionniste/dashboard.php"
           class="sidebar-item <?= $cur==='dashboard.php'?'active':'' ?>">
            <i class="fas fa-chart-line"></i> Dashboard
        </a>

        <div class="sidebar-section">Nutrition</div>

        <a href="<?= BASE ?>/view/nutritionniste/conseils.php"
           class="sidebar-item <?= $cur==='conseils.php'?'active':'' ?>">
            <i class="fas fa-lightbulb"></i> Conseils
        </a>

        <a href="<?= BASE ?>/view/nutritionniste/plans.php"
           class="sidebar-item <?= $cur==='plans.php'?'active':'' ?>">
            <i class="fas fa-utensils"></i> Plans alimentaires
        </a>

        <div class="sidebar-section">Validation</div>

        <a href="<?= BASE ?>/view/nutritionniste/validation.php"
           class="sidebar-item <?= $cur==='validation.php'?'active':'' ?>">
            <i class="fas fa-check-circle"></i> Produits
        </a>

        <a href="<?= BASE ?>/view/nutritionniste/avis.php"
           class="sidebar-item <?= $cur==='avis.php'?'active':'' ?>">
            <i class="fas fa-star"></i> Avis
        </a>

        <div class="sidebar-section">Clients</div>

        <a href="<?= BASE ?>/view/nutritionniste/clients.php"
           class="sidebar-item <?= $cur==='clients.php'?'active':'' ?>">
            <i class="fas fa-users"></i> Clients
        </a>

        <a href="<?= BASE ?>/view/nutritionniste/consultations.php"
           class="sidebar-item <?= $cur==='consultations.php'?'active':'' ?>">
            <i class="fas fa-video"></i> Consultations
        </a>

        <a href="<?= BASE ?>/view/nutritionniste/analyse_habituelle.php"
           class="sidebar-item <?= $cur==='analyse_habituelle.php'?'active':'' ?>">
            <i class="fas fa-chart-line"></i> Analyses
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="user-avatar"></div>
        <div>
            <div class="user-name"><?= htmlspecialchars($_SESSION['nom']) ?></div>
            <div class="user-role">Nutritionniste</div>
        </div>

        <a href="<?= BASE ?>/controller/auth_controller.php?action=logout"
           class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
        </a>
    </div>
</aside>
<main class="main-content">
<?php if (!empty($_SESSION['success'])): ?><div class="flash success"> <?= htmlspecialchars($_SESSION['success']) ?></div><?php unset($_SESSION['success']); endif; ?>
<?php if (!empty($_SESSION['error'])): ?><div class="flash error"><?= htmlspecialchars($_SESSION['error']) ?></div><?php unset($_SESSION['error']); endif; ?>