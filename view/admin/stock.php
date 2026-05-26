<?php
$pageTitle = "Stock";
include "partials/admin_header.php";

// Fetch all stock with product info
$stocks = $cnx->query("
    SELECT s.id, p.nom, s.quantite, s.seuil_alerte, s.en_production, s.updated_at,
           p.id AS produit_id
    FROM stock s
    JOIN produits p ON p.id = s.produit_id
    ORDER BY (s.quantite <= s.seuil_alerte) DESC, s.quantite ASC
")->fetchAll();

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_stock') {
    $stock_id = (int)$_POST['stock_id'];
    $quantite = (int)$_POST['quantite'];
    $seuil    = (int)$_POST['seuil_alerte'];
    $en_prod  = (int)$_POST['en_production'];
    $stmt = $cnx->prepare("UPDATE stock SET quantite = ?, seuil_alerte = ?, en_production = ? WHERE id = ?");
    $stmt->execute([$quantite, $seuil, $en_prod, $stock_id]);
    $_SESSION['success'] = "Stock mis à jour.";
    header("Location: stock.php");
    exit;
}
?>

<div class="topbar">
  <h1><i class="fas fa-warehouse"></i> Gestion des stocks</h1>
</div>

<div class="card">
  <div class="card-title">
    <i class="fas fa-boxes"></i> Niveaux de stock
  </div>
  <div class="table-wrapper">
    <table class="data-table">
      <thead>
        <tr>
          <th>Produit</th>
          <th>Quantité</th>
          <th>Seuil d’alerte</th>
          <th>En production</th>
          <th>Statut</th>
          <th>Dernière mise à jour</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($stocks as $s):
          $isLow = $s['quantite'] <= $s['seuil_alerte'];
          $statusBadge = $isLow ? 'badge-danger' : 'badge-success';
          $statusText  = $isLow ? '⚠️ Alerte' : '✅ OK';
        ?>
        <tr>
          <td><strong><?= htmlspecialchars($s['nom']) ?></strong></td>
          <form method="POST">
            <input type="hidden" name="action" value="update_stock">
            <input type="hidden" name="stock_id" value="<?= $s['id'] ?>">
            <td><input type="number" name="quantite" value="<?= $s['quantite'] ?>" class="form-control-sm" style="width:90px;"></td>
            <td><input type="number" name="seuil_alerte" value="<?= $s['seuil_alerte'] ?>" class="form-control-sm" style="width:90px;"></td>
            <td><input type="number" name="en_production" value="<?= $s['en_production'] ?>" class="form-control-sm" style="width:80px;"></td>
            <td><span class="badge <?= $statusBadge ?>"><?= $statusText ?></span></td>
            <td class="date-cell"><?= date('d/m/Y H:i', strtotime($s['updated_at'])) ?></td>
            <td><button type="submit" class="btn btn-green btn-sm">Mettre à jour</button></td>
          </form>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include "partials/admin_footer.php"; ?>