<?php
// view/usine/reclamations.php
$pageTitle = "Réclamations reçues";
include "partials/usine_header.php";

// ── Handle reply POST ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'repondre') {
    $id      = (int)($_POST['reclamation_id'] ?? 0);
    $reponse = trim($_POST['reponse'] ?? '');
    $statut  = $_POST['statut'] ?? 'transmise_usine';
    $par     = $_SESSION['user_id'] ?? $_SESSION['id'] ?? 0;

    if ($id && $reponse !== '') {
        $ok = repondreReclamation($cnx, $id, $reponse, $statut, $par);
        $_SESSION[$ok ? 'success' : 'error'] = $ok
            ? "Réponse envoyée avec succès."
            : "Erreur lors de l'envoi de la réponse.";
    } else {
        $_SESSION['error'] = "Veuillez saisir une réponse avant d'envoyer.";
    }
    header("Location: " . BASE . "/view/usine/reclamations.php");
    exit();
}

$reclamations = getReclamationsUsine($cnx);

// Counts per statut
$nbTotal    = count($reclamations);
$nbEnAttente = count(array_filter($reclamations, fn($r) => $r['statut'] === 'transmise_usine'));
$nbResolues  = count(array_filter($reclamations, fn($r) => $r['statut'] === 'resolue'));

$statutConfig = [
    'transmise_usine' => ['cls' => 'badge-yellow', 'label' => 'En attente',  'icon' => 'fa-clock'],
    'resolue'         => ['cls' => 'badge-green',  'label' => 'Résolue',     'icon' => 'fa-check-circle'],
    'ouverte'         => ['cls' => 'badge-gray',   'label' => 'Ouverte',     'icon' => 'fa-circle'],
    'en_cours'        => ['cls' => 'badge-blue',   'label' => 'En cours',    'icon' => 'fa-spinner'],
    'fermee'          => ['cls' => 'badge-gray',   'label' => 'Fermée',      'icon' => 'fa-ban'],
];
?>

<style>
/* ── Layout ──────────────────────────────────────────────── */
.topbar       { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.4rem; }
.topbar h1    { font-family:var(--font-head,serif); font-size:1.5rem; display:flex; align-items:center; gap:.45rem; }

