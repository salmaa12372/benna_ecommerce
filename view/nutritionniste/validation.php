<?php
$pageTitle = "Validation Produits";
include "partials/nutri_header.php";

// ── Handle POST ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['produit_id']) && isset($_POST['action_val'])) {
        $pid = (int)$_POST['produit_id'];
        if ($_POST['action_val'] === 'valider') {
            $ok = validerProduit($cnx, $pid);
            $_SESSION[$ok ? 'success' : 'error'] = $ok ? "✅ Produit validé et publié." : "Erreur lors de la validation.";
        } elseif ($_POST['action_val'] === 'rejeter') {
            $motif = trim($_POST['motif'] ?? '');
            if (empty($motif)) {
                $_SESSION['error'] = "Veuillez indiquer un motif de rejet.";
            } else {
                $ok = rejeterProduit($cnx, $pid, $motif);
                $_SESSION[$ok ? 'success' : 'error'] = $ok ? "❌ Produit rejeté." : "Erreur lors du rejet.";
            }
        }
    }
    header("Location: " . BASE . "/view/nutritionniste/validation.php"); exit();
}

$enAttente = getProduitsPourValidation($cnx);
$rejetes   = getProduitsRejetes($cnx);
$valides   = $cnx->query("SELECT COUNT(*) FROM produits WHERE est_valide=1")->fetchColumn();
?>

