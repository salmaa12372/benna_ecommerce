<?php
// view/client/vip_chat.php
session_start();

// Include database connection FIRST
include_once __DIR__ . "/../../config/database.php";
include_once __DIR__ . "/../../config/app.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) { 
    header("Location: ".BASE."/view/client/signup.php"); 
    exit(); 
}

// If user is nutritionist, redirect to their dashboard
if (isset($_SESSION['role']) && $_SESSION['role'] === 'nutritionniste') {
    header("Location: ".BASE."/view/nutritionniste/dashboard.php");
    exit();
}

// Check if user has VIP access
if (!isVip($cnx, $_SESSION['user_id'])) { 
    header("Location: ".BASE."/view/client/vip.php"); 
    exit(); 
}

$pageTitle = "Messagerie VIP";
include "partials/header.php";

// Fetch nutritionist - handle case when none exists
$nutri = $cnx->query("SELECT * FROM users WHERE role='nutritionniste' LIMIT 1")->fetch();
if (!$nutri) {
    echo '<div class="alert alert-warning">Aucun nutritionniste n\'est disponible pour le moment. Veuillez réessayer plus tard.</div>';
    include "partials/footer.php";
    exit();
}

$withId = isset($_GET['with']) ? (int)$_GET['with'] : ($nutri['id'] ?? 0);

// Get conversation
$msgs = $withId ? getConversation($cnx, $_SESSION['user_id'], $withId) : [];

// Mark messages as read
if ($withId) {
    markMessagesLu($cnx, $withId, $_SESSION['user_id']);
}

