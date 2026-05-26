<?php
$pageTitle = "Mes livraisons";

require_once __DIR__ . "/../../config/database.php";
if (session_status() === PHP_SESSION_NONE) session_start();

// Récupération du livreur (identique à header)
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

// ========== TRAITEMENT POST (formulaires classiques) ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $idLiv  = (int)($_POST['livraison_id'] ?? 0);

    if ($idLiv > 0) {
        // Vérifier que la livraison appartient bien au livreur
        $check = $cnx->prepare("SELECT id FROM livraisons WHERE id = ? AND livreur_id = ?");
        $check->execute([$idLiv, $id]);
        if ($check->fetch()) {
            if ($action === 'accepter') {
                $cnx->prepare("UPDATE livraisons SET statut='acceptee' WHERE id=?")->execute([$idLiv]);
                $_SESSION['success'] = "✅ Livraison acceptée";
            } elseif ($action === 'demarrer') {
                $cnx->prepare("UPDATE livraisons SET statut='en_cours' WHERE id=?")->execute([$idLiv]);
                $cnx->prepare("UPDATE commandes SET statut='en_livraison' WHERE id=(SELECT commande_id FROM livraisons WHERE id=?)")->execute([$idLiv]);
                $_SESSION['success'] = "✅ Livraison démarrée";
                header("Location: carte.php");
                exit;
            } elseif ($action === 'livrer') {
                $cnx->prepare("UPDATE livraisons SET statut='livree' WHERE id=?")->execute([$idLiv]);
                $cnx->prepare("UPDATE commandes SET statut='livree' WHERE id=(SELECT commande_id FROM livraisons WHERE id=?)")->execute([$idLiv]);
                $_SESSION['success'] = "✅ Livraison livrée";
            } elseif ($action === 'echec') {
                $probleme = $_POST['probleme'] ?? '';
                $cnx->prepare("UPDATE livraisons SET statut='echec', probleme=? WHERE id=?")->execute([$probleme, $idLiv]);
                $cnx->prepare("UPDATE commandes SET statut='annulee' WHERE id=(SELECT commande_id FROM livraisons WHERE id=?)")->execute([$idLiv]);
                $_SESSION['success'] = "⚠️ Échec signalé";
            }
        } else {
            $_SESSION['error'] = "Livraison non trouvée ou non autorisée";
        }
    } else {
        $_SESSION['error'] = "ID de livraison manquant";
    }
    header("Location: mes_livraisons.php");
    exit;
}

