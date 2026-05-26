<?php
// view/nutritionniste/consultations.php
date_default_timezone_set('Africa/Tunis'); // ← ajoute cette ligne
$pageTitle = "Consultations VIP";
include "partials/nutri_header.php";


$consultations = getConsultationsNutri($cnx, $_SESSION['user_id']);
$vipClients    = getAllVipClients($cnx);
$clientFocus   = isset($_GET['client']) ? (int)$_GET['client'] : null;
$niveauColors  = ['basic'=>'#22c55e','premium'=>'#3b82f6','elite'=>'#8b5cf6'];
$niveauEmoji   = ['basic'=>'🟢','premium'=>'🔵','elite'=>'🟣'];
?>
<div class="topbar">
  <h1>Consultations VIP</h1>
  <button class="btn btn-green" onclick="document.getElementById('planModal').style.display='flex'">+ Planifier consultation</button>
</div>

<!-- CLIENTS VIP -->
<div class="card">
  <div class="card-title"> Clients VIP actifs (<?= count($vipClients) ?>)</div>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:1rem;">
    <?php foreach ($vipClients as $cl): ?>
    <div style="background:#f9fafb;border-radius:14px;padding:1.2rem;border:2px solid <?= $niveauColors[$cl['niveau']] ?? '#e5e7eb' ?>20;position:relative;">
      <div style="position:absolute;top:10px;right:10px;font-size:1.2rem;"><?= $niveauEmoji[$cl['niveau']] ?? '⭐' ?></div>
      <div style="font-weight:700;font-family:'Playfair Display',serif;"><?= htmlspecialchars($cl['nom']) ?></div>
      <div style="font-size:.78rem;color:var(--muted);"><?= htmlspecialchars($cl['email']) ?></div>
      <div style="margin-top:.5rem;display:flex;flex-wrap:wrap;gap:.3rem;">
        <span class="badge" style="background:<?= $niveauColors[$cl['niveau']] ?? '#e5e7eb' ?>20;color:<?= $niveauColors[$cl['niveau']] ?? '#374151' ?>;font-size:.7rem;"><?= strtoupper($cl['niveau']) ?></span>
        <span class="badge badge-gray" style="font-size:.7rem;"><?= $cl['jours_restants'] ?>j restants</span>
      </div>
      <div style="margin-top:.8rem;display:flex;gap:.4rem;">
        <a href="<?= BASE ?>/view/nutritionniste/vip_chat.php?with=<?= $cl['id'] ?>" class="btn btn-green" style="flex:1;text-align:center;font-size:.78rem;padding:.35rem .5rem;">Chat</a>
        <button class="btn btn-gray" title="Planifier une consultation" onclick="openPlanForClient(<?= $cl['id'] ?>, '<?= addslashes(htmlspecialchars($cl['nom'])) ?>')" style="font-size:.78rem;padding:.35rem .7rem;">📅</button>
        <a href="<?= BASE ?>/view/nutritionniste/plans.php?client_id=<?= $cl['id'] ?>" title="Créer un plan alimentaire" class="btn btn-gray" style="font-size:.78rem;padding:.35rem .7rem;">📋</a>
      </div>
    </div>
    <?php endforeach; ?>
    <?php if (empty($vipClients)): ?>
      <div style="text-align:center;padding:2rem;color:var(--muted);grid-column:1/-1;">Aucun client VIP actif pour l'instant.</div>
    <?php endif; ?>
  </div>
</div>

