<?php
ob_start();
session_start();

require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../config/app.php";
require_once __DIR__ . "/../../controller/traitement.php";

// Fetch current subscription only if logged in
$abonnement = isset($_SESSION['user_id']) ? getAbonnementUser($cnx, $_SESSION['user_id']) : null;

$pageTitle = "Club VIP Benna";
include "partials/header.php";
ob_end_flush();
?>
<style>
/* ── Force light background on this page ── */
html, body {
    background: #FFF9EF !important;
    color: #2c2a29 !important;
}
body.dark {
    background: #1a1a1a !important;
}

/* ── Navbar override for this page ── */
.navbar {
    background: rgba(255,249,239,0.95) !important;
    backdrop-filter: blur(12px) !important;
    border-bottom: 1px solid rgba(200,104,74,0.15) !important;
}
.navbar .nav-links a,
.navbar .nav-links2 a,
.navbar .nav-logo {
    color: #2c2a29 !important;
    text-shadow: none !important;
}
.navbar.scrolled {
    background: rgba(255,249,239,0.98) !important;
}
body.dark .navbar {
    background: rgba(20,20,20,0.95) !important;
}
body.dark .navbar .nav-links a,
body.dark .navbar .nav-links2 a,
body.dark .navbar .nav-logo {
    color: #e5e0d5 !important;
}

/* ── Page layout ── */
.vip-page {
    padding-top: 70px;
    background: #FFF9EF;
    min-height: 100vh;
}
body.dark .vip-page {
    background: #1a1a1a;
}

