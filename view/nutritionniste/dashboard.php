<?php
$pageTitle = "Dashboard Nutritionniste";
include "partials/nutri_header.php";

// Seule action du dashboard : add_alerte
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $action = $_POST['form_action'] ?? '';
    if ($action === 'add_alerte') {
        $ok = addAlerteNutrition($cnx,$_SESSION['user_id'],$_POST['client_id'],$_POST['titre'],$_POST['message'],$_POST['gravite']);
        $_SESSION[$ok?'success':'error'] = $ok?"Alerte envoyée.":"Erreur.";
        header("Location: ".BASE."/view/nutritionniste/dashboard.php"); exit();
    }
}

$tousConseils  = getAllConseils($cnx);
$mesConseils   = array_values(array_filter($tousConseils, fn($c)=>$c['nutritionniste_id']==$_SESSION['user_id']));
$avisEnAttente = array_values(array_filter(getAllAvis($cnx), fn($a)=>!$a['valide']));
$produits      = getAllProduits($cnx);
$clients       = getClientsAvecCommandes($cnx);
$alertes       = getAlertesNutrition($cnx);
?>

<div class="topbar">
  <h1>Bonjour, <?= htmlspecialchars(explode(' ',$_SESSION['nom'])[0]) ?> !</h1>
  <span style="background:white;padding:.4rem 1rem;border-radius:20px;font-size:.88rem;box-shadow:0 2px 8px rgba(0,0,0,.08);">🥗 Nutritionniste</span>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1.2rem;margin-bottom:2rem;">
  <div class="card" style="border-left:4px solid var(--green);padding:1.2rem;">
    <div style="font-family:'Playfair Display',serif;font-size:2rem;color:var(--green-dark);"><?= count($mesConseils) ?></div>
    <div style="color:var(--muted);font-size:.85rem;">Conseils publiés</div>
  </div>
  <div class="card" style="border-left:4px solid #f59e0b;padding:1.2rem;">
    <div style="font-family:'Playfair Display',serif;font-size:2rem;color:#b45309;"><?= count($avisEnAttente) ?></div>
    <div style="color:var(--muted);font-size:.85rem;">Avis à valider</div>
  </div>
  <div class="card" style="border-left:4px solid var(--green);padding:1.2rem;">
    <div style="font-family:'Playfair Display',serif;font-size:2rem;color:var(--green-dark);"><?= count($produits) ?></div>
    <div style="color:var(--muted);font-size:.85rem;">Produits actifs</div>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">
  <div class="card">
    <div class="card-title">Mes derniers conseils</div>
    <?php if (empty($mesConseils)): ?>
      <p style="color:var(--muted);">Aucun conseil. <a href="<?= BASE ?>/view/nutritionniste/conseils.php" style="color:var(--green);">Créer →</a></p>
    <?php else: ?>
      <?php foreach (array_slice($mesConseils,0,5) as $c): ?>
      <div style="padding:.6rem 0;border-bottom:1px solid var(--border);">
        <div style="font-weight:600;font-size:.92rem;"><?= htmlspecialchars($c['titre']) ?></div>
        <div style="font-size:.78rem;color:var(--muted);"><?= $c['produit_nom']?'🌿 '.$c['produit_nom']:'Général' ?> · <?= date('d/m/Y',strtotime($c['created_at'])) ?></div>
      </div>
      <?php endforeach; ?>
      <a href="<?= BASE ?>/view/nutritionniste/conseils.php" class="btn btn-green" style="margin-top:1rem;">Gérer mes conseils →</a>
    <?php endif; ?>
  </div>

  <div class="card">
    <div class="card-title">Avis à valider</div>
    <?php if (empty($avisEnAttente)): ?>
      <p style="color:var(--muted);">Tous les avis sont validés.</p>
    <?php else: ?>
      <?php foreach (array_slice($avisEnAttente,0,5) as $a): ?>
      <div style="display:flex;justify-content:space-between;align-items:center;padding:.6rem 0;border-bottom:1px solid var(--border);">
        <div>
          <div style="font-size:.9rem;font-weight:600;"><?= htmlspecialchars($a['auteur']) ?></div>
          <div style="font-size:.78rem;color:var(--muted);"><?= htmlspecialchars($a['produit_nom']) ?> · <?= str_repeat('★',$a['note']) ?></div>
        </div>
        <a href="<?= BASE ?>/controller/conseil_controller.php?action=valider_avis&id=<?= $a['id'] ?>" class="btn btn-green" style="font-size:.78rem;padding:.3rem .7rem;">✓</a>
      </div>
      <?php endforeach; ?>
      <a href="<?= BASE ?>/view/nutritionniste/avis.php" class="btn btn-green" style="margin-top:1rem;">Tous les avis →</a>
    <?php endif; ?>
  </div>