<!-- CONSULTATIONS -->
<div class="card">
  <div class="card-title"> Planning des consultations</div>
  <table>
    <thead><tr><th>Client</th><th>Titre</th><th>Date & heure</th><th>Type</th><th>Durée</th><th>Statut</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($consultations as $c):
      $statClass = ['planifiee'=>'badge-blue','en_cours'=>'badge-green','terminee'=>'badge-gray','annulee'=>'badge-red'][$c['statut']] ?? 'badge-gray';
      
      // Vérifier si on est dans la fenêtre ±30 min
      $now        = new DateTime();
      $rdv        = new DateTime($c['date_heure']);
      $diffMin    = ($now->getTimestamp() - $rdv->getTimestamp()) / 60;
      $canStart   = $diffMin >= -30 && $diffMin <= 30;
    ?>
    <tr>
      <td>
        <div style="font-weight:600;"><?= htmlspecialchars($c['client_nom']) ?></div>
        <div style="font-size:.75rem;color:var(--muted);"><?= htmlspecialchars($c['client_email']) ?></div>
      </td>
      <td><?= htmlspecialchars($c['titre']) ?></td>
      <td style="font-size:.85rem;white-space:nowrap;">
          <?= date('d/m/Y', strtotime($c['date_heure'])) ?><br/>
          <?= date('H:i', strtotime($c['date_heure'])) ?>
      </td>
      <td><?= $c['type']==='visio'?' Visio':' Chat' ?></td>
      <td><?= $c['duree_min'] ?> min</td>
      <td><span class="badge <?= $statClass ?>"><?= ucfirst($c['statut']) ?></span></td>
      <td style="white-space:nowrap;">

        <?php if ($c['statut'] === 'planifiee'): ?>

          <?php if ($canStart): ?>
            <!-- Bouton Démarrer → passe en en_cours -->
            <?php if ($canStart): ?>
  <form action="<?= BASE ?>/controller/vip_controller.php?action=update_consult" method="POST" style="display:inline;">
    <input type="hidden" name="id" value="<?= $c['id'] ?>"/>
    <input type="hidden" name="statut" value="en_cours"/>
    <button type="submit" class="btn btn-green" style="font-size:.78rem;padding:.3rem .7rem;"
      onclick="window.open('<?= htmlspecialchars($c['lien_visio'] ?? '#') ?>', '_blank')">
      ▶ Démarrer
    </button>
  </form>
            <?php endif; ?>
          <?php else: ?>
            <!-- Trop tôt ou trop tard -->
        <?php
          $secondes = $rdv->getTimestamp() - $now->getTimestamp();
          if ($secondes > 0) {
            $jours  = floor($secondes / 86400);
            $heures = floor(($secondes % 86400) / 3600);
            $mins   = floor(($secondes % 3600) / 60);

           if ($jours > 0)       $restantStr = "⏳ Dans {$jours}j {$heures}h";
           elseif ($heures > 0)  $restantStr = "⏳ Dans {$heures}h {$mins}min";
           else                  $restantStr = "⏳ Dans {$mins}min";
            } else {
             $restantStr = '⌛ Expiré';
              }
          ?>
<span class="badge badge-gray" title="Disponible 30 min avant/après le rendez-vous">
  <?= $restantStr ?>
</span>
          <?php endif; ?>

          <!-- Modifier -->
          <button class="btn btn-yellow" onclick="openEdit(<?= htmlspecialchars(json_encode($c)) ?>)" style="font-size:.78rem;padding:.3rem .7rem;">✏️</button>

          <!-- Annuler -->
          <form action="<?= BASE ?>/controller/vip_controller.php?action=update_consult" method="POST" style="display:inline;" onsubmit="return confirm('Annuler cette consultation ?')">
            <input type="hidden" name="id" value="<?= $c['id'] ?>"/>
            <input type="hidden" name="statut" value="annulee"/>
            <button type="submit" class="btn btn-red" style="font-size:.78rem;padding:.3rem .7rem;">✕</button>
          </form>

        <?php elseif ($c['statut'] === 'en_cours'): ?>

          <!-- Terminer -->
          <button class="btn btn-green" onclick="openNotes(<?= htmlspecialchars(json_encode($c)) ?>)" style="font-size:.78rem;padding:.3rem .7rem;">✓ Terminer</button>

          <!-- Annuler même en cours -->
          <form action="<?= BASE ?>/controller/vip_controller.php?action=update_consult" method="POST" style="display:inline;" onsubmit="return confirm('Annuler cette consultation en cours ?')">
            <input type="hidden" name="id" value="<?= $c['id'] ?>"/>
            <input type="hidden" name="statut" value="annulee"/>
            <button type="submit" class="btn btn-red" style="font-size:.78rem;padding:.3rem .7rem;">✕ Annuler</button>
          </form>

        <?php endif; ?>

      </td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($consultations)): ?><tr><td colspan="7" style="text-align:center;color:var(--muted);padding:1.5rem;">Aucune consultation planifiée.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>

<!-- MODAL PLANIFIER -->
<div id="planModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;padding:1rem;overflow-y:auto;">
  <div style="background:white;max-width:580px;width:100%;border-radius:16px;padding:2rem;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;">
      <h2 style="font-family:'Playfair Display',serif;">Planifier une consultation</h2>
      <button onclick="document.getElementById('planModal').style.display='none'" style="border:none;background:none;font-size:1.5rem;cursor:pointer;">×</button>
    </div>
    <form action="<?= BASE ?>/controller/vip_controller.php?action=planifier" method="POST">
      <div class="form-group"><label>Client VIP *</label>
        <select class="form-control" name="client_id" id="plan_client_id" required>
          <option value="">Sélectionner...</option>
          <?php foreach ($vipClients as $cl): ?><option value="<?= $cl['id'] ?>"><?= htmlspecialchars($cl['nom']) ?> (<?= strtoupper($cl['niveau']) ?>)</option><?php endforeach; ?>
        </select>
      </div>
      <div class="form-group"><label>Titre de la consultation</label><input class="form-control" name="titre" value="Consultation nutritionnelle"/></div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
        <div class="form-group"><label>Date &amp; heure *</label><input class="form-control" type="datetime-local" name="date_heure" required/></div>
        <div class="form-group"><label>Durée (min)</label><input class="form-control" type="number" name="duree" value="30" min="15" max="120"/></div>
      </div>
      <div class="form-group"><label>Type de consultation</label>
        <select class="form-control" name="type">
          <option value="visio"> Visioconférence (Jitsi Meet)</option>
          <option value="chat"> Chat textuel</option>
        </select>
      </div>
      <div class="form-group"><label>Objectifs de la séance</label><textarea class="form-control" name="objectifs" rows="2" placeholder="Bilan initial, suivi poids, révision plan..."></textarea></div>
      <div class="form-group"><label>Notes préparatoires</label><textarea class="form-control" name="notes_avant" rows="2" placeholder="Points à aborder..."></textarea></div>
      <div style="display:flex;gap:.8rem;margin-top:1rem;">
        <button type="submit" class="btn btn-green" style="flex:1;padding:.8rem;">Planifier &amp; Envoyer le lien</button>
        <button type="button" onclick="document.getElementById('planModal').style.display='none'" class="btn btn-gray">Annuler</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL NOTES APRÈS CONSULTATION -->
