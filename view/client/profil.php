<?php
/**
 * view/client/profil.php — Client profile page
 */
if (session_status() === PHP_SESSION_NONE) session_start();

// profil.php - just kick out anyone who isn't a logged-in client
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header('Location: signup.php');
    exit;
}
include_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../config/app.php";
require_once __DIR__ . "/../../controller/traitement.php";

// Rest of your code...

$userId = (int)$_SESSION['user_id'];

$stmtU = $cnx->prepare("SELECT * FROM users WHERE id=? AND actif=1");
$stmtU->execute([$userId]);
$user = $stmtU->fetch();
if (!$user) { session_destroy(); header('Location: signup.php'); exit; }

// Variables for navbar
$isLoggedIn = true;
$u          = $user;
$userInitial = mb_strtoupper(mb_substr($user['nom'], 0, 1));

$stmtStats = $cnx->prepare("SELECT COUNT(*) AS total_orders, COALESCE(SUM(total),0) AS total_spent, COALESCE(SUM(CASE WHEN statut='livre' THEN 1 ELSE 0 END),0) AS delivered FROM commandes WHERE user_id=?");
$stmtStats->execute([$userId]);
$stats = $stmtStats->fetch();

$stmtCart = $cnx->prepare("SELECT COALESCE(SUM(quantite),0) FROM panier WHERE user_id=?");
$stmtCart->execute([$userId]);
$cartCount = (int)$stmtCart->fetchColumn();

$stmtVip = $cnx->prepare("SELECT niveau, date_debut, date_fin, actif FROM vip_abonnements WHERE user_id=? AND actif=1 ORDER BY created_at DESC LIMIT 1");
$stmtVip->execute([$userId]);
$vipSub = $stmtVip->fetch();

$stmtVipPay = $cnx->prepare("SELECT vp.montant, vp.methode, vp.statut, vp.created_at, va.niveau FROM vip_paiements vp JOIN vip_abonnements va ON va.id=vp.abonnement_id WHERE vp.user_id=? ORDER BY vp.created_at DESC LIMIT 5");
$stmtVipPay->execute([$userId]);
$vipPayments = $stmtVipPay->fetchAll();

$stmtAvis = $cnx->prepare("SELECT a.note, a.commentaire, a.valide, a.created_at, p.nom AS produit FROM avis a JOIN produits p ON p.id=a.produit_id WHERE a.user_id=? ORDER BY a.created_at DESC LIMIT 5");
$stmtAvis->execute([$userId]);
$myAvis = $stmtAvis->fetchAll();

$stmtOrd = $cnx->prepare("SELECT id, total, statut, date_commande FROM commandes WHERE user_id=? ORDER BY date_commande DESC LIMIT 5");
$stmtOrd->execute([$userId]);
$recentOrders = $stmtOrd->fetchAll();

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profil') {
        $nom    = trim($_POST['nom'] ?? '');
        $email  = trim($_POST['email'] ?? '');
        $tel    = trim($_POST['telephone'] ?? '');
        $adresse= trim($_POST['adresse'] ?? '');
        $curPw  = $_POST['current_password'] ?? '';
        $newPw  = $_POST['new_password'] ?? '';
        $confPw = $_POST['confirm_password'] ?? '';

        if (empty($nom))   $errors[] = 'Le nom est obligatoire.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email invalide.';
        if (empty($errors)) {
            $chk = $cnx->prepare("SELECT id FROM users WHERE email=? AND id!=?");
            $chk->execute([$email, $userId]);
            if ($chk->fetch()) $errors[] = 'Cet email est déjà utilisé.';
        }
        $newHash = null;
        if (!empty($newPw)) {
            if (empty($curPw)) $errors[] = 'Mot de passe actuel requis.';
            elseif (!password_verify($curPw, $user['password'])) $errors[] = 'Mot de passe actuel incorrect.';
            elseif (strlen($newPw) < 8) $errors[] = 'Min. 8 caractères.';
            elseif ($newPw !== $confPw) $errors[] = 'Les mots de passe ne correspondent pas.';
            else $newHash = password_hash($newPw, PASSWORD_DEFAULT);
        }
        if (empty($errors)) {
            if ($newHash) {
                $cnx->prepare("UPDATE users SET nom=?, email=?, telephone=?, adresse=?, password=? WHERE id=?")
                    ->execute([$nom, $email, $tel ?: null, $adresse ?: null, $newHash, $userId]);
            } else {
                $cnx->prepare("UPDATE users SET nom=?, email=?, telephone=?, adresse=? WHERE id=?")
                    ->execute([$nom, $email, $tel ?: null, $adresse ?: null, $userId]);
            }
            $_SESSION['nom'] = $nom;
            $success = 'Profil mis à jour !';
            $stmtU->execute([$userId]);
            $user = $stmtU->fetch();
            $u = $user;
            $userInitial = mb_strtoupper(mb_substr($user['nom'], 0, 1));
        }
    }

    if ($action === 'delete_account') {
        $pw = $_POST['confirm_delete_password'] ?? '';
        if (!password_verify($pw, $user['password'])) {
            $errors[] = 'Mot de passe incorrect.';
        } else {
            $cnx->prepare("UPDATE users SET actif=0, email=?, nom='Compte supprimé' WHERE id=?")
                ->execute(['deleted_' . $userId . '_' . time() . '@benna.tn', $userId]);
            session_destroy();
            header('Location: ' . BASE . '/index.php?deleted=1');
            exit;
        }
    }
}

