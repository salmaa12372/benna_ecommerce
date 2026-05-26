<?php
// view/admin/commandes.php
$pageTitle = "Commandes";
include "partials/admin_header.php";

$commandes     = getAllCommandes($cnx);
$livreurs      = getAllUsers($cnx, 'livreur');

// FIX: proper French labels — never display raw DB values
$statutOptions = ['en_attente','confirmee','en_preparation','expedie','en_livraison','livre','annulee'];
$statutLabels  = [
    'en_attente'     => 'En attente',
    'confirmee'      => 'Confirmée',
    'en_preparation' => 'En préparation',
    'expedie'        => 'Expédiée',
    'en_livraison'   => 'En livraison',
    'livre'          => 'Livrée',       // FIX: was "Livrée" raw → now consistent
    'annulee'        => 'Annulée',
];
$badgeMap = [
    'livre'          => 'badge-success',
    'en_attente'     => 'badge-warning',
    'annulee'        => 'badge-danger',
    'en_livraison'   => 'badge-info',
    'en_preparation' => 'badge-secondary',
    'confirmee'      => 'badge-success',
    'expedie'        => 'badge-info',
];
?>

<style>
.topbar { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.4rem; }
.topbar h1 { font-family:var(--font-head); font-size:1.5rem; display:flex; align-items:center; gap:.45rem; }
.user-badge { background:var(--card); border:1px solid var(--border); border-radius:10px; padding:.4rem .85rem; font-size:.82rem; font-weight:600; }

.filter-bar { display:flex; gap:.6rem; margin-bottom:1rem; flex-wrap:wrap; align-items:center; }
.filter-bar input, .filter-bar select { height:36px; padding:0 .75rem; border:1px solid var(--border); border-radius:9px; background:var(--card); color:var(--text); font-size:.83rem; }
.filter-bar input { flex:1; min-width:180px; }
.filter-bar input:focus, .filter-bar select:focus { outline:none; border-color:var(--green); }
.btn-sm { height:36px; padding:0 1rem; border-radius:9px; border:1px solid var(--border); background:var(--card); color:var(--text); font-size:.83rem; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:.35rem; transition:.15s; text-decoration:none; }
.btn-sm:hover { background:var(--bg); }

.data-table { width:100%; border-collapse:collapse; font-size:.85rem; }
.data-table th { padding:.55rem .75rem; text-align:left; color:var(--muted); font-weight:600; font-size:.73rem; text-transform:uppercase; letter-spacing:.05em; border-bottom:2px solid var(--border); white-space:nowrap; }
.data-table td { padding:.62rem .75rem; border-bottom:1px solid var(--border); vertical-align:middle; }
.data-table tr:last-child td { border-bottom:none; }
.data-table tbody tr:hover td { background:var(--bg); }

