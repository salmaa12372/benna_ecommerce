<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../../../config/database.php";
require_once __DIR__ . "/../../../config/app.php";
require_once __DIR__ . "/../../../controller/traitement.php";

$cartCount = isset($_SESSION['user_id']) ? countPanier($cnx, $_SESSION['user_id']) : 0;
$userAvatar = null;
$userInitial = '?';
$u = null;
if (isset($_SESSION['user_id'])) {
    $u = getUserById($cnx, $_SESSION['user_id']);
    $userAvatar = $u['avatar'] ?? null;
    $userInitial = mb_strtoupper(mb_substr($_SESSION['nom'] ?? 'U', 0, 1));
}
$currentPage = basename($_SERVER['PHP_SELF']);
$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover"/>
  <title>Benna | <?= htmlspecialchars($pageTitle ?? 'Alimentation Saine & Artisanale Tunisienne') ?></title>
  <meta name="description" content="Benna — Snacks artisanaux tunisiens sans gluten, sans lactose, 100 % naturels. Livrés depuis Sousse."/>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,600&family=DM+Sans:ital,opsz,wght@0,9..40,200;0,9..40,400;0,9..40,500;1,9..40,300&family=Noto+Naskh+Arabic:wght@400;500&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="<?= BASE ?>/view/client/assets/style.css"/>
  <?php if (!empty($extraCss)): ?>
    <link rel="stylesheet" href="<?= BASE ?>/view/client/assets/<?= $extraCss ?>"/>
  <?php endif; ?>
</head>
<body>

<nav id="navbar" class="navbar">
  <div class="nav-container">
    
    <a href="<?= BASE ?>/index.php" class="nav-logo">
      <img src="<?= BASE ?>/view/pics/logo.png"  alt="Benna" class="nav-logo-img" onerror="this.style.display='none'"/>
      Benna
    </a>
    <div class="nav-links" id="navLinks">
      <a href="<?= BASE ?>/index.php#home">Accueil</a>
      <a href="<?= BASE ?>/index.php#story">Histoire</a>
      <a href="<?= BASE ?>/index.php#shop">Boutique</a>
      <a href="<?= BASE ?>/index.php#vip">VIP</a>
      <a href="<?= BASE ?>/index.php#gallery">Galerie</a>
      <a href="<?= BASE ?>/index.php#reviews">Avis</a>
      <a href="<?= BASE ?>/index.php#contact">Contact</a>
    </div>

    <div class="nav-actions">

  <a href="<?= BASE ?>/view/client/produits.php" class="nav-join"><strong>Acheter</strong></a>
  <?php if (isset($_SESSION['user_id'])): ?>
  <a href="<?= BASE ?>/view/client/home.php" class="nav-join"><strong>Mon Espace</strong></a>
  </a>
