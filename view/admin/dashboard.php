<?php
$pageTitle = "Dashboard";
include "partials/admin_header.php";

$stats       = getStats($cnx);

$revenuTotal = $cnx->query("
    SELECT COALESCE(SUM(total), 0) as revenu
    FROM commandes
    WHERE paiement_statut = 'paye'
")->fetchColumn();

$clients  = $cnx->query("SELECT COUNT(*) FROM users WHERE role='client'")->fetchColumn();
$produits = $cnx->query("SELECT COUNT(*) FROM produits")->fetchColumn();
$commandes = $cnx->query("SELECT COUNT(*) FROM commandes")->fetchColumn();
$dernieresCommandes = array_slice(getAllCommandes($cnx), 0, 6);
$alertesStock       = getStockAlertes($cnx);

$livreurs = $cnx->query("
    SELECT u.id, u.nom, u.email, u.telephone, u.created_at,
           COUNT(l.id)                                          AS total_livraisons,
           SUM(l.statut = 'en_cours')                          AS en_cours,
           SUM(l.statut = 'livree')                            AS livrees,
           SUM(l.statut = 'echec')                             AS echecs,
           SUM(l.statut IN ('assignee','acceptee','en_cours')) AS actives
    FROM users u
    LEFT JOIN livraisons l ON l.livreur_id = u.id
    WHERE u.role = 'livreur'
    GROUP BY u.id
    ORDER BY actives DESC, total_livraisons DESC
")->fetchAll();

$alertesCount   = (int)$cnx->query("SELECT COUNT(*) FROM stock WHERE quantite <= seuil_alerte")->fetchColumn();
$reclamCount    = (int)$stats['reclamations'];
$avisCount      = (int)$stats['avis_a_valider'];
$enAttenteCount = (int)$cnx->query("SELECT COUNT(*) FROM commandes WHERE statut='en_attente'")->fetchColumn();
$totalAlerts    = $alertesCount + $reclamCount + $avisCount;

// Revenue curve — last 30 days
$sparkline = $cnx->query("
    SELECT DATE(date_commande) AS jour, COALESCE(SUM(total),0) AS rev
    FROM commandes
    WHERE paiement_statut='paye' AND date_commande >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY jour ORDER BY jour
")->fetchAll(PDO::FETCH_KEY_PAIR);

$sparkVals   = [];
$sparkLabels = [];
for ($d = 29; $d >= 0; $d--) {
    $key           = date('Y-m-d', strtotime("-$d days"));
    $sparkVals[]   = round((float)($sparkline[$key] ?? 0), 3);
    $sparkLabels[] = date('d/m', strtotime("-$d days"));
}

$roleLabels = [
    'admin'          => 'Administrateur',
    'nutritionniste' => 'Nutritionniste',
    'livreur'        => 'Livreur',
    'usine'          => 'Responsable Usine',
    'client'         => 'Client',
];
$currentRoleLabel = $roleLabels[$_SESSION['role'] ?? ''] ?? 'Utilisateur';

$statusLabels = [
    'livre'          => 'Livrée',
    'en_attente'     => 'En attente',
    'annulee'        => 'Annulée',
    'en_livraison'   => 'En livraison',
    'en_preparation' => 'En préparation',
    'confirmee'      => 'Confirmée',
    'expedie'        => 'Expédiée',
];
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<style>


*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
  background: var(--bg);
  color: var(--text);
  min-height: 100vh;
}

/* ── Layout wrapper ── */
.dash {
  max-width: 1400px;
  margin: 0 auto;
  padding: 1.6rem 1.4rem 3rem;
}

/* ── Layout ──────────────────────────────────────────────── */
.topbar          { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem; }
.topbar h1       { font-family:var(--font-head); font-size:1.55rem; display:flex; align-items:center; gap:.45rem; }
.topbar-right    { display:flex; align-items:center; gap:.6rem; }

.notif-btn {
  display: flex;
  align-items: center;
  gap: .45rem;
  background: white;
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: .5rem .9rem;
  font-size: .83rem;
  font-weight: 600;
  color: var(--text);
  text-decoration: none;
  transition: .15s;
  position: relative;
}
.notif-btn:hover { background: var(--surface2); box-shadow: var(--shadow); }
.alert-badge {
  background: var(--red);
  color: #fff;
  border-radius: 20px;
  font-size: .68rem;
  font-weight: 700;
  padding: .1rem .42rem;
  line-height: 1.4;
}
.user-pill {
  background: white;
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: .45rem .9rem;
  font-size: .82rem;
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 1px;
}
.user-pill-name { font-weight: 600; color: var(--text); }
.user-pill-role { font-size: .67rem; color: var(--muted); }

/* ── Cards ── */
.card {
  background: white;
  border-radius: var(--radius);
  padding: 1.4rem;
  box-shadow: var(--shadow);
  border: 1px solid var(--border);
}
.card-title {
  font-family: 'Syne', sans-serif;
  font-size: .9rem;
  font-weight: 700;
  letter-spacing: -.01em;
  margin-bottom: 1rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.card-title a {
  font-family: 'DM Sans', sans-serif;
  font-size: .78rem;
  font-weight: 500;
  color: var(--green);
  text-decoration: none;
}
.card-title a:hover { text-decoration: underline; }

/* ── KPI Grid ── */
.kpi-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(155px, 1fr));
  gap: .9rem;
  margin-bottom: 1.4rem;
}
.kpi {
  background: white;
  border-radius: var(--radius);
  padding: 1.2rem 1.1rem 1rem;
  box-shadow: var(--shadow);
  border: 1px solid var(--border);
  position: relative;
  overflow: hidden;
  transition: transform .2s, box-shadow .2s;
  cursor: default;
}
.kpi:hover { transform: translateY(-3px); box-shadow: var(--shadow-md); }
.kpi-icon-wrap {
  width: 38px; height: 38px;
  border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.1rem;
  margin-bottom: .75rem;
  background: var(--green-dim);
}
.kpi.warn  .kpi-icon-wrap { background: var(--orange-dim); }
.kpi.red   .kpi-icon-wrap { background: var(--red-dim); }
.kpi.gold  .kpi-icon-wrap { background: var(--gold-dim); }
.kpi.blue  .kpi-icon-wrap { background: var(--blue-dim); }
.kpi-val {
  font-family: 'Syne', sans-serif;
  font-size: 2rem;
  font-weight: 800;
  line-height: 1;
  color: var(--text);
  letter-spacing: -.04em;
}
.kpi.warn .kpi-val { color: var(--orange); }
.kpi.red  .kpi-val { color: var(--red); }
.kpi.gold .kpi-val { color: var(--gold); }
.kpi-label {
  font-size: .82rem;
  color: var(--muted);
  margin-top: .3rem;
  font-family:'playfair display';
  text-transform: uppercase;
  letter-spacing: .07em;
  font-weight: 500;
}
.kpi-stripe {
  position: absolute;
  bottom: 0; left: 0; right: 0;
  height: 3px;
  background: var(--green);
  border-radius: 0 0 var(--radius) var(--radius);
}
.kpi.warn .kpi-stripe { background: var(--orange); }
.kpi.red  .kpi-stripe { background: var(--red); }
.kpi.gold .kpi-stripe { background: var(--gold); }
.kpi.blue .kpi-stripe { background: var(--blue); }

/* ── Main grid layouts ── */
.grid-2-1 {
  display: grid;
  grid-template-columns: 1fr 300px;
  gap: 1.1rem;
  margin-bottom: 1.1rem;
}
.grid-2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.1rem;
  margin-bottom: 1.1rem;
}
@media (max-width: 960px) {
  .grid-2-1, .grid-2 { grid-template-columns: 1fr; }
}

