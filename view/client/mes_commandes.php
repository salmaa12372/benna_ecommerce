<?php
// view/client/mes_commandes.php
session_start();
if (!isset($_SESSION['user_id'])) { include_once __DIR__ . "/../../config/app.php"; header("Location: " . BASE . "/view/client/signup.php"); exit(); }
$pageTitle = "Mes Commandes";
include "partials/header.php";
include_once __DIR__ . "/../../controller/traitement.php";

$commandes = getCommandesByUser($cnx, $_SESSION['user_id']);

$statutInfo = [
    'en_attente'     => ['icon'=>'🕐','label'=>'En attente',      'color'=>'#b45309','bg'=>'#fef3c7','step'=>1],
    'confirmee'      => ['icon'=>'✅','label'=>'Confirmée',        'color'=>'#065f46','bg'=>'#d1fae5','step'=>2],
    'en_preparation' => ['icon'=>'👨‍🍳','label'=>'En préparation', 'color'=>'#1e40af','bg'=>'#dbeafe','step'=>3],
    'expedie'        => ['icon'=>'📦','label'=>'Expédiée',         'color'=>'#6d28d9','bg'=>'#ede9fe','step'=>4],
    'en_livraison'   => ['icon'=>'🚚','label'=>'En livraison',     'color'=>'#0891b2','bg'=>'#cffafe','step'=>5],
    'livre'          => ['icon'=>'🎉','label'=>'Livrée',           'color'=>'#16a34a','bg'=>'#dcfce7','step'=>6],
    'annulee'        => ['icon'=>'❌','label'=>'Annulée',          'color'=>'#dc2626','bg'=>'#fee2e2','step'=>0],
];
$steps = [
    'en_attente'     => '🕐',
    'confirmee'      => '✅',
    'en_preparation' => '👨‍🍳',
    'expedie'        => '📦',
    'en_livraison'   => '🚚',
    'livre'          => '🎉',
];
$stepLabels = [
    'en_attente'     => 'Reçue',
    'confirmee'      => 'Confirmée',
    'en_preparation' => 'Préparation',
    'expedie'        => 'Expédiée',
    'en_livraison'   => 'En route',
    'livre'          => 'Livrée',
];
?>
<style>
.cmd-page{padding-top:110px;min-height:80vh;}
.cmd-card{background:var(--cream);border-radius:20px;margin-bottom:1.5rem;box-shadow:0 4px 24px rgba(0,0,0,.07);overflow:hidden;transition:.3s;}
.cmd-card:hover{box-shadow:0 8px 32px rgba(0,0,0,.12);}
.cmd-header{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.8rem;padding:1.3rem 1.8rem;cursor:pointer;border-bottom:2px solid transparent;transition:.2s;user-select:none;}
.cmd-header.open{border-bottom-color:var(--border);}
.cmd-header:hover{background:rgba(0,0,0,.02);}
.cmd-id{font-family:var(--font-display);font-size:1.15rem;}
.cmd-meta{display:flex;gap:1.2rem;flex-wrap:wrap;font-size:.88rem;color:var(--muted);margin-top:.2rem;}
.cmd-meta strong{color:var(--fg);}
.statut-pill{display:inline-flex;align-items:center;gap:.4rem;padding:.35rem 1rem;border-radius:20px;font-size:.83rem;font-weight:700;}
/* PROGRESS */
.progress-track{padding:1.3rem 1.8rem;background:white;border-bottom:1px solid var(--border);}
.progress-steps{display:flex;align-items:flex-start;justify-content:space-between;position:relative;}
.progress-line-bg{position:absolute;top:17px;left:16px;right:16px;height:3px;background:var(--border);z-index:0;}
.progress-line-fill{position:absolute;top:17px;left:16px;height:3px;background:var(--green);z-index:1;transition:width .5s ease;}
.p-step{display:flex;flex-direction:column;align-items:center;gap:.35rem;position:relative;z-index:2;flex:1;}
.p-dot{width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.95rem;border:3px solid var(--border);background:white;transition:.3s;font-family:sans-serif;}
.p-dot.done{background:var(--green);border-color:var(--green);color:white;font-size:.8rem;}
.p-dot.current{background:var(--green-light);border-color:var(--green);color:white;transform:scale(1.18);box-shadow:0 0 0 5px rgba(45,106,79,.15);}
.p-dot.future{background:white;border-color:var(--border);color:var(--muted);}
.p-label{font-size:.65rem;color:var(--muted);text-align:center;line-height:1.2;max-width:60px;}
.p-label.done{color:var(--green);font-weight:600;}
.p-label.current{color:var(--green-dark);font-weight:700;}
/* BODY */
.cmd-body{display:none;}
.cmd-body.open{display:block;}
/* ITEMS */
.cmd-items{padding:1.4rem 1.8rem;}
.cmd-items-title{font-family:var(--font-display);color:var(--green-dark);margin:0 0 .9rem;font-size:1rem;}
.cmd-item{display:flex;align-items:center;gap:1rem;padding:.7rem 0;border-bottom:1px solid var(--border);}
.cmd-item:last-child{border-bottom:none;}
.cmd-item img{width:58px;height:58px;object-fit:cover;border-radius:10px;flex-shrink:0;}
.cmd-item-info{flex:1;}
.cmd-item-name{font-family:var(--font-display);font-size:1rem;}
.cmd-item-qty{font-size:.82rem;color:var(--muted);}
.cmd-item-price{font-family:var(--font-display);color:var(--green-dark);font-weight:700;white-space:nowrap;}
/* TOTAUX */
.cmd-totaux{background:rgba(0,0,0,.03);padding:1.2rem 1.8rem;display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1.2rem;}
.adresse-box{font-size:.88rem;color:var(--muted);max-width:280px;}
.adresse-box strong{color:var(--fg);display:block;margin-bottom:.2rem;}
.totaux-lines{font-size:.9rem;}
.totaux-lines>div{display:flex;justify-content:space-between;gap:3rem;padding:.2rem 0;}
.total-final{font-family:var(--font-display);font-size:1.15rem;color:var(--green-dark);border-top:2px solid var(--border);margin-top:.4rem;padding-top:.4rem;}
/* GPS SECTION */
.gps-wrap{margin:0 1.8rem 1.4rem;border-radius:14px;overflow:hidden;border:2px solid var(--green-light);}
.gps-head{background:linear-gradient(135deg,var(--green-dark),var(--green));color:white;padding:.9rem 1.2rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.5rem;}
.gps-head h4{font-family:var(--font-display);font-size:1rem;margin:0;}
.gps-head .pulse{display:inline-block;width:10px;height:10px;background:#4ade80;border-radius:50%;margin-left:.4rem;animation:pulse 1.5s infinite;}
@keyframes pulse{0%,100%{box-shadow:0 0 0 0 rgba(74,222,128,.5);}50%{box-shadow:0 0 0 8px rgba(74,222,128,0);}}
.gps-map{width:100%;height:270px;border:none;display:block;}
.gps-info{padding:.8rem 1.2rem;background:white;font-size:.85rem;display:flex;gap:1.5rem;flex-wrap:wrap;color:var(--muted);align-items:center;}
.gps-info strong{color:var(--fg);}
.gps-info a{color:var(--green);font-weight:600;text-decoration:none;}
.no-gps{padding:1.5rem;text-align:center;color:var(--muted);background:white;font-size:.9rem;line-height:1.6;}
/* SUCCESS BANNER */
.success-banner{margin:0 1.8rem 1.2rem;padding:1rem 1.4rem;background:#d1fae5;border-radius:12px;color:#065f46;font-family:var(--font-display);font-size:1rem;}
/* ACTIONS */
.cmd-actions{padding:.9rem 1.8rem;display:flex;gap:.7rem;flex-wrap:wrap;border-top:1px solid var(--border);}
.btn-recl{display:inline-flex;align-items:center;gap:.4rem;padding:.45rem 1rem;border-radius:10px;border:2px solid var(--terracotta);color:var(--terracotta);font-family:var(--font-display);font-size:.88rem;cursor:pointer;background:none;transition:.2s;text-decoration:none;}
.btn-recl:hover{background:var(--terracotta);color:white;}
.btn-avis{display:inline-flex;align-items:center;gap:.4rem;padding:.45rem 1rem;border-radius:10px;border:2px solid var(--gold);color:var(--gold);font-family:var(--font-display);font-size:.88rem;cursor:pointer;background:none;transition:.2s;border:none;}
.btn-avis:hover{opacity:.8;}
/* MODAL */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:1000;align-items:center;justify-content:center;padding:1rem;}
.modal-overlay.open{display:flex;}
.modal-box{background:var(--cream);border-radius:20px;padding:2rem;max-width:460px;width:100%;box-shadow:0 24px 64px rgba(0,0,0,.2);}
.modal-box h3{font-family:var(--font-display);font-size:1.3rem;margin:0 0 1.2rem;}
.stars-row{display:flex;gap:.3rem;font-size:2.2rem;margin:.4rem 0 1rem;cursor:pointer;}
.star{color:var(--border);transition:.15s;} .star.lit{color:var(--gold);}
.form-g{margin-bottom:1rem;}
.form-g label{display:block;font-family:var(--font-display);font-size:.9rem;color:var(--green-dark);margin-bottom:.3rem;}
.form-g select,.form-g textarea{width:100%;padding:.7rem 1rem;border:2px solid var(--border);border-radius:10px;background:white;font-family:var(--font-body);font-size:.95rem;color:var(--fg);outline:none;box-sizing:border-box;resize:vertical;}
.form-g select:focus,.form-g textarea:focus{border-color:var(--green);}
.btn-submit{width:100%;padding:.85rem;border:none;border-radius:12px;background:linear-gradient(135deg,var(--green),var(--green-dark));color:white;font-family:var(--font-display);font-size:1rem;font-weight:600;cursor:pointer;margin-top:.5rem;}
.btn-submit:hover{opacity:.9;}
.btn-cancel-m{display:block;text-align:center;margin-top:.6rem;color:var(--muted);font-size:.88rem;cursor:pointer;background:none;border:none;width:100%;font-family:var(--font-body);}
/* EMPTY */
.empty-box{text-align:center;padding:5rem 2rem;}
.empty-box .big-icon{font-size:5rem;margin-bottom:1rem;}
.empty-box h2{font-family:var(--font-display);margin:0 0 .5rem;}


.navbar {
      background: transparent !important;
      backdrop-filter: none !important;
      border-bottom: 1px solid rgba(255,255,255,0.15);
    }
    .navbar.scrolled {
      background: #1a4a2e !important;
      backdrop-filter: none !important;
      border-bottom: 1px solid rgba(255,255,255,0.15);
    }

    body.dark .navbar {
      background: #0a1a0a !important;
    }
    .navbar .nav-links a,
    .navbar .nav-links2 a {
      color: rgba(20, 58, 31, 0.95) !important;
      text-shadow: none !important;
    }
    body.dark .navbar .nav-links a,
    body.dark .navbar .nav-links2 a{
      color:rgba(255, 255, 255, 0.95) !important;
      text-shadow: none !important;
    }
    .navbar.scrolled .nav-links a,
    .navbar.scrolled .nav-links2 a {
      color: rgba(255, 255, 255, 0.95) !important;
    }
    .navbar .nav-logo {
      color: #1a4a2e !important;
      text-shadow: none !important;
    }
    body.dark .navbar .nav-logo {
      color: #ffffff !important;
      text-shadow: none !important;
    }
    .navbar.scrolled .nav-logo {
      color: #ffffff !important;
      text-shadow: none !important;
    }
</style>

<main class="cmd-page container">

  <div class="section-header fade-in">
    <p class="section-label">Mon compte</p>
    <h1 class="section-title">Mes <em>Commandes</em></h1>
    <div class="section-divider"></div>
    <p class="section-sub"><?= count($commandes) ?> commande(s) passée(s)</p>
  </div>

  <?php if (empty($commandes)): ?>
    <div class="empty-box fade-in">
      <div class="big-icon">📦</div>
      <h2>Aucune commande pour l'instant</h2>
      <p style="color:var(--muted);margin:.5rem 0 1.5rem;">Découvrez nos produits sains et passez votre première commande.</p>
      <a href="produits.php" class="nav-join">
        <strong>Aller à la boutique →</strong>
        <span class="star-1"></span><span class="star-2"></span><span class="star-3"></span>
      </a>
    </div>

  <?php else:
    $hasEnLivraison = false;
    foreach ($commandes as $i => $cmd):
      $s       = $statutInfo[$cmd['statut']] ?? $statutInfo['en_attente'];
      $step    = $s['step'];
      $details = getCommandeById($cnx, $cmd['id']);

      // Récupérer livraison + position GPS
      $livrQ = $cnx->prepare("SELECT l.*,u.nom AS livreur_nom FROM livraisons l LEFT JOIN users u ON l.livreur_id=u.id WHERE l.commande_id=:id");
      $livrQ->execute([':id'=>$cmd['id']]);
      $livraison = $livrQ->fetch();
      $hasGps    = $livraison && $livraison['latitude'] && $livraison['longitude'];
      $isAnnulee = $cmd['statut'] === 'annulee';
      if ($cmd['statut'] === 'en_livraison') $hasEnLivraison = true;
  ?>

    <div class="cmd-card fade-in" style="--delay:<?= $i * 0.08 ?>s">

      <!-- EN-TÊTE -->
      <div class="cmd-header" onclick="toggleCmd(<?= $cmd['id'] ?>)" id="hdr-<?= $cmd['id'] ?>">
        <div>
          <div class="cmd-id">Commande <em>#<?= $cmd['id'] ?></em></div>
          <div class="cmd-meta">
            <span>📅 <strong><?= date('d/m/Y à H:i', strtotime($cmd['date_commande'])) ?></strong></span>
            <span>💰 <strong><?= number_format($cmd['total'],3) ?> TND</strong></span>
            <?php if ($cmd['date_livraison_estimee']): ?>
            <span>🗓 Livraison estimée : <strong><?= date('d/m/Y', strtotime($cmd['date_livraison_estimee'])) ?></strong></span>
            <?php endif; ?>
          </div>
        </div>
        <div style="display:flex;align-items:center;gap:.8rem;">
          <span class="statut-pill" style="color:<?= $s['color'] ?>;background:<?= $s['bg'] ?>;">
            <?= $s['icon'] ?> <?= $s['label'] ?>
          </span>
          <span id="chevron-<?= $cmd['id'] ?>" style="font-size:1.1rem;color:var(--muted);transition:.3s;display:inline-block;">▼</span>
        </div>
      </div>

      <!-- BARRE DE PROGRESSION -->
      <?php if (!$isAnnulee): ?>
      <div class="progress-track">
        <?php
          $stepKeys = array_keys($steps);
          $total    = count($stepKeys);
          $curIdx   = array_search($cmd['statut'], $stepKeys);
          $curIdx   = $curIdx === false ? 0 : $curIdx;
          $fillPct  = $total > 1 ? round($curIdx / ($total-1) * 100) : 0;
        ?>
        <div class="progress-steps">
          <div class="progress-line-bg"></div>
          <div class="progress-line-fill" style="width:calc(<?= $fillPct ?>% - 16px);"></div>
          <?php foreach ($steps as $sv => $icon):
            $idx = array_search($sv, $stepKeys);
            $cls = $idx < $curIdx ? 'done' : ($idx === $curIdx ? 'current' : 'future');
          ?>
          <div class="p-step">
            <div class="p-dot <?= $cls ?>"><?= $cls === 'done' ? '✓' : $icon ?></div>
            <div class="p-label <?= $cls ?>"><?= $stepLabels[$sv] ?></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- CORPS (masqué par défaut) -->
      <div class="cmd-body" id="body-<?= $cmd['id'] ?>">

        <!-- PRODUITS COMMANDÉS -->
        <div class="cmd-items">
          <p class="cmd-items-title">🛍 Produits commandés</p>
          <?php if (!empty($details['details'])): ?>
            <?php foreach ($details['details'] as $d): ?>
            <div class="cmd-item">
              <img src="../../<?= htmlspecialchars($d['image'] ?? '') ?>"
                   alt="<?= htmlspecialchars($d['nom']) ?>"
                   onerror="this.src='https://placehold.co/58x58/e8f0e3/2c5e2e?text=🌿'"/>
              <div class="cmd-item-info">
                <div class="cmd-item-name"><?= htmlspecialchars($d['nom']) ?></div>
                <div class="cmd-item-qty"><?= $d['quantite'] ?> × <?= number_format($d['prix_unitaire'],3) ?> TND</div>
              </div>
              <div class="cmd-item-price"><?= number_format($d['quantite'] * $d['prix_unitaire'],3) ?> TND</div>
            </div>
            <?php endforeach; ?>
          <?php else: ?>
            <p style="color:var(--muted);font-size:.9rem;">Détails non disponibles.</p>
          <?php endif; ?>
        </div>

        <!-- TOTAUX + ADRESSE -->
        <div class="cmd-totaux">
          <div class="adresse-box">
            <strong>📍 Adresse de livraison</strong>
            <?= htmlspecialchars($cmd['adresse_livraison']) ?>
            <?php if (!empty($cmd['note_client'])): ?>
            <div style="margin-top:.4rem;font-style:italic;">💬 <?= htmlspecialchars($cmd['note_client']) ?></div>
            <?php endif; ?>
          </div>
          <div class="totaux-lines">
            <div><span>Sous-total</span><span><?= number_format($cmd['total'],3) ?> TND</span></div>
            <div><span>Livraison</span><span><?= $cmd['total'] >= 150 ? 'Gratuite 🎉' : '5.000 TND' ?></span></div>
            <div class="total-final">
              <span>Total payé</span>
              <span><?= number_format($cmd['total'] >= 150 ? $cmd['total'] : $cmd['total'] + 5, 3) ?> TND</span>
            </div>
          </div>
        </div>

        <!-- ══ SUIVI GPS EN TEMPS RÉEL ══════════════════ -->
        <?php if (in_array($cmd['statut'], ['en_livraison','expedie'])): ?>
        <div class="gps-wrap">
          <div class="gps-head">
            <h4>
              🚚 Suivi de votre livraison en temps réel
              <?php if ($cmd['statut'] === 'en_livraison'): ?>
                <span class="pulse" title="En direct"></span>
              <?php endif; ?>
            </h4>
            <?php if ($livraison && $livraison['livreur_nom']): ?>
              <span style="font-size:.82rem;opacity:.8;">🧑‍✈️ <?= htmlspecialchars($livraison['livreur_nom']) ?></span>
            <?php endif; ?>
          </div>

          <?php if ($hasGps): ?>
            <!-- CARTE OPENSTREETMAP (sans clé API) -->
            <iframe class="gps-map" loading="lazy" title="Position du livreur"
              src="https://www.openstreetmap.org/export/embed.html?bbox=<?= ($livraison['longitude']-0.012) ?>,<?= ($livraison['latitude']-0.008) ?>,<?= ($livraison['longitude']+0.012) ?>,<?= ($livraison['latitude']+0.008) ?>&layer=mapnik&marker=<?= $livraison['latitude'] ?>,<?= $livraison['longitude'] ?>">
            </iframe>
            <div class="gps-info">
              <span>📍 <strong><?= number_format($livraison['latitude'],5) ?>, <?= number_format($livraison['longitude'],5) ?></strong></span>
              <span>🕐 Mis à jour : <strong><?= date('H:i:s', strtotime($livraison['updated_at'])) ?></strong></span>
              <a href="https://maps.google.com/?q=<?= $livraison['latitude'] ?>,<?= $livraison['longitude'] ?>" target="_blank">
                🗺 Ouvrir dans Google Maps →
              </a>
            </div>
          <?php else: ?>
            <div class="no-gps">
              <?php if ($livraison && $livraison['livreur_nom']): ?>
                🚚 <strong><?= htmlspecialchars($livraison['livreur_nom']) ?></strong> est assigné à votre commande.<br/>
                <span style="font-size:.85rem;">La carte apparaîtra dès que le livreur démarre sa tournée.</span>
              <?php else: ?>
                📋 Un livreur va être assigné à votre commande très prochainement.<br/>
                <span style="font-size:.85rem;">La carte de suivi apparaîtra ici automatiquement.</span>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>

        <!-- ══ COMMANDE LIVRÉE ══════════════════════════ -->
        <?php elseif ($cmd['statut'] === 'livre'): ?>
        <div class="success-banner">
          🎉 Votre commande a été livrée avec succès ! Merci de votre confiance en Benna.
        </div>
        <?php endif; ?>

        <!-- ACTIONS -->
        <div class="cmd-actions">
          <a href="mes_reclamations.php?new=1&commande_id=<?= $cmd['id'] ?>" class="btn-recl">
            📝 Réclamation
          </a>
          <?php if ($cmd['statut'] === 'livre' && !empty($details['details'])): ?>
          <button class="btn-avis"
            onclick="openAvisModal(<?= htmlspecialchars(json_encode(array_map(fn($d)=>['id'=>$d['produit_id'],'nom'=>$d['nom']],$details['details']))) ?>)"
            style="background:linear-gradient(135deg,var(--gold),#d97706);color:white;border-radius:10px;font-family:var(--font-display);font-size:.88rem;padding:.45rem 1rem;cursor:pointer;">
            ⭐ Laisser un avis
          </button>
          <?php endif; ?>
        </div>

      </div><!-- /cmd-body -->
    </div><!-- /cmd-card -->

  <?php endforeach; ?>

    <!-- Auto-refresh si une commande est en livraison -->
    <?php if ($hasEnLivraison): ?>
    <div style="text-align:center;padding:.5rem;color:var(--muted);font-size:.82rem;">
      🔄 Position GPS actualisée automatiquement toutes les 30 secondes
    </div>
    <script>setTimeout(() => location.reload(), 30000);</script>
    <?php endif; ?>

  <?php endif; ?>
</main>

<!-- MODAL AVIS -->
<div class="modal-overlay" id="avisModal">
  <div class="modal-box">
    <h3>⭐ Donner votre avis</h3>
    <form action="<?= BASE ?>/controller/conseil_controller.php?action=add_avis" method="POST">

      <div class="form-g">
        <label>Produit concerné</label>
        <select name="produit_id" id="avisProduitSel"></select>
      </div>

      <div class="form-g">
        <label>Votre note</label>
        <div class="stars-row" id="starsRow">
          <span class="star lit" data-n="1">★</span>
          <span class="star lit" data-n="2">★</span>
          <span class="star lit" data-n="3">★</span>
          <span class="star lit" data-n="4">★</span>
          <span class="star lit" data-n="5">★</span>
        </div>
        <input type="hidden" name="note" id="noteVal" value="5"/>
      </div>

      <div class="form-g">
        <label>Commentaire</label>
        <textarea name="commentaire" rows="4" placeholder="Partagez votre expérience avec ce produit..."></textarea>
      </div>

      <button type="submit" class="btn-submit">Publier mon avis →</button>
      <button type="button" class="btn-cancel-m" onclick="closeAvisModal()">Annuler</button>
    </form>
  </div>
</div>

<script>
/* ── TOGGLE COMMANDE ─────────────────────────── */
function toggleCmd(id) {
  const body    = document.getElementById('body-'    + id);
  const hdr     = document.getElementById('hdr-'     + id);
  const chevron = document.getElementById('chevron-' + id);
  const isOpen  = body.classList.toggle('open');
  hdr.classList.toggle('open', isOpen);
  chevron.style.transform = isOpen ? 'rotate(180deg)' : '';
}

/* ── ÉTOILES ─────────────────────────────────── */
const stars = document.querySelectorAll('#starsRow .star');
let currentNote = 5;
stars.forEach(star => {
  star.addEventListener('mouseover', () => highlightStars(+star.dataset.n));
  star.addEventListener('mouseout',  () => highlightStars(currentNote));
  star.addEventListener('click', () => {
    currentNote = +star.dataset.n;
    document.getElementById('noteVal').value = currentNote;
    highlightStars(currentNote);
  });
});
function highlightStars(n) {
  stars.forEach(s => s.classList.toggle('lit', +s.dataset.n <= n));
}
highlightStars(5);

/* ── MODAL AVIS ──────────────────────────────── */
function openAvisModal(produits) {
  const sel = document.getElementById('avisProduitSel');
  sel.innerHTML = produits.map(p => `<option value="${p.id}">${p.nom}</option>`).join('');
  document.getElementById('avisModal').classList.add('open');
}
function closeAvisModal() {
  document.getElementById('avisModal').classList.remove('open');
}
document.getElementById('avisModal').addEventListener('click', e => {
  if (e.target.id === 'avisModal') closeAvisModal();
});
</script>

<?php include "partials/footer.php"; ?>