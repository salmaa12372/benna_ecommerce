<?php
$pageTitle = "Commandes à préparer";
include "partials/usine_header.php";
$cmdEnPrep     = getCommandesByStatut($cnx,'en_preparation');
$cmdConfirmees = getCommandesByStatut($cnx,'confirmee');
$all = array_merge($cmdConfirmees, $cmdEnPrep);
?>
<div class="topbar">
  <h1> Commandes à préparer</h1>
<span class="pill"><?= count($all) ?> commande(s)</span>
</div>
<div class="card">
  <table>
    <thead><tr><th>#</th><th>Client</th><th>Total</th><th>Adresse</th><th>Statut</th><th>Date commande</th><th>Action</th></tr></thead>
    <tbody>
    <?php foreach ($all as $c):
      $isPrep = $c['statut']==='en_preparation';
    ?>
    <tr>
      <td><strong>#<?= $c['id'] ?></strong></td>
      <td><?= htmlspecialchars($c['client_nom']) ?></td>
      <td><?= number_format($c['total'],3) ?> TND</td>
      <td class="text-sm"><?= htmlspecialchars(mb_substr($c['adresse_livraison'],0,50)) ?>...</td>
      <td><span class="badge <?= $isPrep?'badge-purple':'badge-yellow' ?>"><?= $isPrep?' En préparation':' Confirmée' ?></span></td>
      <td class="text-sm"><?= date('d/m/Y H:i',strtotime($c['date_commande'])) ?></td>
      <td>
        <form action="<?= BASE ?>/controller/commande_controller.php?action=update_statut" method="POST" class="flex gap-sm+">
          <input type="hidden" name="commande_id" value="<?= $c['id'] ?>"/>
          <?php if (!$isPrep): ?>
            <button name="statut" value="en_preparation" class="btn btn-purple">▶ Démarrer préparation</button>
          <?php else: ?>
            <button name="statut" value="expedie" class="btn btn-green"> Marquer expédiée</button>
          <?php endif; ?>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($all)): ?><tr><td colspan="7" class="text-center text-muted py-lg"> Aucune commande à préparer pour l'instant.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?php include "partials/usine_footer.php"; ?>
