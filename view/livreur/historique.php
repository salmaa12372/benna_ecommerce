<?php
$pageTitle = "Historique des livraisons";
include "partials/livreur_header.php";
require_once __DIR__ . "/../../config/database.php";

// $cnx, $livreur, $id already set

// ─── Filters ─────────────────────────────────────────────────
$date_debut = $_GET['date_debut'] ?? '';
$date_fin   = $_GET['date_fin']   ?? '';
$statut_f   = $_GET['statut']     ?? '';

// ─── Query ───────────────────────────────────────────────────
$where  = ['l.livreur_id = :lid', "l.statut IN ('livree','echec')"];
$params = [':lid' => $id];

if ($date_debut) {
    $where[]               = 'DATE(c.date_commande) >= :date_debut';
    $params[':date_debut']  = $date_debut;
}
if ($date_fin) {
    $where[]             = 'DATE(c.date_commande) <= :date_fin';
    $params[':date_fin']  = $date_fin;
}
if ($statut_f) {
    $where[]           = 'l.statut = :statut';
    $params[':statut']  = $statut_f;
}

$sql = "
    SELECT
        l.id,
        l.commande_id,
        l.statut,
        u.nom                              AS client,
        c.adresse_livraison                AS adresse,
        DATE(c.date_commande)              AS date_liv,
        TIME(c.date_commande)              AS heure_liv,
        c.total                            AS montant,
        c.paiement_statut
    FROM livraisons l
    JOIN commandes c ON c.id  = l.commande_id
    JOIN users     u ON u.id  = c.user_id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY c.date_commande DESC
";

$stmt = $cnx->prepare($sql);
$stmt->execute($params);
$livraisons = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_livrees = count(array_filter($livraisons, fn($l) => $l['statut'] === 'livree'));
$total_echecs  = count(array_filter($livraisons, fn($l) => $l['statut'] === 'echec'));
?>

<div class="topbar">
    <h1>Historique</h1>
    <span class="pill"><?= count($livraisons) ?> livraison<?= count($livraisons) !== 1 ? 's' : '' ?></span>
</div>

<div class="page-body">

<!-- Mini stats -->
<div style="display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin-bottom:24px;">
    <div class="stat-card">
        <div class="stat-icon"></div>
        <div class="stat-val"><?= count($livraisons) ?></div>
        <div class="stat-label">Total filtré</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"></div>
        <div class="stat-val"><?= $total_livrees ?></div>
        <div class="stat-label">Livrées</div>
    </div>
    <div class="stat-card danger">
        <div class="stat-icon"></div>
        <div class="stat-val <?= $total_echecs > 0 ? 'danger' : '' ?>"><?= $total_echecs ?></div>
        <div class="stat-label">Échecs</div>
    </div>
</div>

<!-- Filters -->
<form method="GET" action="historique.php">
    <div style="display:flex; gap:10px; margin-bottom:20px; flex-wrap:wrap; align-items:center;">
        <input type="date" name="date_debut"
               style="padding:9px 14px; border:1.5px solid var(--border); border-radius:8px; background:#fff; font-family:inherit; font-size:13px; color:var(--text); outline:none;"
               value="<?= htmlspecialchars($date_debut) ?>">
        <input type="date" name="date_fin"
               style="padding:9px 14px; border:1.5px solid var(--border); border-radius:8px; background:#fff; font-family:inherit; font-size:13px; color:var(--text); outline:none;"
               value="<?= htmlspecialchars($date_fin) ?>">
        <select name="statut"
                style="padding:9px 14px; border:1.5px solid var(--border); border-radius:8px; background:#fff; font-family:inherit; font-size:13px; color:var(--text); outline:none; cursor:pointer;">
            <option value="">Tous les statuts</option>
            <option value="livree" <?= $statut_f === 'livree' ? 'selected' : '' ?>>Livrée</option>
            <option value="echec"  <?= $statut_f === 'echec'  ? 'selected' : '' ?>> Échec</option>
        </select>
        <button type="submit" class="btn btn-green">
            <i class="fas fa-search"></i> Filtrer
        </button>
        <?php if ($date_debut || $date_fin || $statut_f): ?>
        <a href="historique.php" class="btn btn-outline">
            <i class="fas fa-times"></i> Réinitialiser
        </a>
        <?php endif; ?>
    </div>
</form>

<!-- Table -->
<div class="card">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-history" style="color:var(--accent);"></i> Livraisons terminées</div>
        <span class="badge badge-gray"><?= count($livraisons) ?> résultat<?= count($livraisons) !== 1 ? 's' : '' ?></span>
    </div>

    <?php if (count($livraisons) > 0): ?>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Client</th>
                <th>Adresse</th>
                <th>Date & Heure</th>
                <th>Montant</th>
                <th>Paiement</th>
                <th>Statut</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($livraisons as $liv): ?>
            <tr>
                <td style="font-family:monospace; font-size:0.78rem; color:var(--muted); font-weight:700;">
                    #<?= htmlspecialchars($liv['commande_id']) ?>
                </td>
                <td style="font-weight:600;">
                    <?= htmlspecialchars($liv['client']) ?>
                </td>
                <td style="font-size:0.82rem; color:var(--muted); max-width:180px;">
                    <i class="fas fa-map-marker-alt" style="color:var(--accent); margin-right:3px;"></i>
                    <?= htmlspecialchars($liv['adresse']) ?>
                </td>
                <td style="font-size:0.82rem; color:var(--muted);">
                    <?= htmlspecialchars($liv['date_liv']) ?>
                    <span style="color:var(--accent); font-weight:500;"> à <?= substr($liv['heure_liv'], 0, 5) ?></span>
                </td>
                <td style="font-weight:700;">
                    <?= number_format((float)$liv['montant'], 3) ?> DT
                </td>
                <td>
                    <?php
                    $pMap = [
                        'paye'       => ['Payé',       'badge-green'],
                        'en_attente' => ['En attente', 'badge-yellow'],
                        'rembourse'  => ['Remboursé',  'badge-blue'],
                    ];
                    [$pl, $pc] = $pMap[$liv['paiement_statut'] ?? ''] ?? [$liv['paiement_statut'] ?? '—', 'badge-gray'];
                    echo "<span class=\"badge {$pc}\">{$pl}</span>";
                    ?>
                </td>
                <td>
                    <?php if ($liv['statut'] === 'livree'): ?>
                        <span class="badge badge-green"><i class="fas fa-check"></i> Livrée</span>
                    <?php else: ?>
                        <span class="badge badge-red"><i class="fas fa-times"></i> Échec</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <div style="text-align:center; padding:60px 24px; color:var(--muted);">

            <div style="font-size:0.875rem;">Aucune livraison dans l'historique.</div>
        </div>
    <?php endif; ?>
</div>

</div><!-- /.page-body -->

<?php include "partials/livreur_footer.php"; ?>