$statutInfo = [
    'en_attente'     => ['icon' => '🕐', 'label' => 'En attente', 'color' => '#fbbf24', 'bg' => 'rgba(251,191,36,.12)'],
    'confirmee'      => ['icon' => '✅', 'label' => 'Confirmée', 'color' => '#4ade80', 'bg' => 'rgba(74,222,128,.1)'],
    'en_preparation' => ['icon' => '👨‍🍳', 'label' => 'En préparation', 'color' => '#60a5fa', 'bg' => 'rgba(96,165,250,.1)'],
    'expedie'        => ['icon' => '📦', 'label' => 'Expédiée', 'color' => '#a78bfa', 'bg' => 'rgba(167,139,250,.1)'],
    'en_livraison'   => ['icon' => '🚚', 'label' => 'En livraison', 'color' => '#22d3ee', 'bg' => 'rgba(34,211,238,.1)'],
    'livre'          => ['icon' => '🎉', 'label' => 'Livrée', 'color' => '#4ade80', 'bg' => 'rgba(74,222,128,.1)'],
    'annulee'        => ['icon' => '❌', 'label' => 'Annulée', 'color' => '#f87171', 'bg' => 'rgba(248,113,113,.1)'],
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Mon Profil — Benna</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=Jost:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <!-- Global styles – defines all CSS variables for light/dark mode -->
  <link rel="stylesheet" href="<?= BASE ?>/view/client/assets/style.css"/>
<style>
  
    .page-wrap {
      max-width: 1100px;
      margin: 0 auto;
      padding: 100px 24px 72px;
    }

    .profile-grid {
      display: grid;
      grid-template-columns: 1fr 320px;
      gap: 24px;
      align-items: start;
    }
    @media (max-width: 860px) {
      .profile-grid { grid-template-columns: 1fr; }
    }

    .prof-hero {
      display: flex;
      align-items: center;
      gap: 18px;
      flex-wrap: wrap;
      margin-bottom: 36px;
      padding: 28px;
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      position: relative;
    }
    .prof-av {
      width: 72px;
      height: 72px;
      border-radius: 50%;
      background: linear-gradient(135deg, #1a4a2e, #2d7a4f);
      color: #a7f3c6;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: var(--font-display);
      font-size: 2rem;
      font-weight: 700;
      border: 2px solid rgba(74,222,128,0.3);
      box-shadow: 0 0 0 6px rgba(74,222,128,0.05);
      flex-shrink: 0;
    }
    .prof-meta h1 {
      font-family: var(--font-display);
      font-size: 1.8rem;
      font-weight: 300;
      color: var(--fg);
    }
    .prof-meta h1 em { font-style: italic; color: var(--green); }
    .prof-meta p { color: var(--muted); font-size: 0.83rem; margin-top: 4px; }
    .prof-chips {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
      margin-left: auto;
    }
    .pchip {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 5px 12px;
      border-radius: 999px;
      font-size: 0.72rem;
      border: 1px solid var(--border);
      background: rgba(255,255,255,0.04);
      color: var(--muted);
    }
    .pchip.vip {
      border-color: rgba(212,170,80,0.3);
      color: var(--gold);
      background: rgba(212,170,80,0.07);
    }

    .card {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      overflow: hidden;
    }
    .card + .card { margin-top: 20px; }
    .card-head {
      padding: 18px 22px;
      border-bottom: 1px solid var(--border);
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 8px;
    }
    .card-head h3 {
      font-family: var(--font-display);
      font-size: 1rem;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 8px;
      color: var(--fg);
    }
    .card-head h3 i { color: var(--green); font-size: 0.8rem; }
    .card-head a { font-size: 0.75rem; color: var(--green); text-decoration: none; }
    .card-body { padding: 22px; }
    .card-danger { border-color: rgba(248,113,113,0.2); }
    .card-danger .card-head h3 { color: var(--red); }

    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 14px;
    }
    @media (max-width: 600px) { .form-row { grid-template-columns: 1fr; } }
    .fgroup { margin-bottom: 14px; }
    .fgroup label {
      display: block;
      font-size: 0.65rem;
      text-transform: uppercase;
      letter-spacing: 0.1em;
      color: var(--muted);
      margin-bottom: 5px;
    }
    .fgroup input {
      width: 100%;
      padding: 10px 14px;
      border-radius: 10px;
      border: 1.5px solid var(--border);
      background: var(--card);
      color: var(--fg);
      font-family: var(--font-body);
      font-size: 0.9rem;
      outline: none;
    }
    .fgroup input:focus {
      border-color: rgba(74,222,128,0.4);
      box-shadow: 0 0 0 3px rgba(74,222,128,0.1);
    }
    .fsep {
      font-size: 0.85rem;
      font-weight: 600;
      color: var(--fg);
      margin: 16px 0 10px;
      padding-top: 14px;
      border-top: 1px solid var(--border);
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .fsep i { color: var(--green); font-size: 0.8rem; }
    /* Save button – white text */
    .fsub {
      width: 100%;
      padding: 12px;
      border-radius: 12px;
      background: var(--green);
      color: #ffffff !important;
      font-family: var(--font-body);
      font-size: 0.9rem;
      font-weight: 700;
      border: none;
      cursor: pointer;
      transition: all 0.2s;
      margin-top: 6px;
    }
    .fsub:hover {
      background: var(--green-d);
      transform: translateY(-1px);
    }
    .fdel {
      width: 100%;
      padding: 12px;
      border-radius: 12px;
      background: rgba(248,113,113,0.12);
      color: var(--red);
      font-family: var(--font-body);
      font-size: 0.9rem;
      font-weight: 600;
      border: 1px solid rgba(248,113,113,0.25);
      cursor: pointer;
    }
    .fdel:hover { background: rgba(248,113,113,0.2); }

    .info-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 10px 0;
      border-bottom: 1px solid var(--border);
    }
    .info-row:last-child { border-bottom: none; }
    .ilbl {
      font-size: 0.65rem;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: var(--muted);
    }
    .ival { font-size: 0.88rem; font-weight: 500; }

    .bp {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      padding: 2px 9px;
      border-radius: 999px;
      font-size: 0.68rem;
      font-weight: 700;
    }
    .bp-green { background: rgba(74,222,128,0.12); color: var(--green); }
    .bp-gold  { background: rgba(212,170,80,0.12); color: var(--gold); }

    /* VIP card – lighter & clearer */
    .vip-card {
      background: rgba(212,170,80,0.08);
      border: 1px solid rgba(212,170,80,0.2);
    }
    body.dark .vip-card {
      background: rgba(212,170,80,0.06);
      border-color: rgba(212,170,80,0.2);
    }
    .vip-card .card-head h3 { color: var(--gold); }
    .vip-card .ilbl { color: var(--muted); }
    .vip-card .ival { color: var(--fg); font-weight: 600; }
    .vip-card .info-row { border-bottom-color: var(--border); }

    .mo-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 10px 0;
      border-bottom: 1px solid var(--border);
      font-size: 0.86rem;
    }
    .mo-item:last-child { border-bottom: none; }
    .statut {
      display: inline-flex;
      align-items: center;
      gap: 3px;
      padding: 2px 9px;
      border-radius: 999px;
      font-size: 0.66rem;
      font-weight: 600;
    }

    .avis-item {
      padding: 12px 0;
      border-bottom: 1px solid var(--border);
    }
    .avis-item:last-child { border-bottom: none; }
    .avis-stars { color: var(--gold); font-size: 0.88rem; }
    .avis-prod {
      font-family: var(--font-display);
      font-size: 0.95rem;
      font-weight: 600;
    }
    .avis-text {
      font-size: 0.82rem;
      color: var(--muted);
      line-height: 1.55;
      margin-top: 4px;
    }
    .avis-meta {
      display: flex;
      justify-content: space-between;
      margin-top: 6px;
      font-size: 0.7rem;
      color: var(--muted);
    }

    .danger-note {
      font-size: 0.83rem;
      color: var(--muted);
      margin-bottom: 16px;
      line-height: 1.55;
    }

    .fade-up {
      opacity: 0;
      transform: translateY(18px);
      transition: opacity 0.55s, transform 0.55s;
    }
    .fade-up.visible {
      opacity: 1;
      transform: none;
    }
   
    .navbar {
      background: transparent !important;
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
    body.dark .navbar .nav-links a,
    body.dark .navbar .nav-links2 a{
      color:rgba(255, 255, 255, 0.95) !important;
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
    body.dark .navbar .nav-logo {
      color: #ffffff !important;
      text-shadow: none !important;
    }
    .navbar.scrolled .nav-logo {
      color: #ffffff !important;
      text-shadow: none !important;
    }
  </style>
</head>
<body>

<nav id="navbar" class="navbar">
  <div class="nav-container">

    <a href="<?= BASE ?>/index.php" class="nav-logo">
      <img src="<?= BASE ?>/view/pics/logo.png" alt="Benna" class="nav-logo-img"
           onerror="this.style.display='none'">
      Benna
    </a>
    <div class="nav-links" id="navLinks">
      <a href="<?= BASE ?>/index.php#home">Accueil</a>
      <a href="<?= BASE ?>/index.php#story">Histoire</a>
      <a href="<?= BASE ?>/view/client/produits.php">Boutique</a>
      <a href="<?= BASE ?>/index.php#vip">VIP</a>
      <a href="<?= BASE ?>/index.php#gallery">Galerie</a>
      <a href="<?= BASE ?>/index.php#reviews">Avis</a>
      <a href="<?= BASE ?>/index.php#contact">Contact</a>
    </div>

    <div class="nav-actions">

      <a href="<?= BASE ?>/view/client/produits.php" class="nav-join">
        <strong>Acheter</strong>
      </a>

      <?php if ($isLoggedIn):
        $avatarSrc = !empty($u['photo'])
            ? BASE . '/' . ltrim($u['photo'], '/')
            : (!empty($u['avatar']) ? BASE . '/' . ltrim($u['avatar'], '/') : null);
      ?>
        <div class="profile-trigger" id="profileTrigger">
          <?php if ($avatarSrc): ?>
            <img src="<?= htmlspecialchars($avatarSrc) ?>" class="profile-avatar"
                 alt="<?= htmlspecialchars($u['nom']) ?>"
                 onerror="this.style.display='none';this.nextElementSibling.style.display='flex'"/>
            <div class="profile-avatar-initials" style="display:none"><?= htmlspecialchars($userInitial) ?></div>
          <?php else: ?>
            <div class="profile-avatar-initials"><?= htmlspecialchars($userInitial) ?></div>
          <?php endif; ?>
          <span class="profile-online-dot"></span>
          <div class="profile-dropdown" id="profileDropdown">
            <div class="pd-header">
              <div class="pd-avatar-wrap">
                <?php if ($avatarSrc): ?>
                  <img src="<?= htmlspecialchars($avatarSrc) ?>" class="pd-avatar"
                       onerror="this.style.display='none';this.nextElementSibling.style.display='flex'"/>
                  <div class="pd-avatar-initials" style="display:none"><?= htmlspecialchars($userInitial) ?></div>
                <?php else: ?>
                  <div class="pd-avatar-initials"><?= htmlspecialchars($userInitial) ?></div>
                <?php endif; ?>
                <span class="pd-dot"></span>
              </div>
              <div class="pd-name"><?= htmlspecialchars($u['nom']) ?></div>
              <div class="pd-email"><?= htmlspecialchars($u['email'] ?? '') ?></div>
            </div>
            <ul class="pd-menu">
              <li><a href="<?= BASE ?>/view/client/profil.php"><span class="pd-icon">👤</span> Profile Details</a></li>
              <li><a href="<?= BASE ?>/view/client/mes_commandes.php"><span class="pd-icon">📦</span> Mes commandes</a></li>
              <li><a href="<?= BASE ?>/view/client/panier.php"><span class="pd-icon">🛒</span> Mon panier <?php if ($cartCount > 0): ?><span class="cart-count-badge"><?= $cartCount ?></span><?php endif; ?></a></li>
              <li><a href="<?= BASE ?>/view/client/parametres.php"><span class="pd-icon">⚙️</span> Settings</a></li>
              <li class="pd-sep"></li>
              <li><a href="<?= BASE ?>/view/client/cookies.php"><span class="pd-icon">🍪</span> Cookies</a></li>
              <li><a href="<?= BASE ?>/view/client/privacy.php"><span class="pd-icon">🛡️</span> Privacy policy</a></li>
              <li><a href="<?= BASE ?>/view/client/terms.php"><span class="pd-icon">📄</span> Terms of service</a></li>
              <li class="pd-sep"></li>
              <li><a href="<?= BASE ?>/controller/auth_controller.php?action=logout" class="pd-logout"><span class="pd-icon">↪</span> Log out</a></li>
            </ul>
          </div>
        </div>
      <?php else: ?>
        <a href="<?= BASE ?>/view/client/signup.php" class="nav-join"><strong>Mon Espace</strong></a>
      <?php endif; ?>

      <div class="toggle-container">
        <div class="toggle-wrap">
          <input class="toggle-input" id="holo-toggle" type="checkbox"/>
          <label class="toggle-track" for="holo-toggle">
            <div class="toggle-thumb"></div>
          </label>
        </div>
      </div>

    </div>
  </div>
</nav>

<div class="page-wrap">

  <!-- PROFILE HERO -->
  <div class="prof-hero fade-up">
    <div class="prof-av"><?= $userInitial ?></div>
    <div class="prof-meta">
      <h1>Mon <em>Profil</em></h1>
      <p>Membre depuis <?= date('d/m/Y', strtotime($user['created_at'])) ?>
        <?php if($vipSub):?>  <strong style="color:var(--gold);">♛ VIP <?= ucfirst($vipSub['niveau']) ?></strong><?php endif;?>
      </p>
    </div>
    <div class="prof-chips">
      <?php if($vipSub):?><span class="pchip vip">♛ VIP <?= ucfirst($vipSub['niveau']) ?></span><?php endif;?>
      <span class="pchip"> <?= $stats['total_orders'] ?> commandes</span>
      <span class="pchip"> <?= date('Y', strtotime($user['created_at'])) ?></span>
    </div>
  </div>

  <!-- ALERTS -->
  <?php if(!empty($errors)):?>
    <div class="alert alert-err"><i class="fas fa-exclamation-circle"></i><div><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div></div>
  <?php elseif(!empty($success)):?>
    <div class="alert alert-ok"><i class="fas fa-check-circle"></i><div><?= htmlspecialchars($success) ?></div></div>
  <?php endif;?>

  <!-- TWO-COL -->
  <div class="profile-grid">

    <!-- LEFT COLUMN -->
    <div>
      <!-- EDIT FORM -->
      <div class="card fade-up">
        <div class="card-head"><h3><i class="fas fa-user-edit"></i> Modifier mes informations</h3></div>
        <div class="card-body">
          <div style="display:flex;align-items:center;gap:14px;margin-bottom:22px;padding-bottom:18px;border-bottom:1px solid var(--border);">
            <div style="width:52px;height:52px;border-radius:50%;background:linear-gradient(135deg,#1a4a2e,#2d7a4f);color:#a7f3c6;display:flex;align-items:center;justify-content:center;font-family:var(--font-d);font-size:1.5rem;font-weight:700;"><?= $userInitial ?></div>
            <div>
              <div style="font-family:var(--font-d);font-size:1rem;font-weight:600;"><?= htmlspecialchars($user['nom']) ?></div>
              <div style="font-size:.78rem;color:var(--muted);"><?= htmlspecialchars($user['email']) ?></div>
            </div>
          </div>

          <form method="POST">
            <input type="hidden" name="action" value="update_profil"/>
            <div class="form-row">
              <div class="fgroup"><label>Nom complet</label><input type="text" name="nom" value="<?= htmlspecialchars($user['nom']) ?>" required/></div>
              <div class="fgroup"><label>Adresse email</label><input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required/></div>
            </div>
            <div class="form-row">
              <div class="fgroup"><label>Téléphone</label><input type="tel" name="telephone" value="<?= htmlspecialchars($user['telephone']??'') ?>" placeholder="+216 XX XXX XXX"/></div>
              <div class="fgroup"><label>Adresse</label><input type="text" name="adresse" value="<?= htmlspecialchars($user['adresse']??'') ?>" placeholder="Sousse, Tunisie"/></div>
            </div>

            <p class="fsep"><i class="fas fa-lock"></i> Changer le mot de passe</p>
            <p style="font-size:.76rem;color:var(--muted);margin-bottom:12px;">Laisser vide pour conserver le mot de passe actuel.</p>
            <div class="fgroup"><label>Mot de passe actuel</label><input type="password" name="current_password" placeholder="••••••••"/></div>
            <div class="form-row">
              <div class="fgroup"><label>Nouveau mot de passe</label><input type="password" name="new_password" id="npw" placeholder="Min. 8 caractères"/></div>
              <div class="fgroup"><label>Confirmer</label><input type="password" name="confirm_password" placeholder="Répéter"/></div>
            </div>
            <button type="submit" class="fsub"><i class="fas fa-save"></i> Enregistrer les modifications</button>
          </form>
        </div>
      </div>

      <!-- MY REVIEWS -->
      <?php if(!empty($myAvis)):?>
      <div class="card fade-up">
        <div class="card-head"><h3><i class="fas fa-star"></i> Mes avis produits</h3></div>
        <div class="card-body">
          <?php foreach($myAvis as $av):?>
            <div class="avis-item">
              <div style="display:flex;justify-content:space-between;">
                <span class="avis-prod"><?= htmlspecialchars($av['produit']) ?></span>
                <span class="avis-stars"><?= str_repeat('★', (int)$av['note']) ?><?= str_repeat('☆', 5-(int)$av['note']) ?></span>
              </div>
              <p class="avis-text"><?= htmlspecialchars(mb_substr($av['commentaire']??'', 0, 120)) ?></p>
              <div class="avis-meta">
                <span><?= date('d/m/Y', strtotime($av['created_at'])) ?></span>
                <?php if(!$av['valide']):?><span style="color:var(--gold);"> En attente</span><?php else:?><span style="color:var(--green);">Publié</span><?php endif;?>
              </div>
            </div>
          <?php endforeach;?>
        </div>
      </div>
      <?php endif;?>

      <!-- DANGER ZONE -->
      <div class="card card-danger fade-up">
        <div class="card-head"><h3><i class="fas fa-exclamation-triangle"></i> Supprimer mon compte</h3></div>
        <div class="card-body">
          <p class="danger-note">Cette action est <strong>irréversible</strong>. Toutes vos données seront définitivement supprimées.</p>
          <form method="POST" onsubmit="return confirm('Êtes-vous absolument sûr ?')">
            <input type="hidden" name="action" value="delete_account"/>
            <div class="fgroup"><label>Confirmer avec votre mot de passe</label><input type="password" name="confirm_delete_password" required/></div>
            <button type="submit" class="fdel"><i class="fas fa-trash-alt"></i> Supprimer définitivement</button>
          </form>
        </div>
      </div>
    </div>

    <!-- RIGHT COLUMN -->
    <div>
      <!-- ACCOUNT INFO -->
      <div class="card fade-up">
        <div class="card-head"><h3><i class="fas fa-id-card"></i> Informations du compte</h3></div>
        <div class="card-body">
          <div class="info-row"><span class="ilbl">Nom</span><span class="ival"><?= htmlspecialchars($user['nom']) ?></span></div>
          <div class="info-row"><span class="ilbl">Email</span><span class="ival"><?= htmlspecialchars($user['email']) ?></span></div>
          <div class="info-row"><span class="ilbl">Téléphone</span><span class="ival"><?= $user['telephone'] ? htmlspecialchars($user['telephone']) : '—' ?></span></div>
          <div class="info-row"><span class="ilbl">Membre depuis</span><span class="ival"><?= date('d/m/Y', strtotime($user['created_at'])) ?></span></div>
          <div class="info-row"><span class="ilbl">Rôle</span><span class="ival"><span class="bp bp-green">✓ <?= ucfirst($user['role']) ?></span></span></div>
          <div class="info-row"><span class="ilbl">Commandes</span><span class="ival"><strong><?= $stats['total_orders'] ?></strong> (<?= $stats['delivered'] ?> livrées)</span></div>
          <div class="info-row"><span class="ilbl">Total dépensé</span><span class="ival"><strong><?= number_format($stats['total_spent'], 3) ?> TND</strong></span></div>
          <div class="info-row"><span class="ilbl">Panier</span><span class="ival"><?= $cartCount ?> article(s)</span></div>
        </div>
      </div>

      <!-- VIP CARD – LIGHTER & CLEARER -->
      <?php if($vipSub):?>
      <div class="card vip-card fade-up">
        <div class="card-head"><h3>VIP <?= ucfirst($vipSub['niveau']) ?></h3><span class="bp bp-gold">Actif</span></div>
        <div class="card-body">
          <div class="info-row"><span class="ilbl">Niveau</span><span class="ival" style="color:var(--gold);font-weight:700;"><?= ucfirst($vipSub['niveau']) ?></span></div>
          <div class="info-row"><span class="ilbl">Début</span><span class="ival"><?= date('d/m/Y', strtotime($vipSub['date_debut'])) ?></span></div>
          <div class="info-row"><span class="ilbl">Renouvellement</span><span class="ival"><?= date('d/m/Y', strtotime($vipSub['date_fin'])) ?></span></div>
          <?php if(!empty($vipPayments)):?>
          <div style="margin-top:14px;padding-top:12px;border-top:1px solid var(--border);">
            <p style="font-size:.62rem;text-transform:uppercase;letter-spacing:.1em;color:var(--muted);margin-bottom:8px;">Paiements</p>
            <?php foreach($vipPayments as $vp):?>
              <div style="display:flex;justify-content:space-between;font-size:.8rem;padding:5px 0;border-bottom:1px solid var(--border);">
                <span class="ilbl"><?= date('d/m', strtotime($vp['created_at'])) ?> · <?= ucfirst($vp['methode']) ?></span>
                <span style="color:var(--gold);"><?= number_format($vp['montant'], 3) ?> TND</span>
              </div>
            <?php endforeach;?>
          </div>
          <?php endif;?>
          <a href="vip.php" class="vip-manage-link">Gérer mon abonnement →</a>
        </div>
      </div>
      <?php else:?>
      <div class="card fade-up" style="text-align:center;padding:28px;">
        <span style="font-size:2.2rem;">♛</span>
        <h3 style="font-family:var(--font-d);color:var(--gold);margin:8px 0;">Club VIP Benna</h3>
        <p style="color:var(--muted);font-size:.82rem;margin-bottom:20px;">Plans personnalisés & jusqu'à 30% de réduction dès 29 TND/mois.</p>
        <a href="vip.php" class="vip-cta">Voir les offres →</a>
      </div>
      <?php endif;?>

      <!-- RECENT ORDERS -->
      <?php if(!empty($recentOrders)):?>
      <div class="card fade-up">
        <div class="card-head"><h3><i class="fas fa-box"></i> Commandes récentes</h3><a href="mes_commandes.php">Tout voir →</a></div>
        <div class="card-body">
          <?php foreach($recentOrders as $o):
            $s = $statutInfo[$o['statut']] ?? $statutInfo['en_attente'];
          ?>
            <div class="mo-item">
              <div><strong>#<?= $o['id'] ?></strong> <span class="ilbl"><?= date('d/m/Y', strtotime($o['date_commande'])) ?></span></div>
              <div style="display:flex;align-items:center;gap:8px;">
                <span class="ival"><?= number_format($o['total'], 3) ?> TND</span>
                <span class="statut" style="color:<?= $s['color'] ?>;background:<?= $s['bg'] ?>;"><?= $s['icon'] ?> <?= $s['label'] ?></span>
              </div>
            </div>
          <?php endforeach;?>
        </div>
      </div>
      <?php endif;?>
    </div>

  </div>
</div>

<!-- FOOTER -->
<footer class="footer">
  <div class="footer-grid">
    <div><h4>Benna</h4><p>Alimentation saine & artisanale<br/>depuis Sousse, Tunisie.</p></div>
    <div><h4>Boutique</h4><a href="produits.php">Tous les produits</a><a href="conseils.php">Conseils santé</a><a href="vip.php">Club VIP</a></div>
    <div><h4>Mon Compte</h4><a href="mes_commandes.php">Mes commandes</a><a href="panier.php">Mon panier</a><a href="<?= BASE ?>/controller/auth_controller.php?action=logout">Déconnexion</a></div>
    <div><h4>Contact</h4><p>📍 Sousse, Tunisie</p><p>✉️ welcome@benna.tn</p></div>
  </div>
  <div class="footer-bottom">© 2026 Benna – Tous droits réservés · Fait avec 🌿 à Sousse</div>
</footer>

<script>
(function(){
  const toggle = document.getElementById('holo-toggle');
  if(toggle){
    const saved = localStorage.getItem('bena-theme');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const isDark = saved ? saved === 'dark' : prefersDark;
    document.body.classList.toggle('dark', isDark);
    toggle.checked = isDark;
    toggle.addEventListener('change', () => {
      document.body.classList.toggle('dark', toggle.checked);
      localStorage.setItem('bena-theme', toggle.checked ? 'dark' : 'light');
    });
  }

  const io = new IntersectionObserver(entries => {
    entries.forEach(e => { if(e.isIntersecting){ e.target.classList.add('visible'); io.unobserve(e.target); } });
  }, { threshold: 0.1 });
  document.querySelectorAll('.fade-up').forEach(el => io.observe(el));

  const npw = document.getElementById('npw');
  if(npw){
    npw.addEventListener('input', function(){
      const v = this.value;
      let borderColor = 'rgba(255,255,255,.14)';
      if(v.length >= 12 && /[A-Z]/.test(v) && /[0-9]/.test(v)) borderColor = 'rgba(74,222,128,.4)';
      else if(v.length >= 8) borderColor = 'rgba(212,170,80,.4)';
      else if(v.length > 0) borderColor = 'rgba(248,113,113,.4)';
      this.style.borderColor = borderColor;
    });
  }

  // FIX: Profile dropdown toggle - add click handler
  const profileTrigger = document.getElementById('profileTrigger');
  if(profileTrigger) {
    profileTrigger.addEventListener('click', function(e) {
      e.stopPropagation();
      this.classList.toggle('open');
    });
    document.addEventListener('click', function(e) {
      if(!profileTrigger.contains(e.target)) {
        profileTrigger.classList.remove('open');
      }
    });
  }
})();
</script>
</body>
</html>