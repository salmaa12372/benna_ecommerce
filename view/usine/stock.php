<?php
$pageTitle = "Gestion du Stock";
include "partials/usine_header.php";
$stock   = getStockComplet($cnx);
$alertes = getStockAlertes($cnx);
?>

<div class="topbar">
  <h1> Gestion du Stock</h1>
  <?php if (!empty($alertes)): ?>
    <span class="badge badge-red"> <?= count($alertes) ?> alerte(s)</span>
  <?php endif; ?>
</div>

<div class="card">
  <table>
    <thead>
      <tr>
        <th>Produit</th>
        <th>Stock actuel</th>
        <th>Seuil alerte</th>
        <th>Statut</th>
        <th>Mis à jour</th>
        <th>Modifier</th>
      </tr>
    </thead>

<tbody>

<?php foreach ($stock as $s): 

  $cls = $s['quantite'] <= $s['seuil_alerte']
    ? 'status-red'
    : ($s['quantite'] <= $s['seuil_alerte'] * 2 ? 'status-yellow' : 'status-green');

  $lbl = $s['quantite'] <= $s['seuil_alerte']
    ? 'Critique'
    : ($s['quantite'] <= $s['seuil_alerte'] * 2 ? 'Faible' : 'OK');

?>

<tr>
  <td><strong><?= htmlspecialchars($s['nom']) ?></strong></td>

  <td>
    <strong class="text-lg"><?= $s['quantite'] ?></strong> unités
  </td>

  <td><?= $s['seuil_alerte'] ?></td>

  <!-- ✅ STATUS HERE -->
  <td>
    <span class="status-pill <?= $cls ?>">
      <span class="status-dot"></span>
      <?= $lbl ?>
    </span>
  </td>

  <td class="text-sm">
    <?= date('d/m/Y H:i', strtotime($s['updated_at'])) ?>
  </td>

  <td>
    <form action="<?= BASE ?>/controller/stock_controller.php?action=update_stock"
          method="POST"
          class="flex gap-sm">

      <input type="hidden" name="produit_id" value="<?= $s['produit_id'] ?>"/>

      <input type="number"
             name="quantite"
             value="<?= $s['quantite'] ?>"
             class="form-control input-sm"/>

      <button type="submit" class="btn btn-green btn-sm">
SAVE      </button>
    </form>
  </td>
</tr>

<?php endforeach; ?>

<?php if (empty($stock)): ?>
<tr>
  <td colspan="6" class="text-center text-muted p-3">
    Aucun produit en stock.
  </td>
</tr>
<?php endif; ?>

</tbody>

  </table>

</div>
<?php include "partials/usine_footer.php"; ?>
