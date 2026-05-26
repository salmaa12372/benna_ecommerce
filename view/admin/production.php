<?php
$pageTitle = "Production";
include "partials/admin_header.php";
$ordres  = getOrdresProduction($cnx);
$produits = getAllProduits($cnx);
$alertes  = getStockAlertes($cnx);
$badgeMap = ['demande'=>'badge-warning','en_cours'=>'badge-info','termine'=>'badge-success'];
?>

<div class="topbar">
  <h1><i class="fas fa-industry"></i> Production</h1>
  <button class="btn btn-green" onclick="document.getElementById('ordreModal').style.display='flex'">
    <i class="fas fa-plus"></i> Nouvel ordre
  </button>
</div>

<?php if (!empty($alertes)): ?>
<div class="card alert-card-red">
  <div class="alert-title-red">
    <i class="fas fa-exclamation-triangle"></i> Produits en alerte de stock
  </div>
  <div class="alert-badges">
    <?php foreach ($alertes as $a): ?>
      <span class="badge badge-danger">
        <i class="fas fa-box"></i> <?= htmlspecialchars($a['nom']) ?> : <?= $a['quantite'] ?> restants
        <small>(seuil: <?= $a['seuil_alerte'] ?>)</small>
      </span>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<div class="card">
  <div class="card-title">
    <i class="fas fa-clipboard-list"></i> Ordres de production
  </div>
  <div class="table-wrapper">
    <table class="data-table">
      <thead>
        <tr>
          <th><i class="fas fa-box"></i> Produit</th>
          <th><i class="fas fa-cubes"></i> Quantité</th>
          <th><i class="fas fa-user-check"></i> Demandé par</th>
          <th><i class="fas fa-chart-simple"></i> Statut</th>
          <th><i class="fas fa-calendar-plus"></i> Créé le</th>
          <th><i class="fas fa-calendar-check"></i> Terminé le</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($ordres as $o): ?>
      <tr class="order-row">
        <td><strong><?= htmlspecialchars($o['produit_nom']) ?></strong></td>
        <td><?= $o['quantite'] ?> unités</td>
        <td><?= htmlspecialchars($o['demande_par_nom']??'–') ?></td>
        <td><span class="badge <?= $badgeMap[$o['statut']]??'badge-secondary' ?>"><?= ucfirst(str_replace('_',' ',$o['statut'])) ?></span></td>
        <td class="order-date"><?= date('d/m/Y',strtotime($o['created_at'])) ?></td>
        <td class="order-date"><?= $o['termine_at']?date('d/m/Y',strtotime($o['termine_at'])):'–' ?></td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($ordres)): ?>
        <tr class="empty-order">
          <td colspan="6"><i class="fas fa-inbox"></i> Aucun ordre de production.</td>
        </tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- MODAL CRÉER ORDRE -->
<div id="ordreModal" class="order-modal">
  <div class="order-modal-content">
    <div class="order-modal-header">
      <h2><i class="fas fa-plus-circle"></i> Créer un ordre de production</h2>
      <button class="order-modal-close" onclick="document.getElementById('ordreModal').style.display='none'">&times;</button>
    </div>
    <form action="<?= BASE ?>/controller/stock_controller.php?action=creer_ordre" method="POST">
      <div class="form-group">
        <label><i class="fas fa-box"></i> Produit</label>
        <select class="form-control" name="produit_id" required>
          <option value="">Sélectionner...</option>
          <?php foreach ($produits as $p): ?>
            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nom']) ?> (stock: <?= $p['stock'] ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label><i class="fas fa-cubes"></i> Quantité à produire</label>
        <input class="form-control" name="quantite" type="number" min="1" value="50" required/>
      </div>
      <div class="order-modal-actions">
        <button type="submit" class="btn btn-green"><i class="fas fa-check"></i> Créer</button>
        <button type="button" onclick="document.getElementById('ordreModal').style.display='none'" class="btn btn-gray"><i class="fas fa-times"></i> Annuler</button>
      </div>
    </form>
  </div>
</div>

<?php include "partials/admin_footer.php"; ?>
