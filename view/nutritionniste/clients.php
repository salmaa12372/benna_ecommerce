<?php
$pageTitle = "Profils Clients";
include "partials/nutri_header.php";
$clients = getClientsAvecCommandes($cnx);
$alertes = getAlertesNutrition($cnx);

// Récupérer les produits commandés par chaque client
$produitsParClient = [];
$stmt = $cnx->query("
    SELECT c.user_id, p.nom AS produit 
    FROM commande_details cd
    JOIN commandes c ON c.id = cd.commande_id
    JOIN produits p ON p.id = cd.produit_id
    GROUP BY c.user_id, cd.produit_id
");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $produitsParClient[$row['user_id']][] = $row['produit'];
}
?>
<div class="topbar">
  <h1>Profils Clients</h1>
  <span style="color:var(--muted);"><?= count($clients) ?> client(s)</span>
</div>
<div class="card">
  <div class="card-title">Habitudes alimentaires & Commandes</div>
  <table>
    <thead>
      <tr>
        <th>Client</th>
        <th>Email</th>
        <th>Téléphone</th>
        <th>Adresse</th>
        <th>Inscrit le</th>
        <th>Produits commandés</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($clients as $cl): ?>
    <tr>
      <td><strong><?= htmlspecialchars($cl['nom']) ?></strong></td>
      <td style="font-size:.85rem;"><?= htmlspecialchars($cl['email']) ?></td>
      <td style="font-size:.85rem;"><?= htmlspecialchars($cl['telephone'] ?? '—') ?></td>
      <td style="font-size:.82rem;max-width:150px;"><?= htmlspecialchars($cl['adresse'] ?? '—') ?></td>
      <td style="font-size:.82rem;"><?= date('d/m/Y', strtotime($cl['created_at'])) ?></td>
      <td style="font-size:0.9em;line-height:1.4;max-width:200px;">
        <?php if (!empty($produitsParClient[$cl['id']])): ?>
          <?= implode(',  ', array_map('htmlspecialchars', $produitsParClient[$cl['id']])) ?>
        <?php else: ?>
          <span style="color:var(--muted);">Aucune commande</span>
        <?php endif; ?>
      </td>
      <td>
        <a href="<?= BASE ?>/view/nutritionniste/plans.php?client_id=<?= $cl['id'] ?>" class="btn btn-green" style="font-size:.78rem;padding:.3rem .7rem;">📋 Créer plan</a>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($clients)): ?>
      <tr><td colspan="7" style="text-align:center;color:var(--muted);padding:1.5rem;">Aucun client inscrit.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Alertes récentes -->
<div class="card">
  <div class="card-title">Dernières alertes nutritionnelles envoyées</div>
  <?php foreach (array_slice($alertes,0,5) as $a): ?>
  <div style="padding:.7rem 0;border-bottom:1px solid var(--border);">
    <div style="display:flex;justify-content:space-between;align-items:center;">
      <div>
        <span style="font-weight:600;"><?= htmlspecialchars($a['titre']) ?></span>
        <?php if (!empty($a['client_nom'])): ?>
          — <span style="font-size:.85rem;color:var(--muted);">👤 <?= htmlspecialchars($a['client_nom']) ?></span>
        <?php endif; ?>
      </div>
      <span class="badge <?= match($a['gravite']){'info'=>'badge-blue','attention'=>'badge-yellow','urgent'=>'badge-red',default=>'badge-gray'} ?>">
        <?= ucfirst($a['gravite']) ?>
      </span>
    </div>
    <div style="font-size:.8rem;color:var(--muted);margin-top:.2rem;">
      <?= htmlspecialchars(mb_substr($a['message'],0,80)) ?>...
    </div>
  </div>
  
  <?php endforeach; ?>
  <?php if (empty($alertes)): ?>
    <p style="color:var(--muted);text-align:center;padding:1rem;">Aucune alerte envoyée.</p>
  <?php endif; ?>
</div>

<?php include "partials/nutri_footer.php"; ?>