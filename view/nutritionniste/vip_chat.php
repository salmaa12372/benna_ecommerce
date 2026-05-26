<?php
$pageTitle = "Chat VIP";
include "partials/nutri_header.php";
// nutri_header.php inclut déjà traitement.php donc isVip() est disponible

$nutri_id = $_SESSION['user_id'];

// Récupérer tous les clients VIP qui ont écrit au nutri
$req = $cnx->prepare("
    SELECT DISTINCT u.id, u.nom, u.email,
           (SELECT COUNT(*) FROM vip_messages 
            WHERE expediteur_id=u.id AND destinataire_id=:nid AND lu=0) AS non_lus
    FROM vip_messages m
    JOIN users u ON (
        CASE WHEN m.expediteur_id=:nid2 THEN m.destinataire_id ELSE m.expediteur_id END = u.id
    )
    WHERE m.expediteur_id=:nid3 OR m.destinataire_id=:nid4
    ORDER BY non_lus DESC, u.nom ASC
");
$req->execute([':nid'=>$nutri_id,':nid2'=>$nutri_id,':nid3'=>$nutri_id,':nid4'=>$nutri_id]);
$clients = $req->fetchAll();

$selected_id = (int)($_GET['client_id'] ?? 0);
$messages = [];
if ($selected_id) {
    $messages = getConversation($cnx, $nutri_id, $selected_id);
    markMessagesLu($cnx, $selected_id, $nutri_id);
    $req2 = $cnx->prepare("SELECT nom, email FROM users WHERE id=:id");
    $req2->execute([':id'=>$selected_id]);
    $selected_client = $req2->fetch();
}

// Envoyer message
if ($_SERVER['REQUEST_METHOD']==='POST' && !empty($_POST['contenu']) && $selected_id) {
    sendVipMessage($cnx, $nutri_id, $selected_id, $_POST['contenu']);
    header("Location: ".BASE."/view/nutritionniste/vip_chat.php?client_id=$selected_id"); exit();
}
?>

<div class="topbar">
  <h1>💬 Chat VIP Clients</h1>
</div>

<div style="display:grid;grid-template-columns:280px 1fr;gap:1.5rem;height:75vh;">

  <!-- Liste clients -->
  <div class="card" style="overflow-y:auto;padding:1rem;">
    <div style="font-weight:700;margin-bottom:1rem;color:var(--green);">Clients</div>
    <?php if (empty($clients)): ?>
      <p style="color:var(--muted);font-size:.85rem;">Aucune conversation.</p>
    <?php endif; ?>
    <?php foreach ($clients as $c): ?>
    <a href="?client_id=<?= $c['id'] ?>" style="display:block;padding:.75rem;border-radius:12px;text-decoration:none;color:var(--fg);margin-bottom:.4rem;background:<?= $selected_id==$c['id']?'var(--green-light)':'transparent' ?>;border:1.5px solid <?= $selected_id==$c['id']?'var(--green)':'transparent' ?>;">
      <div style="font-weight:600;"><?= htmlspecialchars($c['nom']) ?></div>
      <div style="font-size:.78rem;color:var(--muted);"><?= htmlspecialchars($c['email']) ?></div>
      <?php if ($c['non_lus']>0): ?>
        <span style="background:#e74c3c;color:#fff;border-radius:20px;padding:.1rem .5rem;font-size:.72rem;font-weight:700;"><?= $c['non_lus'] ?> nouveau<?= $c['non_lus']>1?'x':'' ?></span>
      <?php endif; ?>
    </a>
    <?php endforeach; ?>
  </div>

  <!-- Zone chat -->
  <div class="card" style="display:flex;flex-direction:column;padding:1rem;">
    <?php if (!$selected_id): ?>
      <div style="margin:auto;text-align:center;color:var(--muted);">
        <div style="font-size:3rem;">💬</div>
        <p>Sélectionnez un client pour voir la conversation.</p>
      </div>
    <?php else: ?>
      <div style="font-weight:700;margin-bottom:1rem;padding-bottom:.75rem;border-bottom:1px solid var(--border);">
        <?= htmlspecialchars($selected_client['nom'] ?? '') ?>
      </div>

      <!-- Messages -->
      <div id="msgs" style="flex:1;overflow-y:auto;display:flex;flex-direction:column;gap:.6rem;margin-bottom:1rem;">
        <?php foreach ($messages as $m): ?>
        <?php $moi = $m['expediteur_id'] == $nutri_id; ?>
        <div style="display:flex;justify-content:<?= $moi?'flex-end':'flex-start' ?>;">
          <div style="max-width:70%;background:<?= $moi?'var(--green)':'#f1f5f2' ?>;color:<?= $moi?'#fff':'var(--fg)' ?>;padding:.65rem 1rem;border-radius:<?= $moi?'18px 18px 4px 18px':'18px 18px 18px 4px' ?>;font-size:.9rem;">
            <?= htmlspecialchars($m['contenu']) ?>
            <div style="font-size:.68rem;opacity:.65;margin-top:.25rem;text-align:right;">
              <?= date('H:i', strtotime($m['created_at'])) ?>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($messages)): ?>
          <p style="text-align:center;color:var(--muted);margin:auto;">Aucun message. Commencez la conversation !</p>
        <?php endif; ?>
      </div>

      <!-- Input -->
      <form method="POST" style="display:flex;gap:.75rem;">
        <input type="text" name="contenu" placeholder="Écrire un message…" required
               style="flex:1;border-radius:12px;border:1.5px solid var(--border);padding:.65rem 1rem;font-size:.9rem;font-family:inherit;">
        <button type="submit" class="btn btn-green">Envoyer</button>
      </form>
    <?php endif; ?>
  </div>

</div>

<script>
// Auto-scroll vers le bas
const msgs = document.getElementById('msgs');
if (msgs) msgs.scrollTop = msgs.scrollHeight;
</script>

<?php include "partials/nutri_footer.php"; ?>