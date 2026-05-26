<?php
// view/admin/livraisons.php
$pageTitle = "Livraisons";
include "partials/admin_header.php";

$livraisons = getAllLivraisons($cnx);
$livreurs   = getAllUsers($cnx, 'livreur');

// ── Stats summary ──────────────────────────────────────────────────────────
$totalLiv   = count($livraisons);
$enCours    = count(array_filter($livraisons, fn($l) => $l['statut'] === 'en_cours'));
$livrees    = count(array_filter($livraisons, fn($l) => $l['statut'] === 'livree'));
$echecs     = count(array_filter($livraisons, fn($l) => $l['statut'] === 'echec'));
$assignees  = count(array_filter($livraisons, fn($l) => in_array($l['statut'], ['assignee','acceptee'])));
$tauxSucces = $totalLiv > 0 ? round($livrees / $totalLiv * 100) : 0;

$badgeMap = [
    'assignee' => ['cls' => 'badge-warning',   'icon' => 'fa-clock',        'label' => 'Assignée'],
    'acceptee' => ['cls' => 'badge-info',       'icon' => 'fa-thumbs-up',    'label' => 'Acceptée'],
    'en_cours' => ['cls' => 'badge-primary',    'icon' => 'fa-truck',        'label' => 'En cours'],
    'livree'   => ['cls' => 'badge-success',    'icon' => 'fa-circle-check', 'label' => 'Livrée'],
    'echec'    => ['cls' => 'badge-danger',     'icon' => 'fa-times-circle', 'label' => 'Échec'],
];

// FIX: map role → human-readable label (never display raw 'admin')
$roleLabels = [
    'admin'          => 'Administrateur',
    'nutritionniste' => 'Nutritionniste',
    'livreur'        => 'Livreur',
    'usine'          => 'Responsable Usine',
    'client'         => 'Client',
];
?>

<style>
/* ── Layout ──────────────────────────────────────────────── */
.topbar          { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem; }
.topbar h1       { font-family:var(--font-head); font-size:1.55rem; display:flex; align-items:center; gap:.45rem; }
.topbar-right    { display:flex; align-items:center; gap:.6rem; }

/* FIX: role pill — reads "Administrateur" not "admin" */
.role-pill {
    display: flex; align-items: center; gap: .4rem;
    background: var(--card); border: 1px solid var(--border);
    border-radius: 10px; padding: .4rem .85rem;
    font-size: .82rem; font-weight: 600; color: var(--text);
}
.user-name-pill {
    font-size: .82rem; font-weight: 500; color: var(--muted);
}