<div id="notesModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;padding:1rem;">
  <div style="background:white;max-width:500px;width:100%;border-radius:16px;padding:2rem;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.2rem;">
      <h2 style="font-family:'Playfair Display',serif;">Notes de consultation</h2>
      <button onclick="document.getElementById('notesModal').style.display='none'" style="border:none;background:none;font-size:1.5rem;cursor:pointer;">×</button>
    </div>
    <form action="<?= BASE ?>/controller/vip_controller.php?action=update_consult" method="POST">
      <input type="hidden" name="id" id="notes_id"/>
      <input type="hidden" name="statut" value="terminee"/>
      <div class="form-group"><label>Compte-rendu de la séance</label>
        <textarea class="form-control" name="notes" rows="5" placeholder="Résumé, recommandations, prochaines étapes..." required></textarea>
      </div>
      <div style="display:flex;gap:.8rem;margin-top:1rem;">
        <button type="submit" class="btn btn-green" style="flex:1;padding:.8rem;">Sauvegarder &amp; Terminer</button>
        <button type="button" onclick="document.getElementById('notesModal').style.display='none'" class="btn btn-gray">Annuler</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL MODIFIER CONSULTATION -->
<div id="editModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;padding:1rem;">
  <div style="background:white;max-width:500px;width:100%;border-radius:16px;padding:2rem;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.2rem;">
      <h2 style="font-family:'Playfair Display',serif;">Modifier la consultation</h2>
      <button onclick="document.getElementById('editModal').style.display='none'" style="border:none;background:none;font-size:1.5rem;cursor:pointer;">×</button>
    </div>
    <form action="<?= BASE ?>/controller/vip_controller.php?action=modifier_consult" method="POST">
      <input type="hidden" name="id" id="edit_id"/>
      <div class="form-group"><label>Titre</label>
        <input class="form-control" name="titre" id="edit_titre"/>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
        <div class="form-group"><label>Date & heure *</label>
          <input class="form-control" type="datetime-local" name="date_heure" id="edit_date_heure" required/>
        </div>
        <div class="form-group"><label>Durée (min)</label>
          <input class="form-control" type="number" name="duree" id="edit_duree" min="15" max="120"/>
        </div>
      </div>
      <div class="form-group"><label>Notes préparatoires</label>
        <textarea class="form-control" name="notes_avant" id="edit_notes_avant" rows="3"></textarea>
      </div>
      <div style="display:flex;gap:.8rem;margin-top:1rem;">
        <button type="submit" class="btn btn-green" style="flex:1;padding:.8rem;">💾 Sauvegarder</button>
        <button type="button" onclick="document.getElementById('editModal').style.display='none'" class="btn btn-gray">Annuler</button>
      </div>
    </form>
  </div>
</div>

<script>
function openPlanForClient(id, nom) {
  document.getElementById('plan_client_id').value = id;
  document.getElementById('planModal').style.display = 'flex';
}
function openNotes(c) {
  document.getElementById('notes_id').value = c.id;
  document.getElementById('notesModal').style.display = 'flex';
}
function openEdit(c) {
  document.getElementById('edit_id').value        = c.id;
  document.getElementById('edit_titre').value     = c.titre;
  document.getElementById('edit_duree').value     = c.duree_min;
  document.getElementById('edit_notes_avant').value = c.notes_avant ?? '';
  // Formater date pour datetime-local
  const d = new Date(c.date_heure.replace(' ', 'T'));
  const pad = n => String(n).padStart(2,'0');
  document.getElementById('edit_date_heure').value =
    `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
  document.getElementById('editModal').style.display = 'flex';
}
</script>


<?php include "partials/nutri_footer.php"; ?>
