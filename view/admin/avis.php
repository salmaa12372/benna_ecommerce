<?php
$pageTitle = "Avis Clients";
include "partials/admin_header.php";
include_once __DIR__ . "/../../controller/traitement.php";
$avis = getAllAvis($cnx);
$pendingCount = count(array_filter($avis, fn($a) => !$a['valide']));
?>
<div class="topbar">
  <h1><i class="fas fa-star"></i> Avis Clients</h1>
  <?php if ($pendingCount > 0): ?>
    <span class="pending-badge"><i class="fas fa-clock"></i> <?= $pendingCount ?> en attente</span>
  <?php else: ?>
    <span class="all-valid-badge"><i class="fas fa-check-circle"></i> Tout validé</span>
  <?php endif; ?>
</div>

<div class="card">
  <div class="card-title">Tous les avis</div>
  <div class="table-wrapper">
    <table>
      <thead><tr><th>Client</th><th>Produit</th><th>Note</th><th>Commentaire</th><th>Date</th><th>Statut</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($avis as $a): ?>
      <tr class="<?= !$a['valide'] ? 'pending-row' : '' ?>">
        <td><?= htmlspecialchars($a['auteur']) ?></td>
        <td><?= htmlspecialchars($a['produit_nom']) ?></td>
        <td><?= str_repeat('★', $a['note']) . str_repeat('☆', 5-$a['note']) ?></td>
        <td><?= htmlspecialchars($a['commentaire'] ?? '–') ?></td>
        <td><?= date('d/m/Y', strtotime($a['created_at'])) ?></td>
        <td><span class="status-badge <?= $a['valide'] ? 'status-valid' : 'status-pending' ?>"><?= $a['valide'] ? 'Validé' : 'En attente' ?></span></td>
        <td>
          <?php if (!$a['valide']): ?>
            <a href="<?= BASE ?>/controller/traitement.php?action=valider_avis&id=<?= $a['id'] ?>" class="action-btn validate-btn">✓ Valider</a>
          <?php endif; ?>
          <a href="<?= BASE ?>/controller/traitement.php?action=delete_avis&id=<?= $a['id'] ?>" onclick="return confirm('Supprimer ?')" class="action-btn delete-btn">🗑 Supprimer</a>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($avis)): ?><tr><td colspan="7">Aucun avis.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include "partials/admin_footer.php"; ?>