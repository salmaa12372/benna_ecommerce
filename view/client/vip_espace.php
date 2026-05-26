<?php
ob_start();
session_start();

require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../config/app.php";
require_once __DIR__ . "/../../config/auth.php";
require_once __DIR__ . "/../../controller/traitement.php";

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE . "/view/client/signup.php");
    ob_end_clean();
    exit();
}

// Get user
$user = getUserById($cnx, $_SESSION['user_id']);
if (!$user) {
    session_destroy();
    header("Location: " . BASE . "/view/client/signup.php");
    ob_end_clean();
    exit();
}

// Get subscription
$abonnement = getAbonnementUser($cnx, $_SESSION['user_id']);
if (!$abonnement) {
    header("Location: " . BASE . "/view/client/vip.php");
    ob_end_clean();
    exit();
}

$niveau = $abonnement['niveau']; // 'basic', 'premium', 'elite'

// Get nutritionist
$stmtNutri = $cnx->prepare("SELECT * FROM users WHERE role='nutritionniste' LIMIT 1");
$stmtNutri->execute();
$nutri = $stmtNutri->fetch(PDO::FETCH_ASSOC);

// Get data based on level
$msgs = [];
$consultations = [];
$objectifs = [];
$unread = 0;

// Basic+ : chat
if (in_array($niveau, ['basic', 'premium', 'elite'])) {
    if ($nutri) {
        $msgs = getConversation($cnx, $_SESSION['user_id'], $nutri['id']);
        markMessagesLu($cnx, $nutri['id'], $_SESSION['user_id']);
        $unread = countUnreadMessages($cnx, $_SESSION['user_id']);
    }
}

// Premium+ : consultations + objectives
if (in_array($niveau, ['premium', 'elite'])) {
    $consultations = getConsultationsClient($cnx, $_SESSION['user_id']);
    $objectifs = getObjectifsClient($cnx, $_SESSION['user_id']);
}

$niveauConfig = [
    'basic'   => ['emoji' => '🟢', 'color' => '#aaebc2', 'label' => 'VIP Basic',   'bg' => 'linear-gradient(135deg,#14532d,#16a34a)'],
    'premium' => ['emoji' => '🔵', 'color' => '#aac4ee', 'label' => 'VIP Premium', 'bg' => 'linear-gradient(135deg,#1e3a8a,#3b82f6)'],
    'elite'   => ['emoji' => '🟣', 'color' => '#cbb8f7', 'label' => 'VIP Elite',   'bg' => 'linear-gradient(135deg,#4c1d95,#8b5cf6)'],
];
$cfg = $niveauConfig[$niveau] ?? $niveauConfig['basic'];

$pageTitle = "Mon Espace " . $cfg['label'];
include "partials/header.php";
ob_end_flush();
?>

