<?php
$pageTitle = "Réclamations";
include "partials/admin_header.php";
$reclamations = getAllReclamations($cnx);
$badgeMap = ['ouverte'=>'badge-yellow','en_cours'=>'badge-blue','resolue'=>'badge-green','rejetee'=>'badge-red'];
?>
<div class="topbar">
  <h1>🚨 Réclamations</h1>
  <span style="color:var(--muted);"><?= count(array_filter($reclamations,fn($r)=>$r['statut']==='ouverte')) ?> ouverte(s)</span>
</div>
<div class="card">
  <table>
    <thead><tr><th>#</th><th>Client</th><th>Sujet</th><th>Commande</th><th>Statut</th><th>Date</th><th>Action</th></tr></thead>
    <tbody>
    <?php foreach ($reclamations as $r): ?>
    <tr>
      <td><?= $r['id'] ?></td>
      <td><strong><?= htmlspecialchars($r['client_nom']) ?></strong></td>
      <td>
        <div style="font-weight:600;"><?= htmlspecialchars($r['sujet']) ?></div>
        <div style="font-size:.78rem;color:var(--muted);max-width:200px;"><?= htmlspecialchars(mb_substr($r['message'],0,80)) ?>...</div>
      </td>
      <td><?= $r['commande_id']?'#'.$r['commande_id']:'–' ?></td>
      <td><span class="badge <?= $badgeMap[$r['statut']]??'badge-gray' ?>"><?= ucfirst(str_replace('_',' ',$r['statut'])) ?></span></td>
      <td style="font-size:.82rem;"><?= date('d/m/Y',strtotime($r['created_at'])) ?></td>
      <td>
        <button class="btn btn-green" onclick="openReply(<?= htmlspecialchars(json_encode($r)) ?>)" style="margin-bottom:.3rem;">💬 Répondre</button><br/>
        <?php if (!$r['transmis_usine']): ?>
          <a href="<?= BASE ?>/controller/reclamation_controller.php?action=transmettre_usine&id=<?= $r['id'] ?>"
             onclick="return confirm('Transmettre cette réclamation à l\'usine ?')"
             class="btn btn-yellow" style="font-size:.75rem;padding:.3rem .7rem;">🏭 Transmettre usine</a>
        <?php else: ?>
          <span class="badge badge-purple" style="font-size:.72rem;">🏭 Transmise usine</span>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php if (empty($reclamations)): ?><p style="text-align:center;color:var(--muted);padding:1.5rem;">Aucune réclamation.</p><?php endif; ?>
</div>

<div id="replyModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;padding:1rem;">
  <div style="background:white;max-width:560px;width:100%;border-radius:16px;padding:2rem;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
      <h2 style="font-family:'Playfair Display',serif;">Répondre</h2>
      <button onclick="document.getElementById('replyModal').style.display='none'" style="border:none;background:none;font-size:1.5rem;cursor:pointer;">×</button>
    </div>
    <div id="replyContext" style="background:#f9fafb;border-radius:10px;padding:1rem;margin-bottom:1.2rem;font-size:.9rem;line-height:1.6;"></div>
    <form action="<?= BASE ?>/controller/reclamation_controller.php?action=repondre" method="POST">
      <input type="hidden" name="id" id="reply_id"/>
      <div class="form-group"><label>Nouveau statut</label>
        <select class="form-control" name="statut">
          <option value="en_cours">En cours</option>
          <option value="resolue">Résolue</option>
          <option value="rejetee">Rejetée</option>
        </select>
      </div>
      <div class="form-group"><label>Réponse au client</label>
        <textarea class="form-control" name="reponse" rows="5" placeholder="Votre réponse..." required style="resize:vertical;"></textarea>
      </div>
      <div style="display:flex;gap:.8rem;">
        <button type="submit" class="btn btn-green" style="flex:1;padding:.8rem;">Envoyer</button>
        <button type="button" onclick="document.getElementById('replyModal').style.display='none'" class="btn btn-gray">Annuler</button>
      </div>
    </form>
  </div>
</div>
<script>
function openReply(r){
  document.getElementById('reply_id').value = r.id;
  document.getElementById('replyContext').innerHTML =
    '<strong>Client :</strong> '+r.client_nom+
    '<br><strong>Sujet :</strong> '+r.sujet+
    '<br><strong>Message :</strong> '+r.message+
    (r.reponse?'<br><br><strong>Réponse précédente :</strong> '+r.reponse:'');
  document.getElementById('replyModal').style.display='flex';
}
</script>
<?php include "partials/admin_footer.php"; ?>
