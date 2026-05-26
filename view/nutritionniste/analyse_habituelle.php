<?php
$pageTitle = "Analyse Nutritionnelle";
include "partials/nutri_header.php";


// ──────────────────────────────────────────────
// DONNÉES : profil nutritionnel par client
// ──────────────────────────────────────────────


// 1. Tous les clients ayant commandé
$clients = getClientsAvecCommandes($cnx);


// 2. Données nutritionnelles agrégées par client (sur toutes les commandes)
$stmtNutri = $cnx->query("
    SELECT
        c.user_id,
        SUM(cd.quantite * p.calories)  AS total_calories,
        SUM(cd.quantite * p.proteines) AS total_proteines,
        SUM(cd.quantite * p.glucides)  AS total_glucides,
        SUM(cd.quantite * p.lipides)   AS total_lipides,
        COUNT(DISTINCT c.id)           AS nb_commandes,
        MIN(c.date_commande)           AS premiere_commande,
        MAX(c.date_commande)           AS derniere_commande
    FROM commandes c
    JOIN commande_details cd ON cd.commande_id = c.id
    JOIN produits p ON p.id = cd.produit_id
    WHERE c.statut NOT IN ('annulee')
    GROUP BY c.user_id
");
$nutriData = [];
foreach ($stmtNutri->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $nutriData[$row['user_id']] = $row;
}


// 3. Tendance mensuelle par client (6 derniers mois)
$stmtTrend = $cnx->query("
    SELECT
        c.user_id,
        DATE_FORMAT(c.date_commande, '%Y-%m') AS mois,
        SUM(cd.quantite * p.calories) AS calories_mois
    FROM commandes c
    JOIN commande_details cd ON cd.commande_id = c.id
    JOIN produits p ON p.id = cd.produit_id
    WHERE c.statut NOT IN ('annulee')
      AND c.date_commande >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY c.user_id, mois
    ORDER BY mois ASC
");
$trendData = [];
foreach ($stmtTrend->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $trendData[$row['user_id']][$row['mois']] = (int)$row['calories_mois'];
}


// 4. Produits les + commandés par client
$stmtTop = $cnx->query("
    SELECT
        c.user_id,
        p.nom,
        p.calories,
        p.proteines,
        p.glucides,
        p.lipides,
        p.regime,
        SUM(cd.quantite) AS qte_totale
    FROM commandes c
    JOIN commande_details cd ON cd.commande_id = c.id
    JOIN produits p ON p.id = cd.produit_id
    WHERE c.statut NOT IN ('annulee')
    GROUP BY c.user_id, cd.produit_id
    ORDER BY c.user_id, qte_totale DESC
");
$topProduits = [];
foreach ($stmtTop->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $topProduits[$row['user_id']][] = $row;
}


// 5. Alertes existantes
$alertes = getAlertesNutrition($cnx);
$alertesParClient = [];
foreach ($alertes as $a) {
    if ($a['client_id']) $alertesParClient[$a['client_id']][] = $a;
}


// ──────────────────────────────────────────────
// ANALYSE : détecter déséquilibres
// ──────────────────────────────────────────────
function analyserDesequilibres(array $n): array {
    $issues = [];
    if (empty($n)) return $issues;


    $cal = (float)$n['total_calories'];
    $pro = (float)$n['total_proteines'];
    $glu = (float)$n['total_glucides'];
    $lip = (float)$n['total_lipides'];
    $nb  = max(1, (int)$n['nb_commandes']);


    // Ratio macros sur calories estimées
    $calPro = $pro * 4;
    $calGlu = $glu * 4;
    $calLip = $lip * 9;
    $total  = max(1, $calPro + $calGlu + $calLip);


    $ratioPro = round($calPro / $total * 100);
    $ratioGlu = round($calGlu / $total * 100);
    $ratioLip = round($calLip / $total * 100);


    if ($ratioPro < 15)  $issues[] = ['type'=>'warning', 'msg'=>"Apport en protéines faible ({$ratioPro}% des calories) , risque de carence musculaire"];
    if ($ratioPro > 35)  $issues[] = ['type'=>'info',    'msg'=>"Apport en protéines élevé ({$ratioPro}%) , surveiller la fonction rénale"];
    if ($ratioGlu > 65)  $issues[] = ['type'=>'danger',  'msg'=>"Excès de glucides ({$ratioGlu}%) , risque glycémique élevé"];
    if ($ratioGlu < 30)  $issues[] = ['type'=>'info',    'msg'=>"Glucides bas ({$ratioGlu}%) , régime potentiellement cétogène"];
    if ($ratioLip > 40)  $issues[] = ['type'=>'warning', 'msg'=>"Lipides élevés ({$ratioLip}%) , surveiller le bilan lipidique"];


    return $issues;
}


// ──────────────────────────────────────────────
// MODAL : envoyer alerte (POST)
// ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'add_alerte') {

    $client_id = !empty($_POST['client_id']) ? (int)$_POST['client_id'] : null;

    $ok = addAlerteNutrition(
        $cnx,
        $_SESSION['user_id'],
        $client_id,
        $_POST['titre'] ?? '',
        $_POST['message'] ?? '',
        $_POST['gravite'] ?? 'info'
    );

    $_SESSION[$ok ? 'success' : 'error'] = $ok ? "Alerte envoyée." : "Erreur lors de l’envoi.";

    header("Location: " . BASE . "/view/nutritionniste/analyse_habituelle.php");
    exit();
}

// Client sélectionné (filtre)
$focusId = isset($_GET['client_id']) ? (int)$_GET['client_id'] : 0;
?>


<style> 

/* ── Stat pill ──────────────────────────────── */

.stat-pill .val {
  font-size: 1.9rem;
  color: var(--green);
  line-height: 1;
}
.stat-pill .lbl {
  font-size: .78rem;
  color: var(--muted);
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: .05em;
}
.stat-pill{
  background: #ddfaedf4;
  border-radius: 16px;
  padding: 1.2rem;
}
.macro-bar{
  height:10px;
  background:#e5e7eb;
  border-radius:999px;
  overflow:hidden;
}
.macro-bar-fill{
  height:100%;
}
.issue{
  padding:.6rem .8rem;
  border-radius:10px;
  margin-bottom:.5rem;
}
.issue.danger{ background:#fee2e2; }
.issue.warning{ background:#fef3c7; }
.issue.info{ background:#dbeafe; }
.sparkline-bar{
  display:flex;
  align-items:flex-end;
  gap:3px;
  height:40px;
}
.spark-b{
  flex:1;
  background:var(--green-l);
}
/* ── Grid ───────────────────────────────────── */
.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
.grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.2rem; }
@media (max-width: 900px) { .grid-2, .grid-3 { grid-template-columns: 1fr; } }

/* ── Client row ─────────────────────────────── */
.client-row {
  display: grid;
  grid-template-columns: 200px 1fr 1fr auto;
  align-items: center;
  gap: 1rem;
  padding: 1rem 0;
  border-bottom: 1px solid var(--border);
  cursor: pointer;
  transition: background .15s;
  border-radius: var(--radius-sm);
}
.client-row:hover { background: var(--green-xl); padding-left: .5rem; }
.client-row:last-child { border-bottom: none; }

/* ── Filter bar ─────────────────────────────── */
.filter-bar {
  display: flex;
  gap: .75rem;
  align-items: center;
  flex-wrap: wrap;
  margin-bottom: 1.5rem;
}
.filter-bar select {
  padding: .55rem 1rem;
  border-radius: var(--radius-sm);
  border: 1.5px solid var(--border);
  background: var(--card);
  font-family: inherit;
  font-size: .9rem;
  color: var(--fg);
  cursor: pointer;
}
.filter-bar select:focus { outline: none; border-color: var(--green-l); }

</style>


<div class="topbar">
  <h1> Analyse Nutritionnelle</h1>
  <div style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:center;">
    <button class="btn btn-yellow" onclick="document.getElementById('alerteModal').classList.add('open')">
      ⚠ Envoyer une alerte
    </button>
  </div>
</div>


<?php
// ── Stats globales ───────────────────────────
$clientsAvecDonnees = array_filter($clients, fn($c) => !empty($nutriData[$c['id']]));
$totalClients = count($clientsAvecDonnees);
$totalAlertes = count($alertes);
$moyCalories  = $totalClients
    ? round(array_sum(array_map(fn($c) => (float)($nutriData[$c['id']]['total_calories'] ?? 0) / max(1,(int)($nutriData[$c['id']]['nb_commandes'] ?? 1)), $clientsAvecDonnees)) / $totalClients)
    : 0;
$nbDesequilibres = 0;
foreach ($clientsAvecDonnees as $c) {
    $nb = count(analyserDesequilibres($nutriData[$c['id']] ?? []));
    $nbDesequilibres += $nb;
}
?>


<!-- Stat cards -->
<div class="grid-3" style="margin-bottom:1.5rem;">
  <div class="stat-pill">
    <span class="val"><?= $totalClients ?></span>
    <span class="lbl">Clients avec données</span>
  </div>
  <div class="stat-pill" style="background:#fef3c7;border-color:#fde68a;">
    <span class="val" style="color:#92400e;"><?= $nbDesequilibres ?></span>
    <span class="lbl" style="color:#92400e;">Déséquilibres détectés</span>
  </div>
  <div class="stat-pill" style="background:#dbeafe;border-color:#bfdbfe;">
    <span class="val" style="color:#1e40af;"><?= number_format($moyCalories) ?></span>
    <span class="lbl" style="color:#1e40af;">Kcal moy. / commande</span>
  </div>
</div>


<!-- Filter -->
<div class="filter-bar">
  <label style="font-size:.85rem;font-weight:600;color:var(--muted);">Filtrer par client :</label>
  <select onchange="filterClient(this.value)">
    <option value="0">Tous les clients</option>
    <?php foreach ($clients as $cl): ?>
      <option value="<?= $cl['id'] ?>" <?= $focusId == $cl['id'] ? 'selected' : '' ?>>
        <?= htmlspecialchars($cl['nom']) ?>
      </option>
    <?php endforeach; ?>
  </select>
  <?php if ($focusId): ?>
    <a href="?" class="btn btn-gray" style="font-size:.8rem;">✕ Effacer filtre</a>
  <?php endif; ?>
</div>


<!-- ─── LISTE CLIENTS ──────────────────────── -->
<div class="card">
  <div class="card-title">Profils nutritionnels — Clients</div>


  <?php
  $listeClients = $focusId
      ? array_filter($clients, fn($c) => $c['id'] == $focusId)
      : $clients;
  ?>


  <?php foreach ($listeClients as $cl):
    $nd = $nutriData[$cl['id']] ?? null;
    if (!$nd) continue;


    $cal = (float)$nd['total_calories'];
    $pro = (float)$nd['total_proteines'];
    $glu = (float)$nd['total_glucides'];
    $lip = (float)$nd['total_lipides'];
    $nb  = max(1, (int)$nd['nb_commandes']);


    $calMoy = round($cal / $nb);
    $proMoy = round($pro / $nb, 1);
    $gluMoy = round($glu / $nb, 1);
    $lipMoy = round($lip / $nb, 1);


    // Ratios pour donut
    $calPro = $pro * 4;
    $calGlu = $glu * 4;
    $calLip = $lip * 9;
    $totalMacro = max(1, $calPro + $calGlu + $calLip);
    $ratioPro = round($calPro / $totalMacro * 100);
    $ratioGlu = round($calGlu / $totalMacro * 100);
    $ratioLip = round($calLip / $totalMacro * 100);


    $issues = analyserDesequilibres($nd);
    $scoreColor = count($issues) === 0 ? '#22c55e' : (count($issues) === 1 ? '#f59e0b' : '#ef4444');
    $scoreLabel = count($issues) === 0 ? 'Équilibré' : (count($issues) === 1 ? 'À surveiller' : 'Déséquilibré');


    // Sparkline trend (6 mois)
    $trend = $trendData[$cl['id']] ?? [];
    $trendVals = array_values($trend);
    $maxTrend  = $trendVals ? max($trendVals) : 1;
  ?>


  <!-- Client card -->
  <div style="border:1.5px solid var(--border);border-radius:var(--radius);padding:1.2rem;margin-bottom:1rem;background:#fafcfa;">
    <!-- Header -->
    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:.8rem;margin-bottom:1rem;">
      <div>
        <div style="font-family:'Playfair Display',serif;font-size:1.1rem;color:var(--fg);">
          <?= htmlspecialchars($cl['nom']) ?>
        </div>
        <div style="font-size:.78rem;color:var(--muted);"><?= htmlspecialchars($cl['email']) ?></div>
        <div style="margin-top:.3rem;">
          <span class="badge <?= count($issues)===0?'badge-green':(count($issues)===1?'badge-yellow':'badge-red') ?>">
            <?= $scoreLabel ?>
          </span>
          <span class="badge badge-gray" style="margin-left:.3rem;"><?= $nb ?> commande<?= $nb>1?'s':'' ?></span>
        </div>
      </div>
      <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
        <button class="btn btn-gray" style="font-size:.78rem;"
                onclick="toggleDetail(<?= $cl['id'] ?>)">
          📋 Détail
        </button>
        <button class="btn btn-yellow" style="font-size:.78rem;"
                onclick="openAlerte(<?= $cl['id'] ?>, '<?= addslashes(htmlspecialchars($cl['nom'])) ?>')">
          ⚠ Alerte
        </button>
        <a href="<?= BASE ?>/view/nutritionniste/plans.php?client_id=<?= $cl['id'] ?>" class="btn btn-green" style="font-size:.78rem;">
          📋 Plan
        </a>
      </div>
    </div>


    <!-- Macros overview -->
    <div style="display:flex;gap:1.5rem;align-items:center;flex-wrap:wrap;">


      <!-- Donut SVG -->
      <div class="donut-wrap">
        <?php
        // SVG donut avec 3 segments
        $r = 50; $cx = 65; $cy = 65;
        $circumference = 2 * M_PI * $r;
        $segs = [
          ['val'=>$ratioPro, 'color'=>'#bef6dc', 'label'=>'Prot.'],
          ['val'=>$ratioGlu, 'color'=>'#faddab', 'label'=>'Gluc.'],
          ['val'=>$ratioLip, 'color'=>'#b3cefa', 'label'=>'Lip.'],
        ];
        $offset = 0;
        ?>
        <svg viewBox="0 0 130 130" class="progress-ring" style="width:130px;height:130px;">
          <circle cx="65" cy="65" r="50" fill="none" stroke="#e5e7eb" stroke-width="18"/>
          <?php foreach ($segs as $seg):
            $dash = $circumference * $seg['val'] / 100;
            $gap  = $circumference - $dash;
          ?>
          <circle cx="65" cy="65" r="50" fill="none"
                  stroke="<?= $seg['color'] ?>" stroke-width="18"
                  stroke-dasharray="<?= $dash ?> <?= $gap ?>"
                  stroke-dashoffset="-<?= $circumference * $offset / 100 ?>"
                  stroke-linecap="butt"/>
          <?php $offset += $seg['val']; endforeach; ?>
        </svg>
        <div class="donut-label">
          <span><?= $calMoy ?></span>
          <small>kcal/cmd</small>
        </div>
      </div>


      <!-- Bars -->
      <div style="flex:1;min-width:180px;">
        <?php
        $macros = [
          ['label'=>'Protéines', 'val'=>$proMoy, 'unit'=>'g', 'pct'=>$ratioPro, 'color'=>'#cbf8e3'],
          ['label'=>'Glucides',  'val'=>$gluMoy, 'unit'=>'g', 'pct'=>$ratioGlu, 'color'=>'#f9e0b4'],
          ['label'=>'Lipides',   'val'=>$lipMoy, 'unit'=>'g', 'pct'=>$ratioLip, 'color'=>'#c1d3f0'],
        ];
        foreach ($macros as $m):
        ?>
        <div style="margin-bottom:.45rem;">
          <div style="display:flex;justify-content:space-between;font-size:.75rem;margin-bottom:2px;">
            <span style="color:var(--muted);font-weight:500;"><?= $m['label'] ?></span>
            <span style="font-weight:600;"><?= $m['val'] ?><?= $m['unit'] ?> <span style="color:var(--muted);font-weight:400;">(<?= $m['pct'] ?>%)</span></span>
          </div>
          <div class="macro-bar">
            <div class="macro-bar-fill" style="width:<?= min(100,$m['pct']) ?>%;background:<?= $m['color'] ?>;"></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>


      <!-- Sparkline -->
      <?php if (count($trendVals) > 1): ?>
      <div style="text-align:center;min-width:80px;">
        <div style="font-size:.7rem;color:var(--muted);margin-bottom:.3rem;">Tendance 6 mois</div>
        <div class="sparkline-bar">
          <?php foreach (array_slice($trendVals, -6) as $tv): ?>
          <div class="spark-b" style="height:<?= round($tv / $maxTrend * 40) ?>px;" title="<?= number_format($tv) ?> kcal"></div>
          <?php endforeach; ?>
        </div>
        <?php
        if (count($trendVals) >= 2) {
            $last = end($trendVals);
            $prev = $trendVals[count($trendVals)-2];
            $delta = $last - $prev;
            $arrow = $delta > 0 ? '↑' : ($delta < 0 ? '↓' : '→');
            $col   = $delta > 0 ? '#dc2626' : ($delta < 0 ? '#16a34a' : '#6b7280');
            echo "<div style='font-size:.72rem;color:{$col};font-weight:700;margin-top:.2rem;'>{$arrow} " . number_format(abs($delta)) . " kcal</div>";
        }
        ?>
      </div>
      <?php endif; ?>


    </div><!-- /macros overview -->


    <!-- Issues -->
    <?php if (!empty($issues)): ?>
  <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--border);">
    <div style="font-size:.8rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:.5rem;">
      Déséquilibres détectés
    </div>

    <?php foreach ($issues as $iss): ?>
      <div class="issue <?= $iss['type'] ?>">
        <span><?= htmlspecialchars($iss['msg']) ?></span>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>


    <!-- Detail panel (collapse) -->
    <div id="detail-<?= $cl['id'] ?>" style="display:none;margin-top:1rem;padding-top:1rem;border-top:1px solid var(--border);animation:slideIn .2s ease;">
      <div class="grid-2" style="gap:1rem;">


        <!-- Top produits -->
        <div>
          <div style="font-size:.8rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--muted);margin-bottom:.7rem;">
            🛒 Produits les plus commandés
          </div>
          <?php foreach (array_slice($topProduits[$cl['id']] ?? [], 0, 5) as $prod): ?>
          <div style="display:flex;justify-content:space-between;align-items:center;padding:.4rem 0;border-bottom:1px solid var(--border);font-size:.82rem;">
            <span style="font-weight:500;"><?= htmlspecialchars($prod['nom']) ?></span>
            <div style="text-align:right;color:var(--muted);">
              <div><?= $prod['calories'] ?> kcal</div>
              <div style="font-size:.72rem;"><?= $prod['qte_totale'] ?>× commandé</div>
            </div>
          </div>
          <?php endforeach; ?>
          <?php if (empty($topProduits[$cl['id']])): ?>
            <p style="color:var(--muted);font-size:.82rem;">Aucune donnée.</p>
          <?php endif; ?>
        </div>


        <!-- Recommandations auto -->
        <div>
          <div style="font-size:.8rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--muted);margin-bottom:.7rem;">
             Recommandations
          </div>
          <?php
          $recs = [];
          if ($ratioPro < 15) $recs[] = "Augmenter les sources de protéines végétales : légumineuses, graines de chanvre, tofu.";
          if ($ratioPro > 35) $recs[] = "Réduire légèrement les protéines et surveiller l'apport hydrique.";
          if ($ratioGlu > 65) $recs[] = "Réduire les glucides raffinés. Privilégier les céréales complètes et les fibres.";
          if ($ratioGlu < 30) $recs[] = "Ajouter des féculents à index glycémique bas (lentilles, patate douce, avoine).";
          if ($ratioLip > 40) $recs[] = "Opter pour des graisses insaturées (avocat, huile d'olive) et limiter les graisses saturées.";
          if (empty($recs)) $recs[] = "Profil nutritionnel globalement équilibré. Encourager la diversité des produits.";
          $recs[] = "Veiller à maintenir une hydratation suffisante (1.5L d'eau/jour minimum).";
          foreach ($recs as $r):
          ?>
          <div style="display:flex;gap:.5rem;font-size:.82rem;margin-bottom:.5rem;padding:.4rem .6rem;background:var(--green-xl);border-radius:var(--radius-sm);">
            <span><?= htmlspecialchars($r) ?></span>
          </div>
          <?php endforeach; ?>
        </div>


      </div>


      <!-- Alertes envoyées à ce client -->
      <?php if (!empty($alertesParClient[$cl['id']])): ?>
      <div style="margin-top:1rem;">
        <div style="font-size:.8rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--muted);margin-bottom:.5rem;">
           Alertes envoyées
        </div>
        <div style="display:flex;flex-wrap:wrap;gap:.4rem;">
          <?php foreach ($alertesParClient[$cl['id']] as $al): ?>
          <span class="badge <?= match($al['gravite']){'info'=>'badge-blue','attention'=>'badge-yellow','urgent'=>'badge-red',default=>'badge-gray'} ?>">
            <?= htmlspecialchars($al['titre']) ?> · <?= date('d/m', strtotime($al['created_at'])) ?>
          </span>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>


    </div><!-- /detail -->
  </div><!-- /client card -->


  <?php endforeach; ?>


  <?php if (empty($listeClients) || !array_filter($listeClients, fn($c)=>!empty($nutriData[$c['id']]))): ?>
    <div style="text-align:center;padding:2.5rem;color:var(--muted);">
      Aucune donnée nutritionnelle disponible pour ce client.
    </div>
  <?php endif; ?>


</div><!-- /.card -->




<!-- ─── LÉGENDE MACROS ───────────────────────── -->
<div class="card" style="padding:1rem 1.5rem;">
  <div style="display:flex;gap:1.5rem;flex-wrap:wrap;align-items:center;font-size:.78rem;">

    <strong style="color:var(--muted);font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;">
      Référence OMS :
    </strong>

    <span style="display:flex;align-items:center;gap:.4rem;">
      <span style="width:10px;height:10px;background:#bef6dc;border-radius:2px;display:inline-block;"></span>
      Protéines : 15–35%
    </span>

    <span style="display:flex;align-items:center;gap:.4rem;">
      <span style="width:10px;height:10px;background:#faddab;border-radius:2px;display:inline-block;"></span>
      Glucides : 45–65%
    </span>

    <span style="display:flex;align-items:center;gap:.4rem;">
      <span style="width:10px;height:10px;background:#b3cefa;border-radius:2px;display:inline-block;"></span>
      Lipides : 20–35%
    </span>

  </div>
</div>


<!-- ─── MODAL ALERTE (COMPACT) ─────────────────────────── -->
<div class="modal-overlay" id="alerteModal">
  <div class="modal-box" style="max-width:420px;padding:1.5rem;">

    <!-- Header -->
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
      <div>
        <h2 style="font-family:'Playfair Display',serif;font-size:1.2rem;color:var(--green);margin:0;">
          Nouvelle alerte
        </h2>
        <small style="color:var(--muted);font-size:.75rem;">
          Notifier un client rapidement
        </small>
      </div>

      <button type="button"
        onclick="document.getElementById('alerteModal').classList.remove('open')"
        style="border:none;background:none;font-size:1.4rem;cursor:pointer;color:var(--muted);">
        ×
      </button>
    </div>

    <form method="POST" action="<?= $_SERVER['PHP_SELF'] ?>">
              <input type="hidden" name="form_action" value="add_alerte"/>

      <!-- Client -->
      <div class="form-group" style="margin-bottom:.8rem;">
        <label>Client</label>
        <select class="form-control" name="client_id">
          <option value="">Tous les clients</option>
          <?php foreach ($clients as $cl): ?>
            <option value="<?= $cl['id'] ?>"><?= htmlspecialchars($cl['nom']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Title -->
      <div class="form-group" style="margin-bottom:.8rem;">
        <label>Titre</label>
        <input class="form-control"
               name="titre"
               required
               placeholder="Ex: Déséquilibre détecté"/>
      </div>

      <!-- Message -->
      <div class="form-group" style="margin-bottom:.8rem;">
        <label>Message</label>
        <textarea class="form-control"
                  name="message"
                  rows="3"
                  required
                  placeholder="Expliquez brièvement..."></textarea>
      </div>

      <!-- Gravité -->
      <div class="form-group" style="margin-bottom:1rem;">
        <label>Gravité</label>
        <select class="form-control" name="gravite">
          <option value="info">Information</option>
          <option value="attention">Attention</option>
          <option value="urgent">Urgent</option>
        </select>
      </div>

      <!-- Buttons -->
      <div style="display:flex;gap:.6rem;">
        <button type="submit" class="btn btn-green" style="flex:1;padding:.65rem;">
          Envoyer
        </button>

        <button type="button"
          class="btn btn-gray"
          onclick="document.getElementById('alerteModal').classList.remove('open')">
          Annuler
        </button>
      </div>

    </form>
  </div>
</div>



<script>
// Toggle detail panel
function toggleDetail(clientId) {
  const panel = document.getElementById('detail-' + clientId);
  if (!panel) return;
  panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
}


// Pré-remplir modal alerte avec client
function openAlerte(clientId, clientNom) {
  document.getElementById('alerte_client_id').value = clientId;
  const titleField = document.getElementById('alerte_titre');
  if (titleField && !titleField.value) {
    titleField.value = 'Déséquilibre nutritionnel détecté';
  }
  document.getElementById('alerteModal').classList.add('open');
}


// Filtre client via URL
function filterClient(val) {
  window.location = val > 0 ? '?client_id=' + val : '?';
}


// Fermer modal en cliquant backdrop
document.getElementById('alerteModal').addEventListener('click', function(e) {
  if (e.target === this) this.classList.remove('open');
});
</script>


</body>
</html>


<?php include "partials/nutri_footer.php"; ?>