<?php endif; ?>

  <?php if ($isLoggedIn): ?>
  <?php
    $avatarSrc = !empty($u['photo'])
        ? BASE . '/' . ltrim($u['photo'], '/')
        : (!empty($u['avatar']) ? BASE . '/' . ltrim($u['avatar'], '/') : null);
  ?>

  <div class="profile-trigger" id="profileTrigger">

    <?php if ($avatarSrc): ?>
      <img src="<?= htmlspecialchars($avatarSrc) ?>"
           class="profile-avatar"
           alt="<?= htmlspecialchars($u['nom']) ?>"
           onerror="this.style.display='none';this.nextElementSibling.style.display='flex'"/>
      <div class="profile-avatar-initials" style="display:none">
        <?= htmlspecialchars($userInitial) ?>
      </div>
    <?php else: ?>
      <div class="profile-avatar-initials">
        <?= htmlspecialchars($userInitial) ?>
      </div>
    <?php endif; ?>

    <span class="profile-online-dot"></span>

    <div class="profile-dropdown" id="profileDropdown">

      <div class="pd-header">
        <div class="pd-avatar-wrap">
          <?php if ($avatarSrc): ?>
            <img src="<?= htmlspecialchars($avatarSrc) ?>"
                 class="pd-avatar"
                 alt="<?= htmlspecialchars($u['nom']) ?>"
                 onerror="this.style.display='none';this.nextElementSibling.style.display='flex'"/>
            <div class="pd-avatar-initials" style="display:none">
              <?= htmlspecialchars($userInitial) ?>
            </div>
          <?php else: ?>
            <div class="pd-avatar-initials">
              <?= htmlspecialchars($userInitial) ?>
            </div>
          <?php endif; ?>
          <span class="pd-dot"></span>
        </div>
        <div class="pd-name"><?= htmlspecialchars($u['nom']) ?></div>
        <div class="pd-email"><?= htmlspecialchars($u['email'] ?? '') ?></div>
      </div>



      
      <ul class="pd-menu">
        <li>
          <a href="<?= BASE ?>/view/client/profil.php">
            <span class="pd-icon">👤</span> Profile Details
          </a>
        </li>
        <li>
          <a href="<?= BASE ?>/view/client/home.php">
            <span class="pd-icon">🏠</span> Mon Espace
         </a>
        </li>
        <li>
          <a href="<?= BASE ?>/view/client/parametres.php">
            <span class="pd-icon">⚙️</span> Settings
          </a>
        </li>
        <li class="pd-sep"></li>
        <li>
          <a href="<?= BASE ?>/view/client/cookies.php">
            <span class="pd-icon">🍪</span> Cookies
          </a>
        </li>
        <li>
          <a href="<?= BASE ?>/view/client/privacy.php">
            <span class="pd-icon">🛡️</span> Privacy policy
          </a>
        </li>
        <li>
          <a href="<?= BASE ?>/view/client/terms.php">
            <span class="pd-icon">📄</span> Terms of service
          </a>
        </li>
        <li class="pd-sep"></li>
        <li>
          <a href="<?= BASE ?>/controller/auth_controller.php?action=logout" class="pd-logout">
            <span class="pd-icon">↪</span> Log out
          </a>
        </li>
        
      </ul>

    </div>
  </div>
  <?php else: ?>
  <a href="<?= BASE ?>/view/client/signup.php" class="nav-join"><strong>Mon Espace</strong></a>
<?php endif; ?>

</div>

      <!-- Dark mode toggle -->
      <div class="toggle-wrap">
        <input class="toggle-input" id="holo-toggle" type="checkbox"/>
        <label class="toggle-track" for="holo-toggle"><div class="toggle-thumb"></div></label>
      </div>
    </div>
  </div>
</nav>

<?php if (!empty($_SESSION['flash_success'])): ?>
  <div class="flash-success">success <?= htmlspecialchars($_SESSION['flash_success']) ?></div>
  <?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>
<?php if (!empty($_SESSION['error'])): ?>
  <div class="flash-error">error <?= htmlspecialchars($_SESSION['error']) ?></div>
  <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<script>
  window.addEventListener('scroll', () => {
    document.getElementById('navbar')?.classList.toggle('scrolled', window.scrollY > 60);
  });

  (function() {
    const toggle = document.getElementById('holo-toggle');
    if (!toggle) return;
    const saved = localStorage.getItem('bena-theme');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const isDark = saved ? saved === 'dark' : prefersDark;
    document.body.classList.toggle('dark', isDark);
    toggle.checked = isDark;
    toggle.addEventListener('change', () => {
      document.body.classList.toggle('dark', toggle.checked);
      localStorage.setItem('bena-theme', toggle.checked ? 'dark' : 'light');
    });
  })();

  document.getElementById('hamburger')?.addEventListener('click', () => {
    document.getElementById('navLinks')?.classList.toggle('show');
  });

  (function () {
  const trigger  = document.getElementById('profileTrigger');
  const dropdown = document.getElementById('profileDropdown');
  if (!trigger || !dropdown) return;

  function open()  { trigger.classList.add('open');    trigger.setAttribute('aria-expanded','true'); }
  function close() { trigger.classList.remove('open'); trigger.setAttribute('aria-expanded','false'); }
  function toggle(){ trigger.classList.contains('open') ? close() : open(); }

  trigger.addEventListener('click', function(e){ e.stopPropagation(); toggle(); });
  document.addEventListener('click', function(e){ if (!trigger.contains(e.target)) close(); });
  document.addEventListener('keydown', function(e){ if (e.key === 'Escape') close(); });
})();




</script>
<style>
  @media (max-width: 768px) {
    .nav-links { display: none; flex-direction: column; position: absolute; top: 80px; left: 0; right: 0; background: var(--card); border-bottom: 1px solid var(--border); padding: 16px; gap: 12px; z-index: 1000; }
    .nav-links.show { display: flex; }
  }
</style>