/* ── HERO ── */
.vip-hero {
    background: linear-gradient(145deg, #0d1c16, #3e7346 35%, #3b622a 65%, #e3dfdb);
    color: white;
    text-align: center;
    padding: 80px 24px 60px;
    position: relative;
    overflow: hidden;
}

body.dark .vip-hero {
    background: linear-gradient(145deg, #060d0a, #0d1f14 35%, #0f1a0a 65%, #12100e);
    color: white;
    text-align: center;
    padding: 80px 24px 60px;
    position: relative;
    overflow: hidden;
}

.vip-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background:
        radial-gradient(ellipse at 20% 60%, rgba(30,100,55,.28), transparent 55%),
        radial-gradient(ellipse at 80% 30%, rgba(180,130,50,.16), transparent 50%);
    pointer-events: none;
}
.vip-hero-inner { position: relative; z-index: 1; }
.vip-stars { font-size: 1.1rem; letter-spacing: .4rem; color: hsl(42,72%,55%); margin-bottom: 8px; }
.vip-hero h1 {
    font-family: var(--font-display, 'Cormorant Garamond', serif);
    font-size: clamp(2.4rem, 6vw, 4.2rem);
    font-weight: 300;
    margin: 14px 0;
    line-height: 1.05;
}
.vip-hero h1 em { color: #D4AF37; font-style: italic; }
.vip-hero p {
    font-size: .96rem;
    color: rgba(255,255,255,.65);
    max-width: 540px;
    margin: 0 auto 24px;
    font-weight: 300;
    line-height: 1.8;
}
.vip-badge-current {
    display: inline-flex;
    align-items: center;
    gap: .6rem;
    background: rgba(255,255,255,.08);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,.16);
    padding: .5rem 1.2rem;
    border-radius: 24px;
    font-size: .86rem;
    color: rgba(255,255,255,.78);
}
.vip-badge-current a { color: hsl(145,55%,62%); font-weight: 600; text-decoration: none; }

/* ── PLANS ── */
.plans-section {
    background: #FFF9EF;
    padding: 56px 0;
}
body.dark .plans-section {
    background: #1a1a1a;
}
.plans-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 18px;
    max-width: 1160px;
    margin: 0 auto;
    padding: 0 24px;
}
.plan-card {
    background: #fff;
    border: 2px solid #e8dccb;
    border-radius: 24px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,.06);
    transition: transform .3s ease, box-shadow .3s, border-color .3s;
    display: flex;
    flex-direction: column;
}
body.dark .plan-card {
    background: #242424;
    border-color: #333;
}
.plan-card:hover { transform: translateY(-8px); box-shadow: 0 20px 40px rgba(0,0,0,.12); }
.plan-card.featured {
    border-color: #3b82f6;
    transform: translateY(-4px);
    box-shadow: 0 16px 56px rgba(59,130,246,.18);
}
.plan-card.featured:hover { transform: translateY(-12px); }
.plan-header { padding: 26px 22px 18px; text-align: center; position: relative; }
.plan-badge-featured {
    position: absolute; top: 12px; right: 12px;
    background: #3b82f6; color: white;
    padding: .22rem .72rem; border-radius: 20px;
    font-size: .66rem; font-weight: 700; letter-spacing: .06em;
}
.plan-emoji { font-size: 2.5rem; margin-bottom: .5rem; display: block; }
.plan-name {
    font-family: var(--font-display, 'Cormorant Garamond', serif);
    font-size: 1.4rem; font-weight: 500;
    margin: 0 0 .25rem; color: #2c2a29;
}
body.dark .plan-name { color: #040403; }
.plan-desc { color: #8a7a6a; font-size: .84rem; font-weight: 300; margin-bottom: .4rem; }
body.dark .plan-desc { color: #000000; }
.plan-price {
    font-family: var(--font-display, 'Cormorant Garamond', serif);
    font-size: 2.4rem; font-weight: 500;
    margin: .6rem 0 .2rem; color: #2c2a29; line-height: 1;
}
body.dark .plan-price { color: #000000; }
.plan-price span { font-size: .9rem; font-weight: 300; color: #8a7a6a; }
.plan-price-range { font-size: .76rem; color: #8a7a6a; margin-bottom: .3rem; }
.plan-features { padding: 4px 22px 22px; flex: 1; display: flex; flex-direction: column; }
.plan-features ul { list-style: none; padding: 0; margin: 0 0 16px; flex: 1; }
.plan-features li {
    display: flex; align-items: flex-start; gap: 9px;
    padding: 7px 0; border-bottom: 1px solid #e8dccb;
    font-size: .86rem; color: #2c2a29; font-weight: 300; line-height: 1.5;
}
body.dark .plan-features li { color: #c5bfb5; border-color: #333; }
.plan-features li:last-child { border-bottom: none; }
.check { color: #22c55e; font-weight: 700; flex-shrink: 0; }
.cross { color: #ccc; flex-shrink: 0; }
.dimmed { color: #aaa; }
.plan-btn {
    width: 100%; padding: .9rem; border: none;
    border-radius: 12px; font-size: .86rem; font-weight: 700;
    letter-spacing: .05em; text-transform: uppercase;
    cursor: pointer; transition: transform .25s, box-shadow .25s;
}
.plan-btn:hover:not(.disabled) { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,.18); }
.plan-btn.disabled { background: #e8dccb !important; color: #aaa !important; cursor: default; transform: none !important; }

/* ── FAQ ── */
.faq-section { max-width: 720px; margin: 0 auto; padding: 0 24px 56px; }
.faq-section h2 {
    font-family: var(--font-display, 'Cormorant Garamond', serif);
    font-size: clamp(1.6rem, 4vw, 2.2rem);
    font-weight: 300; text-align: center; margin-bottom: 26px;
    color: #2c2a29;
}
body.dark .faq-section h2 { color: #e5e0d5; }
.faq-section h2 em { font-style: italic; color: #22c55e; }
.faq-item {
    background: #fff; border: 1px solid #e8dccb;
    border-radius: 12px; margin-bottom: 9px;
    box-shadow: 0 2px 8px rgba(0,0,0,.04);
    overflow: hidden; transition: border-color .2s;
}
body.dark .faq-item { background: #242424; border-color: #333; }
.faq-item:hover { border-color: #22c55e; }
.faq-q {
    padding: 14px 18px; cursor: pointer;
    display: flex; justify-content: space-between; align-items: center;
    font-family: var(--font-display, 'Cormorant Garamond', serif);
    font-size: .97rem; font-weight: 500; color: #2c2a29; user-select: none;
}
body.dark .faq-q { color: #e5e0d5; }
.faq-a { display: none; padding: 0 18px 13px; color: #8a7a6a; font-size: .88rem; line-height: 1.75; font-weight: 300; }
.faq-item.open .faq-a { display: block; }
.faq-arrow { transition: transform .25s; color: #8a7a6a; }
.faq-item.open .faq-arrow { transform: rotate(180deg); color: #22c55e; }

/* ── MODAL ── */
.vip-modal-overlay {
    display: none; position: fixed; inset: 0;
    background: rgba(0,0,0,.6); backdrop-filter: blur(6px);
    z-index: 2000; align-items: center; justify-content: center; padding: 18px;
}
.vip-modal-overlay.open { display: flex; }
.vip-modal {
    background: #fff; max-width: 480px; width: 100%;
    border-radius: 24px; padding: 32px;
    box-shadow: 0 30px 80px rgba(0,0,0,.2);
    border: 1px solid #e8dccb;
}
body.dark .vip-modal { background: #242424; border-color: #333; }
.vip-modal h2 {
    font-family: var(--font-display, 'Cormorant Garamond', serif);
    font-size: 1.5rem; font-weight: 400; margin: 0 0 .5rem; color: #2c2a29;
}
body.dark .vip-modal h2 { color: #e5e0d5; }
.plan-summary {
    background: #f0fdf4; border: 1px solid #bbf7d0;
    border-radius: 12px; padding: 12px 14px; margin: 12px 0 17px;
}
body.dark .plan-summary { background: #1a2e1a; border-color: #166534; }
.fm { margin-bottom: 13px; }
.fm label {
    display: block; font-size: .8rem; font-weight: 600;
    color: #16a34a; margin-bottom: 5px; letter-spacing: .04em; text-transform: uppercase;
}
.fm input[type=range] { width: 100%; accent-color: #16a34a; }
.price-display-wrap { display: flex; gap: 10px; align-items: center; }
.pay-vip-btn {
    width: 100%; padding: .9rem; border: none; border-radius: 12px;
    font-size: .9rem; font-weight: 700; cursor: pointer;
    transition: transform .25s, box-shadow .25s;
    letter-spacing: .04em; text-transform: uppercase;
}
.pay-vip-btn:hover { transform: translateY(-2px); box-shadow: 0 10px 28px rgba(0,0,0,.2); }
.modal-cancel {
    width: 100%; margin-top: 8px; padding: .6rem; border: none;
    background: none; cursor: pointer; color: #8a7a6a; font-size: .86rem; transition: color .2s;
}
.modal-cancel:hover { color: #dc2626; }

/* ── Section header ── */
.section-label {
    font-size: .8rem; letter-spacing: 3px; text-transform: uppercase;
    color: #C8684A; font-weight: 500; margin-bottom: 8px; display: block;
}
.section-title {
    font-family: var(--font-display, 'Cormorant Garamond', serif);
    font-size: clamp(1.8rem, 4vw, 3rem); font-weight: 400;
    color: #2c2a29; margin-bottom: 12px;
}
body.dark .section-title { color: #e5e0d5; }
.section-title em { color: #C8684A; font-style: italic; }
.section-divider {
    width: 50px; height: 2px; background: #D4AF37; margin: 16px auto 32px;
}
.section-header { text-align: center; margin-bottom: 8px; }
</style>

<main class="vip-page">

  <!-- HERO -->
  <section class="vip-hero">
    <div class="vip-hero-inner">
      <div class="vip-stars">✦ ✦ ✦</div>
      <h1>Club <em>VIP Benna</em></h1>
      <p>Un suivi nutritionnel personnalisé, des consultations en ligne et des avantages exclusifs pour atteindre vos objectifs.</p>
      <?php if ($abonnement): ?>
        <div class="vip-badge-current">
           Abonnement actuel : <strong><?= ucfirst($abonnement['niveau']) ?></strong>
          · <?= $abonnement['jours_restants'] ?> jours restants
          · <a href="<?= BASE ?>/view/client/vip_espace.php" >→ Mon espace VIP</a>
        </div>
      <?php else: ?>
        <div class="vip-badge-current">🟡 Client Normal — Passez au VIP pour un suivi complet</div>
      <?php endif; ?>
    </div>
  </section>

  <!-- PLANS -->
  <section class="plans-section">
    <div class="section-header fade-in" style="padding:0 24px;">
      <p class="section-label">Choisissez votre plan</p>
      <h2 class="section-title">Nos <em>Offres VIP</em></h2>
      <div class="section-divider"></div>
    </div>
    <div class="plans-grid">

      <!-- NORMAL -->
      <div class="plan-card fade-in" style="--delay:.0s">
        <div class="plan-header" style="background:linear-gradient(135deg,hsl(55,88%,93%),hsl(50,78%,87%));">
          <span class="plan-emoji">🟡</span>
          <div class="plan-name">Client Normal</div>
          <div class="plan-desc">L'accès de base à Benna</div>
          <div class="plan-price">0 <span>TND/mois</span></div>
        </div>
        <div class="plan-features">
          <ul>
            <li><span class="check">✓</span> Achat de tous nos produits</li>
            <li><span class="check">✓</span> Conseils santé publics</li>
            <li><span class="check">✓</span> Suivi de commandes</li>
            <li><span class="cross">✗</span> <span class="dimmed">Chat nutritionniste</span></li>
            <li><span class="cross">✗</span> <span class="dimmed">Consultations en ligne</span></li>
            <li><span class="cross">✗</span> <span class="dimmed">Plan alimentaire</span></li>
          </ul>
          <button class="plan-btn" style="background:hsl(50,78%,73%);color:hsl(30,60%,25%);"
                  onclick="window.location='<?= BASE ?>/view/client/produits.php'">Parcourir la boutique →</button>
        </div>
      </div>

      <!-- BASIC -->
      <div class="plan-card fade-in" style="--delay:.12s">
        <div class="plan-header" style="background:linear-gradient(135deg,hsl(145,48%,93%),hsl(145,40%,86%));">
          <span class="plan-emoji">🟢</span>
          <div class="plan-name">VIP Basic</div>
          <div class="plan-desc">Premiers pas personnalisés</div>
          <div class="plan-price">10–15 <span>TND/mois</span></div>
          <div class="plan-price-range">Choisissez votre montant</div>
        </div>
        <div class="plan-features">
          <ul>
            <li><span class="check">✓</span> Badge VIP 🟢 sur votre profil</li>
            <li><span class="check">✓</span> Chat limité avec nutritionniste</li>
            <li><span class="check">✓</span> Conseils généraux personnalisés</li>
            <li><span class="check">✓</span> Offres exclusives 5%</li>
            <li><span class="check">✓</span> Accès contenu premium</li>
            <li><span class="cross">✗</span> <span class="dimmed">Consultations visio</span></li>
          </ul>
          <?php if ($abonnement && $abonnement['niveau'] === 'basic'): ?>
            <button class="plan-btn disabled">✓ Abonnement actuel</button>
          <?php elseif (!isset($_SESSION['user_id'])): ?>
            <button class="plan-btn" style="background:#22c55e;color:white;"
                    onclick="window.location='<?= BASE ?>/view/client/signup.php'">S'inscrire d'abord →</button>
          <?php else: ?>
            <button class="plan-btn" style="background:#22c55e;color:white;"
                    onclick="openVipModal('basic','VIP Basic','10','15','#22c55e')">Choisir Basic →</button>
          <?php endif; ?>
        </div>
      </div>

      <!-- PREMIUM -->
      <div class="plan-card featured fade-in" style="--delay:.24s">
        <div class="plan-header" style="background:linear-gradient(135deg,hsl(220,78%,94%),hsl(220,63%,87%));">
          <div class="plan-badge-featured">⭐ Meilleur choix</div>
          <span class="plan-emoji">🔵</span>
          <div class="plan-name">VIP Premium</div>
          <div class="plan-desc">Le suivi complet</div>
          <div class="plan-price">20–35 <span>TND/mois</span></div>
          <div class="plan-price-range">Le plus populaire</div>
        </div>
        <div class="plan-features">
          <ul>
            <li><span class="check">✓</span> Badge VIP Premium 🔵</li>
            <li><span class="check">✓</span> <strong>Messagerie illimitée</strong></li>
            <li><span class="check">✓</span> <strong>1–2 consultations/mois</strong></li>
            <li><span class="check">✓</span> Plans alimentaires personnalisés</li>
            <li><span class="check">✓</span> Suivi des objectifs santé</li>
            <li><span class="check">✓</span> Réduction 10% produits</li>
          </ul>
          <?php if ($abonnement && $abonnement['niveau'] === 'premium'): ?>
            <button class="plan-btn disabled">✓ Abonnement actuel</button>
          <?php elseif (!isset($_SESSION['user_id'])): ?>
            <button class="plan-btn" style="background:#3b82f6;color:white;"
                    onclick="window.location='<?= BASE ?>/view/client/signup.php'">S'inscrire d'abord →</button>
          <?php else: ?>
            <button class="plan-btn" style="background:#3b82f6;color:white;"
                    onclick="openVipModal('premium','VIP Premium','20','35','#3b82f6')">Choisir Premium →</button>
          <?php endif; ?>
        </div>
      </div>

      <!-- ELITE -->
      <div class="plan-card fade-in" style="--delay:.36s">
        <div class="plan-header" style="background:linear-gradient(135deg,hsl(270,58%,94%),hsl(270,48%,87%));">
          <span class="plan-emoji">🟣</span>
          <div class="plan-name">VIP Elite</div>
          <div class="plan-desc">Coaching de très haut niveau</div>
          <div class="plan-price">40–60 <span>TND/mois</span></div>
          <div class="plan-price-range">Expérience premium totale</div>
        </div>
        <div class="plan-features">
          <ul>
            <li><span class="check">✓</span> Badge VIP Elite 🟣 exclusif</li>
            <li><span class="check">✓</span> <strong>Consultations illimitées</strong></li>
            <li><span class="check">✓</span> Suivi complet 24/7</li>
            <li><span class="check">✓</span> Plans + réajustement auto</li>
            <li><span class="check">✓</span> <strong>Accès prioritaire</strong></li>
            <li><span class="check">✓</span> Réduction 15% + cadeaux</li>
          </ul>
          <?php if ($abonnement && $abonnement['niveau'] === 'elite'): ?>
            <button class="plan-btn disabled">✓ Abonnement actuel</button>
          <?php elseif (!isset($_SESSION['user_id'])): ?>
            <button class="plan-btn" style="background:#8b5cf6;color:white;"
                    onclick="window.location='<?= BASE ?>/view/client/signup.php'">S'inscrire d'abord →</button>
          <?php else: ?>
            <button class="plan-btn" style="background:#8b5cf6;color:white;"
                    onclick="openVipModal('elite','VIP Elite','40','60','#8b5cf6')">Choisir Elite →</button>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </section>

  <!-- FAQ -->
  <div class="faq-section">
    <h2>Questions <em>fréquentes</em></h2>
    <?php
    $faqs = [
      ['Comment fonctionne une consultation en ligne ?', 'Via Jitsi Meet, une plateforme vidéo sécurisée. Vous recevez un lien unique avant chaque séance. Aucune installation requise.'],
      ['Puis-je changer de niveau VIP ?', 'Oui ! Upgrade ou downgrade à tout moment. Le changement prend effet immédiatement.'],
      ['Comment annuler mon abonnement ?', 'Depuis votre espace VIP → «Annuler l\'abonnement». Vous profitez des avantages jusqu\'à la fin de la période.'],
      ['La nutritionniste est disponible 24h/24 ?', 'Elle répond sous 2–4h en semaine. Les membres Elite ont accès prioritaire.'],
      ['Y a-t-il un engagement minimum ?', 'Non. Abonnement mensuel, annulable à tout moment.'],
    ];
    foreach ($faqs as $i => $f): ?>
    <div class="faq-item" id="faq-<?= $i ?>">
      <div class="faq-q" onclick="toggleFaq(<?= $i ?>)">
        <?= htmlspecialchars($f[0]) ?>
        <span class="faq-arrow">▼</span>
      </div>
      <div class="faq-a"><?= htmlspecialchars($f[1]) ?></div>
    </div>
    <?php endforeach; ?>
  </div>

</main>

<!-- MODAL -->
<div class="vip-modal-overlay" id="vipModal">
  <div class="vip-modal">
    <h2 id="modal-title">Rejoindre VIP</h2>
    <div class="plan-summary" id="modal-summary"></div>
    <form action="<?= BASE ?>/view/client/vip_paiement.php" method="GET" id="vipPaymentForm">
      <input type="hidden" name="niveau" id="modal-niveau"/>
      <div class="fm">
        <label>Montant mensuel (TND)</label>
        <div class="price-display-wrap">
          <input type="range" id="prix-slider" step="1"
                 oninput="document.getElementById('prix-display').textContent=this.value+' TND';
                          document.getElementById('prix-input').value=this.value"/>
          <span id="prix-display"
                style="font-family:var(--font-display,'Cormorant Garamond',serif);
                       font-size:1.05rem;min-width:62px;text-align:right;color:#16a34a;">-- TND</span>
        </div>
        <input type="hidden" name="prix" id="prix-input"/>
      </div>
      <div style="background:#fefce8;border-radius:11px;padding:11px 15px;font-size:.8rem;
                  margin-bottom:14px;color:#713f12;border:1px solid #fde68a;">
         Vous allez être redirigé vers la page de paiement sécurisé.
      </div>
      <button type="submit" class="pay-vip-btn" id="modal-btn">Continuer vers le paiement →</button>
      <button type="button" class="modal-cancel"
              onclick="document.getElementById('vipModal').classList.remove('open')">Annuler</button>
    </form>
  </div>
</div>

<script>
function openVipModal(niveau, label, pMin, pMax, couleur) {
  document.getElementById('modal-niveau').value = niveau;
  document.getElementById('modal-title').textContent = '🌿 Rejoindre ' + label;
  document.getElementById('modal-summary').innerHTML =
    '<strong style="font-size:1.02rem;">' + label + '</strong><br/>' +
    '<span style="font-size:.82rem;color:#8a7a6a;">Mensuel · ' + pMin + '–' + pMax + ' TND · Résiliable à tout moment</span>';
  const s = document.getElementById('prix-slider');
  s.min = pMin; s.max = pMax;
  s.value = Math.round((+pMin + +pMax) / 2);
  document.getElementById('prix-display').textContent = s.value + ' TND';
  document.getElementById('prix-input').value = s.value;
  document.getElementById('modal-btn').style.background = couleur;
  document.getElementById('modal-btn').style.color = 'white';
  document.getElementById('vipModal').classList.add('open');
}
document.getElementById('vipModal').addEventListener('click', e => {
  if (e.target.id === 'vipModal') document.getElementById('vipModal').classList.remove('open');
});
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') document.getElementById('vipModal').classList.remove('open');
});
function toggleFaq(i) {
  document.getElementById('faq-' + i).classList.toggle('open');
}
</script>

<?php include "partials/footer.php"; ?>