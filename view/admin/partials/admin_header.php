<?php
// view/admin/partials/admin_header.php
if (session_status() === PHP_SESSION_NONE) session_start();
include_once __DIR__ . "/../../../config/database.php";
include_once __DIR__ . "/../../../config/app.php";
include_once __DIR__ . "/../../../controller/traitement.php";

// 🔐 Admin only
if (($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: " . BASE . "/view/client/signup.php");
    exit();
}

// FIX: role → human-readable label (never display raw DB value "admin")
$roleLabels = [
    'admin'          => 'Administrateur',
    'nutritionniste' => 'Nutritionniste',
    'livreur'        => 'Livreur',
    'usine'          => 'Responsable Usine',
    'client'         => 'Client',
];
$currentRoleLabel = $roleLabels[$_SESSION['role'] ?? ''] ?? 'Utilisateur';

$cur = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Benna — Admin | <?= htmlspecialchars($pageTitle ?? '') ?></title>

<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>

<link rel="stylesheet" href="<?= BASE ?>/public/css/dashboard.css"/>

<style>
.alert-badge {
    background: #ef4444;
    color: white;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 700;
    padding: 0.1rem 0.45rem;
    margin-left: auto;
}
.sidebar-item {
    position: relative;
}
.sidebar-item .alert-badge {
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
}
/* FIX: sidebar footer — shows role label not raw string */
.user-role-label {
    font-size: .72rem;
    color: var(--muted);
}
</style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">

  <a href="<?= BASE ?>/view/admin/dashboard.php" class="sidebar-logo-link">
    <div class="sidebar-logo">
      <img src="<?= BASE ?>/view/pics/logo.png" alt="Benna" class="benna-logo-img">
      <div>
        <span class="sidebar-logo-text">Benna</span>
        <span class="sidebar-logo-sub">ADMIN</span>
      </div>
    </div>
  </a>

  <nav class="sidebar-nav">

    <div class="sidebar-section">Principal</div>
    <a href="<?= BASE ?>/view/admin/dashboard.php"
       class="sidebar-item <?= $cur==='dashboard.php'?'active':'' ?>">
      <i class="fas fa-chart-pie"></i> Dashboard
    </a>

    <div class="sidebar-section">Catalogue</div>
    <a href="<?= BASE ?>/view/admin/produits.php"
       class="sidebar-item <?= $cur==='produits.php'?'active':'' ?>">
      <i class="fas fa-leaf"></i> Produits
    </a>

    <div class="sidebar-section">Commerce</div>
    <a href="<?= BASE ?>/view/admin/commandes.php"
       class="sidebar-item <?= $cur==='commandes.php'?'active':'' ?>">
      <i class="fas fa-box"></i> Commandes
    </a>
    <a href="<?= BASE ?>/view/admin/livraisons.php"
       class="sidebar-item <?= $cur==='livraisons.php'?'active':'' ?>">
      <i class="fas fa-truck"></i> Livraisons
    </a>
    <a href="<?= BASE ?>/view/admin/livreurs.php"
       class="sidebar-item <?= $cur==='livreurs.php'?'active':'' ?>">
      <i class="fas fa-truck-moving"></i> Livreurs
    </a>
    <a href="<?= BASE ?>/view/admin/usines.php"
       class="sidebar-item <?= $cur==='usines.php'?'active':'' ?>">
      <i class="fas fa-industry"></i> Usines
    </a>
    <a href="<?= BASE ?>/view/admin/production.php"
       class="sidebar-item <?= $cur==='production.php'?'active':'' ?>">
      <i class="fas fa-cogs"></i> Production
    </a>

    <div class="sidebar-section">Gestion</div>
    <a href="<?= BASE ?>/view/admin/users.php"
       class="sidebar-item <?= $cur==='users.php'?'active':'' ?>">
      <i class="fas fa-users"></i> Utilisateurs
    </a>
    <a href="<?= BASE ?>/view/admin/stock.php"
       class="sidebar-item <?= $cur==='stock.php'?'active':'' ?>">
      <i class="fas fa-warehouse"></i> Stock
    </a>
    <a href="<?= BASE ?>/view/admin/avis.php"
       class="sidebar-item <?= $cur==='avis.php'?'active':'' ?>">
      <i class="fas fa-star"></i> Avis
    </a>
    <a href="<?= BASE ?>/view/admin/reclamations.php"
       class="sidebar-item <?= $cur==='reclamations.php'?'active':'' ?>">
      <i class="fas fa-flag"></i> Réclamations
    </a>

    <div class="sidebar-section">VIP</div>
  
    <a href="<?= BASE ?>/view/admin/vip.php"
       class="sidebar-item <?= $cur==='vip.php'?'active':'' ?>">
      <i class="fas fa-crown"></i> VIP
    </a>

  </nav>

  <!-- Sidebar footer — FIX: shows "Administrateur" not "admin" -->
  <div class="sidebar-footer">
    <div class="user-avatar">👤</div>
    <div>
      <div class="user-name"><?= htmlspecialchars($_SESSION['nom'] ?? 'Admin') ?></div>
      <!-- FIX: $currentRoleLabel = "Administrateur" -->
      <div class="user-role-label"><?= htmlspecialchars($currentRoleLabel) ?></div>
    </div>
    <a href="<?= BASE ?>/controller/auth_controller.php?action=logout"
       class="logout-btn" title="Déconnexion">
      <i class="fas fa-sign-out-alt"></i>
    </a>
  </div>

</aside>

<!-- MAIN -->
<main class="main-content">

<?php if (!empty($_SESSION['success'])): ?>
  <div class="flash success">
    <i class="fas fa-check-circle"></i>
    <?= htmlspecialchars($_SESSION['success']) ?>
  </div>
  <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['error'])): ?>
  <div class="flash error">
    <i class="fas fa-exclamation-triangle"></i>
    <?= htmlspecialchars($_SESSION['error']) ?>
  </div>
  <?php unset($_SESSION['error']); ?>
<?php endif; ?>