/* ── Stat cards ───────────────────────────────────────────── */
.stats-row { display:grid; grid-template-columns:repeat(auto-fill,minmax(130px,1fr)); gap:.8rem; margin-bottom:1.3rem; }
.stat-card { background:var(--card); border-radius:var(--radius); padding:.9rem 1rem; box-shadow:var(--shadow); text-align:center; position:relative; overflow:hidden; }
.stat-val  { font-family:var(--font-head); font-size:1.7rem; font-weight:700; line-height:1; }
.stat-lbl  { font-size:.72rem; color:var(--muted); margin-top:.3rem; text-transform:uppercase; letter-spacing:.05em; }
.stat-ok   .stat-val { color:var(--green); }
.stat-warn .stat-val { color:var(--orange); }
.stat-err  .stat-val { color:var(--red); }
.stat-info .stat-val { color:var(--blue,#3b82f6); }
.stat-card::after { content:''; position:absolute; bottom:0; left:0; right:0; height:3px; background:var(--green); }
.stat-ok::after   { background:var(--green); }
.stat-warn::after { background:var(--orange); }
.stat-err::after  { background:var(--red); }
.stat-info::after { background:var(--blue,#3b82f6); }

/* ── Filter bar ───────────────────────────────────────────── */
.filter-bar { display:flex; gap:.6rem; margin-bottom:1rem; flex-wrap:wrap; align-items:center; }
.filter-bar input,
.filter-bar select {
    height: 36px; padding: 0 .75rem;
    border: 1px solid var(--border); border-radius: 9px;
    background: var(--card); color: var(--text); font-size: .83rem;
    transition: border-color .15s;
}
.filter-bar input  { flex: 1; min-width: 180px; }
.filter-bar input:focus,
.filter-bar select:focus { outline: none; border-color: var(--green); }
.btn-sm { height:36px; padding:0 1rem; border-radius:9px; border:1px solid var(--border); background:var(--card); color:var(--text); font-size:.83rem; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:.35rem; transition:.15s; text-decoration:none; }
.btn-sm:hover          { background:var(--bg); }
.btn-sm.btn-green      { background:var(--green); color:white; border-color:var(--green); }
.btn-sm.btn-green:hover{ background:var(--green-dark,#16a34a); }

/* ── Table ────────────────────────────────────────────────── */
.data-table { width:100%; border-collapse:collapse; font-size:.85rem; }
.data-table th {
    padding: .55rem .75rem; text-align: left;
    color: var(--muted); font-weight: 600; font-size: .73rem;
    text-transform: uppercase; letter-spacing: .05em;
    border-bottom: 2px solid var(--border); white-space: nowrap;
}
.data-table td { padding: .62rem .75rem; border-bottom: 1px solid var(--border); vertical-align: middle; }
.data-table tr:last-child td { border-bottom: none; }
.data-table tbody tr:hover td { background: var(--bg); }

/* ── Badges ───────────────────────────────────────────────── */
.badge { display:inline-flex; align-items:center; gap:4px; padding:.22rem .65rem; border-radius:20px; font-size:.72rem; font-weight:600; }
.badge-success  { background:#d1fae5; color:#065f46; }
.badge-warning  { background:#fef3c7; color:#92400e; }
.badge-danger   { background:#fee2e2; color:#991b1b; }
.badge-info     { background:#dbeafe; color:#1e40af; }
.badge-primary  { background:#ede9fe; color:#5b21b6; }
.badge-secondary{ background:#f3f4f6; color:#374151; }

/* ── Client cell ──────────────────────────────────────────── */
.client-cell { display:flex; align-items:center; gap:.5rem; }
.c-avatar    { width:30px; height:30px; border-radius:50%; background:#d1fae5; display:flex; align-items:center; justify-content:center; font-size:.68rem; font-weight:700; color:#065f46; flex-shrink:0; }

/* ── Livreur tag ──────────────────────────────────────────── */
.livreur-tag { background:var(--bg); border:1px solid var(--border); border-radius:8px; padding:.18rem .6rem; font-size:.78rem; font-weight:600; display:inline-flex; align-items:center; gap:4px; }

/* ── GPS button ───────────────────────────────────────────── */
.gps-btn { display:inline-flex; align-items:center; gap:4px; padding:.22rem .6rem; border:1px solid var(--green); border-radius:8px; color:var(--green); font-size:.76rem; font-weight:600; text-decoration:none; background:transparent; transition:.15s; }
.gps-btn:hover { background:#d1fae5; }
.no-gps { color:var(--muted); font-size:.8rem; }

/* ── Inline status update ─────────────────────────────────── */
.status-form        { display:flex; gap:.4rem; align-items:center; }
.status-form select { height:29px; padding:0 .4rem; font-size:.76rem; border:1px solid var(--border); border-radius:7px; background:var(--card); color:var(--text); }
.btn-update { height:29px; padding:0 .7rem; border-radius:7px; border:none; background:var(--green); color:white; font-size:.74rem; font-weight:600; cursor:pointer; transition:.15s; }
.btn-update:hover { opacity:.85; }

/* ── Misc ─────────────────────────────────────────────────── */
.addr-cell  { max-width:190px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; color:var(--muted); }
.date-muted { color:var(--muted); font-size:.78rem; white-space:nowrap; }
.count-badge{ background:var(--bg); border:1px solid var(--border); border-radius:20px; padding:.1rem .6rem; font-size:.75rem; font-weight:600; color:var(--muted); margin-left:auto; }
.empty-td   { text-align:center; padding:2.5rem !important; color:var(--muted); }
</style>

<!-- ═══════════════════════════════════════════════════════════
     TOPBAR
════════════════════════════════════════════════════════════ -->
<div class="topbar">
  <h1>
    <i class="fas fa-truck" style="color:var(--green);"></i>
    Livraisons
  </h1>
  <div class="topbar-right">
    <!-- FIX: shows "Administrateur" instead of raw "admin" -->
    <span class="role-pill">
      <i class="fas fa-shield-halved" style="color:var(--green);font-size:.8rem;"></i>
      <?= htmlspecialchars($roleLabels[$_SESSION['role'] ?? ''] ?? 'Utilisateur') ?>
    </span>
    <span class="user-name-pill">
      👤 <?= htmlspecialchars($_SESSION['nom'] ?? '') ?>
    </span>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     STATS
════════════════════════════════════════════════════════════ -->
<div class="stats-row">
  <div class="stat-card stat-info">
    <div class="stat-val"><?= $totalLiv ?></div>
    <div class="stat-lbl">Total</div>
  </div>
  <div class="stat-card stat-warn">
    <div class="stat-val"><?= $assignees ?></div>
    <div class="stat-lbl">Assignées</div>
  </div>
  <div class="stat-card" style="--accent:var(--blue,#3b82f6);">
    <div class="stat-val" style="color:var(--blue,#3b82f6);"><?= $enCours ?></div>
    <div class="stat-lbl">En cours</div>
  </div>
  <div class="stat-card stat-ok">
    <div class="stat-val"><?= $livrees ?></div>
    <div class="stat-lbl">Livrées</div>
  </div>
  <div class="stat-card stat-err">
    <div class="stat-val"><?= $echecs ?></div>
    <div class="stat-lbl">Échecs</div>
  </div>
  <div class="stat-card">
    <div class="stat-val"><?= $tauxSucces ?>%</div>
    <div class="stat-lbl">Taux succès</div>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     FILTER BAR
════════════════════════════════════════════════════════════ -->
<div class="filter-bar">
  <input
    type="text"
    id="filterSearch"
    placeholder="🔍  Chercher client ou livreur…"
    oninput="applyFilters()"
  />
  <select id="filterStatut" onchange="applyFilters()">
    <option value="">Tous les statuts</option>
    <option value="assignee">Assignée</option>
    <option value="acceptee">Acceptée</option>
    <option value="en_cours">En cours</option>
    <option value="livree">Livrée</option>
    <option value="echec">Échec</option>
  </select>
  <select id="filterLivreur" onchange="applyFilters()">
    <option value="">Tous les livreurs</option>
    <?php foreach ($livreurs as $lv): ?>
      <option value="<?= $lv['id'] ?>"><?= htmlspecialchars($lv['nom']) ?></option>
    <?php endforeach; ?>
  </select>
  <button class="btn-sm" onclick="resetFilters()">
    <i class="fas fa-undo"></i> Reset
  </button>
</div>

<!-- ═══════════════════════════════════════════════════════════
     TABLE CARD
════════════════════════════════════════════════════════════ -->
<div class="card">
  <div class="card-title" style="display:flex;align-items:center;gap:.5rem;">
    <i class="fas fa-list"></i> Toutes les livraisons
    <span class="count-badge" id="countBadge"><?= $totalLiv ?> livraison(s)</span>
  </div>

  <div class="table-wrapper">
    <table class="data-table">
      <thead>
        <tr>
          <th><i class="fas fa-hashtag"></i> Commande</th>
          <th><i class="fas fa-user"></i> Client</th>
          <th><i class="fas fa-motorcycle"></i> Livreur</th>
          <th><i class="fas fa-location-dot"></i> Adresse</th>
          <th><i class="fas fa-chart-line"></i> Statut</th>
          <th><i class="fas fa-map-marker-alt"></i> GPS</th>
          <th><i class="fas fa-money-bill-wave"></i> Total</th>
          <th><i class="fas fa-clock"></i> Mis à jour</th>
          <th><i class="fas fa-cogs"></i> Changer statut</th>
        </tr>
      </thead>
      <tbody id="livBody">

        <?php if (empty($livraisons)): ?>
        <tr>
          <td colspan="9" class="empty-td">
            <i class="fas fa-inbox" style="font-size:2rem;display:block;margin-bottom:.5rem;opacity:.4;"></i>
            Aucune livraison enregistrée.
          </td>
        </tr>
        <?php endif; ?>

        <?php foreach ($livraisons as $l):
          $b        = $badgeMap[$l['statut']] ?? ['cls'=>'badge-secondary','icon'=>'fa-circle','label'=>ucfirst($l['statut'])];
          $parts    = explode(' ', trim($l['client_nom']));
          $initials = strtoupper(substr($parts[0]??'',0,1) . substr($parts[1]??'',0,1));
        ?>
        <tr
          data-statut="<?= htmlspecialchars($l['statut']) ?>"
          data-livreur="<?= (int)($l['livreur_id'] ?? 0) ?>"
          data-search="<?= strtolower(htmlspecialchars($l['client_nom'] . ' ' . ($l['livreur_nom'] ?? ''))) ?>"
        >
          <!-- Commande # -->
          <td>
            <strong style="color:var(--green);">#<?= (int)$l['commande_id'] ?></strong>
          </td>

          <!-- Client -->
          <td>
            <div class="client-cell">
              <div class="c-avatar"><?= $initials ?></div>
              <span><?= htmlspecialchars($l['client_nom']) ?></span>
            </div>
          </td>

          <!-- Livreur -->
          <td>
            <?php if (!empty($l['livreur_nom'])): ?>
              <span class="livreur-tag">
                <i class="fas fa-user-tie" style="font-size:.7rem;color:var(--muted);"></i>
                <?= htmlspecialchars($l['livreur_nom']) ?>
              </span>
            <?php else: ?>
              <span class="badge badge-warning">
                <i class="fas fa-exclamation-circle"></i> Non assigné
              </span>
            <?php endif; ?>
          </td>

          <!-- Adresse -->
          <td>
            <span class="addr-cell" title="<?= htmlspecialchars($l['adresse_livraison']) ?>">
              <?= htmlspecialchars(mb_substr($l['adresse_livraison'], 0, 45)) ?>…
            </span>
          </td>

          <!-- Statut — "Livrée" FIX: uses $badgeMap label, never a raw DB string -->
          <td>
            <span class="badge <?= $b['cls'] ?>">
              <i class="fas <?= $b['icon'] ?>" style="font-size:.7rem;"></i>
              <?= htmlspecialchars($b['label']) ?>
            </span>
          </td>

          <!-- GPS -->
          <td>
            <?php if (!empty($l['latitude']) && !empty($l['longitude'])): ?>
              <a class="gps-btn"
                 href="https://maps.google.com/?q=<?= (float)$l['latitude'] ?>,<?= (float)$l['longitude'] ?>"
                 target="_blank" rel="noopener">
                <i class="fas fa-map-pin"></i> Voir
              </a>
            <?php else: ?>
              <span class="no-gps">–</span>
            <?php endif; ?>
          </td>

          <!-- Total -->
          <td>
            <strong><?= number_format((float)$l['total'], 3) ?> TND</strong>
          </td>

          <!-- Mis à jour -->
          <td class="date-muted">
            <i class="far fa-calendar-alt"></i>
            <?= date('d/m/Y H:i', strtotime($l['updated_at'])) ?>
          </td>

          <!-- Inline status change -->
          <td>
            <form class="status-form"
                  action="<?= BASE ?>/controller/livraison_controller.php?action=update_statut"
                  method="POST">
              <input type="hidden" name="livraison_id" value="<?= (int)$l['id'] ?>"/>
              <select name="statut" title="Changer le statut">
                <?php foreach ($badgeMap as $sKey => $sConf): ?>
                  <option value="<?= $sKey ?>" <?= $sKey === $l['statut'] ? 'selected' : '' ?>>
                    <?= $sConf['label'] ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <button type="submit" class="btn-update" title="Enregistrer">
                <i class="fas fa-check"></i>
              </button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>

      </tbody>
    </table>
  </div><!-- /.table-wrapper -->

  <!-- Empty message shown by JS when filters match nothing -->
  <div id="emptyMsg"
       style="display:none;text-align:center;padding:2rem;color:var(--muted);">
    <i class="fas fa-search"
       style="font-size:1.8rem;margin-bottom:.5rem;display:block;opacity:.4;"></i>
    Aucun résultat pour ces filtres.
  </div>
</div><!-- /.card -->

<script>
/* ── Client-side filtering ─────────────────────────────────── */
function applyFilters() {
    const q   = document.getElementById('filterSearch').value.toLowerCase().trim();
    const st  = document.getElementById('filterStatut').value;
    const lv  = document.getElementById('filterLivreur').value;
    const rows = document.querySelectorAll('#livBody tr[data-statut]');
    let visible = 0;

    rows.forEach(row => {
        const matchQ  = !q  || row.dataset.search.includes(q);
        const matchSt = !st || row.dataset.statut === st;
        const matchLv = !lv || row.dataset.livreur === lv;
        const show = matchQ && matchSt && matchLv;
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });

    document.getElementById('countBadge').textContent = visible + ' livraison(s)';
    document.getElementById('emptyMsg').style.display =
        (visible === 0 && rows.length > 0) ? 'block' : 'none';
}

function resetFilters() {
    document.getElementById('filterSearch').value  = '';
    document.getElementById('filterStatut').value  = '';
    document.getElementById('filterLivreur').value = '';
    applyFilters();
}
</script>

<?php include "partials/admin_footer.php"; ?>