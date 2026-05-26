<?php
session_start();
include_once __DIR__ . "/../../config/database.php";
include_once __DIR__ . "/../../config/app.php";
include_once __DIR__ . "/../../controller/traitement.php";

if (!isset($_SESSION['user_id'])) { 
    header("Location: ".BASE."/view/client/signup.php"); exit(); 
}

$commande_id = $_GET['commande_id'] ?? null;
if (!$commande_id) { header("Location: ".BASE."/view/client/panier.php"); exit(); }

$commande = getCommandeById($cnx, $commande_id);
if (!$commande || $commande['user_id'] != $_SESSION['user_id']) {
    header("Location: ".BASE."/view/client/mes_commandes.php"); exit(); 
}

if ($commande['paiement_statut'] === 'paye') {
    header("Location: ".BASE."/view/client/mes_commandes.php"); exit(); 
}

$totalNet = (float)$commande['total'];
$livraison = $totalNet >= 150 ? 0 : 5;
$totalFinal = $totalNet; // déjà inclus dans commande.total

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $methode = $_POST['methode'] ?? '';
    if ($methode === 'cash') {
        updatePaiementStatut($cnx, $commande_id, 'paye');
        $_SESSION['pay_success'] = "Paiement confirmé ! Votre commande est en préparation.";
        header("Location: ".BASE."/view/client/mes_commandes.php");
        exit();
    } elseif ($methode === 'carte') {
        // Simulation validation carte (à remplacer par vrai gateway)
        updatePaiementStatut($cnx, $commande_id, 'paye');
        $_SESSION['pay_success'] = "Paiement par carte accepté. Merci !";
        header("Location: ".BASE."/view/client/mes_commandes.php");
        exit();
    } elseif ($methode === 'virement') {
        $_SESSION['pay_success'] = "Votre virement est attendu. La commande sera validée après réception.";
        header("Location: ".BASE."/view/client/mes_commandes.php");
        exit();
    } else {
        $_SESSION['pay_error'] = "Veuillez choisir un mode de paiement.";
    }
}

$pageTitle = "Paiement";
include "partials/header.php";
?>

<style>