/* ── Revenue Chart ── */
.chart-wrap {
  position: relative;
  height: 500px;
  margin: .5rem 0 .6rem;
}
.chart-total {
  text-align: right;
  font-size: .8rem;
  color: var(--muted);
}
.chart-total strong { color: var(--text); font-weight: 600; }

/* ── Quick links ── */
.quick-links { display: flex; flex-direction: column; gap: .4rem; }
.quick-btn {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: .5rem .85rem;
  border-radius: var(--radius-sm);
  border: 1px solid var(--border);
  background:  white;
  font-size: .82rem;
  font-weight: 500;
  color: var(--text);
  text-decoration: none;
  transition: .15s;
}
.quick-btn:hover {
  background: var(--green);
  color: #fff;
  border-color: var(--green);
}
.quick-btn:hover .alert-badge { background: rgba(255,255,255,.3); }
.quick-btn i { width: 15px; margin-right: .45rem; opacity: .7; }

/* ── Orders table ── */
.orders-table { width: 100%; border-collapse: collapse; font-size: .84rem; }
.orders-table th {
  padding: .45rem .65rem;
  text-align: left;
  color: var(--muted);
  font-size: .7rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: .07em;
  border-bottom: 2px solid var(--border);
}
.orders-table td {
  padding: .55rem .65rem;
  border-bottom: 1px solid var(--border);
  vertical-align: middle;
}
.orders-table tr:last-child td { border-bottom: none; }
.orders-table tbody tr { transition: background .1s; }
.orders-table tbody tr:hover td { background: var(--surface2); }
.order-id { font-weight: 700; color: var(--green); font-family: 'Syne', sans-serif; }

