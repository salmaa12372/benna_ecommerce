<?php
ob_start();
session_start();

// Check admin role
if (($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../../client/signup.php");
    exit;
}

// Include config files (app.php defines BASE correctly)
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../config/app.php";
require_once __DIR__ . "/../../controller/traitement.php";

// Handle toggle (Activer / Désactiver)
if (isset($_GET['toggle']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $newStatus = (isset($_GET['actif']) && $_GET['actif'] == 1) ? 0 : 1;
    $stmt = $cnx->prepare("UPDATE vip_abonnements SET actif = ? WHERE id = ?");
    $stmt->execute([$newStatus, $id]);
    $_SESSION['success'] = "Abonnement " . ($newStatus ? "activé" : "désactivé") . " avec succès.";
    header("Location: " . BASE . "/view/admin/vip.php");
    exit;
}

$pageTitle = "VIP";
include "partials/admin_header.php";

// Fetch all subscriptions
$abonnements = $cnx->query("
    SELECT va.id, u.nom AS client, va.niveau, va.date_debut, va.date_fin, va.actif,
           va.prix_mensuel, va.renouvellement
    FROM vip_abonnements va
    JOIN users u ON u.id = va.user_id
    ORDER BY va.created_at DESC
")->fetchAll();
?>

<div class="topbar">
  <h1><i class="fas fa-crown"></i> Abonnements VIP</h1>
</div>

<div class="card">
  <div class="card-title">
    <i class="fas fa-list"></i> Tous les abonnements
  </div>
  <div class="table-wrapper">
    <table class="data-table">
      <thead>
        <tr>
          <th>Client</th>
          <th>Niveau</th>
          <th>Prix (TND)</th>
          <th>Début</th>
          <th>Fin</th>
          <th>Renouvellement</th>
          <th>Statut</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($abonnements as $a): ?>
        <tr>
          <td><?= htmlspecialchars($a['client']) ?></td>
          <td><span class="badge" style="background:var(--gold); color:#2c2c2c;"><?= ucfirst($a['niveau']) ?></span></td>
          <td><?= number_format($a['prix_mensuel'], 3) ?></td>
          <td><?= date('d/m/Y', strtotime($a['date_debut'])) ?></td>
          <td><?= date('d/m/Y', strtotime($a['date_fin'])) ?></td>
          <td><?= $a['renouvellement'] ? 'Auto' : 'Manuel' ?></td>
          <td>
            <span class="badge <?= $a['actif'] ? 'badge-success' : 'badge-danger' ?>">
              <?= $a['actif'] ? 'Actif' : 'Inactif' ?>
            </span>
          </td>
          <td>
            <a href="?toggle=1&id=<?= $a['id'] ?>&actif=<?= $a['actif'] ?>"
               class="btn btn-sm <?= $a['actif'] ? 'btn-warning' : 'btn-green' ?>"
               onclick="return confirm('<?= $a['actif'] ? 'Désactiver' : 'Activer' ?> cet abonnement ?')">
              <?= $a['actif'] ? 'Désactiver' : 'Activer' ?>
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($abonnements)): ?>
          <tr><td colspan="8" class="empty-td">Aucun abonnement VIP.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include "partials/admin_footer.php"; ?>