</div>

<!-- ALERTES NUTRITIONNELLES -->
<div class="card" style="margin-top:1.5rem;">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
    <div class="card-title" style="margin:0;">Alertes nutritionnelles</div>
    <button class="btn btn-yellow" onclick="document.getElementById('alerteModal').style.display='flex'" style="font-size:.82rem;padding:.35rem .8rem;">+ Nouvelle alerte</button>
  </div>
  <table>
    <thead><tr><th>Titre</th><th>Client</th><th>Gravité</th><th>Date</th></tr></thead>
    <tbody>
    <?php foreach (array_slice($alertes,0,5) as $a): ?>
    <tr>
      <td><strong><?= htmlspecialchars($a['titre']) ?></strong><br/>
        <span style="font-size:.78rem;color:var(--muted);"><?= htmlspecialchars(mb_substr($a['message'],0,60)) ?>...</span>
      </td>
      <td><?= $a['client_nom'] ? '👤 '.htmlspecialchars($a['client_nom']) : 'Global' ?></td>
      <td><span class="badge <?= match($a['gravite']){'info'=>'badge-blue','attention'=>'badge-yellow','urgent'=>'badge-red',default=>'badge-gray'} ?>"><?= ucfirst($a['gravite']) ?></span></td>
      <td style="font-size:.82rem;"><?= date('d/m/Y',strtotime($a['created_at'])) ?></td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($alertes)): ?>
      <tr><td colspan="4" style="text-align:center;color:var(--muted);padding:1.5rem;">Aucune alerte envoyée.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- MODAL ALERTE -->
<div id="alerteModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;padding:1rem;">
  <div style="background:white;max-width:500px;width:100%;border-radius:16px;padding:2rem;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.2rem;">
      <h2 style="font-family:'Playfair Display',serif;">Envoyer une alerte nutritionnelle</h2>
      <button onclick="document.getElementById('alerteModal').style.display='none'" style="border:none;background:none;font-size:1.5rem;cursor:pointer;">×</button>
    </div>
    <form method="POST">
      <input type="hidden" name="form_action" value="add_alerte"/>
      <div class="form-group"><label>Client ciblé</label>
        <select class="form-control" name="client_id">
          <option value="">– Tous les clients –</option>
          <?php foreach ($clients as $cl): ?>
            <option value="<?= $cl['id'] ?>"><?= htmlspecialchars($cl['nom']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group"><label>Titre *</label><input class="form-control" name="titre" required/></div>
      <div class="form-group"><label>Message *</label><textarea class="form-control" name="message" rows="4" required></textarea></div>
      <div class="form-group"><label>Niveau de gravité</label>
        <select class="form-control" name="gravite">
          <option value="info">Information</option>
          <option value="attention">Attention</option>
          <option value="urgent">Urgent</option>
        </select>
      </div>
      <div style="display:flex;gap:.8rem;margin-top:1rem;">
        <button type="submit" class="btn btn-green" style="flex:1;padding:.8rem;">Envoyer l'alerte</button>
        <button type="button" onclick="document.getElementById('alerteModal').style.display='none'" class="btn btn-gray">Annuler</button>
      </div>
    </form>
  </div>
</div>

<?php include "partials/nutri_footer.php"; ?>