/* ── Badges ── */
.badge {
  display: inline-flex;
  align-items: center;
  padding: .18rem .55rem;
  border-radius: 20px;
  font-size: .68rem;
  font-weight: 600;
  white-space: nowrap;
}
.badge-success   { background: #dcfce7; color: #166534; }
.badge-warning   { background: #fef3c7; color: #92400e; }
.badge-danger    { background: #fee2e2; color: #991b1b; }
.badge-info      { background: #dbeafe; color: #1e40af; }
.badge-secondary { background: #f3f4f6; color: #374151; }

/* ── Stock alerts ── */
.stock-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: .55rem 0;
  border-bottom: 1px solid var(--border);
  gap: .8rem;
}
.stock-item:last-child { border-bottom: none; }
.stock-name { font-size: .85rem; font-weight: 500; }
.stock-seuil { font-size: .72rem; color: var(--muted); }
.pbar { width: 72px; height: 6px; background: var(--border); border-radius: 20px; overflow: hidden; flex-shrink: 0; }
.pbar-fill { height: 100%; border-radius: 20px; }

/* ── Livreur grid ── */
.livreur-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(270px, 1fr));
  gap: 1rem;
}
.liv-card {
  background:  white;
  border-radius: var(--radius);
  padding: 1.1rem;
  border: 1px solid var(--border);
  border-left: 4px solid var(--green);
  transition: transform .2s, box-shadow .2s;
}
.liv-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); }
.liv-card.busy { border-left-color: var(--orange); }
.liv-card.idle { border-left-color: var(--border); }
.liv-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: .6rem; }
.liv-name {
  font-family: 'Syne', sans-serif;
  font-size: .95rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: .4rem;
}
.liv-contact { font-size: .73rem; color: var(--muted); margin-top: .15rem; }
.dot { width: 7px; height: 7px; border-radius: 50%; display: inline-block; flex-shrink: 0; }
.dot-on  { background: var(--orange); box-shadow: 0 0 0 2px var(--orange-dim); }
.dot-ok  { background: var(--green);  box-shadow: 0 0 0 2px var(--green-dim); }
.dot-off { background: var(--muted); }

