<?php
$pageTitle = "Plans Alimentaires";
include "partials/nutri_header.php";

// Actions
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $action = $_POST['form_action'] ?? '';
    if ($action === 'create_plan') {
        $pid = createPlan($cnx,$_SESSION['user_id'],$_POST['client_id'],$_POST['titre'],$_POST['objectif']);
        if ($pid) { $_SESSION['success']="Plan créé !"; header("Location: ".BASE."/view/nutritionniste/plans.php?edit=$pid"); exit(); }
    }
    if ($action === 'add_repas') {
        $ok = addRepas($cnx,$_POST['plan_id'],$_POST['jour'],$_POST['moment'],$_POST['description'],$_POST['calories']??0);
        $_SESSION[$ok?'success':'error'] = $ok?"Repas ajouté.":"Erreur.";
        header("Location: ".BASE."/view/nutritionniste/plans.php?edit=".$_POST['plan_id']); exit();
    }
    if ($action === 'delete_repas') {
        deleteRepas($cnx,(int)$_POST['repas_id']);
        header("Location: ".BASE."/view/nutritionniste/plans.php?edit=".$_POST['plan_id']); exit();
    }
    if ($action === 'delete_plan') {
        deletePlan($cnx,(int)$_POST['plan_id']);
        $_SESSION['success']="Plan supprimé.";
        header("Location: ".BASE."/view/nutritionniste/plans.php"); exit();
    }
    if ($action === 'edit_plan') {
        $ok = $cnx->prepare("
            UPDATE plans_alimentaires 
            SET titre = :titre, objectif = :objectif 
            WHERE id = :id AND nutritionniste_id = :nid
        ")->execute([
            ':titre'    => $_POST['titre']    ?? '',
            ':objectif' => $_POST['objectif'] ?? '',
            ':id'       => (int)$_POST['plan_id'],
            ':nid'      => $_SESSION['user_id'],
        ]);
        $_SESSION[$ok ? 'success' : 'error'] = $ok ? "Plan modifié." : "Erreur.";
        header("Location: ".BASE."/view/nutritionniste/plans.php?edit=".$_POST['plan_id']); exit();
    }
}

$plans   = getAllPlans($cnx,$_SESSION['user_id']);
$clients = getClientsAvecCommandes($cnx);
$editId  = isset($_GET['edit']) ? (int)$_GET['edit'] : null;
$editPlan= $editId ? getPlanById($cnx,$editId) : null;
$jours   = ['Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi','Dimanche'];
$moments = ['matin'=>'Matin','midi'=>'Midi','soir'=>'Soir','collation'=>'Collation'];
?>

<div class="topbar">
  <h1>Plans Alimentaires</h1>
  <button class="btn btn-green" onclick="document.getElementById('createModal').style.display='flex'">+ Nouveau plan</button>
</div>

<?php if ($editPlan): ?>

