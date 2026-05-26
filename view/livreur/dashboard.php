<?php
$pageTitle = "Tableau de bord";

require_once __DIR__ . "/../../config/database.php";

if (session_status() === PHP_SESSION_NONE) session_start();

// Récupération de l'ID livreur (plus de valeur par défaut)
$id = $_SESSION['user_id'] ?? 0;
if (!$id) {
    header("Location: ../../client/signup.php");
    exit;
}

$stmt = $cnx->prepare("SELECT * FROM users WHERE id = ? AND role = 'livreur'");
$stmt->execute([$id]);
$livreur = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$livreur) {
    header("Location: ../../client/signup.php");
    exit;
}

// ========== TRAITEMENT POST ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $livraison_id = (int)($_POST['livraison_id'] ?? 0);

    if ($livraison_id > 0) {
        $check = $cnx->prepare("SELECT id FROM livraisons WHERE id = ? AND livreur_id = ?");
        $check->execute([$livraison_id, $id]);
        if ($check->fetch()) {
            switch ($action) {
                case 'accepter':
                    $cnx->prepare("UPDATE livraisons SET statut='acceptee' WHERE id=?")->execute([$livraison_id]);
                    $_SESSION['success'] = "✅ Livraison acceptée";
                    break;
                case 'demarrer':
                    $cnx->prepare("UPDATE livraisons SET statut='en_cours' WHERE id=?")->execute([$livraison_id]);
                    $cnx->prepare("UPDATE commandes SET statut='en_livraison' WHERE id=(SELECT commande_id FROM livraisons WHERE id=?)")->execute([$livraison_id]);
                    $_SESSION['success'] = "✅ Livraison démarrée";
                    break;
                case 'livrer':
                    $cnx->prepare("UPDATE livraisons SET statut='livree' WHERE id=?")->execute([$livraison_id]);
                    $cnx->prepare("UPDATE commandes SET statut='livree' WHERE id=(SELECT commande_id FROM livraisons WHERE id=?)")->execute([$livraison_id]);
                    $_SESSION['success'] = "✅ Livraison livrée";
                    break;
                case 'echec':
                    $probleme = trim($_POST['probleme'] ?? '');
                    $cnx->prepare("UPDATE livraisons SET statut='echec', probleme=? WHERE id=?")->execute([$probleme, $livraison_id]);
                    $cnx->prepare("UPDATE commandes SET statut='annulee' WHERE id=(SELECT commande_id FROM livraisons WHERE id=?)")->execute([$livraison_id]);
                    $_SESSION['success'] = "⚠️ Échec signalé";
                    break;
                default:
                    $_SESSION['error'] = "Action inconnue";
            }
        } else {
            $_SESSION['error'] = "Livraison non trouvée ou non autorisée";
        }
    } else {
        $_SESSION['error'] = "ID de livraison manquant";
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// ─── Stats ────────────────────────────────────────────────────
$stmt = $cnx->prepare("
    SELECT COUNT(*) FROM livraisons l
    JOIN commandes c ON c.id = l.commande_id
    WHERE l.livreur_id = ? AND DATE(c.date_commande) = CURDATE() AND l.statut NOT IN ('livree','echec')
");
$stmt->execute([$id]);
$livraisons_aujourdhui = (int)$stmt->fetchColumn();

$stmt = $cnx->prepare("SELECT COUNT(*) FROM livraisons WHERE livreur_id = ? AND statut = 'en_cours'");
$stmt->execute([$id]);
$en_cours = (int)$stmt->fetchColumn();

$stmt = $cnx->prepare("SELECT COUNT(*) FROM livraisons WHERE livreur_id = ? AND statut = 'livree'");
$stmt->execute([$id]);
$total_livrees = (int)$stmt->fetchColumn();

$stmt = $cnx->prepare("SELECT COUNT(*) FROM livraisons WHERE livreur_id = ? AND statut = 'echec'");
$stmt->execute([$id]);
$echecs = (int)$stmt->fetchColumn();

// ─── Open réclamations ────────────────────────────────────────
$stmtRecOuv = $cnx->prepare("
    SELECT COUNT(*) FROM reclamations r
    JOIN commandes c ON c.id = r.commande_id
    JOIN livraisons l ON l.commande_id = c.id
    WHERE l.livreur_id = ? AND r.statut != 'resolue'
");
$stmtRecOuv->execute([$id]);
$recOuvertes = (int)$stmtRecOuv->fetchColumn();

// ─── Livraisons actives (sans doublons) ───────────────────────
$stmt = $cnx->prepare("
    SELECT
        l.id AS livraison_id,
        l.statut AS livraison_statut,
        l.note_livreur,
        l.probleme,
        c.id AS commande_id,
        c.adresse_livraison,
        c.total,
        c.note_client,
        c.date_livraison_estimee,
        c.paiement_methode,
        c.paiement_statut,
        u.nom AS client_nom,
        u.telephone AS client_tel,
        GROUP_CONCAT(DISTINCT p.nom SEPARATOR ', ') AS produits,
        GROUP_CONCAT(DISTINCT cd.quantite SEPARATOR ', ') AS quantites
    FROM livraisons l
    JOIN commandes c ON c.id = l.commande_id
    JOIN users u ON u.id = c.user_id
    LEFT JOIN commande_details cd ON cd.commande_id = c.id
    LEFT JOIN produits p ON p.id = cd.produit_id
    WHERE l.livreur_id = ? AND l.statut IN ('assignee','acceptee','en_cours')
    GROUP BY l.id
    ORDER BY FIELD(l.statut, 'en_cours', 'acceptee', 'assignee'), c.date_livraison_estimee ASC
    LIMIT 10
");
$stmt->execute([$id]);
$livraisons_actives = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ─── Historique récent (sans doublons) ────────────────────────
$stmt = $cnx->prepare("
    SELECT
        l.id AS livraison_id,
        l.statut AS livraison_statut,
        l.updated_at,
        c.id AS commande_id,
        c.adresse_livraison,
        c.total,
        c.paiement_statut,
        u.nom AS client_nom,
        GROUP_CONCAT(DISTINCT p.nom SEPARATOR ', ') AS produits,
        GROUP_CONCAT(DISTINCT cd.quantite SEPARATOR ', ') AS quantites
    FROM livraisons l
    JOIN commandes c ON c.id = l.commande_id
    JOIN users u ON u.id = c.user_id
    LEFT JOIN commande_details cd ON cd.commande_id = c.id
    LEFT JOIN produits p ON p.id = cd.produit_id
    WHERE l.livreur_id = ? AND l.statut IN ('livree','echec')
    GROUP BY l.id
    ORDER BY l.updated_at DESC
    LIMIT 8
");
$stmt->execute([$id]);
$historique = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Helpers
function statutBadge(string $statut): string {
    $map = [
        'assignee' => ['Assignée',  'badge-yellow'],
        'acceptee' => ['Acceptée',  'badge-blue'],
        'en_cours' => ['En cours',  'badge-orange'],
        'livree'   => ['Livrée',    'badge-green'],
        'echec'    => ['Échec',     'badge-red'],
    ];
    [$label, $cls] = $map[$statut] ?? [$statut, 'badge-gray'];
    return "<span class=\"badge {$cls}\">{$label}</span>";
}

function paiementBadge(string $statut): string {
    $map = [
        'en_attente' => ['En attente', 'badge-yellow'],
        'paye'       => ['Payé',       'badge-green'],
        'rembourse'  => ['Remboursé',  'badge-blue'],
    ];
    [$label, $cls] = $map[$statut] ?? [$statut, 'badge-gray'];
    return "<span class=\"badge {$cls}\">{$label}</span>";
}

include "partials/livreur_header.php";
?>

<!-- ── Topbar ─────────────────────────────────────────────── -->
<div class="topbar">
    <h1>Tableau de bord</h1>
    <span class="pill"><i class="fas fa-motorcycle"></i> <?= htmlspecialchars($livreur['nom']) ?></span>
</div>

<div class="page-body">

<!-- ── Alerte réclamations ───────────────────────────────── -->
<?php if ($recOuvertes > 0): ?>
<div class="card card-danger" style="margin-bottom:20px;">
    <div class="card-header" style="border-left: none;">
        <div class="card-title text-danger">
            <i class="fas fa-exclamation-triangle"></i>
            <?= $recOuvertes ?> réclamation<?= $recOuvertes > 1 ? 's' : '' ?> non résolue<?= $recOuvertes > 1 ? 's' : '' ?>
        </div>
        <a href="reclamations.php" class="btn btn-red btn-sm">Voir les réclamations →</a>
    </div>
</div>
<?php endif; ?>

<!-- ── Stats ─────────────────────────────────────────────── -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-val"><?= $livraisons_aujourdhui ?></div>
        <div class="stat-label">Livraisons aujourd'hui</div>
    </div>
    <div class="stat-card warn">
        <div class="stat-val warn"><?= $en_cours ?></div>
        <div class="stat-label">En cours</div>
    </div>
    <div class="stat-card blue">
        <div class="stat-val blue"><?= $total_livrees ?></div>
        <div class="stat-label">Total livrées</div>
    </div>
    <div class="stat-card <?= $echecs > 0 ? 'danger' : '' ?>">
        <div class="stat-val <?= $echecs > 0 ? 'danger' : '' ?>"><?= $echecs ?></div>
        <div class="stat-label">Échecs</div>
    </div>
</div>

<!-- ── Two-column grid ───────────────────────────────────── -->
<div style="display:grid; grid-template-columns: 1fr 360px; gap:20px; align-items:start;">

    <!-- Livraisons actives -->
    <div class="card" style="margin-bottom:0;">
        <div class="card-header">
            <div class="card-title"><i class="fas fa-truck" style="color:var(--accent);"></i> Livraisons actives</div>
            <a href="mes_livraisons.php" class="btn btn-green btn-sm">Voir tout</a>
        </div>

        <?php if (empty($livraisons_actives)): ?>
            <div style="text-align:center; padding:48px 20px; color:var(--muted);">
                <p style="font-size:0.875rem;">Aucune livraison en attente pour le moment.</p>
            </div>
        <?php else: ?>
            <?php foreach ($livraisons_actives as $liv): ?>
            <div style="padding:16px 22px; border-bottom:1px solid var(--border); transition:background 0.12s;"
                 onmouseover="this.style.background='#f7faf7'" onmouseout="this.style.background=''">

                <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:14px; margin-bottom:10px;">
                    <!-- Left info -->
                    <div style="flex:1;">
                        <div style="font-size:0.7rem; color:var(--muted); font-weight:700; letter-spacing:.06em; text-transform:uppercase; margin-bottom:3px;">
                            #CMD-<?= str_pad($liv['commande_id'], 4, '0', STR_PAD_LEFT) ?>
                            &nbsp;·&nbsp;
                            LIV-<?= str_pad($liv['livraison_id'], 3, '0', STR_PAD_LEFT) ?>
                        </div>
                        <div style="font-size:0.95rem; font-weight:700; color:var(--text); margin-bottom:2px;">
                            <?= $liv['livraison_statut']==='en_cours' ? '<span class="dot-pulse"></span>' : '' ?>
                            <?= htmlspecialchars($liv['client_nom']) ?>
                        </div>
                        <div style="font-size:0.82rem; color:var(--muted);">
                            <?= htmlspecialchars($liv['produits']) ?> × <?= $liv['quantites'] ?? 1 ?>
                        </div>
                        <div style="font-size:0.8rem; color:var(--muted); margin-top:4px; display:flex; align-items:center; gap:4px;">
                            <i class="fas fa-map-marker-alt" style="color:var(--accent);"></i>
                            <?= htmlspecialchars($liv['adresse_livraison']) ?>
                        </div>
                        <?php if (!empty($liv['client_tel'])): ?>
                        <div style="font-size:0.8rem; color:var(--muted); margin-top:3px; display:flex; align-items:center; gap:4px;">
                            <i class="fas fa-phone" style="color:var(--accent);"></i>
                            <a href="tel:<?= htmlspecialchars($liv['client_tel']) ?>"
                               style="color:var(--accent); text-decoration:none; font-weight:500;">
                               <?= htmlspecialchars($liv['client_tel']) ?>
                            </a>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($liv['note_client'])): ?>
                        <div style="font-size:0.78rem; color:var(--muted); margin-top:3px; display:flex; align-items:center; gap:4px;">
                            <i class="fas fa-comment-dots"></i> <?= htmlspecialchars($liv['note_client']) ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Right info -->
                    <div style="display:flex; flex-direction:column; align-items:flex-end; gap:6px; flex-shrink:0;">
                        <?= statutBadge($liv['livraison_statut']) ?>
                        <div style="font-size:1.05rem; font-weight:700; color:var(--text);">
                            <?= number_format((float)$liv['total'], 3) ?> DT
                        </div>
                        <?= paiementBadge($liv['paiement_statut']) ?>
                        <?php if (!empty($liv['date_livraison_estimee'])): ?>
                        <span class="badge badge-gray">
                            <i class="fas fa-calendar-alt"></i>
                            <?= date('d/m', strtotime($liv['date_livraison_estimee'])) ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Action buttons -->
                <div style="display:flex; gap:7px; flex-wrap:wrap;">
                    <?php if ($liv['livraison_statut'] === 'assignee'): ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="livraison_id" value="<?= $liv['livraison_id'] ?>">
                        <input type="hidden" name="action" value="accepter">
                        <button class="btn btn-outline btn-sm" type="submit">
                            <i class="fas fa-check"></i> Accepter
                        </button>
                    </form>
                    <?php endif; ?>

                    <?php if (in_array($liv['livraison_statut'], ['assignee','acceptee'])): ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="livraison_id" value="<?= $liv['livraison_id'] ?>">
                        <input type="hidden" name="action" value="demarrer">
                        <button class="btn btn-green btn-sm" type="submit">
                            <i class="fas fa-play"></i> Démarrer
                        </button>
                    </form>
                    <?php endif; ?>

                    <?php if ($liv['livraison_statut'] === 'en_cours'): ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="livraison_id" value="<?= $liv['livraison_id'] ?>">
                        <input type="hidden" name="action" value="livrer">
                        <button class="btn btn-blue btn-sm" type="submit">
                            <i class="fas fa-box"></i> Marquer livrée
                        </button>
                    </form>
                    <?php endif; ?>

                    <button class="btn btn-red btn-sm" type="button"
                        onclick="document.getElementById('modal-<?= $liv['livraison_id'] ?>').classList.add('open')">
                        <i class="fas fa-times"></i> Signaler échec
                    </button>
                </div>
            </div>

            <!-- Modal échec -->
            <div class="modal-overlay" id="modal-<?= $liv['livraison_id'] ?>">
                <div class="modal-box">
                    <h4><i class="fas fa-exclamation-triangle" style="color:var(--red);"></i> Signaler un échec</h4>
                    <p>Commande #CMD-<?= str_pad($liv['commande_id'],4,'0',STR_PAD_LEFT) ?>
                       — <?= htmlspecialchars($liv['client_nom']) ?></p>
                    <form method="POST">
                        <input type="hidden" name="livraison_id" value="<?= $liv['livraison_id'] ?>">
                        <input type="hidden" name="action" value="echec">
                        <textarea name="probleme" rows="3" required
                            placeholder="Décrivez le problème (absent, adresse introuvable…)"></textarea>
                        <div class="modal-actions">
                            <button type="button" class="btn btn-outline btn-sm"
                                onclick="document.getElementById('modal-<?= $liv['livraison_id'] ?>').classList.remove('open')">
                                Annuler
                            </button>
                            <button type="submit" class="btn btn-red btn-sm">Confirmer l'échec</button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Historique récent -->
    <div class="card" style="margin-bottom:0;">
        <div class="card-header">
            <div class="card-title"><i class="fas fa-history" style="color:var(--accent);"></i> Historique récent</div>
            <a href="historique.php" class="btn btn-outline btn-sm">Voir tout</a>
        </div>

        <?php if (empty($historique)): ?>
            <div style="text-align:center; padding:40px 20px; color:var(--muted);">
                <p style="font-size:0.875rem;">Aucune livraison dans l'historique.</p>
            </div>
        <?php else: ?>
            <?php foreach ($historique as $h): ?>
            <div style="display:flex; align-items:flex-start; gap:10px; padding:12px 22px; border-bottom:1px solid var(--border);">
                <div style="width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:13px; flex-shrink:0; margin-top:1px;
                    background:<?= $h['livraison_statut']==='livree' ? 'var(--green-bg)' : 'var(--red-bg)' ?>;">
                    <?= $h['livraison_statut']==='livree' ? '✓' : '✗' ?>
                </div>
                <div style="flex:1;">
                    <div style="font-size:0.875rem; font-weight:600; color:var(--text);">
                        <?= htmlspecialchars($h['client_nom']) ?>
                    </div>
                    <div style="font-size:0.78rem; color:var(--muted);">
                        <?= htmlspecialchars($h['produits']) ?>
                    </div>
                    <div style="font-size:0.72rem; color:var(--muted); margin-top:2px;">
                        <i class="fas fa-clock"></i>
                        <?= date('d/m/Y à H:i', strtotime($h['updated_at'])) ?>
                    </div>
                </div>
                <div style="text-align:right; flex-shrink:0;">
                    <div style="font-size:0.875rem; font-weight:700; color:var(--text);">
                        <?= number_format((float)$h['total'], 3) ?> DT
                    </div>
                    <div style="margin-top:4px;">
                        <?= statutBadge($h['livraison_statut']) ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div><!-- /.two-col -->

</div><!-- /.page-body -->

<?php include "partials/livreur_footer.php"; ?>