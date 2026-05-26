<?php
// view/livreur/partials/livreur_header.php
if (session_status() === PHP_SESSION_NONE) session_start();

include_once __DIR__ . "/../../../config/database.php";
require_once __DIR__ . "/../../../config/app.php";

// ─── Auth ──────────────────────────────────────────────────────
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['livreur', 'admin'])) {
    header("Location: " . BASE . "/view/client/signup.php");
    exit;
}

$id = $_SESSION['user_id']; // Plus de valeur par défaut
$stmt = $cnx->prepare("SELECT * FROM users WHERE id = ? AND role = 'livreur'");
$stmt->execute([$id]);
$livreur = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$livreur) {
    header("Location: " . BASE . "/view/client/signup.php");
    exit;
}

$cur = basename($_SERVER['PHP_SELF']);

// ─── Open réclamations count ───────────────────────────────────
$stmtRec = $cnx->prepare("
    SELECT COUNT(*) FROM reclamations r
    JOIN commandes c ON c.id = r.commande_id
    JOIN livraisons l ON l.commande_id = c.id
    WHERE l.livreur_id = ? AND r.statut != 'resolue'
");
$stmtRec->execute([$id]);
$openRec = (int)$stmtRec->fetchColumn();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Benna — Livreur | <?= htmlspecialchars($pageTitle ?? '') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com"/>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=DM+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
    <link rel="stylesheet" href="<?= BASE ?>/public/css/dashboard.css"/>

</head>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
    --sidebar-bg: #1c2b1c;
    --sidebar-hover: rgba(255,255,255,0.06);
    --sidebar-active-bg: #2d4d2d;
    --sidebar-text: #b8ccb8;
    --sidebar-section: #5c7a5c;
    --accent: #3d7a3d;
    --accent-hover: #4e9e4e;
    --accent-light: #e8f5e8;

    --bg: #f2f7f2;
    --card-bg: #ffffff;
    --text: #1c2b1c;
    --muted: #6b826b;
    --border: #dce8dc;

    --red: #dc2626;
    --red-bg: #fee2e2;
    --orange: #f97316;
    --orange-bg: #fff7ed;
    --blue: #2563eb;
    --blue-bg: #dbeafe;
    --green: #16a34a;
    --green-bg: #dcfce7;
    --yellow-bg: #fef9c3;
    --yellow: #a16207;

    --radius: 12px;
    --shadow: 0 1px 3px rgba(0,0,0,.07), 0 1px 2px rgba(0,0,0,.04);
    --shadow-md: 0 4px 16px rgba(0,0,0,.08);
}

body {
    font-family: 'DM Sans', sans-serif;
    background: var(--bg);
    color: var(--text);
    display: flex;
    min-height: 100vh;
    font-size: 14px;
    line-height: 1.5;
}

/* ── Sidebar ──────────────────────────────────────────── */
.sidebar {
    width: 248px;
    min-height: 100vh;
    background: var(--sidebar-bg);
    display: flex;
    flex-direction: column;
    position: fixed;
    top: 0; left: 0; bottom: 0;
    z-index: 100;
}

.sidebar-logo-link { text-decoration: none; }
.sidebar-logo {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 22px 20px 18px;
    border-bottom: 1px solid rgba(255,255,255,.07);
}
.sidebar-logo-img {
    width: 38px; height: 38px;
    border-radius: 9px;
    object-fit: cover;
    background: var(--accent);
}
.sidebar-logo-img-fallback {
    width: 38px; height: 38px;
    background: var(--accent);
    border-radius: 9px;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px; color: #fff;
    flex-shrink: 0;
}
.sidebar-logo-text {
    font-family: 'Playfair Display', serif;
    color: #fff;
    font-size: 1.25rem;
    font-weight: 600;
    line-height: 1.1;
    display: block;
}
.sidebar-logo-sub {
    color: var(--sidebar-text);
    font-size: 0.68rem;
    font-weight: 500;
    letter-spacing: 0.07em;
    text-transform: uppercase;
    display: block;
}