<!-- MODE ÉDITION DU PLAN -->
<div class="card" style="border-left:4px solid var(--green);">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.2rem;flex-wrap:wrap;gap:.5rem;">
    <div>
      <div class="card-title" style="margin:0 0 .2rem;">
        <?= htmlspecialchars($editPlan['titre']) ?>
        <button onclick="document.getElementById('editPlanModal').style.display='flex'" style="border:none;background:none;cursor:pointer;font-size:.85rem;color:var(--muted);">✏️</button>
      </div>
      <div style="font-size:.85rem;color:var(--muted);">Client : <strong><?= htmlspecialchars($editPlan['client_nom']) ?></strong> · <?= htmlspecialchars($editPlan['objectif']??'') ?></div>
    </div>
    <a href="<?= BASE ?>/view/nutritionniste/plans.php" class="btn btn-gray">← Retour aux plans</a>
  </div>

  <!-- Tableau des repas par jour -->
  <div style="overflow-x:auto;">
    <table>
      <thead><tr><th>Jour</th><?php foreach ($moments as $mk=>$ml): ?><th><?= $ml ?></th><?php endforeach; ?></tr></thead>
      <tbody>
      <?php foreach ($jours as $jour):
        $repasJour = array_filter($editPlan['repas']??[],fn($r)=>$r['jour']===$jour);
      ?>
      <tr>
        <td style="font-weight:700;font-family:'Playfair Display',serif;"><?= $jour ?></td>
        <?php foreach (array_keys($moments) as $mk):
          $repas = array_filter($repasJour,fn($r)=>$r['moment']===$mk);
          $repas = array_values($repas);
        ?>
        <td style="min-width:160px;vertical-align:top;padding:.5rem;">
          <?php foreach ($repas as $r): ?>
          <div style="background:#f0fdf4;border-radius:8px;padding:.4rem .6rem;margin-bottom:.3rem;font-size:.8rem;border:1px solid #86efac;">
            <?= htmlspecialchars($r['description']) ?>
            <?php if ($r['calories']): ?><span style="color:var(--muted);font-size:.73rem;"> · <?= $r['calories'] ?> kcal</span><?php endif; ?>
            <form method="POST" style="display:inline;">
              <input type="hidden" name="form_action" value="delete_repas"/>
              <input type="hidden" name="repas_id" value="<?= $r['id'] ?>"/>
              <input type="hidden" name="plan_id" value="<?= $editId ?>"/>
              <button type="submit" style="border:none;background:none;cursor:pointer;color:#ef4444;font-size:.75rem;" onclick="return confirm('Supprimer ?')">✕</button>
            </form>
          </div>
          <?php endforeach; ?>
          <button onclick="openAddRepas('<?= $jour ?>','<?= $mk ?>')" style="border:1px dashed var(--border);background:none;border-radius:6px;padding:.25rem .5rem;font-size:.75rem;cursor:pointer;color:var(--muted);width:100%;">+ Ajouter</button>
        </td>
        <?php endforeach; ?>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Supprimer plan -->
  <form method="POST" style="margin-top:1rem;">
    <input type="hidden" name="form_action" value="delete_plan"/>
    <input type="hidden" name="plan_id" value="<?= $editId ?>"/>
    <button type="submit" class="btn btn-red" onclick="return confirm('Supprimer ce plan ?')">Supprimer ce plan</button>
  </form>
</div>

<!-- MODAL MODIFIER PLAN -->
<div id="editPlanModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;padding:1rem;">
  <div style="background:white;max-width:480px;width:100%;border-radius:16px;padding:2rem;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.2rem;">
      <h2 style="font-family:'Playfair Display',serif;">Modifier le plan</h2>
      <button onclick="document.getElementById('editPlanModal').style.display='none'" style="border:none;background:none;font-size:1.5rem;cursor:pointer;">×</button>
    </div>
    <form method="POST">
      <input type="hidden" name="form_action" value="edit_plan"/>
      <input type="hidden" name="plan_id" value="<?= $editId ?>"/>
      <div class="form-group"><label>Titre du plan *</label>
        <input class="form-control" name="titre" value="<?= htmlspecialchars($editPlan['titre']) ?>" required/>
      </div>
      <div class="form-group"><label>Objectif</label>
        <textarea class="form-control" name="objectif" rows="2"><?= htmlspecialchars($editPlan['objectif']??'') ?></textarea>
      </div>
      <div style="display:flex;gap:.8rem;margin-top:1rem;">
        <button type="submit" class="btn btn-green" style="flex:1;padding:.8rem;">💾 Sauvegarder</button>
        <button type="button" onclick="document.getElementById('editPlanModal').style.display='none'" class="btn btn-gray">Annuler</button>
      </div>
    </form>
  </div>
</div>

<?php else: ?>

<div class="card">
  <div class="card-title">Mes plans alimentaires</div>
  <table>
    <thead><tr><th>Plan</th><th>Client</th><th>Objectif</th><th>Créé le</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($plans as $p): ?>
    <tr>
      <td><strong><?= htmlspecialchars($p['titre']) ?></strong></td>
      <td><?= htmlspecialchars($p['client_nom']) ?></td>
      <td style="font-size:.85rem;color:var(--muted);"><?= htmlspecialchars(mb_substr($p['objectif']??'–',0,60)) ?></td>
      <td style="font-size:.82rem;"><?= date('d/m/Y',strtotime($p['created_at'])) ?></td>
      <td>
        <a href="?edit=<?= $p['id'] ?>" class="btn btn-green" style="font-size:.8rem;padding:.35rem .8rem;">Éditer</a>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($plans)): ?>
      <tr><td colspan="5" style="text-align:center;color:var(--muted);padding:1.5rem;">Aucun plan créé. Créez le premier !</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>