<style>
/* ── Tabs ── */
.val-tabs { display:flex; gap:.5rem; margin-bottom:1.5rem; }
.val-tab  {
  padding:.55rem 1.3rem; border-radius:10px; font-weight:600; font-size:.88rem;
  cursor:pointer; border:2px solid transparent;
  background:var(--card-bg,#fff); color:var(--muted);
  transition:.2s;
}
.val-tab.active { background:var(--green,#2d6a4f); color:#fff; border-color:var(--green,#2d6a4f); }
.val-panel { display:none; }
.val-panel.active { display:block; }

/* ── Reject modal ── */
.modal-overlay {
  display:none; position:fixed; inset:0;
  background:rgba(0,0,0,.45); z-index:1000;
  align-items:center; justify-content:center;
}
.modal-overlay.open { display:flex; }
.modal-box {
  background:#fff; border-radius:18px;
  padding:2rem; width:min(480px,95vw);
  box-shadow:0 20px 60px rgba(0,0,0,.2);
}
.modal-box h3 { margin:0 0 .5rem; color:#c0392b; }
.modal-box p  { font-size:.88rem; color:#555; margin-bottom:1rem; }
.modal-box textarea {
  width:100%; border-radius:10px; border:1.5px solid #ddd;
  padding:.75rem; font-size:.9rem; resize:vertical;
  font-family:inherit; min-height:90px;
  box-sizing:border-box;
}
.modal-box textarea:focus { outline:none; border-color:#e74c3c; }
.modal-actions { display:flex; gap:.75rem; margin-top:1.2rem; justify-content:flex-end; }
.btn-cancel { padding:.5rem 1.2rem; border:2px solid #ccc; border-radius:10px; background:none; cursor:pointer; font-weight:600; }
.btn-reject { padding:.5rem 1.4rem; border:none; border-radius:10px; background:#e74c3c; color:#fff; font-weight:700; cursor:pointer; }

/* ── Rejected badge ── */
.tag-rejet {
  display:inline-block; background:#fdecea; color:#c0392b;
  border-radius:8px; padding:.15rem .55rem;
  font-size:.74rem; font-weight:700; margin-bottom:.3rem;
}
.motif-text { font-size:.78rem; color:#c0392b; font-style:italic; }
</style>

<!-- Topbar -->
<div class="topbar">
  <h1>Validation des Produits</h1>
  <div style="display:flex;gap:1rem;align-items:center;flex-wrap:wrap;">
    <span class="badge badge-green"><?= $valides ?> validés</span>
    <span class="badge badge-yellow"><?= count($enAttente) ?> en attente</span>
    <span class="badge badge-red"><?= count($rejetes) ?> rejetés</span>
  </div>
</div>

<!-- Tabs -->
<div class="val-tabs">
  <button class="val-tab active" onclick="switchTab('attente',this)">
     En attente (<?= count($enAttente) ?>)
  </button>
  <button class="val-tab" onclick="switchTab('rejetes',this)">
     Rejetés (<?= count($rejetes) ?>)
  </button>
</div>

<!-- ══ TAB 1 : EN ATTENTE ══ -->
<div class="val-panel active" id="panel-attente">
<div class="card">
  <div class="card-title">🔍 Produits en attente de validation nutritionnelle</div>

  <?php if (empty($enAttente)): ?>
    <div style="text-align:center;padding:2.5rem;color:var(--muted);">
       Tous les produits ont été traités !
    </div>
  <?php else: ?>
  <table>
    <thead>
      <tr>
        <th>Image</th>
        <th>Produit</th>
        <th>Catégorie</th>
        <th>Prix</th>
        <th>Régime</th>
        <th>Valeurs nutritives</th>
        <th style="min-width:180px;">Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($enAttente as $p): ?>
    <tr>
      <td>
        <img src="<?= BASE ?>/public/uploads/produits/pics/<?= $p['id'] ?>.jpg"
             style="width:52px;height:52px;object-fit:cover;border-radius:8px;"
             onerror="this.src='https://placehold.co/52x52/e8f0e3/2c5e2e?text=🌿'"/>
      </td>
      <td>
        <div style="font-weight:600;"><?= htmlspecialchars($p['nom']) ?></div>
        <div style="font-size:.78rem;color:var(--muted);">
          <?= htmlspecialchars(mb_substr($p['description'] ?? '', 0, 65)) ?>…
        </div>
      </td>
      <td><?= htmlspecialchars($p['cat_nom'] ?? '–') ?></td>
      <td><?= number_format($p['prix'], 3) ?> TND</td>
      <td style="font-size:.78rem;"><?= htmlspecialchars($p['regime'] ?? '–') ?></td>
      <td style="font-size:.78rem;">
        <?php if (!empty($p['calories']) && $p['calories'] > 0): ?>
          <?= $p['calories'] ?> kcal<br/>
          <?= $p['proteines'] ?>g protéines<br/>
          <?= $p['glucides'] ?>g glucides
        <?php else: ?>
          <span style="color:var(--muted);">Non renseigné</span>
        <?php endif; ?>
      </td>
      <td>
        <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
          <!-- VALIDER -->
          <form method="POST" style="display:inline;">
            <input type="hidden" name="produit_id"  value="<?= $p['id'] ?>"/>
            <input type="hidden" name="action_val"  value="valider"/>
            <button type="submit" class="btn btn-green" style="font-size:.82rem;padding:.4rem .85rem;">
              Valider
            </button>
          </form>
          <!-- REJETER → ouvre modal -->
          <button class="btn btn-red" style="font-size:.82rem;padding:.4rem .85rem;"
                  onclick="openRejectModal(<?= $p['id'] ?>, '<?= htmlspecialchars($p['nom'], ENT_QUOTES) ?>')">
            Rejeter
          </button>
        </div>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>
</div>

<!-- ══ TAB 2 : REJETÉS ══ -->
<div class="val-panel" id="panel-rejetes">
<div class="card">
  <div class="card-title">Produits rejetés</div>

  <?php if (empty($rejetes)): ?>
    <div style="text-align:center;padding:2.5rem;color:var(--muted);">
      Aucun produit rejeté.
    </div>
  <?php else: ?>
  <table>
    <thead>
      <tr>
        <th>Image</th>
        <th>Produit</th>
        <th>Catégorie</th>
        <th>Motif de rejet</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($rejetes as $p): ?>
    <tr>
      <td>
        <img src="<?= BASE ?>/public/uploads/produits/pics/<?= $p['id'] ?>.jpg"
             style="width:52px;height:52px;object-fit:cover;border-radius:8px;opacity:.6;"
             onerror="this.src='https://placehold.co/52x52/fde8e8/c0392b?text=✕'"/>
      </td>
      <td>
        <span class="tag-rejet">Rejeté</span><br/>
        <strong><?= htmlspecialchars($p['nom']) ?></strong>
      </td>
      <td><?= htmlspecialchars($p['cat_nom'] ?? '–') ?></td>
      <td>
        <?php $motif = $p['motif_rejet'] ?? ''; ?>
        <?php if ($motif): ?>
          <span class="motif-text">"<?= htmlspecialchars($motif) ?>"</span>
        <?php else: ?>
          <span style="color:var(--muted);font-size:.8rem;">—</span>
        <?php endif; ?>
      </td>
      <td>
        <!-- Re-mettre en attente (est_valide=0) pour revalidation -->
        <form method="POST" style="display:inline;">
          <input type="hidden" name="produit_id" value="<?= $p['id'] ?>"/>
          <input type="hidden" name="action_val"  value="valider"/>
          <button type="submit" class="btn btn-green" style="font-size:.8rem;padding:.35rem .8rem;">
            ↩ Valider quand même
          </button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>
</div>

<!-- ══ MODAL REJET ══ -->
<div class="modal-overlay" id="reject-modal">
  <div class="modal-box">
    <h3> Rejeter le produit</h3>
    <p id="reject-modal-name" style="font-weight:600;color:#333;"></p>
    <p>Indiquez le motif de rejet. L'administrateur sera informé.</p>
    <form method="POST" id="reject-form">
      <input type="hidden" name="produit_id"  id="reject-pid"/>
      <input type="hidden" name="action_val"  value="rejeter"/>
      <textarea name="motif" id="reject-motif"
                placeholder="Ex : valeurs nutritionnelles manquantes, allergènes non déclarés, description insuffisante…"
                required></textarea>
      <div class="modal-actions">
        <button type="button" class="btn-cancel" onclick="closeRejectModal()">Annuler</button>
        <button type="submit" class="btn-reject">Confirmer le rejet</button>
      </div>
    </form>
  </div>
</div>

<script>
function switchTab(tab, btn) {
  document.querySelectorAll('.val-panel').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.val-tab').forEach(b => b.classList.remove('active'));
  document.getElementById('panel-' + tab).classList.add('active');
  btn.classList.add('active');
}
function openRejectModal(id, nom) {
  document.getElementById('reject-pid').value   = id;
  document.getElementById('reject-modal-name').textContent = nom;
  document.getElementById('reject-motif').value = '';
  document.getElementById('reject-modal').classList.add('open');
}
function closeRejectModal() {
  document.getElementById('reject-modal').classList.remove('open');
}
document.getElementById('reject-modal').addEventListener('click', function(e) {
  if (e.target === this) closeRejectModal();
});
</script>

<?php include "partials/nutri_footer.php"; ?>