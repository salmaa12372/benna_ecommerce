<?php
$pageTitle = "Validation des Avis";
include "partials/nutri_header.php";
$avis = getAllAvis($cnx);
?>
<div class="topbar">
  <h1> Validation des Avis</h1>
  <span style="color:var(--muted);"><?= count(array_filter($avis,fn($a)=>!$a['valide'])) ?> en attente</span>
</div>
<div class="card">
  <table>
    <thead>
      <tr><th>Client</th><th>Produit</th><th>Note</th><th>Commentaire</th><th>Date</th><th>Statut</th><th>Action</th></tr>
    </thead>
    <tbody>
    <?php foreach ($avis as $a): ?>
    <tr style="<?= !$a['valide']?'background:#fffbeb;':'' ?>">
      <td><strong><?= htmlspecialchars($a['auteur']) ?></strong></td>
      <td><?= htmlspecialchars($a['produit_nom']) ?></td>
      <td style="color:#f59e0b;font-size:1.1rem;"><?= str_repeat('★',$a['note']) ?></td>
      <td style="font-size:.85rem;max-width:220px;"><?= htmlspecialchars($a['commentaire']??'–') ?></td>
      <td style="font-size:.82rem;"><?= date('d/m/Y',strtotime($a['created_at'])) ?></td>
      <td><span class="badge <?= $a['valide']?'badge-green':'badge-yellow' ?>"><?= $a['valide']?'Validé':'En attente' ?></span></td>
      <td>
        <?php if (!$a['valide']): ?>
          <a href="<?= BASE ?>/controller/conseil_controller.php?action=valider_avis&id=<?= $a['id'] ?>" class="btn btn-green">✓ Valider</a>
        <?php else: ?>
          <span style="color:var(--muted);font-size:.85rem;">–</span>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($avis)): ?><tr><td colspan="7" style="text-align:center;color:var(--muted);padding:1.5rem;">Aucun avis.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?php include "partials/nutri_footer.php"; ?>