/* ── Stats strip ─────────────────────────────────────────── */
.stats-strip  { display:grid; grid-template-columns:repeat(auto-fill,minmax(130px,1fr)); gap:.8rem; margin-bottom:1.3rem; }
.stat-card    { background:var(--card,#fff); border-radius:var(--radius,12px); padding:.9rem 1rem; box-shadow:var(--shadow,0 1px 4px rgba(0,0,0,.06)); text-align:center; position:relative; overflow:hidden; }
.stat-val     { font-family:var(--font-head,serif); font-size:1.7rem; font-weight:700; line-height:1; }
.stat-lbl     { font-size:.72rem; color:var(--muted,#6b7280); margin-top:.3rem; text-transform:uppercase; letter-spacing:.05em; }
.stat-card::after { content:''; position:absolute; bottom:0; left:0; right:0; height:3px; }
.stat-total::after  { background:#6366f1; }
.stat-warn::after   { background:#f59e0b; }
.stat-ok::after     { background:#22c55e; }
.stat-total .stat-val { color:#6366f1; }
.stat-warn  .stat-val { color:#f59e0b; }
.stat-ok    .stat-val { color:#22c55e; }

/* ── Filter bar ──────────────────────────────────────────── */
.filter-bar   { display:flex; gap:.6rem; margin-bottom:1rem; flex-wrap:wrap; align-items:center; }
.filter-input,
.filter-select { height:36px; padding:0 .75rem; border:1px solid var(--border,#e5e7eb); border-radius:9px; background:var(--card,#fff); color:var(--text,#111); font-size:.83rem; }
.filter-input  { flex:1; min-width:180px; }
.filter-input:focus,.filter-select:focus { outline:none; border-color:#22c55e; }

/* ── Card / Table ────────────────────────────────────────── */
.card         { background:var(--card,#fff); border-radius:var(--radius,12px); padding:1rem 1.1rem; box-shadow:var(--shadow,0 1px 4px rgba(0,0,0,.06)); margin-bottom:1rem; }
.card-title   { font-size:.92rem; font-weight:600; margin-bottom:.9rem; display:flex; align-items:center; gap:.5rem; }
.table-wrapper{ overflow-x:auto; }
table         { width:100%; border-collapse:collapse; font-size:.85rem; }
thead th      { padding:.55rem .75rem; text-align:left; color:var(--muted,#6b7280); font-weight:600; font-size:.73rem; text-transform:uppercase; letter-spacing:.05em; border-bottom:2px solid var(--border,#e5e7eb); white-space:nowrap; }
tbody td      { padding:.62rem .75rem; border-bottom:1px solid var(--border,#e5e7eb); vertical-align:middle; }
tbody tr:last-child td { border-bottom:none; }
tbody tr:hover td      { background:var(--bg,#f9fafb); }

/* ── Badges ──────────────────────────────────────────────── */
.badge        { display:inline-flex; align-items:center; gap:4px; padding:.22rem .65rem; border-radius:20px; font-size:.72rem; font-weight:600; }
.badge-yellow { background:#fef3c7; color:#92400e; }
.badge-green  { background:#d1fae5; color:#065f46; }
.badge-blue   { background:#dbeafe; color:#1e40af; }
.badge-gray   { background:#f3f4f6; color:#374151; }
.badge-red    { background:#fee2e2; color:#991b1b; }
.count-pill   { background:var(--bg,#f3f4f6); border:1px solid var(--border,#e5e7eb); border-radius:20px; padding:.1rem .6rem; font-size:.75rem; font-weight:600; color:var(--muted,#6b7280); margin-left:auto; }

/* ── Client cell ─────────────────────────────────────────── */
.client-cell  { display:flex; align-items:center; gap:.45rem; }
.c-avatar     { width:30px; height:30px; border-radius:50%; background:#d1fae5; display:flex; align-items:center; justify-content:center; font-size:.68rem; font-weight:700; color:#065f46; flex-shrink:0; }

/* ── Message preview ─────────────────────────────────────── */
.msg-preview  { color:var(--muted,#6b7280); font-size:.8rem; max-width:220px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.date-muted   { color:var(--muted,#6b7280); font-size:.78rem; white-space:nowrap; }

/* ── Reply indicator ─────────────────────────────────────── */
.replied-chip { display:inline-flex; align-items:center; gap:4px; font-size:.72rem; color:#065f46; background:#d1fae5; border-radius:20px; padding:.15rem .5rem; }

/* ── Action button ───────────────────────────────────────── */
.btn-reply    { height:30px; padding:0 .85rem; border-radius:8px; border:none; background:#22c55e; color:white; font-size:.76rem; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:5px; transition:.15s; }
.btn-reply:hover { background:#16a34a; transform:translateY(-1px); }
.btn-view     { height:30px; padding:0 .75rem; border-radius:8px; border:1px solid var(--border,#e5e7eb); background:var(--card,#fff); color:var(--text,#111); font-size:.76rem; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:5px; transition:.15s; }
.btn-view:hover { background:var(--bg,#f9fafb); }
.actions-cell { display:flex; gap:.4rem; align-items:center; flex-wrap:nowrap; }

.empty-td     { text-align:center; padding:2.5rem !important; color:var(--muted,#6b7280); }

/* ── Modal ───────────────────────────────────────────────── */
.modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:1000; align-items:center; justify-content:center; padding:1rem; }
.modal-box     { background:var(--card,#fff); border-radius:16px; width:100%; max-width:560px; max-height:90vh; overflow-y:auto; box-shadow:0 12px 48px rgba(0,0,0,.22); }
.modal-header  { display:flex; align-items:center; justify-content:space-between; padding:1.2rem 1.4rem; border-bottom:1px solid var(--border,#e5e7eb); }
.modal-header h2 { font-family:var(--font-head,serif); font-size:1.1rem; display:flex; align-items:center; gap:.4rem; }
.modal-close   { background:none; border:none; font-size:1.5rem; cursor:pointer; color:var(--muted,#6b7280); line-height:1; }
.modal-close:hover { color:#ef4444; }
.modal-body    { padding:1.3rem 1.4rem; }
.modal-footer  { padding:.9rem 1.4rem; border-top:1px solid var(--border,#e5e7eb); display:flex; gap:.6rem; justify-content:flex-end; }

/* Context block */
.ctx-block     { background:var(--bg,#f9fafb); border-radius:10px; padding:.8rem 1rem; margin-bottom:1rem; font-size:.83rem; }
.ctx-row       { display:flex; gap:.5rem; margin-bottom:.35rem; }
.ctx-row:last-child { margin-bottom:0; }
.ctx-label     { font-weight:600; color:var(--muted,#6b7280); min-width:70px; flex-shrink:0; font-size:.77rem; }
.ctx-val       { color:var(--text,#111); }
.orig-msg      { background:#fff; border:1px solid var(--border,#e5e7eb); border-radius:8px; padding:.7rem .9rem; font-size:.82rem; color:var(--text,#111); line-height:1.55; margin-bottom:1rem; white-space:pre-wrap; }
.prev-reply    { background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; padding:.7rem .9rem; font-size:.82rem; color:#065f46; line-height:1.55; margin-bottom:1rem; }
.prev-reply-lbl{ font-size:.73rem; font-weight:600; color:#22c55e; margin-bottom:.3rem; display:flex; align-items:center; gap:4px; }

/* Form inside modal */
.form-group    { margin-bottom:.9rem; }
.form-group label { display:block; font-size:.8rem; font-weight:600; color:var(--muted,#6b7280); margin-bottom:.3rem; }
.form-control  { width:100%; padding:.55rem .75rem; border:1px solid var(--border,#e5e7eb); border-radius:9px; background:var(--bg,#f9fafb); color:var(--text,#111); font-size:.85rem; font-family:inherit; transition:border-color .15s; box-sizing:border-box; }
.form-control:focus { outline:none; border-color:#22c55e; }
textarea.form-control { resize:vertical; min-height:110px; }
select.form-control   { height:36px; }

.btn-submit    { height:36px; padding:0 1.2rem; border-radius:9px; border:none; background:#22c55e; color:white; font-size:.85rem; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:.4rem; transition:.15s; }
.btn-submit:hover { background:#16a34a; }
.btn-cancel    { height:36px; padding:0 1rem; border-radius:9px; border:1px solid var(--border,#e5e7eb); background:transparent; color:var(--text,#111); font-size:.85rem; font-weight:600; cursor:pointer; transition:.15s; }
.btn-cancel:hover { background:var(--bg,#f9fafb); }

/* Flash */
.flash        { border-radius:10px; padding:.7rem 1rem; margin-bottom:1rem; font-size:.85rem; font-weight:500; display:flex; align-items:center; gap:.5rem; }
.flash.success{ background:#d1fae5; color:#065f46; }
.flash.error  { background:#fee2e2; color:#991b1b; }
</style>

<!-- ═══════════════════════════════════════════════════════════
     TOPBAR
════════════════════════════════════════════════════════════ -->
<div class="topbar">
  <h1>
    <i class="fas fa-flag" style="color:#f59e0b;"></i>
    Réclamations de l'Administration
  </h1>
</div>

<!-- Flash messages -->
<?php if (!empty($_SESSION['success'])): ?>
  <div class="flash success">
    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_SESSION['success']) ?>
  </div>
  <?php unset($_SESSION['success']); ?>
<?php endif; ?>
<?php if (!empty($_SESSION['error'])): ?>
  <div class="flash error">
    <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($_SESSION['error']) ?>
  </div>
  <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════════════════
     STATS STRIP
════════════════════════════════════════════════════════════ -->
<div class="stats-strip">
  <div class="stat-card stat-total">
    <div class="stat-val"><?= $nbTotal ?></div>
    <div class="stat-lbl">Total reçues</div>
  </div>
  <div class="stat-card stat-warn">
    <div class="stat-val"><?= $nbEnAttente ?></div>
    <div class="stat-lbl">En attente</div>
  </div>
  <div class="stat-card stat-ok">
    <div class="stat-val"><?= $nbResolues ?></div>
    <div class="stat-lbl">Résolues</div>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     FILTER BAR
════════════════════════════════════════════════════════════ -->
<div class="filter-bar">
  <input class="filter-input" type="text" id="fsearch"
         placeholder="🔍  Chercher client ou sujet…" oninput="applyFilter()"/>
  <select class="filter-select" id="fstatut" onchange="applyFilter()">
    <option value="">Tous les statuts</option>
    <?php foreach ($statutConfig as $sKey => $sCfg): ?>
      <option value="<?= $sKey ?>"><?= $sCfg['label'] ?></option>
    <?php endforeach; ?>
  </select>
  <select class="filter-select" id="freply" onchange="applyFilter()">
    <option value="">Réponse : tous</option>
    <option value="1">Avec réponse</option>
    <option value="0">Sans réponse</option>
  </select>
</div>

<!-- ═══════════════════════════════════════════════════════════
     TABLE CARD
════════════════════════════════════════════════════════════ -->
<div class="card">
  <div class="card-title">
    <i class="fas fa-list"></i>
    Réclamations transmises par l'admin
    <span class="count-pill" id="countPill"><?= $nbTotal ?> réclamation(s)</span>
  </div>

  <div class="table-wrapper">
    <table>
      <thead>
        <tr>
          <th><i class="fas fa-user"></i> Client</th>
          <th><i class="fas fa-tag"></i> Sujet</th>
          <th><i class="fas fa-comment"></i> Message</th>
          <th><i class="fas fa-chart-line"></i> Statut</th>
          <th><i class="fas fa-reply"></i> Réponse</th>
          <th><i class="fas fa-calendar"></i> Date</th>
          <th><i class="fas fa-cogs"></i> Actions</th>
        </tr>
      </thead>
      <tbody id="reclBody">

        <?php if (empty($reclamations)): ?>
        <tr>
          <td colspan="7" class="empty-td">
            <i class="fas fa-inbox" style="font-size:2rem;display:block;margin-bottom:.5rem;opacity:.4;"></i>
            Aucune réclamation transmise.
          </td>
        </tr>
        <?php endif; ?>

        <?php foreach ($reclamations as $r):
          $cfg      = $statutConfig[$r['statut']] ?? ['cls'=>'badge-gray','label'=>ucfirst($r['statut']),'icon'=>'fa-circle'];
          $hasReply = !empty($r['reponse']);
          $parts    = explode(' ', trim($r['client_nom']));
          $initials = strtoupper(substr($parts[0]??'',0,1) . substr($parts[1]??'',0,1));
        ?>
        <tr
          data-statut="<?= htmlspecialchars($r['statut']) ?>"
          data-replied="<?= $hasReply ? '1' : '0' ?>"
          data-search="<?= strtolower(htmlspecialchars($r['client_nom'] . ' ' . $r['sujet'])) ?>"
        >
          <!-- Client -->
          <td>
            <div class="client-cell">
              <div class="c-avatar"><?= $initials ?></div>
              <strong><?= htmlspecialchars($r['client_nom']) ?></strong>
            </div>
          </td>

          <!-- Sujet -->
          <td><strong><?= htmlspecialchars($r['sujet']) ?></strong></td>

          <!-- Message preview -->
          <td>
            <span class="msg-preview" title="<?= htmlspecialchars($r['message']) ?>">
              <?= htmlspecialchars(mb_substr($r['message'], 0, 70)) ?>…
            </span>
          </td>

          <!-- Statut -->
          <td>
            <span class="badge <?= $cfg['cls'] ?>">
              <i class="fas <?= $cfg['icon'] ?>" style="font-size:.65rem;"></i>
              <?= $cfg['label'] ?>
            </span>
          </td>

          <!-- Réponse indicator -->
          <td>
            <?php if ($hasReply): ?>
              <span class="replied-chip">
                <i class="fas fa-check" style="font-size:.65rem;"></i> Répondu
              </span>
            <?php else: ?>
              <span style="color:var(--muted,#6b7280);font-size:.78rem;">–</span>
            <?php endif; ?>
          </td>

          <!-- Date -->
          <td class="date-muted">
            <i class="far fa-calendar-alt" style="font-size:.7rem;margin-right:2px;"></i>
            <?= date('d/m/Y', strtotime($r['created_at'])) ?>
          </td>

          <!-- Actions -->
          <td>
            <div class="actions-cell">
              <button class="btn-reply"
                      onclick="openReply(<?= htmlspecialchars(json_encode([
                          'id'         => $r['id'],
                          'client_nom' => $r['client_nom'],
                          'sujet'      => $r['sujet'],
                          'message'    => $r['message'],
                          'statut'     => $r['statut'],
                          'reponse'    => $r['reponse'] ?? '',
                          'created_at' => $r['created_at'],
                      ])) ?>)">
                <i class="fas fa-reply"></i>
                <?= $hasReply ? 'Modifier réponse' : 'Répondre' ?>
              </button>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>

      </tbody>
    </table>
  </div>

  <div id="emptyMsg" style="display:none;text-align:center;padding:2rem;color:var(--muted,#6b7280);">
    <i class="fas fa-search" style="font-size:1.8rem;display:block;margin-bottom:.5rem;opacity:.4;"></i>
    Aucun résultat pour ces filtres.
  </div>
</div><!-- /.card -->

<!-- ═══════════════════════════════════════════════════════════
     REPLY MODAL
════════════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="replyModal">
  <div class="modal-box">

    <div class="modal-header">
      <h2>
        <i class="fas fa-reply" style="color:#22c55e;"></i>
        Répondre à la réclamation
      </h2>
      <button class="modal-close" onclick="closeModal()">&times;</button>
    </div>

    <div class="modal-body">

      <!-- Context block (read-only recap) -->
      <div class="ctx-block">
        <div class="ctx-row">
          <span class="ctx-label">Client :</span>
          <span class="ctx-val" id="ctx_client">–</span>
        </div>
        <div class="ctx-row">
          <span class="ctx-label">Sujet :</span>
          <span class="ctx-val" id="ctx_sujet">–</span>
        </div>
        <div class="ctx-row">
          <span class="ctx-label">Date :</span>
          <span class="ctx-val" id="ctx_date">–</span>
        </div>
      </div>

      <!-- Original message -->
      <div class="form-group">
        <label><i class="fas fa-comment" style="margin-right:4px;"></i>Message du client</label>
        <div class="orig-msg" id="ctx_message">–</div>
      </div>

      <!-- Previous reply (shown only if exists) -->
      <div id="prevReplyBlock" style="display:none;">
        <div class="prev-reply-lbl">
          <i class="fas fa-check-circle"></i> Réponse déjà envoyée
        </div>
        <div class="prev-reply" id="ctx_prev_reply">–</div>
      </div>

      <!-- Reply form -->
      <form method="POST" action="<?= BASE ?>/view/usine/reclamations.php" id="replyForm">
        <input type="hidden" name="action"          value="repondre"/>
        <input type="hidden" name="reclamation_id"  id="f_id"/>

        <div class="form-group">
          <label for="f_reponse">
            <i class="fas fa-pen" style="margin-right:4px;"></i>
            Votre réponse <span style="color:#ef4444;">*</span>
          </label>
          <textarea class="form-control" name="reponse" id="f_reponse"
                    placeholder="Rédigez votre réponse ici…" required></textarea>
        </div>

        <div class="form-group">
          <label for="f_statut">
            <i class="fas fa-chart-line" style="margin-right:4px;"></i>
            Mettre à jour le statut
          </label>
          <select class="form-control" name="statut" id="f_statut">
            <option value="transmise_usine">En attente</option>
            <option value="en_cours">En cours de traitement</option>
            <option value="resolue">Résolue</option>
            <option value="fermee">Fermée</option>
          </select>
        </div>

      </form><!-- /replyForm — button is in footer via form attr -->
    </div><!-- /.modal-body -->

    <div class="modal-footer">
      <button class="btn-cancel" onclick="closeModal()">
        <i class="fas fa-times"></i> Annuler
      </button>
      <button class="btn-submit" form="replyForm" type="submit">
        <i class="fas fa-paper-plane"></i> Envoyer la réponse
      </button>
    </div>

  </div><!-- /.modal-box -->
</div><!-- /.modal-overlay -->

<script>
/* ── Filter ────────────────────────────────────────────────── */
function applyFilter() {
    const q      = document.getElementById('fsearch').value.toLowerCase().trim();
    const st     = document.getElementById('fstatut').value;
    const rep    = document.getElementById('freply').value;
    const rows   = document.querySelectorAll('#reclBody tr[data-statut]');
    let visible  = 0;

    rows.forEach(row => {
        const matchQ   = !q   || row.dataset.search.includes(q);
        const matchSt  = !st  || row.dataset.statut  === st;
        const matchRep = rep === '' || row.dataset.replied === rep;
        const show = matchQ && matchSt && matchRep;
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });

    document.getElementById('countPill').textContent = visible + ' réclamation(s)';
    document.getElementById('emptyMsg').style.display =
        (visible === 0 && rows.length > 0) ? 'block' : 'none';
}

/* ── Modal ─────────────────────────────────────────────────── */
function openReply(data) {
    document.getElementById('ctx_client').textContent   = data.client_nom;
    document.getElementById('ctx_sujet').textContent    = data.sujet;
    document.getElementById('ctx_date').textContent     = formatDate(data.created_at);
    document.getElementById('ctx_message').textContent  = data.message;
    document.getElementById('f_id').value               = data.id;
    document.getElementById('f_reponse').value          = data.reponse || '';
    document.getElementById('f_statut').value           = data.statut  || 'transmise_usine';

    // Previous reply block
    const prevBlock = document.getElementById('prevReplyBlock');
    if (data.reponse) {
        document.getElementById('ctx_prev_reply').textContent = data.reponse;
        prevBlock.style.display = 'block';
    } else {
        prevBlock.style.display = 'none';
    }

    document.getElementById('replyModal').style.display = 'flex';
    setTimeout(() => document.getElementById('f_reponse').focus(), 120);
}

function closeModal() {
    document.getElementById('replyModal').style.display = 'none';
}

// Close on backdrop click
document.getElementById('replyModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

// Close on Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeModal();
});

function formatDate(str) {
    if (!str) return '–';
    const d = new Date(str.replace(' ', 'T'));
    return d.toLocaleDateString('fr-FR', { day:'2-digit', month:'2-digit', year:'numeric' });
}
</script>

<?php include "partials/usine_footer.php"; ?>