<style>
    /* Force navbar background & text colors on profile page */
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
  color: #fdf8f0 !important;
}
.vip-espace { padding-top: 90px; min-height: 100vh; background: var(--cream, #fdf8f0); }
.vip-container { max-width: 1200px; margin: 0 auto; padding: 2rem 1.5rem; }

/* Welcome card */
.vip-welcome {
    background: <?= $cfg['bg'] ?>;
    border-radius: 24px;
    padding: 2rem 2.5rem;
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 20px 60px rgba(0,0,0,0.2);
}
.vip-welcome h1 { font-family: var(--font-display,'Cormorant Garamond',serif); font-size: 1.8rem; margin: 0 0 .5rem; font-weight: 400; }
.vip-level-badge {
    display: inline-flex; align-items: center; gap: .5rem;
    background: rgba(255,255,255,.18); backdrop-filter: blur(10px);
    padding: .4rem 1.1rem; border-radius: 40px; font-size: .85rem; font-weight: 600; margin: .4rem 0;
}
.vip-meta { font-size: .83rem; opacity: .85; margin-top: .4rem; }
.vip-actions { display: flex; gap: .7rem; flex-wrap: wrap; }
.vip-btn {
    padding: .65rem 1.2rem; border-radius: 40px; font-size: .82rem; font-weight: 600;
    text-decoration: none; border: none; cursor: pointer; display: inline-flex; align-items: center;
    gap: .4rem; transition: all .25s ease; font-family: var(--font-body,'Inter',sans-serif);
}
.vip-btn-white { background: white; color: <?= $cfg['color'] ?>; }
.vip-btn-white:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,.15); }
.vip-btn-ghost { background: rgba(255,255,255,.15); color: white; border: 1.5px solid rgba(255,255,255,.4); }
.vip-btn-ghost:hover { background: rgba(255,255,255,.25); }
.vip-btn-red { background: #dc2626; color: white; }
.vip-btn-red:hover { background: #b91c1c; transform: translateY(-2px); }

/* Grid */
.vip-grid { display: grid; gap: 1.5rem; }
.vip-grid-2 { grid-template-columns: repeat(auto-fit, minmax(380px,1fr)); }
.vip-grid-1 { grid-template-columns: 1fr; }
@media(max-width:768px) { .vip-grid-2 { grid-template-columns: 1fr; } }

/* Cards */
.vip-card {
    background: var(--card,#fff); border-radius: 20px; padding: 1.5rem;
    border: 1px solid var(--border,#e8dccb); box-shadow: 0 4px 20px rgba(0,0,0,.05);
}
.vip-card-head {
    font-family: var(--font-display,'Cormorant Garamond',serif);
    font-size: 1.2rem; font-weight: 500; color: var(--fg,#2c2a29);
    padding-bottom: .8rem; margin-bottom: 1.2rem;
    border-bottom: 2px solid var(--border,#e8dccb);
    display: flex; align-items: center; gap: .5rem;
}

/* Chat */
.chat-box { height: 320px; display: flex; flex-direction: column; }
.chat-msgs { flex: 1; overflow-y: auto; display: flex; flex-direction: column; gap: .6rem; padding: .3rem 0; }
.msg { max-width: 78%; padding: .6rem 1rem; border-radius: 16px; font-size: .84rem; line-height: 1.5; word-break: break-word; }
.msg-me { align-self: flex-end; background: <?= $cfg['color'] ?>; color: white; border-radius: 16px 4px 16px 16px; }
.msg-other { align-self: flex-start; background: var(--cream,#fdf8f0); border: 1px solid var(--border,#e8dccb); border-radius: 4px 16px 16px 16px; color: var(--fg,#2c2a29); }
.msg-time { font-size: .62rem; opacity: .6; margin-top: .2rem; }
.chat-form { display: flex; gap: .6rem; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--border,#e8dccb); }
.chat-input {
    flex: 1; padding: .65rem 1rem; border: 1.5px solid var(--border,#e8dccb);
    border-radius: 40px; font-size: .84rem; background: var(--cream,#fdf8f0);
    color: var(--fg,#2c2a29); outline: none; font-family: var(--font-body,'Inter',sans-serif);
    transition: border-color .2s;
}
.chat-input:focus { border-color: <?= $cfg['color'] ?>; }
.chat-send {
    width: 40px; height: 40px; border-radius: 50%; border: none;
    background: <?= $cfg['color'] ?>; color: white; cursor: pointer;
    font-size: 1rem; transition: transform .2s;
}
.chat-send:hover { transform: scale(1.08); }
.chat-full-link { text-align: center; margin-top: .8rem; padding-top: .7rem; border-top: 1px solid var(--border,#e8dccb); }
.chat-full-link a { color: <?= $cfg['color'] ?>; font-size: .82rem; font-weight: 600; text-decoration: none; }

/* Objectives */
.obj-item { margin-bottom: 1.1rem; }
.obj-header { display: flex; justify-content: space-between; font-size: .83rem; font-weight: 500; margin-bottom: .35rem; }
.progress-bar { height: 9px; background: #e5e7eb; border-radius: 10px; overflow: hidden; }
.progress-fill { height: 100%; border-radius: 10px; background: <?= $cfg['color'] ?>; transition: width .5s; }
.obj-date { font-size: .68rem; color: var(--muted,#8a7a6a); margin-top: .25rem; }

/* Consultations */
.consult-grid { display: grid; grid-template-columns: repeat(auto-fill,minmax(300px,1fr)); gap: 1rem; }
.consult-item { background: var(--cream,#fdf8f0); border-radius: 14px; padding: 1.1rem; border: 1px solid var(--border,#e8dccb); transition: all .25s; }
.consult-item:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,.07); }
.consult-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: .7rem; }
.consult-title { font-weight: 600; font-size: .9rem; }
.consult-status { padding: .18rem .65rem; border-radius: 20px; font-size: .68rem; font-weight: 700; }
.st-planifiee { background:#dbeafe; color:#1e40af; }
.st-confirmee { background:#d1fae5; color:#065f46; }
.st-terminee  { background:#f3f4f6; color:#374151; }
.st-annulee   { background:#fee2e2; color:#991b1b; }
.consult-meta { font-size: .78rem; color: var(--muted,#8a7a6a); margin: .3rem 0; display: flex; gap: .7rem; flex-wrap: wrap; }
.consult-nutri { font-size: .78rem; color: <?= $cfg['color'] ?>; font-weight: 500; margin-top: .3rem; }
.consult-join { display: inline-flex; align-items: center; gap: .4rem; margin-top: .7rem; padding: .45rem .9rem; background: <?= $cfg['color'] ?>; color: white; border-radius: 40px; text-decoration: none; font-size: .78rem; font-weight: 600; transition: all .2s; }
.consult-join:hover { filter: brightness(1.1); }

/* Empty state */
.empty { text-align: center; padding: 2rem; color: var(--muted,#8a7a6a); }
.empty-icon { font-size: 2.8rem; margin-bottom: .5rem; }

/* Upgrade banner */
.upgrade-banner {
    background: linear-gradient(135deg, <?= $cfg['color'] ?>22, <?= $cfg['color'] ?>11);
    border: 1.5px solid <?= $cfg['color'] ?>44;
    border-radius: 16px; padding: 1.5rem; text-align: center;
}
.upgrade-banner h3 { font-family: var(--font-display,'Cormorant Garamond',serif); font-size: 1.3rem; margin: 0 0 .5rem; }
.upgrade-banner p { font-size: .84rem; color: var(--muted,#8a7a6a); margin: 0 0 1rem; }
.upgrade-link { display: inline-block; padding: .65rem 1.5rem; background: <?= $cfg['color'] ?>; color: white; border-radius: 40px; text-decoration: none; font-size: .84rem; font-weight: 600; transition: all .25s; }
.upgrade-link:hover { filter: brightness(1.1); transform: translateY(-2px); }

/* Locked section */
.locked-section { opacity: .55; pointer-events: none; filter: blur(1px); user-select: none; }
</style>

<main class="vip-espace">
<div class="vip-container">

    <!-- ── Welcome Header ── -->
    <div class="vip-welcome">
        <div>
            <h1>Bienvenue, <?= htmlspecialchars(explode(' ', $user['nom'])[0]) ?> 👋</h1>
            <div class="vip-level-badge"><?= $cfg['emoji'] ?> <?= $cfg['label'] ?></div>
            <div class="vip-meta">
                <?= number_format($abonnement['prix_mensuel'], 3) ?> TND/mois &nbsp;·&nbsp;
                 Expire le <?= date('d/m/Y', strtotime($abonnement['date_fin'])) ?> &nbsp;·&nbsp;
                 <?= $abonnement['jours_restants'] ?? 0 ?> jours restants
            </div>
        </div>
        <div class="vip-actions">
            <a href="<?= BASE ?>/view/client/vip_chat.php" class="vip-btn vip-btn-white">
                Messagerie<?= $unread > 0 ? " ($unread)" : '' ?>
            </a>
            <?php if (in_array($niveau, ['premium','elite'])): ?>
                <a href="#consultations" class="vip-btn vip-btn-ghost"> Consultations</a>
            <?php endif; ?>
            <a href="<?= BASE ?>/view/client/vip.php" class="vip-btn vip-btn-ghost">⬆ Changer de plan</a>
            <a href="<?= BASE ?>/controller/vip_controller.php?action=annuler"
               onclick="return confirm('Annuler votre abonnement VIP ?')"
               class="vip-btn vip-btn-red">Annuler</a>
        </div>
    </div>

    <?php if ($niveau === 'basic'): ?>
    <!-- ════════════════════════════════════
         BASIC SPACE
    ════════════════════════════════════ -->
    <div class="vip-grid vip-grid-2">

        <!-- Chat with nutritionist (limited) -->
        <div class="vip-card">
            <div class="vip-card-head">Chat avec la nutritionniste
                <span style="font-size:.7rem;background:#dcfce7;color:#166534;padding:.2rem .6rem;border-radius:20px;margin-left:auto;">Limité</span>
            </div>
            <?php if ($nutri): ?>
                <div class="chat-box">
                    <div class="chat-msgs" id="miniMsgs">
                        <?php if (empty($msgs)): ?>
                            <div class="empty"></div>Commencez la conversation </div>
                        <?php else: ?>
                            <?php foreach (array_slice($msgs, -6) as $m): ?>
                                <div class="msg <?= $m['expediteur_id'] == $_SESSION['user_id'] ? 'msg-me' : 'msg-other' ?>">
                                    <?= nl2br(htmlspecialchars($m['contenu'])) ?>
                                    <div class="msg-time"><?= date('H:i', strtotime($m['created_at'])) ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <form action="<?= BASE ?>/controller/vip_controller.php?action=message" method="POST" class="chat-form">
                        <input type="hidden" name="destinataire_id" value="<?= $nutri['id'] ?>"/>
                        <input type="text" name="contenu" class="chat-input" placeholder="Votre message..." required autocomplete="off"/>
                        <button type="submit" class="chat-send">➤</button>
                    </form>
                </div>
                <div class="chat-full-link"><a href="<?= BASE ?>/view/client/vip_chat.php">Ouvrir la messagerie complète →</a></div>
            <?php else: ?>
                <div class="empty">Nutritionniste non disponible pour le moment.</div>
            <?php endif; ?>
        </div>

        <!-- Upgrade to Premium -->
        <div class="vip-card">
            <div class="vip-card-head"> Fonctionnalités Premium</div>
            <div class="upgrade-banner">
                <h3>Passez au Premium 🔵</h3>
                <p>Débloquez les consultations en ligne, les plans alimentaires personnalisés et le suivi de vos objectifs de santé.</p>
                <a href="<?= BASE ?>/view/client/vip.php?plan=premium" class="upgrade-link">Voir le plan Premium →</a>
            </div>
            <div style="margin-top:1.2rem;">
                <p style="font-size:.82rem;color:var(--muted);margin-bottom:.8rem;">Ce que vous débloquez :</p>
                <ul style="list-style:none;padding:0;font-size:.83rem;display:flex;flex-direction:column;gap:.5rem;">
                    <li style="color:#9ca3af;">🔒 Consultations en ligne (1–2/mois)</li>
                    <li style="color:#9ca3af;">🔒 Plans alimentaires personnalisés</li>
                    <li style="color:#9ca3af;">🔒 Suivi des objectifs de santé</li>
                    <li style="color:#9ca3af;">🔒 Réduction 10% sur les produits</li>
                </ul>
            </div>
        </div>

    </div>

    <!-- Exclusive content for Basic -->
    <div class="vip-card" style="margin-top:1.5rem;">
        <div class="vip-card-head"> Conseils exclusifs Basic</div>
        <p style="font-size:.85rem;color:var(--muted,#8a7a6a);margin-bottom:1rem;">Contenu santé réservé aux membres VIP Basic et plus.</p>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:1rem;">
            <div style="background:var(--cream,#fdf8f0);border-radius:12px;padding:1rem;border:1px solid var(--border,#e8dccb);">
                <div style="font-size:.85rem;font-weight:600;margin-bottom:.3rem;">Alimentation équilibrée</div>
                <div style="font-size:.78rem;color:var(--muted,#8a7a6a);">Conseils personnalisés selon vos préférences tunisiennes.</div>
            </div>
            <div style="background:var(--cream,#fdf8f0);border-radius:12px;padding:1rem;border:1px solid var(--border,#e8dccb);">
                <div style="font-size:.85rem;font-weight:600;margin-bottom:.3rem;">Hydratation optimale</div>
                <div style="font-size:.78rem;color:var(--muted,#8a7a6a);">Calculez votre besoin quotidien en eau selon votre activité.</div>
            </div>
            <div style="background:var(--cream,#fdf8f0);border-radius:12px;padding:1rem;border:1px solid var(--border,#e8dccb);">
                <div style="font-size:.85rem;font-weight:600;margin-bottom:.3rem;">Sommeil & nutrition</div>
                <div style="font-size:.78rem;color:var(--muted,#8a7a6a);">Les aliments qui améliorent la qualité de votre sommeil.</div>
            </div>
        </div>
    </div>

    <?php elseif ($niveau === 'premium'): ?>
    <!-- ════════════════════════════════════
         PREMIUM SPACE
    ════════════════════════════════════ -->
    <div class="vip-grid vip-grid-2">

        <!-- Chat -->
        <div class="vip-card">
            <div class="vip-card-head"> Chat nutritionniste
                <span style="font-size:.7rem;background:#dbeafe;color:#1e40af;padding:.2rem .6rem;border-radius:20px;margin-left:auto;">Illimité</span>
            </div>
            <?php if ($nutri): ?>
                <div class="chat-box">
                    <div class="chat-msgs" id="miniMsgs">
                        <?php if (empty($msgs)): ?>
                            <div class="empty">Commencez la conversation </div>
                        <?php else: ?>
                            <?php foreach (array_slice($msgs, -8) as $m): ?>
                                <div class="msg <?= $m['expediteur_id'] == $_SESSION['user_id'] ? 'msg-me' : 'msg-other' ?>">
                                    <?= nl2br(htmlspecialchars($m['contenu'])) ?>
                                    <div class="msg-time"><?= date('H:i', strtotime($m['created_at'])) ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <form action="<?= BASE ?>/controller/vip_controller.php?action=message" method="POST" class="chat-form">
                        <input type="hidden" name="destinataire_id" value="<?= $nutri['id'] ?>"/>
                        <input type="text" name="contenu" class="chat-input" placeholder="Votre message..." required autocomplete="off"/>
                        <button type="submit" class="chat-send">➤</button>
                    </form>
                </div>
                <div class="chat-full-link"><a href="<?= BASE ?>/view/client/vip_chat.php">Ouvrir la messagerie complète →</a></div>
            <?php else: ?>
                <div class="empty">Nutritionniste non disponible.</div>
            <?php endif; ?>
        </div>

        <!-- Objectives -->
        <div class="vip-card">
            <div class="vip-card-head"> Mes objectifs de santé</div>
            <?php if (empty($objectifs)): ?>
                <div class="empty">
                    Votre nutritionniste définira vos objectifs lors de votre première consultation.
                </div>
            <?php else: ?>
                <?php foreach ($objectifs as $obj):
                    $pct = $obj['valeur_cible'] > 0 ? min(100, round(($obj['valeur_actuelle'] / $obj['valeur_cible']) * 100)) : 0;
                ?>
                    <div class="obj-item">
                        <div class="obj-header">
                            <span><?= htmlspecialchars($obj['titre']) ?></span>
                            <span><?= $obj['valeur_actuelle'] ?> / <?= $obj['valeur_cible'] ?> <?= htmlspecialchars($obj['unite']) ?></span>
                        </div>
                        <div class="progress-bar"><div class="progress-fill" style="width:<?= $pct ?>%"></div></div>
                        <?php if (!empty($obj['deadline'])): ?>
                            <div class="obj-date">📅 <?= date('d/m/Y', strtotime($obj['deadline'])) ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>

    <!-- Consultations -->
    <div class="vip-card" style="margin-top:1.5rem;" id="consultations">
        <div class="vip-card-head"> Mes consultations (1–2/mois)</div>
        <?php if (empty($consultations)): ?>
            <div class="empty">
                Aucune consultation planifiée. Contactez votre nutritionniste via la messagerie.
            </div>
        <?php else: ?>
            <div class="consult-grid">
                <?php foreach ($consultations as $c): ?>
                    <div class="consult-item">
                        <div class="consult-top">
                            <div class="consult-title"><?= htmlspecialchars($c['titre']) ?></div>
                            <span class="consult-status st-<?= $c['statut'] ?>">
                                <?= ['planifiee'=>'Planifiée','confirmee'=>'Confirmée','terminee'=>'Terminée','annulee'=>'Annulée'][$c['statut']] ?? ucfirst($c['statut']) ?>
                            </span>
                        </div>
                        <div class="consult-meta">
                            <span>📅 <?= date('d/m/Y', strtotime($c['date_heure'])) ?></span>
                            <span>⏰ <?= date('H:i', strtotime($c['date_heure'])) ?></span>
                            <span>⏱ <?= $c['duree_min'] ?>min</span>
                            <span><?= $c['type'] === 'visio' ? '📹 Visio' : '💬 Chat' ?></span>
                        </div>
                        <div class="consult-nutri">👩‍⚕️ <?= htmlspecialchars($c['nutri_nom']) ?></div>
                        <?php if (in_array($c['statut'], ['planifiee','confirmee']) && !empty($c['lien_visio'])): ?>
                            <a href="<?= htmlspecialchars($c['lien_visio']) ?>" target="_blank" class="consult-join">📹 Rejoindre</a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Upgrade to Elite -->
    <div class="vip-card" style="margin-top:1.5rem;">
        <div class="vip-card-head">🟣 Fonctionnalités Elite</div>
        <div class="upgrade-banner">
            <h3>Passez à l'Elite 🟣</h3>
            <p>Consultations illimitées, suivi 24/7, réajustement automatique de votre plan et réduction 15% + cadeaux.</p>
            <a href="<?= BASE ?>/view/client/vip.php?plan=elite" class="upgrade-link">Voir le plan Elite →</a>
        </div>
    </div>

    <?php elseif ($niveau === 'elite'): ?>
    <!-- ════════════════════════════════════
         ELITE SPACE
    ════════════════════════════════════ -->
    <div class="vip-grid vip-grid-2">

        <!-- Chat (unlimited) -->
        <div class="vip-card">
            <div class="vip-card-head">Chat prioritaire
                <span style="font-size:.7rem;background:#ede9fe;color:#5b21b6;padding:.2rem .6rem;border-radius:20px;margin-left:auto;">✦ Elite</span>
            </div>
            <?php if ($nutri): ?>
                <div class="chat-box">
                    <div class="chat-msgs" id="miniMsgs">
                        <?php if (empty($msgs)): ?>
                            <div class="empty">Commencez la conversation </div>
                        <?php else: ?>
                            <?php foreach (array_slice($msgs, -10) as $m): ?>
                                <div class="msg <?= $m['expediteur_id'] == $_SESSION['user_id'] ? 'msg-me' : 'msg-other' ?>">
                                    <?= nl2br(htmlspecialchars($m['contenu'])) ?>
                                    <div class="msg-time"><?= date('H:i', strtotime($m['created_at'])) ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <form action="<?= BASE ?>/controller/vip_controller.php?action=message" method="POST" class="chat-form">
                        <input type="hidden" name="destinataire_id" value="<?= $nutri['id'] ?>"/>
                        <input type="text" name="contenu" class="chat-input" placeholder="Votre message prioritaire..." required autocomplete="off"/>
                        <button type="submit" class="chat-send">➤</button>
                    </form>
                </div>
                <div class="chat-full-link"><a href="<?= BASE ?>/view/client/vip_chat.php">Ouvrir la messagerie complète →</a></div>
            <?php else: ?>
                <div class="empty">Nutritionniste non disponible.</div>
            <?php endif; ?>
        </div>

        <!-- Objectives -->
        <div class="vip-card">
            <div class="vip-card-head"> Objectifs & Suivi personnalisé</div>
            <?php if (empty($objectifs)): ?>
                <div class="empty">
                    Votre plan personnalisé sera mis en place lors de votre première séance Elite.
                </div>
            <?php else: ?>
                <?php foreach ($objectifs as $obj):
                    $pct = $obj['valeur_cible'] > 0 ? min(100, round(($obj['valeur_actuelle'] / $obj['valeur_cible']) * 100)) : 0;
                ?>
                    <div class="obj-item">
                        <div class="obj-header">
                            <span><?= htmlspecialchars($obj['titre']) ?></span>
                            <span><?= $obj['valeur_actuelle'] ?> / <?= $obj['valeur_cible'] ?> <?= htmlspecialchars($obj['unite']) ?></span>
                        </div>
                        <div class="progress-bar"><div class="progress-fill" style="width:<?= $pct ?>%"></div></div>
                        <?php if (!empty($obj['deadline'])): ?>
                            <div class="obj-date">📅 <?= date('d/m/Y', strtotime($obj['deadline'])) ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>

    <!-- Consultations (unlimited) -->
    <div class="vip-card" style="margin-top:1.5rem;" id="consultations">
        <div class="vip-card-head"> Consultations illimitées
            <span style="font-size:.7rem;background:#ede9fe;color:#5b21b6;padding:.2rem .6rem;border-radius:20px;margin-left:auto;">✦ Elite</span>
        </div>
        <?php if (empty($consultations)): ?>
            <div class="empty">
                Aucune consultation planifiée. Contactez votre nutritionniste — accès prioritaire 24/7.
            </div>
        <?php else: ?>
            <div class="consult-grid">
                <?php foreach ($consultations as $c): ?>
                    <div class="consult-item">
                        <div class="consult-top">
                            <div class="consult-title"><?= htmlspecialchars($c['titre']) ?></div>
                            <span class="consult-status st-<?= $c['statut'] ?>">
                                <?= ['planifiee'=>'Planifiée','confirmee'=>'Confirmée','terminee'=>'Terminée','annulee'=>'Annulée'][$c['statut']] ?? ucfirst($c['statut']) ?>
                            </span>
                        </div>
                        <div class="consult-meta">
                            <span>📅 <?= date('d/m/Y', strtotime($c['date_heure'])) ?></span>
                            <span>⏰ <?= date('H:i', strtotime($c['date_heure'])) ?></span>
                            <span>⏱ <?= $c['duree_min'] ?>min</span>
                            <span><?= $c['type'] === 'visio' ? '📹 Visio' : '💬 Chat' ?></span>
                        </div>
                        <div class="consult-nutri">👩‍⚕️ <?= htmlspecialchars($c['nutri_nom']) ?></div>
                        <?php if (in_array($c['statut'], ['planifiee','confirmee']) && !empty($c['lien_visio'])): ?>
                            <a href="<?= htmlspecialchars($c['lien_visio']) ?>" target="_blank" class="consult-join">📹 Rejoindre</a>
                        <?php endif; ?>
                        <?php if (!empty($c['notes_apres'])): ?>
                            <div style="font-size:.75rem;margin-top:.6rem;color:var(--muted);border-top:1px solid var(--border);padding-top:.5rem;">
                                📝 <?= htmlspecialchars(mb_substr($c['notes_apres'], 0, 120)) ?>…
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Elite exclusive perks -->
    <div class="vip-grid vip-grid-2" style="margin-top:1.5rem;">
        <div class="vip-card">
            <div class="vip-card-head"> Avantages exclusifs Elite</div>
            <ul style="list-style:none;padding:0;font-size:.85rem;display:flex;flex-direction:column;gap:.7rem;">
                <li>✦ Réduction <strong>15%</strong> sur tous les produits Benna</li>
                <li>✦ Coffret cadeau mensuel curé</li>
                <li>✦ Accès prioritaire aux nouveaux produits</li>
                <li>✦ Plan alimentaire réajusté automatiquement</li>
                <li>✦ Réponse nutritionniste sous <strong>2h</strong></li>
            </ul>
        </div>
        <div class="vip-card">
            <div class="vip-card-head"> Plan alimentaire personnalisé</div>
            <div class="empty">
                <div class="empty-icon">📋</div>
                <div style="font-size:.84rem;">Votre plan personnalisé est défini par Dr. <?= $nutri ? htmlspecialchars(explode(' ', $nutri['nom'])[0]) : 'Nutritionniste' ?> et mis à jour automatiquement.</div>
                <a href="<?= BASE ?>/view/client/vip_chat.php" style="display:inline-block;margin-top:1rem;padding:.55rem 1.2rem;background:<?= $cfg['color'] ?>;color:white;border-radius:40px;text-decoration:none;font-size:.82rem;font-weight:600;">Demander mon plan →</a>
            </div>
        </div>
    </div>

    <?php endif; ?>

</div>
</main>

<script>
// Auto-scroll chat to bottom
const miniMsgs = document.getElementById('miniMsgs');
if (miniMsgs) miniMsgs.scrollTop = miniMsgs.scrollHeight;
</script>

<?php include "partials/footer.php"; ?>