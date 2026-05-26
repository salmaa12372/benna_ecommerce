<?php
$pageTitle = "Réclamations";
include "partials/livreur_header.php";
require_once __DIR__ . "/../../config/database.php";

// $cnx, $livreur, $id already set

// Requête corrigée : on joint livraisons pour récupérer les réclamations liées au livreur
$stmt = $cnx->prepare("
    SELECT
        r.id,
        r.commande_id,
        r.sujet,
        r.message,
        r.statut,
        r.created_at,
        u.nom AS client_nom
    FROM reclamations r
    JOIN commandes c ON c.id = r.commande_id
    JOIN livraisons l ON l.commande_id = c.id
    JOIN users u ON u.id = c.user_id
    WHERE l.livreur_id = ?
    ORDER BY r.created_at DESC
");
$stmt->execute([$id]);
$reclamations = $stmt->fetchAll(PDO::FETCH_ASSOC);

$ouvertes = count(array_filter($reclamations, fn($r) => $r['statut'] !== 'resolue'));
$resolues = count(array_filter($reclamations, fn($r) => $r['statut'] === 'resolue'));
?>

<!-- reste du HTML identique à ton original -->
<div class="topbar">
    <h1>Réclamations</h1>
    <span class="pill">
        <?= count($reclamations) ?> réclamation<?= count($reclamations) !== 1 ? 's' : '' ?>
    </span>
</div>

<div class="page-body">
    <!-- Stats -->
    <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin-bottom:24px;">
        <div class="stat-card">
            <div class="stat-val"><?= count($reclamations) ?></div>
            <div class="stat-label">Total</div>
        </div>
        <div class="stat-card <?= $ouvertes > 0 ? 'danger' : '' ?>">
            <div class="stat-val <?= $ouvertes > 0 ? 'danger' : '' ?>"><?= $ouvertes ?></div>
            <div class="stat-label">Non résolues</div>
        </div>
        <div class="stat-card">
            <div class="stat-val"><?= $resolues ?></div>
            <div class="stat-label">Résolues</div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <i class="fas fa-flag" style="color:var(--red);"></i>
                Réclamations liées à mes livraisons
            </div>
            <?php if ($ouvertes > 0): ?>
                <span class="badge badge-red"><?= $ouvertes ?> ouvertes</span>
            <?php endif; ?>
        </div>

        <?php if (count($reclamations) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Commande</th>
                    <th>Client</th>
                    <th>Sujet</th>
                    <th>Message</th>
                    <th>Statut</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($reclamations as $r): ?>
            <tr>
                <td style="font-family:monospace; font-weight:700; font-size:0.82rem; color:var(--muted);">
                    #<?= htmlspecialchars($r['commande_id']) ?>
                </td>
                <td style="font-weight:600;"><?= htmlspecialchars($r['client_nom'] ?? '') ?></td>
                <td style="font-size:0.875rem; max-width:160px;">
                    <?= htmlspecialchars(mb_substr($r['sujet'] ?? '', 0, 50)) ?>
                </td>
                <td style="font-size:0.82rem; color:var(--muted); max-width:200px;">
                    <?= htmlspecialchars(mb_substr($r['message'] ?? '', 0, 80)) ?>
                    <?= strlen($r['message'] ?? '') > 80 ? '…' : '' ?>
                </td>
                <td>
                    <?php
                    $sMap = [
                        'resolue'     => ['Résolue',    'badge-green'],
                        'en_cours'    => ['En cours',   'badge-blue'],
                        'en_attente'  => ['En attente', 'badge-yellow'],
                    ];
                    [$sl, $sc] = $sMap[$r['statut'] ?? ''] ?? [$r['statut'] ?? '—', 'badge-gray'];
                    echo "<span class=\"badge {$sc}\">{$sl}</span>";
                    ?>
                </td>
                <td style="font-size:0.8rem; color:var(--muted); white-space:nowrap;">
                    <?= date('d/m/Y', strtotime($r['created_at'])) ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
            <div style="text-align:center; padding:60px 24px; color:var(--muted);">
                <div style="font-size:0.9rem;">Aucune réclamation liée à vos livraisons.</div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include "partials/livreur_footer.php"; ?>