// ─── Livraisons actives (sans doublons) ────────────────────────
$stmt = $cnx->prepare("
    SELECT
        l.id,
        l.statut,
        l.commande_id,
        c.adresse_livraison   AS adresse,
        c.note_client,
        c.date_livraison_estimee,
        c.total,
        c.paiement_statut,
        c.paiement_methode,
        u.nom                 AS client,
        u.telephone           AS client_tel,
        TIME(c.date_commande) AS heure_prevue,
        GROUP_CONCAT(DISTINCT p.nom SEPARATOR ', ') AS produits
    FROM livraisons l
    JOIN commandes  c ON c.id = l.commande_id
    JOIN users      u ON u.id = c.user_id
    LEFT JOIN commande_details cd ON cd.commande_id = c.id
    LEFT JOIN produits p ON p.id = cd.produit_id
    WHERE l.livreur_id = :lid
      AND l.statut NOT IN ('livree', 'echec')
    GROUP BY l.id
    ORDER BY FIELD(l.statut, 'en_cours', 'acceptee', 'assignee'), c.date_commande ASC
");
$stmt->execute([':lid' => $id]);
$livraisons = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total      = count($livraisons);
$en_cours   = count(array_filter($livraisons, fn($l) => $l['statut'] === 'en_cours'));
$acceptees  = count(array_filter($livraisons, fn($l) => $l['statut'] === 'acceptee'));
$assignees  = count(array_filter($livraisons, fn($l) => $l['statut'] === 'assignee'));

function statutLabel(string $s): string {
    return match($s) {
        'en_cours' => 'En cours',
        'acceptee' => 'Acceptée',
        'assignee' => 'Assignée',
        default    => $s,
    };
}
function statutClass(string $s): string {
    return match($s) {
        'en_cours' => 'badge-orange',
        'acceptee' => 'badge-blue',
        'assignee' => 'badge-yellow',
        default    => 'badge-gray',
    };
}

include "partials/livreur_header.php";
?>

<div class="topbar">
    <h1>Mes Livraisons</h1>
    <span class="pill"><i class="fas fa-motorcycle"></i> <?= htmlspecialchars($livreur['nom']) ?></span>
</div>

<div class="page-body">

<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-val" id="stat-total"><?= $total ?></div>
        <div class="stat-label">Total actives</div>
    </div>
    <div class="stat-card warn">
        <div class="stat-val warn" id="stat-encours"><?= $en_cours ?></div>
        <div class="stat-label">En cours</div>
    </div>
    <div class="stat-card blue">
        <div class="stat-val blue"><?= $acceptees ?></div>
        <div class="stat-label">Acceptées</div>
    </div>
    <div class="stat-card">
        <div class="stat-val"><?= $assignees ?></div>
        <div class="stat-label">Assignées</div>
    </div>
</div>

<!-- List -->
<div style="font-size:0.9rem; font-weight:700; color:var(--muted); margin-bottom:12px; text-transform:uppercase; letter-spacing:.06em;">
    <?= $total ?> livraison<?= $total !== 1 ? 's' : '' ?> en cours
</div>

<?php if ($total > 0): ?>
    <?php foreach ($livraisons as $i => $liv): ?>
    <div class="card" style="margin-bottom:12px; padding:18px 22px;"
         id="card-<?= $liv['id'] ?>">
        <div style="display:flex; align-items:flex-start; gap:16px;">

            <!-- Number circle -->
            <div style="width:40px; height:40px; background:var(--accent-light); border-radius:50%;
                        display:flex; align-items:center; justify-content:center;
                        font-weight:700; color:var(--accent); font-size:14px; flex-shrink:0;">
                <?= $i + 1 ?>
            </div>

            <!-- Info -->
            <div style="flex:1; min-width:0;">
                <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:10px; margin-bottom:6px;">
                    <div>
                        <div style="font-weight:700; font-size:0.95rem; color:var(--text);">
                            <?= $liv['statut']==='en_cours' ? '<span class="dot-pulse"></span>' : '' ?>
                            <?= htmlspecialchars($liv['client']) ?>
                        </div>
                        <div style="font-size:0.8rem; color:var(--muted); margin-top:2px;">
                            <i class="fas fa-map-marker-alt" style="color:var(--accent);"></i>
                            <?= htmlspecialchars($liv['adresse']) ?>
                        </div>
                    </div>
                    <div style="display:flex; flex-direction:column; align-items:flex-end; gap:5px; flex-shrink:0;">
                        <span class="badge <?= statutClass($liv['statut']) ?>" id="badge-<?= $liv['id'] ?>">
                            <?= statutLabel($liv['statut']) ?>
                        </span>
                        <div style="font-size:0.95rem; font-weight:700; color:var(--text);">
                            <?= number_format((float)$liv['total'], 3) ?> DT
                        </div>
                    </div>
                </div>

                <!-- Meta chips -->
                <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-bottom:10px;">
                    <span class="badge badge-gray">#<?= htmlspecialchars($liv['commande_id']) ?></span>
                    <?php if ($liv['heure_prevue']): ?>
                    <span class="badge badge-gray">
                        <i class="fas fa-clock"></i> <?= substr($liv['heure_prevue'], 0, 5) ?>
                    </span>
                    <?php endif; ?>
                    <?php if (!empty($liv['client_tel'])): ?>
                    <a href="tel:<?= htmlspecialchars($liv['client_tel']) ?>"
                       style="color:var(--accent); font-size:0.8rem; text-decoration:none; font-weight:500;">
                        <i class="fas fa-phone"></i> <?= htmlspecialchars($liv['client_tel']) ?>
                    </a>
                    <?php endif; ?>
                    <?php if (!empty($liv['note_client'])): ?>
                    <span class="badge badge-yellow">
                        <i class="fas fa-comment-dots"></i> <?= htmlspecialchars(mb_substr($liv['note_client'], 0, 40)) ?>
                    </span>
                    <?php endif; ?>
                </div>

                <!-- Actions -->
                <div style="display:flex; gap:8px; flex-wrap:wrap;" id="actions-<?= $liv['id'] ?>">
                    <?php if ($liv['statut'] === 'assignee'): ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="livraison_id" value="<?= $liv['id'] ?>">
                        <input type="hidden" name="action" value="accepter">
                        <button class="btn btn-outline btn-sm" type="submit">
                            <i class="fas fa-check"></i> Accepter
                        </button>
                    </form>
                    <?php endif; ?>

                    <?php if (in_array($liv['statut'], ['assignee','acceptee'])): ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="livraison_id" value="<?= $liv['id'] ?>">
                        <input type="hidden" name="action" value="demarrer">
                        <button class="btn btn-green btn-sm" type="submit">
                            <i class="fas fa-play"></i> Démarrer
                        </button>
                    </form>
                    <?php elseif ($liv['statut'] === 'en_cours'): ?>
                    <button class="btn btn-blue btn-sm"
                            onclick="changerStatut(<?= $liv['id'] ?>, 'livree')">
                        <i class="fas fa-box"></i> Marquer livrée
                    </button>
                    <?php endif; ?>

                    <button class="btn btn-red btn-sm"
                        onclick="document.getElementById('modal-<?= $liv['id'] ?>').classList.add('open')">
                        <i class="fas fa-times"></i> Signaler échec
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Failure modal -->
    <div class="modal-overlay" id="modal-<?= $liv['id'] ?>">
        <div class="modal-box">
            <h4><i class="fas fa-exclamation-triangle" style="color:var(--red);"></i> Signaler un échec</h4>
            <p>Commande #<?= $liv['commande_id'] ?> — <?= htmlspecialchars($liv['client']) ?></p>
            <form method="POST">
                <input type="hidden" name="livraison_id" value="<?= $liv['id'] ?>">
                <input type="hidden" name="action" value="echec">
                <textarea name="probleme" rows="3" required
                    placeholder="Décrivez le problème (absent, adresse introuvable…)"></textarea>
                <div class="modal-actions">
                    <button type="button" class="btn btn-outline btn-sm"
                        onclick="document.getElementById('modal-<?= $liv['id'] ?>').classList.remove('open')">
                        Annuler
                    </button>
                    <button type="submit" class="btn btn-red btn-sm">Confirmer l'échec</button>
                </div>
            </form>
        </div>
    </div>
    <?php endforeach; ?>

<?php else: ?>
    <div class="card" style="text-align:center; padding:60px 24px; color:var(--muted);">
        <div style="font-size:0.9rem;">Aucune livraison en attente pour le moment.</div>
    </div>
<?php endif; ?>

</div><!-- /.page-body -->

<!-- Toast -->
<div id="toast" style="
    position:fixed; bottom:28px; right:28px;
    background:var(--sidebar-bg); color:#fff;
    padding:12px 20px; border-radius:10px;
    font-size:13px; font-weight:600;
    opacity:0; transform:translateY(10px);
    transition:all 0.3s; z-index:9999;
    pointer-events:none;
"></div>

<script>
async function changerStatut(id, nouveauStatut) {
    const card = document.getElementById('card-' + id);
    card.style.opacity = '0.6';
    card.style.pointerEvents = 'none';

    try {
        const res = await fetch('update_livraison_statut.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, statut: nouveauStatut }),
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.message);

        // Mise à jour de l'affichage
        const badge = document.getElementById('badge-' + id);
        const labelMap = { livree: 'Livrée', echec: 'Échec' };
        const classMap = { livree: 'badge-green', echec: 'badge-red' };
        badge.className = 'badge ' + (classMap[nouveauStatut] || 'badge-gray');
        badge.textContent = labelMap[nouveauStatut] || nouveauStatut;

        if (nouveauStatut === 'livree' || nouveauStatut === 'echec') {
            setTimeout(() => card.remove(), 400);
        }

        showToast('✅ Statut mis à jour !');
    } catch (err) {
        showToast('❌ ' + (err.message || 'Erreur réseau'), true);
    } finally {
        card.style.opacity = '1';
        card.style.pointerEvents = '';
    }
}

function showToast(msg, isError = false) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.style.background = isError ? 'var(--red)' : 'var(--sidebar-bg)';
    t.style.opacity = '1';
    t.style.transform = 'translateY(0)';
    setTimeout(() => {
        t.style.opacity = '0';
        t.style.transform = 'translateY(10px)';
    }, 3000);
}
</script>

<?php include "partials/livreur_footer.php"; ?>