.pay-page{padding-top:110px;min-height:80vh;}
.pay-grid{display:grid;grid-template-columns:1fr 380px;gap:2rem;margin-top:2rem;}
@media(max-width:800px){.pay-grid{grid-template-columns:1fr;}}
.pay-summary{background:var(--cream);border-radius:20px;padding:2rem;box-shadow:var(--shadow,0 4px 20px rgba(0,0,0,.07));}
.pay-summary h3{font-family:var(--font-display);margin:0 0 1.2rem;}
.sum-line{display:flex;justify-content:space-between;padding:.5rem 0;border-bottom:1px solid var(--border);font-size:.92rem;}
.sum-total{font-family:var(--font-display);font-size:1.2rem;font-weight:700;color:var(--green-dark);border-bottom:none;margin-top:.4rem;}
.pay-form-card{background:var(--cream);border-radius:20px;padding:2rem;box-shadow:var(--shadow,0 4px 20px rgba(0,0,0,.07));}
.pay-form-card h3{font-family:var(--font-display);margin:0 0 1.5rem;font-size:1.3rem;}
.method-cards{display:grid;grid-template-columns:1fr 1fr 1fr;gap:.8rem;margin-bottom:1.5rem;}
.method-card{border:2px solid var(--border);border-radius:14px;padding:1rem;text-align:center;cursor:pointer;transition:.2s;}
.method-card:hover,.method-card.selected{border-color:var(--green);background:var(--cream-dark,#e8f0e8);}
.method-card input{display:none;}
.method-icon{font-size:2rem;margin-bottom:.4rem;}
.method-label{font-family:var(--font-display);font-size:.88rem;color:var(--green-dark);}
/* Carte bancaire */
.card-form{background:white;border-radius:14px;padding:1.5rem;margin-bottom:1rem;display:none;}
.card-form.visible{display:block;}
.frow{margin-bottom:1rem;}
.frow label{display:block;font-size:.85rem;color:var(--muted);margin-bottom:.3rem;font-family:var(--font-display);}
.frow input{width:100%;padding:.7rem 1rem;border:2px solid var(--border);border-radius:10px;font-family:var(--font-body);font-size:.95rem;outline:none;box-sizing:border-box;transition:.2s;}
.frow input:focus{border-color:var(--green);}
.card-visual{background:linear-gradient(135deg,var(--green-dark),var(--green));border-radius:16px;padding:1.5rem;color:white;margin-bottom:1.2rem;font-family:var(--font-display);}
.card-visual .num{font-size:1.3rem;letter-spacing:.2em;margin:.8rem 0;}
.card-visual .details{display:flex;justify-content:space-between;font-size:.8rem;opacity:.8;}
.pay-btn{width:100%;padding:1rem;border:none;border-radius:14px;background:linear-gradient(135deg,var(--green),var(--green-dark));color:white;font-family:var(--font-display);font-size:1.1rem;font-weight:700;cursor:pointer;transition:.2s;margin-top:.5rem;}
.pay-btn:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(45,106,79,.35);}
.secure-badge{display:flex;align-items:center;gap:.4rem;justify-content:center;color:var(--muted);font-size:.8rem;margin-top:.8rem;}
.navbar {
  background: transparent !important;  /* solid green background */
  backdrop-filter: none !important;
  border-bottom: 1px solid rgba(255,255,255,0.15);
}
.navbar.scrolled {
  background: #1a4a2e !important;
  backdrop-filter: none !important;
  border-bottom: 1px solid rgba(255,255,255,0.15) !important;
}

body.dark .navbar {
  background: #0a1a0a !important;
}
.navbar .nav-links a,
.navbar .nav-links2 a {
  color: rgba(20, 58, 31, 0.95) !important;
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
.navbar.scrolled .nav-logo {
  color: #ffffff !important;
  text-shadow: none !important;
}
</style>

<main class="pay-page">
  <div class="container">
    <div class="section-header fade-in">
      <p class="section-label">Finaliser</p>
      <h1 class="section-title">Paiement <em>Sécurisé</em></h1>
      <div class="section-divider"></div>
    </div>

    <?php if (isset($_SESSION['pay_error'])): ?>
      <div class="pay-error">⚠️ <?= htmlspecialchars($_SESSION['pay_error']) ?></div>
      <?php unset($_SESSION['pay_error']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['pay_success'])): ?>
      <div class="pay-success">✓ <?= htmlspecialchars($_SESSION['pay_success']) ?></div>
      <?php unset($_SESSION['pay_success']); ?>
    <?php endif; ?>

    <div class="pay-grid">
      <!-- PAYMENT FORM -->
      <div class="pay-form-card fade-in">
        <h3>Choisissez votre mode de paiement</h3>

        <form method="POST" id="payForm">
          <div class="method-cards">
            <label class="method-card" onclick="selectMethod('carte',this)">
              <input type="radio" name="methode" value="carte"/>
              <div class="method-icon">💳</div>
              <div class="method-label">Carte bancaire</div>
            </label>
            <label class="method-card selected" onclick="selectMethod('cash',this)">
              <input type="radio" name="methode" value="cash" checked/>
              <div class="method-icon">💵</div>
              <div class="method-label">Paiement à la livraison</div>
            </label>
            <label class="method-card" onclick="selectMethod('virement',this)">
              <input type="radio" name="methode" value="virement"/>
              <div class="method-icon">🏦</div>
              <div class="method-label">Virement bancaire</div>
            </label>
          </div>

          <div class="card-form" id="carteForm">
            <div class="card-visual">
              <div> CARTE BANCAIRE</div>
              <div class="num" id="cardNumDisplay">•••• •••• •••• ••••</div>
              <div class="details"><span id="cardNameDisplay">NOM PRÉNOM</span><span id="cardExpDisplay">MM/AA</span></div>
            </div>
            <div class="frow"><label>Numéro de carte</label><input type="text" id="cardNumInput" name="card_num" placeholder="1234 5678 9012 3456" maxlength="19" oninput="formatCard(this)"/></div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
              <div class="frow"><label>Nom sur la carte</label><input type="text" id="cardNameInput" name="card_name" placeholder="Prénom Nom" oninput="document.getElementById('cardNameDisplay').textContent=this.value.toUpperCase()||'NOM PRÉNOM'"/></div>
              <div class="frow"><label>Expiration (MM/AA)</label><input type="text" id="cardExpInput" name="card_exp" placeholder="12/28" maxlength="5" oninput="formatExp(this)"/></div>
            </div>
            <div class="frow"><label>CVV</label><input type="password" name="card_cvv" placeholder="•••" maxlength="3"/></div>
          </div>

          <div id="virementInfo" style="display:none;background:var(--cream);border-radius:14px;padding:1.5rem;margin-bottom:1rem;">
            <p><strong>Virement bancaire</strong><br/>RIB: 10 082 0100000012345 67<br/>Montant: <?= number_format($totalFinal, 3) ?> TND<br/>Référence: CMD-<?= $commande['id'] ?></p>
            <p style="color:#dc2626;"> La commande sera validée après réception du virement (24-48h)</p>
          </div>

          <button type="submit" class="pay-btn"> Confirmer le paiement de <?= number_format($totalFinal, 3) ?> TND</button>
          <div class="secure-badge">Paiement 100% sécurisé</div>
        </form>
      </div>

      <div class="pay-summary fade-in">
        <h3>Récapitulatif</h3>
        <div class="sum-line"><span>Commande #<?= $commande['id'] ?></span></div>
        <?php foreach ($commande['details'] as $d): ?>
        <div class="sum-line"><span><?= htmlspecialchars($d['nom']) ?> ×<?= $d['quantite'] ?></span><span><?= number_format($d['quantite'] * $d['prix_unitaire'], 3) ?> TND</span></div>
        <?php endforeach; ?>
        <div class="sum-line sum-total"><span>Total</span><span><?= number_format($totalFinal, 3) ?> TND</span></div>
      </div>
    </div>
  </div>
</main>

<script>
function selectMethod(method, el) {
  document.querySelectorAll('.method-card').forEach(c => c.classList.remove('selected'));
  el.classList.add('selected');
  el.querySelector('input').checked = true;
  document.getElementById('carteForm').classList.toggle('visible', method === 'carte');
  document.getElementById('virementInfo').style.display = method === 'virement' ? 'block' : 'none';
}
function formatCard(el) {
  let v = el.value.replace(/\D/g, '').substring(0, 16);
  let formatted = v.replace(/(.{4})/g, '$1 ').trim();
  el.value = formatted;
  let displayNum = v.padEnd(16, '•').replace(/(.{4})/g, '$1 ').trim();
  document.getElementById('cardNumDisplay').textContent = displayNum || '•••• •••• •••• ••••';
}
function formatExp(el) {
  let v = el.value.replace(/\D/g, '');
  if (v.length >= 2) v = v.substring(0,2) + '/' + v.substring(2,4);
  el.value = v;
  document.getElementById('cardExpDisplay').textContent = el.value || 'MM/AA';
}
</script> <br>

<?php include "partials/footer.php"; ?>