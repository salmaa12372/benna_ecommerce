<?php $pageTitle = "Dashboard Usine"; 
include "partials/usine_header.php"; 
$stock = getStockComplet($cnx);
 $alertes = getStockAlertes($cnx);
 $stmtTotal = $cnx->query("SELECT COUNT(*) FROM produits WHERE est_actif=1 AND est_valide=1");
$totalProduits = (int)$stmtTotal->fetchColumn();
 $ordres = getOrdresProduction($cnx); 
 $cmdEnPrep = getCommandesByStatut($cnx,'en_preparation'); 
 $cmdConfirmees = getCommandesByStatut($cnx,'confirmee');
 $ordresActifs = array_values(array_filter($ordres, fn($o)=>$o['statut']!=='termine'));
 
?>

<div class="topbar">
  <h1>Tableau de bord Usine</h1>
  <span class="pill">🏭 <?= htmlspecialchars($_SESSION['nom']) ?></span>
</div>

<div class="stats-grid">
  <div class="stat-card border-green">
    <div class="stat-val"><?= count($stock) ?></div>
    <div class="stat-label">Produits en stock</div>
  </div>

  <div class="stat-card border-red">
    <div class="stat-val"><?= $totalProduits ?></div>
    <div class="stat-label">Alertes de stock</div>
  </div>

  <div class="stat-card border-orange">
    <div class="stat-val stat-warn"><?= count($ordresActifs) ?></div>
    <div class="stat-label">Ordres en cours</div>
  </div>

  <div class="stat-card border-green">
    <div class="stat-val"><?= count($cmdEnPrep) + count($cmdConfirmees) ?></div>
    <div class="stat-label">À préparer</div>
  </div>
</div>

<?php if (!empty($alertes)): ?>
<div class="card card-danger">
  <div class="card-title text-danger">Produits en rupture critique</div>

  <div class="flex flex-wrap gap-sm">
    <?php foreach ($alertes as $a): ?>
      <span class="badge badge-red">
        <?= htmlspecialchars($a['nom']) ?> — <?= $a['quantite'] ?> / seuil: <?= $a['seuil_alerte'] ?>
      </span>
    <?php endforeach; ?>
  </div>

  <a href="<?= BASE ?>/view/usine/production.php" class="btn btn-green mt-1">
    Gérer les ordres →
  </a>
</div>
<?php endif; ?>

<div class="grid-2">
  <div class="card">
    <div class="card-title">Niveaux de stock</div>

    <?php foreach (array_slice($stock,0,8) as $s):
      $pct = $s['seuil_alerte']>0 ? min(100,round($s['quantite']/$s['seuil_alerte']*50)) : 100;
      $cls = $s['quantite']<=$s['seuil_alerte'] ? 'danger' : ($s['quantite']<=$s['seuil_alerte']*2 ? 'warn' : '');
    ?>

    <div class="progress-row">
      <div class="flex-between">
        <span><?= htmlspecialchars($s['nom']) ?></span>
        <span class="fw-bold"><?= $s['quantite'] ?> unités</span>
      </div>

      <div class="progress-bar">
        <div class="progress-fill <?= $cls ?>" style="width:<?= $pct ?>%;"></div>
      </div>
    </div>

    <?php endforeach; ?>

    <a href="<?= BASE ?>/view/usine/stock.php" class="btn btn-green mt-sm">
      Gérer le stock →
    </a>
  </div>

  <div class="card">
    <div class="card-title">Ordres actifs</div>

    <?php if (empty($ordresActifs)): ?>
      <p class="text-muted">Aucun ordre en cours.</p>
    <?php else: ?>

      <?php foreach (array_slice($ordresActifs,0,5) as $o): ?>
      <div class="list-item">
        <div class="flex-between">
          <div>
            <div class="fw-bold text-sm"><?= htmlspecialchars($o['produit_nom']) ?></div>
            <div class="text-xs text-muted">
              <?= $o['quantite'] ?> unités · <?= ucfirst($o['statut']) ?>
            </div>
          </div>

          <form action="<?= BASE ?>/controller/stock_controller.php?action=update_ordre" method="POST">
            <input type="hidden" name="ordre_id" value="<?= $o['id'] ?>"/>

            <?php if ($o['statut']==='demande'): ?>
              <button name="statut" value="en_cours" class="btn btn-blue btn-sm">▶ Démarrer</button>
            <?php elseif ($o['statut']==='en_cours'): ?>
              <button name="statut" value="termine" class="btn btn-green btn-sm">✓ Terminé</button>
            <?php endif; ?>
          </form>
        </div>
      </div>
      <?php endforeach; ?>

      <a href="<?= BASE ?>/view/usine/production.php" class="btn btn-green mt-1">
        Tous les ordres →
      </a>

    <?php endif; ?>
  </div>
</div>

<?php include "partials/usine_footer.php"; ?>