.client-info  { display:flex; align-items:center; gap:.5rem; }
.client-avatar{ width:30px; height:30px; border-radius:50%; background:#d1fae5; display:flex; align-items:center; justify-content:center; font-size:.68rem; font-weight:700; color:#065f46; flex-shrink:0; }
.client-name  { font-weight:600; font-size:.85rem; }
.client-email { font-size:.75rem; color:var(--muted); }

.badge { display:inline-flex; align-items:center; gap:3px; padding:.22rem .65rem; border-radius:20px; font-size:.72rem; font-weight:600; }
.badge-success   { background:#d1fae5; color:#065f46; }
.badge-warning   { background:#fef3c7; color:#92400e; }
.badge-danger    { background:#fee2e2; color:#991b1b; }
.badge-info      { background:#dbeafe; color:#1e40af; }
.badge-secondary { background:#f3f4f6; color:#374151; }

.address-cell { max-width:190px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; color:var(--muted); font-size:.82rem; }
.date-cell    { color:var(--muted); font-size:.8rem; white-space:nowrap; }

.action-form  { display:flex; align-items:center; gap:.4rem; margin-bottom:.3rem; }
.form-control-sm { height:28px; padding:0 .4rem; font-size:.75rem; border:1px solid var(--border); border-radius:7px; background:var(--card); color:var(--text); }
.action-btn   { height:28px; padding:0 .7rem; border-radius:7px; border:none; font-size:.74rem; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:4px; transition:.15s; }
.validate-btn { background:var(--green); color:white; }
.validate-btn:hover { opacity:.85; }
.btn-yellow   { background:#fbbf24; color:#78350f; }
.btn-yellow:hover { background:#f59e0b; }

.count-pill { background:var(--bg); border:1px solid var(--border); border-radius:20px; padding:.1rem .6rem; font-size:.75rem; font-weight:600; color:var(--muted); margin-left:auto; }
.empty-td { text-align:center; padding:2.5rem; color:var(--muted); }
</style>

<!-- TOPBAR -->
<div class="topbar">
  <h1>
    <i class="fas fa-box" style="color:var(--green);"></i>
    Gestion des Commandes
  </h1>
  <span class="user-badge">📦 <?= count($commandes) ?> commande(s)</span>
</div>

<!-- FILTER BAR -->
<div class="filter-bar">
  <input type="text" id="filterSearch" placeholder="🔍  Chercher client…" oninput="applyFilters()"/>
  <select id="filterStatut" onchange="applyFilters()">
    <option value="">Tous les statuts</option>
    <?php foreach ($statutOptions as $s): ?>
      <option value="<?= $s ?>"><?= $statutLabels[$s] ?></option>
    <?php endforeach; ?>
  </select>
  <button class="btn-sm" onclick="resetFilters()"><i class="fas fa-undo"></i> Reset</button>
</div>

<!-- TABLE -->
<div class="card">
  <div class="card-title" style="display:flex;align-items:center;gap:.5rem;">
    <i class="fas fa-list"></i> Toutes les commandes
    <span class="count-pill" id="countBadge"><?= count($commandes) ?> commande(s)</span>
  </div>
  <div class="table-wrapper">
    <table class="data-table">
      <thead>
        <tr>
          <th><i class="fas fa-hashtag"></i> #</th>
          <th><i class="fas fa-user"></i> Client</th>
          <th><i class="fas fa-money-bill"></i> Total</th>
          <th><i class="fas fa-location-dot"></i> Adresse</th>
          <th><i class="fas fa-chart-line"></i> Statut</th>
          <th><i class="fas fa-calendar"></i> Date</th>
          <th><i class="fas fa-cogs"></i> Actions</th>
        </tr>
      </thead>
      <tbody id="cmdBody">
        <?php if (empty($commandes)): ?>
        <tr><td colspan="7" class="empty-td"><i class="fas fa-inbox" style="font-size:2rem;display:block;margin-bottom:.5rem;opacity:.4;"></i>Aucune commande.</td></tr>
        <?php endif; ?>

        <?php foreach ($commandes as $c):
          $parts    = explode(' ', trim($c['client_nom']));
          $initials = strtoupper(substr($parts[0]??'',0,1) . substr($parts[1]??'',0,1));
        ?>
        <tr
          data-statut="<?= $c['statut'] ?>"
          data-search="<?= strtolower(htmlspecialchars($c['client_nom'])) ?>"
        >
          <td><strong style="color:var(--green);">#<?= $c['id'] ?></strong></td>
          <td>
            <div class="client-info">
              <div class="client-avatar"><?= $initials ?></div>
              <div>
                <div class="client-name"><?= htmlspecialchars($c['client_nom']) ?></div>
                <?php if (!empty($c['client_email'])): ?>
                  <div class="client-email"><?= htmlspecialchars($c['client_email']) ?></div>
                <?php endif; ?>
              </div>
            </div>
          </td>
          <td><strong><?= number_format($c['total'], 3) ?> TND</strong></td>
          <td>
            <span class="address-cell" title="<?= htmlspecialchars($c['adresse_livraison']) ?>">
              <?= htmlspecialchars(mb_substr($c['adresse_livraison'], 0, 45)) ?>…
            </span>
          </td>
          <td>
            <!-- FIX: $statutLabels gives "Livrée" not raw "livre" -->
            <span class="badge <?= $badgeMap[$c['statut']] ?? 'badge-secondary' ?>">
              <?= $statutLabels[$c['statut']] ?? ucfirst(str_replace('_', ' ', $c['statut'])) ?>
            </span>
          </td>
          <td class="date-cell">
            <i class="far fa-calendar-alt"></i>
            <?= date('d/m/Y H:i', strtotime($c['date_commande'])) ?>
          </td>
          <td>
            <!-- Change status -->
            <form action="<?= BASE ?>/controller/commande_controller.php?action=update_statut"
                  method="POST" class="action-form">
              <input type="hidden" name="commande_id" value="<?= $c['id'] ?>"/>
              <select name="statut" class="form-control-sm">
                <?php foreach ($statutOptions as $s): ?>
                  <option value="<?= $s ?>" <?= $s===$c['statut']?'selected':'' ?>>
                    <?= $statutLabels[$s] ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <button type="submit" class="action-btn validate-btn" title="Enregistrer">
                <i class="fas fa-check"></i>
              </button>
            </form>
            <!-- Assign livreur -->
            <?php if (in_array($c['statut'], ['confirmee','en_preparation','expedie'])): ?>
            <form action="<?= BASE ?>/controller/commande_controller.php?action=assigner_livreur"
                  method="POST" class="action-form">
              <input type="hidden" name="commande_id" value="<?= $c['id'] ?>"/>
              <select name="livreur_id" class="form-control-sm">
                <option value="">— Assigner livreur —</option>
                <?php foreach ($livreurs as $l): ?>
                  <option value="<?= $l['id'] ?>"><?= htmlspecialchars($l['nom']) ?></option>
                <?php endforeach; ?>
              </select>
              <button type="submit" class="action-btn btn-yellow" title="Assigner">
                <i class="fas fa-truck"></i>
              </button>
            </form>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div id="emptyMsg" style="display:none;text-align:center;padding:2rem;color:var(--muted);">
    <i class="fas fa-search" style="font-size:1.8rem;margin-bottom:.5rem;display:block;opacity:.4;"></i>
    Aucun résultat pour ces filtres.
  </div>
</div>

<script>
function applyFilters() {
    const q   = document.getElementById('filterSearch').value.toLowerCase().trim();
    const st  = document.getElementById('filterStatut').value;
    const rows = document.querySelectorAll('#cmdBody tr[data-statut]');
    let visible = 0;
    rows.forEach(row => {
        const show = (!q || row.dataset.search.includes(q)) && (!st || row.dataset.statut === st);
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });
    document.getElementById('countBadge').textContent = visible + ' commande(s)';
    document.getElementById('emptyMsg').style.display = (visible===0 && rows.length>0) ? 'block' : 'none';
}
function resetFilters() {
    document.getElementById('filterSearch').value = '';
    document.getElementById('filterStatut').value = '';
    applyFilters();
}
</script>

<?php include "partials/admin_footer.php"; ?>