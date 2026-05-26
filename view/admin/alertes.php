<?php
$pageTitle = "Alertes";
include "partials/admin_header.php";

// 1. Stock alerts
$stockAlertes = $cnx->query("
    SELECT s.id, p.nom, s.quantite, s.seuil_alerte,
           ROUND((s.quantite / NULLIF(s.seuil_alerte,0)) * 100) AS pct,
           p.id AS produit_id
    FROM stock s
    JOIN produits p ON p.id = s.produit_id
    WHERE s.quantite <= s.seuil_alerte
    ORDER BY pct ASC
")->fetchAll();

// 2. Réclamations ouvertes
$reclamations = $cnx->query("
    SELECT r.id, r.sujet, r.message, r.created_at, r.statut, u.nom AS client
    FROM reclamations r
    JOIN users u ON u.id = r.user_id
    WHERE r.statut = 'ouverte'
    ORDER BY r.created_at DESC
")->fetchAll();

// 3. Avis en attente
$avisEnAttente = $cnx->query("
    SELECT a.id, a.note, a.commentaire, a.created_at, u.nom AS client, p.nom AS produit
    FROM avis a
    JOIN users u ON u.id = a.user_id
    JOIN produits p ON p.id = a.produit_id
    WHERE a.valide = 0
    ORDER BY a.created_at DESC
")->fetchAll();

// 4. Commandes en attente
$commandesEnAttente = $cnx->query("
    SELECT c.id, c.total, c.date_commande, u.nom AS client
    FROM commandes c
    JOIN users u ON u.id = c.user_id
    WHERE c.statut = 'en_attente'
    ORDER BY c.date_commande ASC
")->fetchAll();

$totalAlerts = count($stockAlertes) + count($reclamations) + count($avisEnAttente);
?>

<style>
.topbar { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.4rem; }
.alert-section { background:var(--card); border-radius:var(--radius); margin-bottom:1.2rem; overflow:hidden; }
.sec-header { padding:1rem 1.2rem; border-bottom:2px solid var(--border); font-weight:600; background:var(--bg); }
.sec-body { padding:1rem 1.2rem; }
.badge-red { background:#fee2e2; color:#991b1b; }
.stock-row { display:flex; justify-content:space-between; align-items:center; padding:.5rem 0; border-bottom:1px solid var(--border); }
</style>

<div class="topbar">
  <h1><i class="fas fa-bell"></i> Centre d’alertes</h1>
  <span class="badge badge-danger"><?= $totalAlerts ?> alerte(s)</span>
</div>

<!-- Stock alerts -->
<div class="alert-section">
  <div class="sec-header"><i class="fas fa-exclamation-triangle"></i> Alertes stock (<?= count($stockAlertes) ?>)</div>
  <div class="sec-body">
    <?php if (empty($stockAlertes)): ?>
      <p class="empty-state">✅ Aucune alerte stock.</p>
    <?php else: ?>
      <?php foreach ($stockAlertes as $a): ?>
        <div class="stock-row">
          <strong><?= htmlspecialchars($a['nom']) ?></strong>
          <span>Stock : <?= $a['quantite'] ?> / Seuil : <?= $a['seuil_alerte'] ?></span>
          <a href="produits.php?edit=<?= $a['produit_id'] ?>" class="btn btn-sm btn-green">Réapprovisionner</a>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<!-- Réclamations -->
<div class="alert-section">
  <div class="sec-header"><i class="fas fa-flag"></i> Réclamations ouvertes (<?= count($reclamations) ?>)</div>
  <div class="sec-body">
    <?php if (empty($reclamations)): ?>
      <p class="empty-state">✅ Aucune réclamation.</p>
    <?php else: ?>
      <?php foreach ($reclamations as $r): ?>
        <div style="margin-bottom:.8rem; border-left:3px solid var(--orange); padding-left:.8rem;">
          <div><strong><?= htmlspecialchars($r['sujet']) ?></strong> – <?= htmlspecialchars($r['client']) ?></div>
          <div class="comment-content"><?= htmlspecialchars(mb_substr($r['message'],0,100)) ?>…</div>
          <a href="reclamations.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline">Traiter</a>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<!-- Avis en attente -->
<div class="alert-section">
  <div class="sec-header"><i class="fas fa-star"></i> Avis à modérer (<?= count($avisEnAttente) ?>)</div>
  <div class="sec-body">
    <?php if (empty($avisEnAttente)): ?>
      <p class="empty-state">✅ Aucun avis en attente.</p>
    <?php else: ?>
      <?php foreach ($avisEnAttente as $a): ?>
        <div style="margin-bottom:.8rem;">
          <div><strong><?= htmlspecialchars($a['produit']) ?></strong> – <?= str_repeat('★', $a['note']) ?> – <?= htmlspecialchars($a['client']) ?></div>
          <div class="comment-content"><?= htmlspecialchars(mb_substr($a['commentaire'],0,120)) ?></div>
          <div>
            <a href="<?= BASE ?>/controller/conseil_controller.php?action=valider_avis&id=<?= $a['id'] ?>" class="btn btn-sm btn-green">Valider</a>
            <a href="<?= BASE ?>/controller/commande_controller.php?action=delete_avis&id=<?= $a['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Supprimer ?')">Rejeter</a>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<!-- Commandes en attente -->
<div class="alert-section">
  <div class="sec-header"><i class="fas fa-clock"></i> Commandes en attente (<?= count($commandesEnAttente) ?>)</div>
  <div class="sec-body">
    <?php if (empty($commandesEnAttente)): ?>
      <p class="empty-state">✅ Aucune commande en attente.</p>
    <?php else: ?>
      <?php foreach ($commandesEnAttente as $c): ?>
        <div class="stock-row">
          <span><strong>#<?= $c['id'] ?></strong> – <?= htmlspecialchars($c['client']) ?> – <?= number_format($c['total'],3) ?> TND</span>
          <a href="commandes.php?edit=<?= $c['id'] ?>" class="btn btn-sm btn-green">Confirmer</a>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<?php include "partials/admin_footer.php"; ?>