<?php endif; ?>

<!-- MODAL CRÉER PLAN -->
<div id="createModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;padding:1rem;">
  <div style="background:white;max-width:520px;width:100%;border-radius:16px;padding:2rem;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;">
      <h2 style="font-family:'Playfair Display',serif;">Nouveau plan alimentaire</h2>
      <button onclick="document.getElementById('createModal').style.display='none'" style="border:none;background:none;font-size:1.5rem;cursor:pointer;">×</button>
    </div>
    <form method="POST">
      <input type="hidden" name="form_action" value="create_plan"/>
      <div class="form-group"><label>Client *</label>
        <select class="form-control" name="client_id" required>
          <option value="">Sélectionner un client...</option>
          <?php foreach ($clients as $cl): ?>
            <option value="<?= $cl['id'] ?>" <?= (isset($_GET['client_id']) && $_GET['client_id'] == $cl['id']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($cl['nom']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group"><label>Titre du plan *</label><input class="form-control" name="titre" placeholder="Ex: Plan sans gluten 4 semaines" required/></div>
      <div class="form-group"><label>Objectif</label><textarea class="form-control" name="objectif" rows="2" placeholder="Ex: Réduire l'inflammation, perdre 3kg..."></textarea></div>
      <div style="display:flex;gap:.8rem;margin-top:1rem;">
        <button type="submit" class="btn btn-green" style="flex:1;padding:.8rem;">Créer le plan</button>
        <button type="button" onclick="document.getElementById('createModal').style.display='none'" class="btn btn-gray">Annuler</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL AJOUTER REPAS -->
<div id="repasModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;padding:1rem;">
  <div style="background:white;max-width:480px;width:100%;border-radius:16px;padding:2rem;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.2rem;">
      <h2 style="font-family:'Playfair Display',serif;" id="repasTitle">Ajouter un repas</h2>
      <button onclick="document.getElementById('repasModal').style.display='none'" style="border:none;background:none;font-size:1.5rem;cursor:pointer;">×</button>
    </div>
    <form method="POST">
      <input type="hidden" name="form_action" value="add_repas"/>
      <input type="hidden" name="plan_id" value="<?= $editId ?>"/>
      <input type="hidden" name="jour" id="repas_jour"/>
      <input type="hidden" name="moment" id="repas_moment"/>
      <div class="form-group"><label>Description du repas *</label><textarea class="form-control" name="description" rows="3" placeholder="Ex: Yaourt nature bio avec granola sans gluten, thé vert..." required></textarea></div>
      <div class="form-group"><label>Calories estimées (kcal)</label><input class="form-control" type="number" name="calories" value="0" min="0"/></div>
      <div style="display:flex;gap:.8rem;margin-top:1rem;">
        <button type="submit" class="btn btn-green" style="flex:1;padding:.8rem;">Ajouter</button>
        <button type="button" onclick="document.getElementById('repasModal').style.display='none'" class="btn btn-gray">Annuler</button>
      </div>
    </form>
  </div>
</div>

<script>
function openAddRepas(jour, moment) {
  const labels = {matin:'Matin',midi:'Midi',soir:'Soir',collation:'Collation'};
  document.getElementById('repas_jour').value = jour;
  document.getElementById('repas_moment').value = moment;
  document.getElementById('repasTitle').textContent = jour + ' — ' + (labels[moment]||moment);
  document.getElementById('repasModal').style.display = 'flex';
}
<?php if (!empty($_GET['client_id']) && (int)$_GET['client_id'] > 0): ?>
window.addEventListener('DOMContentLoaded', function() {
  document.getElementById('createModal').style.display = 'flex';
});
<?php endif; ?>
</script>

<?php include "partials/nutri_footer.php"; ?>