.liv-stats-row { display: flex; gap: .35rem; flex-wrap: wrap; margin: .6rem 0; }
.liv-stat {
  font-size: .7rem;
  font-weight: 600;
  padding: .18rem .5rem;
  border-radius: 8px;
  background: white;
  border: 1px solid var(--border);
  color: var(--muted);
}
.liv-stat.active { background: #fef3c7; border-color: #fde68a; color: #92400e; }
.liv-stat.done   { background: #dcfce7; border-color: #bbf7d0; color: #166534; }
.liv-stat.bad    { background: #fee2e2; border-color: #fecaca; color: #991b1b; }

.liv-progress-bar { height: 5px; background: var(--border); border-radius: 20px; overflow: hidden; margin: .5rem 0 .25rem; }
.liv-progress-fill { height: 100%; background: var(--green); border-radius: 20px; transition: width .4s; }
.liv-progress-label { font-size: .68rem; color: var(--muted); text-align: right; }

.current-delivery {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  padding: .55rem .7rem;
  margin-top: .5rem;
  font-size: .75rem;
}
.current-delivery-label { color: var(--muted); font-size: .68rem; margin-bottom: .15rem; }
.current-delivery-id { font-weight: 700; color: var(--orange); font-family: 'Syne', sans-serif; }

.assign-select {
  width: 100%;
  font-size: .74rem;
  padding: .4rem .5rem;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  background: var(--surface);
  color: var(--text);
  margin: .6rem 0 .35rem;
  appearance: none;
}
.assign-btn {
  width: 100%;
  background: var(--green);
  color: #fff;
  border: none;
  border-radius: var(--radius-sm);
  padding: .48rem;
  font-size: .76rem;
  font-weight: 600;
  cursor: pointer;
  transition: opacity .15s, transform .15s;
  font-family: 'DM Sans', sans-serif;
}
.assign-btn:hover { opacity: .88; transform: translateY(-1px); }

.livreur-summary {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
  gap: .7rem;
  margin-top: 1.2rem;
  background:  #dcfcdb;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  padding: .9rem 1rem;
  font-size: .78rem;
}
.livreur-summary-item strong { font-weight: 600; }

/* ── Animations ── */
@keyframes fadeUp {
  from { opacity: 0; transform: translateY(14px); }
  to   { opacity: 1; transform: translateY(0); }
}
.kpi, .card { animation: fadeUp .4s ease both; }
.kpi:nth-child(1) { animation-delay: .03s; }
.kpi:nth-child(2) { animation-delay: .06s; }
.kpi:nth-child(3) { animation-delay: .09s; }
.kpi:nth-child(4) { animation-delay: .12s; }
.kpi:nth-child(5) { animation-delay: .15s; }
.kpi:nth-child(6) { animation-delay: .18s; }
.kpi:nth-child(7) { animation-delay: .21s; }
.kpi:nth-child(8) { animation-delay: .24s; }
</style>

<div class="dash">

  <!-- ══ TOPBAR ══ -->
  <div class="topbar">
    <div class="topbar-left">
      <h1 style="font-size: 3rem;">
        Dashboard
      </h1>
      <div class="date-pill">
        <i class="fas fa-calendar-alt" style="font-size:.7rem;"></i>
        <?= date('l d F Y') ?>
      </div>
    </div>
    

    <div class="topbar-right">
      <a href="<?= BASE ?>/view/admin/alerts.php" class="notif-btn">
        Alertes
        <?php if ($totalAlerts > 0): ?>
          <span class="alert-badge"><?= $totalAlerts ?></span>
        <?php endif; ?>
      </a>
      <span class="role-pill">
        <i class="fas fa-shield-halved" style="color:var(--green);font-size:.8rem;"></i>
        <?= htmlspecialchars($roleLabels[$_SESSION['role'] ?? ''] ?? ' Utilisateur') ?>
      </span>
      
    </div>
  </div>

  <!-- ══ KPI CARDS ══ -->
  <div class="kpi-grid">
    <div class="kpi">
      <div class="kpi-val"><?= number_format($stats['clients']) ?></div>
      <div class="kpi-label">Clients</div>
      <div class="kpi-stripe"></div>
    </div>
    <div class="kpi">
      <div class="kpi-val"><?= number_format($stats['produits']) ?></div>
      <div class="kpi-label">Produits</div>
      <div class="kpi-stripe"></div>
    </div>
    <div class="kpi">
      <div class="kpi-val"><?= number_format($stats['commandes']) ?></div>
      <div class="kpi-label">Commandes</div>
      <div class="kpi-stripe"></div>
    </div>
    <div class="kpi gold">
      <div class="kpi-val"><?= number_format($revenuTotal, 0) ?></div>
      <div class="kpi-label">Revenus TND</div>
      <div class="kpi-stripe"></div>
    </div>
    <div class="kpi warn">
      <div class="kpi-val"><?= $enAttenteCount ?></div>
      <div class="kpi-label">En attente</div>
      <div class="kpi-stripe"></div>
    </div>
    <div class="kpi">
      <div class="kpi-val"><?= $stats['en_preparation'] ?></div>
      <div class="kpi-label">Préparation</div>
      <div class="kpi-stripe"></div>
    </div>
    <div class="kpi red">
      <div class="kpi-val"><?= $alertesCount ?></div>
      <div class="kpi-label">Alertes stock</div>
      <div class="kpi-stripe"></div>
    </div>
    <div class="kpi warn">
      <div class="kpi-val"><?= $reclamCount ?></div>
      <div class="kpi-label">Réclamations</div>
      <div class="kpi-stripe"></div>
    </div>
  </div>

  <!-- ══ REVENUE CHART + QUICK LINKS ══ -->
  <div class="grid-2-1">
    <div class="card">
      <div class="card-title">
         Revenus — 30 derniers jours
        <span style="font-size:.76rem;font-weight:400;font-family:'DM Sans',sans-serif;color:var(--muted);">commandes payées</span>
      </div>
      <div class="chart-wrap">
        <canvas id="revenueChart"></canvas>
      </div>
      <div class="chart-total">
        Total 30 j : <strong><?= number_format(array_sum($sparkVals), 3) ?> TND</strong>
      </div>
    </div>

    <div class="card">
      <div class="card-title"> Accès rapides</div>
      <div class="quick-links">
        <?php $links = [
          ['/view/admin/commandes.php',    'fas fa-box',      'Commandes',    $enAttenteCount > 0 ? $enAttenteCount : null],
          ['/view/admin/livraisons.php',   'fas fa-truck',    'Livraisons',   null],
          ['/view/admin/produits.php',     'fas fa-leaf',     'Produits',     null],
          ['/view/admin/users.php',        'fas fa-users',    'Utilisateurs', null],
          ['/view/admin/livreurs.php',     'fas fa-motorcycle','Livreurs',    null],
          ['/view/admin/usines.php',       'fas fa-industry', 'Usines',       null],
          ['/view/admin/reclamations.php', 'fas fa-flag',     'Réclamations', $reclamCount > 0 ? $reclamCount : null],
          ['/view/admin/avis.php',         'fas fa-star',     'Avis',         $avisCount > 0 ? $avisCount : null],
          ['/view/admin/alerts.php',       'fas fa-bell',     'Alertes',      $totalAlerts > 0 ? $totalAlerts : null],
          ['/view/admin/production.php',   'fas fa-industry', 'Production',   null],
        ]; foreach ($links as $l): ?>
        <a href="<?= BASE . $l[0] ?>" class="quick-btn">
          <span><i class="<?= $l[1] ?>"></i><?= $l[2] ?></span>
          <?php if ($l[3] !== null): ?><span class="alert-badge"><?= $l[3] ?></span><?php endif; ?>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- ══ ORDERS + STOCK ══ -->
  <div class="grid-2">
    <div class="card">
      <div class="card-title">
         Dernières commandes
        <a href="<?= BASE ?>/view/admin/commandes.php">Toutes →</a>
      </div>
      <table class="orders-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Client</th>
            <th>Total</th>
            <th>Statut</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($dernieresCommandes as $c):
            $bc = match($c['statut']) {
              'livre'          => 'badge-success',
              'en_attente'     => 'badge-warning',
              'annulee'        => 'badge-danger',
              'en_livraison'   => 'badge-info',
              default          => 'badge-secondary'
            };
          ?>
          <tr>
            <td><span class="order-id">#<?= $c['id'] ?></span></td>
            <td><?= htmlspecialchars($c['client_nom']) ?></td>
            <td><strong><?= number_format($c['total'], 3) ?></strong> <span style="font-size:.72rem;color:var(--muted);">TND</span></td>
            <td><span class="badge <?= $bc ?>"><?= $statusLabels[$c['statut']] ?? ucfirst(str_replace('_',' ',$c['statut'])) ?></span></td>
            <td style="color:var(--muted);font-size:.78rem;"><?= date('d/m H:i', strtotime($c['date_commande'])) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="card">
      <div class="card-title">
        Alertes stock
        <a href="<?= BASE ?>/view/admin/alerts.php" style="color:var(--red);">Voir tout →</a>
      </div>
      <?php if (empty($alertesStock)): ?>
        <div style="text-align:center;padding:2rem 1rem;color:var(--muted);">
          <div style="font-size:2rem;margin-bottom:.5rem;">✅</div>
          <div style="font-size:.85rem;">Tous les stocks sont suffisants</div>
        </div>
      <?php else: ?>
        <?php foreach (array_slice($alertesStock, 0, 6) as $a):
          $pct = $a['seuil_alerte'] > 0 ? min(100, round($a['quantite'] / $a['seuil_alerte'] * 100)) : 0;
          $col = $pct < 30 ? 'var(--red)' : ($pct < 60 ? 'var(--orange)' : 'var(--green-light)');
        ?>
        <div class="stock-item">
          <div>
            <div class="stock-name"><?= htmlspecialchars($a['nom']) ?></div>
            <div class="stock-seuil">Seuil : <?= $a['seuil_alerte'] ?></div>
          </div>
          <div style="display:flex;align-items:center;gap:.6rem;flex-shrink:0;">
            <div class="pbar">
              <div class="pbar-fill" style="width:<?= max(4,$pct) ?>%;background:<?= $col ?>;"></div>
            </div>
            <span class="badge badge-danger" style="font-size:.68rem;"><?= $a['quantite'] ?></span>
          </div>
        </div>
        <?php endforeach; ?>
        <?php if (count($alertesStock) > 6): ?>
          <a href="<?= BASE ?>/view/admin/alerts.php"
             style="display:block;text-align:center;margin-top:.8rem;font-size:.8rem;color:var(--red);font-weight:600;">
            +<?= count($alertesStock) - 6 ?> autres →
          </a>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- ══ LIVREURS ══ -->
  <div class="card">
    <div class="card-title">
      Livreurs — Vue en temps réel
      <a href="<?= BASE ?>/view/admin/livreurs.php">Gérer les livreurs →</a>
    </div>

    <?php if (empty($livreurs)): ?>
      <div style="text-align:center;padding:2.5rem;color:var(--muted);">
        <div style="font-size:2.5rem;margin-bottom:.6rem;">🚚</div>
        <div>Aucun livreur enregistré.</div>
        <a href="<?= BASE ?>/view/admin/users.php?role=livreur" style="color:var(--green);font-weight:600;font-size:.85rem;">Ajouter un livreur →</a>
      </div>
    <?php else: ?>
      <div class="livreur-grid">
        <?php foreach ($livreurs as $liv):
          $hasActive  = (int)$liv['actives'] > 0;
          $cardClass  = $hasActive ? 'busy' : ((int)$liv['livrees'] > 0 ? '' : 'idle');
          $dotClass   = $hasActive ? 'dot-on' : ((int)$liv['livrees'] > 0 ? 'dot-ok' : 'dot-off');
          $totalDel   = (int)$liv['total_livraisons'];
          $successRate = $totalDel > 0 ? round(((int)$liv['livrees'] / $totalDel) * 100) : 0;

          $currentDelivery = null;
          if ($hasActive) {
            $stmt = $cnx->prepare("
              SELECT l.id, c.id AS commande_id, u.nom AS client_name
              FROM livraisons l
              JOIN commandes c ON c.id = l.commande_id
              JOIN users u ON u.id = c.user_id
              WHERE l.livreur_id = ? AND l.statut IN ('assignee','acceptee','en_cours')
              ORDER BY l.id DESC LIMIT 1
            ");
            $stmt->execute([$liv['id']]);
            $currentDelivery = $stmt->fetch(PDO::FETCH_ASSOC);
          }

          $commandes_dispo = $cnx->query("
            SELECT c.id, u.nom AS client, c.total
            FROM commandes c
            JOIN users u ON u.id = c.user_id
            LEFT JOIN livraisons l ON l.commande_id = c.id
            WHERE c.statut IN ('confirmee','en_attente','payee') AND l.id IS NULL
            ORDER BY c.date_commande LIMIT 5
          ")->fetchAll();
        ?>
        <div class="liv-card <?= $cardClass ?>">
          <div class="liv-header">
            <div>
              <div class="liv-name">
                <span class="dot <?= $dotClass ?>"></span>
                <?= htmlspecialchars($liv['nom']) ?>
                <?php if ($hasActive): ?>
                  <span style="font-size:.62rem;background:var(--orange);color:#fff;padding:.1rem .4rem;border-radius:8px;">Actif</span>
                <?php endif; ?>
              </div>
              <?php if ($liv['telephone']): ?>
                <div class="liv-contact"><i class="fas fa-phone" style="font-size:.6rem;margin-right:.3rem;"></i><?= htmlspecialchars($liv['telephone']) ?></div>
              <?php endif; ?>
              <?php if ($liv['email']): ?>
                <div class="liv-contact"><i class="fas fa-envelope" style="font-size:.6rem;margin-right:.3rem;"></i><?= htmlspecialchars($liv['email']) ?></div>
              <?php endif; ?>
            </div>
            <a href="<?= BASE ?>/view/admin/livreurs.php?livreur=<?= $liv['id'] ?>"
               style="font-size:.72rem;color:var(--green);font-weight:600;white-space:nowrap;">Détail →</a>
          </div>

          <div class="liv-stats-row">
            <?php if ((int)$liv['actives'] > 0): ?>
              <span class="liv-stat active">🔄 <?= $liv['actives'] ?> active<?= $liv['actives'] > 1 ? 's' : '' ?></span>
            <?php endif; ?>
            <span class="liv-stat done"> <?= $liv['livrees'] ?> livrées</span>
            <?php if ((int)$liv['echecs'] > 0): ?>
              <span class="liv-stat bad"> <?= $liv['echecs'] ?> échecs</span>
            <?php endif; ?>
          </div>
          <div class="liv-progress-bar">
            <div class="liv-progress-fill" style="width:<?= $successRate ?>%;"></div>
          </div>
          <div class="liv-progress-label"><?= $successRate ?>% succès · <?= $totalDel ?> total</div>

          <?php if ($currentDelivery): ?>
            <div class="current-delivery">
              <div class="current-delivery-label">En cours</div>
              <div class="current-delivery-id">Commande #<?= $currentDelivery['commande_id'] ?></div>
              <div style="font-size:.72rem;color:var(--muted);">Client : <?= htmlspecialchars($currentDelivery['client_name']) ?></div>
            </div>
          <?php endif; ?>

          <form method="POST" action="<?= BASE ?>/controller/traitement.php">
            <input type="hidden" name="action"     value="assigner_livreur">
            <input type="hidden" name="livreur_id" value="<?= $liv['id'] ?>">
            <?php if (!empty($commandes_dispo)): ?>
              <select name="commande_id" class="assign-select">
                <option value="">— Assigner une commande —</option>
                <?php foreach ($commandes_dispo as $cd): ?>
                  <option value="<?= $cd['id'] ?>">
                    #<?= $cd['id'] ?> — <?= htmlspecialchars(mb_substr($cd['client'], 0, 16)) ?> (<?= number_format($cd['total'], 3) ?> TND)
                  </option>
                <?php endforeach; ?>
              </select>
              <button type="submit" class="assign-btn">
                <i class="fas fa-plus" style="margin-right:.3rem;font-size:.75rem;"></i>Assigner
              </button>
            <?php else: ?>
              <div style="margin-top:.6rem;font-size:.72rem;color:var(--muted);text-align:center;">
                <i class="fas fa-check-circle" style="color:var(--green);margin-right:.3rem;"></i>Aucune commande à assigner
              </div>
            <?php endif; ?>
          </form>
        </div>
        <?php endforeach; ?>
      </div>

      <?php
        $totalLivreurs         = count($livreurs);
        $totalActiveDeliveries = array_sum(array_column($livreurs, 'actives'));
        $totalSuccessDel       = array_sum(array_column($livreurs, 'livrees'));
        $totalFailedDel        = array_sum(array_column($livreurs, 'echecs'));
      ?>
      <div class="livreur-summary">
        <div class="livreur-summary-item"><strong style="color:var(--green);"> <?= $totalLivreurs ?></strong> livreurs enregistrés</div>
        <div class="livreur-summary-item"><strong style="color:var(--orange);"> <?= $totalActiveDeliveries ?></strong> en cours</div>
        <div class="livreur-summary-item"><strong style="color:var(--green);"> <?= $totalSuccessDel ?></strong> réussies</div>
        <div class="livreur-summary-item"><strong style="color:var(--red);"> <?= $totalFailedDel ?></strong> échouées</div>
      </div>
    <?php endif; ?>
  </div>

</div><!-- end .dash -->

<script>
(function() {
    const ctx = document.getElementById('revenueChart');
    if (!ctx) return; // sécurité si l'élément n'existe pas
    const canvas = ctx.getContext('2d');
    
    let revenueChart;
    
    function fetchAndUpdateChart() {
       
        
        fetch('/benna_final2/benna_final/benafinal/view/admin/revenue_api.php')
    
            .then(response => {
                if (!response.ok) throw new Error('HTTP ' + response.status);
                return response.json();
            })
            .then(data => {
                if (!data.labels || !data.values) throw new Error('Format JSON invalide');
                
                if (revenueChart) {
                    revenueChart.data.labels = data.labels;
                    revenueChart.data.datasets[0].data = data.values;
                    revenueChart.update();
                } else {
                    // Création du graphique
                    const grad = canvas.createLinearGradient(0, 0, 0, 200);
                    grad.addColorStop(0, 'rgba(22,163,74,.20)');
                    grad.addColorStop(0.6, 'rgba(22,163,74,.05)');
                    grad.addColorStop(1, 'rgba(22,163,74,0)');
                    
                    revenueChart = new Chart(canvas, {
                        type: 'line',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                data: data.values,
                                borderColor: '#16a34a',
                                borderWidth: 2.5,
                                pointRadius: data.values.map(v => v > 0 ? 4 : 0),
                                pointHoverRadius: 6,
                                pointBackgroundColor: '#16a34a',
                                pointBorderColor: '#fff',
                                pointBorderWidth: 2,
                                fill: true,
                                backgroundColor: grad,
                                tension: 0.45,
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } }
                        }
                    });
                }
            })
            .catch(error => {
                console.error('Erreur de chargement de la courbe:', error);
                // Option : afficher un message dans le canvas
                canvas.fillStyle = '#ccc';
                canvas.font = '12px sans-serif';
                canvas.fillText('Données non disponibles', 10, 50);
            });
    }
    
    // Premier chargement
    fetchAndUpdateChart();
    // Mise à jour toutes les 30 secondes
    setInterval(fetchAndUpdateChart, 30000);
})();
</script>
<script>window.BASE = "<?= BASE ?>";</script>

<?php include "partials/admin_footer.php"; ?>
