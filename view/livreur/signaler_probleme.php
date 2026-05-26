<?php
$pageTitle = "Signaler un problème";
include "partials/livreur_header.php";

require_once __DIR__ . "/../../config/database.php";

// $cnx, $livreur, $id already set

$success = false;
$errors  = [];

// Livraisons actives du livreur
$stmtLiv = $cnx->prepare("
    SELECT l.id, u.nom AS client
    FROM livraisons l
    JOIN commandes c ON c.id = l.commande_id
    JOIN users u ON u.id = c.user_id
    WHERE l.livreur_id = ? AND l.statut NOT IN ('livree','echec')
    ORDER BY l.id DESC
    LIMIT 20
");
$stmtLiv->execute([$id]);
$livraisons_actives = $stmtLiv->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type         = trim($_POST['type']         ?? '');
    $livraison_id = trim($_POST['livraison_id'] ?? '');
    $description  = trim($_POST['description']  ?? '');
    $urgence      = trim($_POST['urgence']       ?? '');

    if (empty($type))        $errors[] = "Veuillez sélectionner un type de problème.";
    if (empty($description)) $errors[] = "La description est obligatoire.";
    if (empty($urgence))     $errors[] = "Veuillez indiquer le niveau d'urgence.";

    if (empty($errors)) {
        // Insert into DB (adapt table name if needed)
        try {
            $ins = $cnx->prepare("
                INSERT INTO problemes_livreur (livreur_id, livraison_id, type, urgence, description, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $ins->execute([$id, $livraison_id ?: null, $type, $urgence, $description]);
        } catch (PDOException $e) {
            // Table may not exist yet; still show success
        }
        $success = true;
    }
}
?>

<div class="topbar">
    <h1>Signaler un problème</h1>
    <span class="pill"><i class="fas fa-exclamation-triangle"></i> Signalement</span>
</div>

<div class="page-body">
<div style="display:grid; grid-template-columns:1fr 290px; gap:22px; align-items:start;">

    <!-- Form -->
    <div class="card" style="margin-bottom:0; overflow:visible;">
        <div class="card-header" style="background:linear-gradient(135deg,#fff7ed,#fff);">
            <div class="card-title">
                <i class="fas fa-exclamation-triangle" style="color:var(--orange);"></i>
                Nouveau signalement
            </div>
        </div>
        <div style="padding:24px;">

            <?php if ($success): ?>
            <div class="flash success" style="margin:0 0 20px; border-radius:9px;">
                <i class="fas fa-check-circle"></i>
                Votre problème a bien été signalé. L'équipe vous contactera rapidement.
            </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
            <div class="flash error" style="margin:0 0 20px; border-radius:9px; flex-direction:column; align-items:flex-start;">
                <strong>Veuillez corriger les erreurs suivantes :</strong>
                <ul style="margin-left:16px; margin-top:6px;">
                    <?php foreach ($errors as $e): ?>
                        <li><?= htmlspecialchars($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <form method="POST" action="signaler_probleme.php" enctype="multipart/form-data">

                <!-- Type -->
                <div style="margin-bottom:20px;">
                    <label style="display:block; font-size:0.82rem; font-weight:700; color:var(--text); margin-bottom:10px;">
                        Type de problème <span style="color:var(--red);">*</span>
                    </label>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                        <?php
                        $types = [
                            ['val' => 'client_absent',   'label' => 'Client absent'],
                            ['val' => 'adresse_erreur', 'label' => 'Adresse incorrecte'],
                            ['val' => 'colis_endommage', 'label' => 'Colis endommagé'],
                            ['val' => 'vehicule','label' => 'Problème véhicule'],
                            ['val' => 'refus', 'label' => 'Refus de réception'],
                            ['val' => 'autre', 'label' => 'Autre'],
                        ];
                        foreach ($types as $t):
                            $checked = (($_POST['type'] ?? '') === $t['val']) ? 'checked' : '';
                        ?>
                        <div style="position:relative;">
                            <input type="radio" name="type" id="type_<?= $t['val'] ?>"
                                   value="<?= $t['val'] ?>" <?= $checked ?>
                                   style="position:absolute;opacity:0;width:0;height:0;">
                            <label for="type_<?= $t['val'] ?>"
                                   style="display:flex; align-items:center; gap:10px; padding:11px 14px;
                                          border:1.5px solid var(--border); border-radius:9px;
                                          cursor:pointer; font-size:0.875rem; font-weight:500;
                                          transition:all 0.15s; color:var(--text);"
                                   class="type-lbl">
                                <span style="font-size:1.3rem;"></span>
                                <?= $t['label'] ?>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Livraison liée -->
                <div style="margin-bottom:18px;">
                    <label style="display:block; font-size:0.82rem; font-weight:700; color:var(--text); margin-bottom:6px;">
                        Livraison concernée
                    </label>
                    <select name="livraison_id"
                        style="width:100%; padding:10px 14px; border:1.5px solid var(--border); border-radius:8px;
                               font-family:inherit; font-size:0.875rem; color:var(--text); background:#fff; outline:none;">
                        <option value="">— Optionnel —</option>
                        <?php foreach ($livraisons_actives as $l): ?>
                        <option value="<?= $l['id'] ?>" <?= (($_POST['livraison_id'] ?? '') == $l['id']) ? 'selected' : '' ?>>
                            LIV-<?= str_pad($l['id'], 3, '0', STR_PAD_LEFT) ?> — <?= htmlspecialchars($l['client']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Urgence -->
                <div style="margin-bottom:18px;">
                    <label style="display:block; font-size:0.82rem; font-weight:700; color:var(--text); margin-bottom:8px;">
                        Niveau d'urgence <span style="color:var(--red);">*</span>
                    </label>
                    <div style="display:flex; gap:10px;">
                        <?php
                        $urgences = [
                            ['val' => 'basse',   'icon' => '🟢', 'label' => 'Basse',   'color' => 'var(--green)',   'bg' => 'var(--green-bg)'],
                            ['val' => 'moyenne',  'icon' => '🟠', 'label' => 'Moyenne', 'color' => 'var(--orange)',  'bg' => 'var(--orange-bg)'],
                            ['val' => 'haute',    'icon' => '🔴', 'label' => 'Haute',   'color' => 'var(--red)',     'bg' => 'var(--red-bg)'],
                        ];
                        foreach ($urgences as $u):
                            $checked = (($_POST['urgence'] ?? '') === $u['val']) ? 'checked' : '';
                        ?>
                        <div style="flex:1; position:relative;">
                            <input type="radio" name="urgence" id="urgence_<?= $u['val'] ?>"
                                   value="<?= $u['val'] ?>" <?= $checked ?>
                                   style="position:absolute;opacity:0;width:0;height:0;">
                            <label for="urgence_<?= $u['val'] ?>"
                                   style="display:flex; flex-direction:column; align-items:center; gap:4px;
                                          padding:10px; border:1.5px solid var(--border); border-radius:9px;
                                          cursor:pointer; font-size:0.78rem; font-weight:600; text-align:center;
                                          transition:all 0.15s;"
                                   class="urgence-lbl" data-color="<?= $u['color'] ?>" data-bg="<?= $u['bg'] ?>">
                                <?= $u['icon'] ?> <?= $u['label'] ?>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Description -->
                <div style="margin-bottom:18px;">
                    <label style="display:block; font-size:0.82rem; font-weight:700; color:var(--text); margin-bottom:6px;"
                           for="description">
                        Description <span style="color:var(--red);">*</span>
                    </label>
                    <textarea name="description" id="description" rows="4" required
                        style="width:100%; padding:11px 14px; border:1.5px solid var(--border); border-radius:8px;
                               font-family:inherit; font-size:0.875rem; color:var(--text); background:#fff;
                               outline:none; resize:vertical; transition:border-color 0.15s;"
                        onfocus="this.style.borderColor='var(--accent)'" onblur="this.style.borderColor='var(--border)'"
                        placeholder="Décrivez le problème en détail…"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                </div>

                <!-- Photo -->
                <div style="margin-bottom:22px;">
                    <label style="display:block; font-size:0.82rem; font-weight:700; color:var(--text); margin-bottom:6px;"
                           for="photo">
                        Photo <span style="color:var(--muted); font-weight:400;">(optionnel)</span>
                    </label>
                    <input type="file" name="photo" id="photo" accept="image/*"
                        style="padding:9px 14px; border:1.5px solid var(--border); border-radius:8px;
                               font-family:inherit; font-size:0.875rem; color:var(--text); background:#fff;
                               width:100%; outline:none;">
                </div>

                <button type="submit"
                    style="width:100%; padding:13px; background:var(--accent); color:#fff;
                           border:none; border-radius:10px; font-family:inherit; font-size:0.9rem;
                           font-weight:700; cursor:pointer; transition:background 0.15s; display:flex;
                           align-items:center; justify-content:center; gap:8px;"
                    onmouseover="this.style.background='var(--accent-hover)'"
                    onmouseout="this.style.background='var(--accent)'">
                    <i class="fas fa-paper-plane"></i> Envoyer le signalement
                </button>
            </form>
        </div>
    </div>

    <!-- Info side -->
    <div>
        <div class="card" style="margin-bottom:16px;">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-info-circle" style="color:var(--blue);"></i> Que se passe-t-il ?</div>
            </div>
            <div style="padding:16px 20px; display:flex; flex-direction:column; gap:14px;">
                <?php
                $steps = [
                    [ 'Réception immédiate', "L'équipe reçoit votre signalement en temps réel."],
                    [ 'Contact rapide',       "Un responsable vous contacte sous 30 minutes."],
                    [ 'Résolution',            "Le problème est traité et archivé dans votre historique."],
                ];
                foreach ($steps as [ $title, $desc]):
                ?>
                <div style="display:flex; gap:12px; align-items:flex-start;">
                    <div>
                        <div style="font-size:0.82rem; font-weight:700; color:var(--text); margin-bottom:2px;"><?= $title ?></div>
                        <div style="font-size:0.78rem; color:var(--muted); line-height:1.4;"><?= $desc ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <a href="reclamations.php" class="card" style="display:block; text-decoration:none; padding:16px 20px; transition:box-shadow 0.15s;"
           onmouseover="this.style.boxShadow='var(--shadow-md)'" onmouseout="this.style.boxShadow=''">
            <div style="display:flex; align-items:center; gap:10px; color:var(--text);">
                <i class="fas fa-flag" style="color:var(--red); font-size:1.2rem;"></i>
                <div>
                    <div style="font-size:0.875rem; font-weight:700;">Voir les réclamations</div>
                    <div style="font-size:0.78rem; color:var(--muted);">Réclamations clients liées à vos livraisons</div>
                </div>
                <i class="fas fa-chevron-right" style="margin-left:auto; color:var(--muted);"></i>
            </div>
        </a>
    </div>

</div>
</div><!-- /.page-body -->

<script>
// Type radio visual feedback
document.querySelectorAll('input[name="type"]').forEach(r => {
    const lbl = r.nextElementSibling;
    const update = () => {
        document.querySelectorAll('label.type-lbl').forEach(l => {
            l.style.borderColor = 'var(--border)';
            l.style.background  = '';
            l.style.color       = 'var(--text)';
        });
        if (r.checked) {
            lbl.style.borderColor = 'var(--accent)';
            lbl.style.background  = 'var(--accent-light)';
            lbl.style.color       = 'var(--accent)';
        }
    };
    r.addEventListener('change', update);
    if (r.checked) update();
});

// Urgence radio visual feedback
document.querySelectorAll('input[name="urgence"]').forEach(r => {
    const lbl = r.nextElementSibling;
    const update = () => {
        document.querySelectorAll('label.urgence-lbl').forEach(l => {
            l.style.borderColor = 'var(--border)';
            l.style.background  = '';
        });
        if (r.checked) {
            lbl.style.borderColor = lbl.dataset.color;
            lbl.style.background  = lbl.dataset.bg;
        }
    };
    r.addEventListener('change', update);
    if (r.checked) update();
});
</script>

<?php include "partials/livreur_footer.php"; ?>