// Get subscription info
$abonnement = getAbonnementUser($cnx, $_SESSION['user_id']);
$niveauInfo = ['basic'=>['emoji'=>'🟢','label'=>'VIP Basic'],'premium'=>['emoji'=>'🔵','label'=>'VIP Premium'],'elite'=>['emoji'=>'🟣','label'=>'VIP Elite']][$abonnement['niveau']] ?? ['emoji'=>'⭐','label'=>'VIP'];
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
  color: #fff !important;
}
.chat-page{padding-top:90px;height:100vh;display:flex;flex-direction:column;background:var(--cream);}
.chat-topbar{background:white;padding:1rem 2rem;display:flex;align-items:center;gap:1rem;border-bottom:2px solid var(--border);box-shadow:0 2px 8px rgba(0,0,0,.05);}
.chat-topbar img{width:44px;height:44px;border-radius:50%;object-fit:cover;background:#d1fae5;display:flex;align-items:center;justify-content:center;}
.chat-nutri-avatar{width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,var(--green-dark),var(--green));color:white;display:flex;align-items:center;justify-content:center;font-size:1.2rem;flex-shrink:0;}
.chat-nutri-info h4{font-family:var(--font-display);margin:0 0 .1rem;font-size:1.05rem;}
.chat-nutri-info p{font-size:.78rem;color:var(--muted);margin:0;}
.chat-online{display:inline-flex;align-items:center;gap:.3rem;font-size:.75rem;color:#22c55e;}
.chat-online::before{content:'';width:8px;height:8px;background:#22c55e;border-radius:50%;display:inline-block;animation:pulse-dot 2s infinite;}
@keyframes pulse-dot{0%,100%{opacity:1}50%{opacity:.4}}
.chat-area{flex:1;overflow-y:auto;padding:1.5rem 2rem;display:flex;flex-direction:column;gap:.8rem;scrollbar-width:thin;}
.chat-msg-wrap{display:flex;gap:.6rem;align-items:flex-start;}
.chat-msg-wrap.me{flex-direction:row-reverse;}
.chat-avatar{width:32px;height:32px;border-radius:50%;background:var(--cream);border:2px solid var(--border);display:flex;align-items:center;justify-content:center;font-size:.9rem;flex-shrink:0;}
.chat-bubble{max-width:72%;padding:.7rem 1rem;border-radius:16px;font-size:.92rem;line-height:1.55;word-wrap:break-word;}
.chat-msg-wrap.me .chat-bubble{background:linear-gradient(135deg,var(--green-dark),var(--green));color:white;border-radius:16px 4px 16px 16px;}
.chat-msg-wrap:not(.me) .chat-bubble{background:white;border:1px solid var(--border);border-radius:4px 16px 16px 16px;color:var(--fg);}
.chat-time{font-size:.65rem;color:var(--muted);margin-top:.2rem;text-align:right;}
.chat-date-sep{text-align:center;font-size:.75rem;color:var(--muted);margin:.5rem 0;font-family:sans-serif;}
.chat-input-bar{background:white;padding:1rem 2rem;border-top:2px solid var(--border);display:flex;gap:.8rem;align-items:center;}
.chat-input{flex:1;border:2px solid var(--border);border-radius:24px;padding:.65rem 1.2rem;font-family:var(--font-body);font-size:.92rem;outline:none;background:var(--cream);transition:.2s;color:var(--fg);}
.chat-input:focus{border-color:var(--green);background:white;}
.chat-send{width:44px;height:44px;border:none;border-radius:50%;background:linear-gradient(135deg,var(--green),var(--green-dark));color:white;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:1.1rem;transition:.2s;flex-shrink:0;}
.chat-send:hover{transform:scale(1.1);}
.back-link{color:var(--muted);text-decoration:none;font-size:.88rem;display:flex;align-items:center;gap:.3rem;}
.back-link:hover{color:var(--green);}
.vip-lvl-badge{padding:.2rem .7rem;border-radius:20px;font-size:.72rem;font-weight:700;background:var(--cream);border:1px solid var(--border);}
.empty-chat{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;color:var(--muted);text-align:center;gap:.8rem;}
.empty-chat .icon{font-size:3.5rem;}
.alert-warning{padding:1rem;background:#fef3c7;color:#92400e;border-radius:8px;margin:1rem;}
</style>

<div class="chat-page">
  <!-- TOP BAR -->
  <div class="chat-topbar">
    <a href="<?= BASE ?>/view/client/vip_espace.php" class="back-link">← Retour</a>
    <div class="chat-nutri-avatar">🥗</div>
    <div class="chat-nutri-info" style="flex:1;">
      <h4><?= htmlspecialchars($nutri['nom'] ?? 'Nutritionniste') ?></h4>
      <p>Nutritionniste · <span class="chat-online">En ligne</span></p>
    </div>
    <span class="vip-lvl-badge"><?= $niveauInfo['emoji'] ?> <?= $niveauInfo['label'] ?></span>
  </div>

  <!-- MESSAGES -->
  <div class="chat-area" id="chatArea">
    <?php if (empty($msgs)): ?>
      <div class="empty-chat">
        <div class="icon">💬</div>
        <h3 style="font-family:var(--font-display);">Démarrez la conversation</h3>
        <p>Posez vos questions nutritionnelles, partagez vos objectifs.<br/>Votre nutritionniste répond sous 2-4h.</p>
      </div>
    <?php else: ?>
      <?php 
      $lastDate = ''; 
      foreach ($msgs as $m):
        $date = date('d/m/Y', strtotime($m['created_at']));
        if ($date !== $lastDate):
          $lastDate = $date;
      ?>
          <div class="chat-date-sep">— <?= $date === date('d/m/Y') ? 'Aujourd\'hui' : $date ?> —</div>
      <?php 
        endif;
        $isMe = $m['expediteur_id'] == $_SESSION['user_id'];
      ?>
      <div class="chat-msg-wrap <?= $isMe ? 'me' : '' ?>">
        <div class="chat-avatar"><?= $isMe ? '👤' : '🥗' ?></div>
        <div>
          <div class="chat-bubble"><?= nl2br(htmlspecialchars($m['contenu'])) ?></div>
          <div class="chat-time"><?= date('H:i', strtotime($m['created_at'])) ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- INPUT -->
  <div class="chat-input-bar">
    <form action="<?= BASE ?>/controller/vip_controller.php?action=message" method="POST" style="display:flex;gap:.8rem;flex:1;align-items:center;">
      <input type="hidden" name="destinataire_id" value="<?= $nutri['id'] ?? 0 ?>"/>
      <input type="text" class="chat-input" name="contenu" id="msgInput"
             placeholder="Écrivez votre message..." required autocomplete="off"/>
      <button type="submit" class="chat-send" aria-label="Envoyer">➤</button>
    </form>
  </div>
</div>

<script>
// Auto-scroll to bottom
const chatArea = document.getElementById('chatArea');
if (chatArea) {
    chatArea.scrollTop = chatArea.scrollHeight;
}

// Focus input
const msgInput = document.getElementById('msgInput');
if (msgInput) {
    msgInput.focus();
}
</script>

<?php include "partials/footer.php"; ?>