.sidebar-nav { flex: 1; padding: 14px 10px; overflow-y: auto; }
.sidebar-section {
    color: var(--sidebar-section);
    font-size: 0.67rem;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    padding: 10px 10px 5px;
    margin-top: 6px;
}
.sidebar-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 9px 12px;
    border-radius: 8px;
    color: var(--sidebar-text);
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
    transition: background 0.15s, color 0.15s;
    margin-bottom: 2px;
    position: relative;
}
.sidebar-item i { width: 18px; text-align: center; font-size: 14px; }
.sidebar-item:hover { background: var(--sidebar-hover); color: #fff; }
.sidebar-item.active { background: var(--sidebar-active-bg); color: #fff; }
.sidebar-item .badge-pill {
    margin-left: auto;
    background: var(--red);
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    padding: 1px 6px;
    border-radius: 10px;
    min-width: 18px;
    text-align: center;
}

.sidebar-footer {
    padding: 14px 16px;
    border-top: 1px solid rgba(255,255,255,.07);
    display: flex;
    align-items: center;
    gap: 10px;
}
.user-avatar {
    width: 36px; height: 36px;
    background: var(--accent);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 15px; color: #fff; flex-shrink: 0;
}
.user-name {
    color: #fff;
    font-size: 0.82rem;
    font-weight: 600;
    display: block;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.user-role { color: var(--sidebar-text); font-size: 0.7rem; display: block; }
.logout-btn {
    margin-left: auto;
    color: var(--sidebar-section);
    text-decoration: none;
    font-size: 16px;
    transition: color 0.15s;
    flex-shrink: 0;
    cursor: pointer;
}
.logout-btn:hover { color: #e57373; }

/* ── Main content ─────────────────────────────────────── */
.main-content {
    margin-left: 248px;
    flex: 1;
    min-height: 100vh;
    padding: 0;
    display: flex;
    flex-direction: column;
}

/* ── Topbar ───────────────────────────────────────────── */
.topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 28px 32px 16px;
    background: var(--bg);
}
.topbar h1 {
    font-family: 'Playfair Display', serif;
    font-size: 1.85rem;
    font-weight: 600;
    color: var(--text);
    letter-spacing: -0.01em;
}
.pill {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: 24px;
    padding: 6px 14px 6px 10px;
    font-size: 0.85rem;
    font-weight: 500;
    color: var(--text);
    box-shadow: var(--shadow);
}

/* ── Page body wrapper ───────────────────────────────── */
.page-body {
    padding: 0 32px 32px;
    flex: 1;
}

/* ── Cards ────────────────────────────────────────────── */
.card {
    background: var(--card-bg);
    border-radius: var(--radius);
    border: 1px solid var(--border);
    box-shadow: var(--shadow);
    overflow: hidden;
    margin-bottom: 20px;
}
.card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 22px 14px;
    border-bottom: 1px solid var(--border);
}
.card-title {
    font-size: 1rem;
    font-weight: 600;
    color: var(--text);
}
.card-danger { border-left: 4px solid var(--red); }

/* ── Stats grid ───────────────────────────────────────── */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}
.stat-card {
    background: var(--card-bg);
    border-radius: var(--radius);
    padding: 20px 22px;
    border: 1px solid var(--border);
    box-shadow: var(--shadow);
    border-top: 3px solid transparent;
}
.stat-card.blue   { border-top-color: var(--blue); }
.stat-card.warn   { border-top-color: var(--orange); }
.stat-card.danger { border-top-color: var(--red); }
.stat-card:not(.blue):not(.warn):not(.danger) { border-top-color: var(--green); }
.stat-icon { font-size: 1.4rem; margin-bottom: 8px; }
.stat-val {
    font-family: 'Playfair Display', serif;
    font-size: 2rem;
    font-weight: 600;
    line-height: 1;
    margin-bottom: 4px;
    color: var(--green);
}
.stat-val.blue   { color: var(--blue); }
.stat-val.warn   { color: var(--orange); }
.stat-val.danger { color: var(--red); }
.stat-label { font-size: 0.8rem; color: var(--muted); font-weight: 500; }

/* ── Badges ───────────────────────────────────────────── */
.badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 9px;
    border-radius: 20px;
    font-size: 0.72rem;
    font-weight: 700;
}
.badge-green  { background: var(--green-bg); color: var(--green); }
.badge-red    { background: var(--red-bg); color: var(--red); }
.badge-orange { background: var(--orange-bg); color: var(--orange); }
.badge-blue   { background: var(--blue-bg); color: var(--blue); }
.badge-yellow { background: var(--yellow-bg); color: var(--yellow); }
.badge-gray   { background: #f1f5f1; color: var(--muted); }

/* ── Buttons ──────────────────────────────────────────── */
.btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border-radius: 8px;
    font-family: 'DM Sans', sans-serif;
    font-size: 0.82rem;
    font-weight: 600;
    cursor: pointer;
    border: none;
    text-decoration: none;
    transition: opacity 0.15s, transform 0.1s;
    white-space: nowrap;
}
.btn:hover { opacity: 0.88; transform: translateY(-1px); }
.btn:active { transform: translateY(0); }
.btn-sm { padding: 5px 11px; font-size: 0.76rem; }
.btn-green  { background: var(--accent); color: #fff; }
.btn-green:hover { background: var(--accent-hover); opacity: 1; }
.btn-blue   { background: var(--blue); color: #fff; }
.btn-orange { background: var(--orange); color: #fff; }
.btn-red    { background: var(--red); color: #fff; }
.btn-outline { background: transparent; color: var(--accent); border: 1.5px solid var(--accent); }
.btn-outline:hover { background: var(--accent-light); }

/* ── Table ────────────────────────────────────────────── */
table { width: 100%; border-collapse: collapse; }
thead th {
    padding: 11px 20px;
    text-align: left;
    font-size: 0.7rem;
    font-weight: 700;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 0.08em;
    background: #f7faf7;
    border-bottom: 1px solid var(--border);
}
tbody tr { border-bottom: 1px solid #f0f5f0; transition: background 0.12s; }
tbody tr:last-child { border-bottom: none; }
tbody tr:hover { background: #f7faf7; }
tbody td { padding: 13px 20px; font-size: 0.875rem; color: var(--text); }

/* ── Flash messages ───────────────────────────────────── */
.flash {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 16px 32px 0;
    padding: 12px 18px;
    border-radius: 9px;
    font-size: 0.875rem;
    font-weight: 500;
}
.flash.success { background: var(--green-bg); color: var(--green); border: 1px solid #86efac; }
.flash.error   { background: var(--red-bg);   color: var(--red);   border: 1px solid #fca5a5; }

/* ── Modal ────────────────────────────────────────────── */
.modal-overlay {
    display: none;
    position: fixed; inset: 0;
    background: rgba(0,0,0,.45);
    z-index: 999;
    align-items: center;
    justify-content: center;
}
.modal-overlay.open { display: flex; }
.modal-box {
    background: #fff;
    border-radius: var(--radius);
    padding: 26px;
    max-width: 420px;
    width: 90%;
    box-shadow: 0 12px 40px rgba(0,0,0,.15);
}
.modal-box h4 { margin-bottom: 10px; font-size: 1rem; font-weight: 600; color: var(--text); }
.modal-box p  { font-size: 0.82rem; color: var(--muted); margin-bottom: 14px; }
.modal-box textarea {
    width: 100%;
    border: 1.5px solid var(--border);
    border-radius: 8px;
    padding: 10px 12px;
    font-family: inherit;
    font-size: 0.875rem;
    resize: vertical;
    outline: none;
    color: var(--text);
    transition: border-color 0.15s;
}
.modal-box textarea:focus { border-color: var(--accent); }
.modal-actions { display: flex; gap: 8px; margin-top: 12px; justify-content: flex-end; }

/* ── Dot animation ────────────────────────────────────── */
.dot-pulse {
    display: inline-block;
    width: 8px; height: 8px;
    border-radius: 50%;
    background: var(--orange);
    margin-right: 4px;
    vertical-align: middle;
    animation: pulse 1.5s infinite;
}
@keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.3} }

/* ── Text helpers ─────────────────────────────────────── */
.text-muted  { color: var(--muted); }
.text-sm     { font-size: 0.82rem; }
.text-xs     { font-size: 0.75rem; }
.text-danger { color: var(--red); }
.fw-bold     { font-weight: 600; }
.mt-1        { margin-top: 0.8rem; }

/* ── Logout confirm modal ─────────────────────────────── */
#logout-modal {
    display: none;
    position: fixed; inset: 0;
    background: rgba(0,0,0,.45);
    z-index: 9999;
    align-items: center;
    justify-content: center;
}
#logout-modal.open { display: flex; }
#logout-modal .modal-box { text-align: center; }
#logout-modal .modal-box .icon {
    font-size: 2rem;
    color: var(--red);
    margin-bottom: 12px;
}
#logout-modal .modal-box h4 { font-size: 1.05rem; margin-bottom: 6px; }
#logout-modal .modal-box p  { font-size: 0.85rem; color: var(--muted); margin-bottom: 20px; }
#logout-modal .modal-actions { justify-content: center; gap: 10px; }
</style>
<body>

<!-- ── Logout confirm modal ──────────────────────────── -->
<div id="logout-modal">
    <div class="modal-box">
        <div class="icon"><i class="fas fa-sign-out-alt"></i></div>
        <h4>Déconnexion</h4>
        <p>Voulez-vous vraiment vous déconnecter ?</p>
        <div class="modal-actions">
            <button class="btn btn-outline btn-sm"
                    onclick="document.getElementById('logout-modal').classList.remove('open')">
                Annuler
            </button>
            <a href="logout.php" class="btn btn-red btn-sm">
                <i class="fas fa-sign-out-alt"></i> Déconnecter
            </a>
        </div>
    </div>
</div>

<aside class="sidebar">
    <a href="dashboard.php" class="sidebar-logo-link">
        <div class="sidebar-logo">
            
        <img src="<?= BASE ?>/view/pics/logo.png" alt="Benna" class="benna-logo-img"
     alt="Logo"
     class="sidebar-logo-img"
     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'"/>
            <div>
                <span class="sidebar-logo-text">Benna</span>
                <span class="sidebar-logo-sub">Espace Livreur</span>
            </div>
        </div>
    </a>

    <nav class="sidebar-nav">
        <div class="sidebar-section">Général</div>
        <a href="dashboard.php" class="sidebar-item <?= $cur==='dashboard.php' ? 'active' : '' ?>">
            <i class="fas fa-chart-bar"></i> Tableau de bord
        </a>

        <div class="sidebar-section">Livraisons</div>
        <a href="mes_livraisons.php" class="sidebar-item <?= $cur==='mes_livraisons.php' ? 'active' : '' ?>">
            <i class="fas fa-truck"></i> Mes livraisons
        </a>
        <a href="carte.php" class="sidebar-item <?= $cur==='carte.php' ? 'active' : '' ?>">
            <i class="fas fa-map-marked-alt"></i> Carte des tournées
        </a>
        <a href="historique.php" class="sidebar-item <?= $cur==='historique.php' ? 'active' : '' ?>">
            <i class="fas fa-history"></i> Historique
        </a>

        <div class="sidebar-section">Suivi</div>
        <a href="reclamations.php" class="sidebar-item <?= $cur==='reclamations.php' ? 'active' : '' ?>">
            <i class="fas fa-flag"></i> Réclamations
            <?php if ($openRec > 0): ?>
                <span class="badge-pill"><?= $openRec ?></span>
            <?php endif; ?>
        </a>
        <a href="signaler_probleme.php" class="sidebar-item <?= $cur==='signaler_probleme.php' ? 'active' : '' ?>">
            <i class="fas fa-exclamation-triangle"></i> Signaler un problème
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="user-avatar"><i class="fas fa-motorcycle"></i></div>
        <div style="flex:1; overflow:hidden;">
            <span class="user-name"><?= htmlspecialchars($livreur['nom']) ?></span>
            <span class="user-role">Livreur</span>
        </div>
        <!-- ── Bouton déconnexion ── -->
        <a class="logout-btn" title="Déconnexion"
           onclick="document.getElementById('logout-modal').classList.add('open')">
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