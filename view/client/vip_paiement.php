<?php
// view/client/vip_paiement.php
session_start();
ob_start();

require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../config/app.php";
require_once __DIR__ . "/../../config/auth.php";
require_once __DIR__ . "/../../controller/traitement.php";

if (!isset($_SESSION['user_id'])) { 
    header("Location: " . BASE . "/view/client/signup.php"); 
    ob_end_flush();
    exit(); 
}

// Get payment parameters
$niveau = $_GET['niveau'] ?? '';
$prix = (float)($_GET['prix'] ?? 0);

// Validate niveau
if (!in_array($niveau, ['basic', 'premium', 'elite']) || $prix <= 0) {
    header("Location: " . BASE . "/view/client/vip.php");
    ob_end_flush();
    exit();
}

$pageTitle = "Paiement VIP - " . ucfirst($niveau);
include "partials/header.php";

$niveauInfo = [
    'basic'   => ['emoji'=>'🟢', 'label'=>'VIP Basic', 'couleur'=>'#b9efcd', 
                  'benefits'=>['Badge VIP Basic', 'Chat limité avec nutritionniste', 'Conseils personnalisés', 'Offres exclusives 5%']],
    'premium' => ['emoji'=>'🔵', 'label'=>'VIP Premium', 'couleur'=>'#bdd3f6', 
                  'benefits'=>['Badge VIP Premium', 'Messagerie illimitée', '2 consultations/mois', 'Plans alimentaires personnalisés', 'Réduction 10%']],
    'elite'   => ['emoji'=>'🟣', 'label'=>'VIP Elite', 'couleur'=>'#c7b7ec', 
                  'benefits'=>['Badge VIP Elite', 'Consultations illimitées', 'Suivi complet 24/7', 'Plans personnalisés', 'Réduction 15%', 'Livraison prioritaire']],
][$niveau];

$user = getUserById($cnx, $_SESSION['user_id']);
$userName = explode(' ', $user['nom'])[0];
?>

<style>
.pay-vip-page {
    padding-top: 110px;
    min-height: 80vh;
    background: var(--cream);
}
.pay-vip-container {
    max-width: 1100px;
    margin: 0 auto;
    padding: 2rem;
}
.pay-vip-grid {
    display: grid;
    grid-template-columns: 1fr 380px;
    gap: 2rem;
}
@media (max-width: 800px) {
    .pay-vip-grid {
        grid-template-columns: 1fr;
    }
}
.pay-vip-summary {
    background: white;
    border-radius: 20px;
    padding: 2rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    border: 1px solid var(--border);
    position: sticky;
    top: 100px;
    height: fit-content;
}
.pay-vip-summary h3 {
    font-family: var(--font-display);
    margin: 0 0 1rem;
    color: var(--fg);
}
.vip-plan-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.3rem 1rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: bold;
    margin-bottom: 1rem;
}
.vip-benefits {
    list-style: none;
    padding: 0;
    margin: 1rem 0;
}
.vip-benefits li {
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.vip-benefits li::before {
    content: "✓";
    color: var(--green);
    font-weight: bold;
}
.pay-vip-price {
    font-size: 2rem;
    font-weight: bold;
    color: var(--green);
    margin: 1rem 0;
}
.pay-vip-price small {
    font-size: 0.9rem;
    color: var(--muted);
}
.pay-vip-form-card {
    background: white;
    border-radius: 20px;
    padding: 2rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    border: 1px solid var(--border);
}
.pay-vip-form-card h3 {
    font-family: var(--font-display);
    margin: 0 0 1.5rem;
    font-size: 1.3rem;
}
.method-cards {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1.5rem;
}
.method-card {
    border: 2px solid var(--border);
    border-radius: 14px;
    padding: 1rem;
    text-align: center;
    cursor: pointer;
    transition: .2s;
}
.method-card:hover, .method-card.selected {
    border-color: var(--green);
    background: #e8f0e8;
}
.method-card input {
    display: none;
}
.method-icon {
    font-size: 1.8rem;
    margin-bottom: 0.3rem;
}
.method-label {
    font-weight: 600;
    font-size: 0.85rem;
}
.card-form {
    background: var(--cream);
    border-radius: 14px;
    padding: 1.5rem;
    margin-bottom: 1rem;
    display: none;
}
.card-form.visible {
    display: block;
}
.frow {
    margin-bottom: 1rem;
}
.frow label {
    display: block;
    font-size: 0.8rem;
    font-weight: 600;
    margin-bottom: 0.3rem;
    color: var(--green-dark);
}
.frow input {
    width: 100%;
    padding: 0.7rem 1rem;
    border: 2px solid var(--border);
    border-radius: 10px;
    font-size: 0.9rem;
    box-sizing: border-box;
}
.frow input:focus {
    border-color: var(--green);
    outline: none;
}
.card-visual {
    background: linear-gradient(135deg, var(--green-dark), var(--green));
    border-radius: 16px;
    padding: 1.2rem;
    color: white;
    margin-bottom: 1rem;
}
.card-visual .num {
    font-size: 1.1rem;
    letter-spacing: 0.1em;
    margin: 0.5rem 0;
}
.pay-vip-btn {
    width: 100%;
    padding: 1rem;
    border: none;
    border-radius: 14px;
    background: linear-gradient(135deg, var(--green), var(--green-dark));
    color: white;
    font-family: var(--font-display);
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: .2s;
    margin-top: 0.5rem;
}
.pay-vip-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(45, 106, 79, 0.3);
}
.virement-info {
    background: var(--cream);
    border-radius: 14px;
    padding: 1.2rem;
    margin-top: 1rem;
    border: 1px solid var(--border);
    display: none;
}
.virement-info.visible {
    display: block;
}
.secure-badge {
    text-align: center;
    margin-top: 1rem;
    font-size: 0.8rem;
    color: var(--muted);
}
body.dark .pay-vip-summary,
body.dark .pay-vip-form-card {
    background: var(--card);
}
</style>

<main class="pay-vip-page">
    <div class="pay-vip-container">
        <div class="section-header fade-in">
            <p class="section-label">Adhésion VIP</p>
            <h1 class="section-title">Finaliser votre <em>abonnement</em></h1>
            <div class="section-divider"></div>
        </div>

        <div class="pay-vip-grid">
            <!-- Payment Form -->
            <div class="pay-vip-form-card fade-in">
                <h3> Mode de paiement</h3>

                <form method="POST" id="vipPaymentForm" action="<?= BASE ?>/controller/vip_controller.php?action=process_payment">
                    <input type="hidden" name="niveau" value="<?= $niveau ?>">
                    <input type="hidden" name="prix" value="<?= $prix ?>">
                    <input type="hidden" name="redirect" value="vip">

                    <div class="method-cards">
                        <div class="method-card selected" onclick="selectMethod('carte', this)">
                            <input type="radio" name="methode" value="carte" checked/>
                            <div class="method-icon">💳</div>
                            <div class="method-label">Carte bancaire</div>
                        </div>
                        <div class="method-card" onclick="selectMethod('virement', this)">
                            <input type="radio" name="methode" value="virement"/>
                            <div class="method-icon">🏦</div>
                            <div class="method-label">Virement bancaire</div>
                        </div>
                    </div>

                    <!-- Card payment form -->
                    <div class="card-form visible" id="carteForm">
                        <div class="card-visual">
                            <div style="font-size:0.7rem; opacity:0.8;">CARTE BANCAIRE</div>
                            <div class="num" id="cardNumDisplay">•••• •••• •••• ••••</div>
                            <div style="display:flex; justify-content:space-between;">
                                <span id="cardNameDisplay"><?= strtoupper($userName) ?></span>
                                <span id="cardExpDisplay">MM/AA</span>
                            </div>
                        </div>
                        <div class="frow">
                            <label>Numéro de carte</label>
                            <input type="text" id="cardNumInput" name="card_num" placeholder="1234 5678 9012 3456" maxlength="19"
                                   oninput="formatCard(this)">
                        </div>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                            <div class="frow">
                                <label>Nom sur la carte</label>
                                <input type="text" id="cardNameInput" name="card_name" placeholder="Prénom Nom" value="<?= $user['nom'] ?>"
                                       oninput="document.getElementById('cardNameDisplay').textContent=this.value.toUpperCase()||'<?= strtoupper($userName) ?>'">
                            </div>
                            <div class="frow">
                                <label>Expiration (MM/AA)</label>
                                <input type="text" id="cardExpInput" name="card_exp" placeholder="12/28" maxlength="5"
                                       oninput="formatExp(this)">
                            </div>
                        </div>
                        <div class="frow">
                            <label>CVV (3 chiffres)</label>
                            <input type="password" name="card_cvv" placeholder="•••" maxlength="3">
                        </div>
                    </div>

                    <!-- Bank transfer info -->
                    <div class="virement-info" id="virementInfo">
                        <p style="font-weight:600; margin-bottom:0.5rem;"> Coordonnées bancaires</p>
                        <p style="font-size:0.85rem; line-height:1.6;">
                            <strong>Banque :</strong> STB Bank Tunisie<br>
                            <strong>Titulaire :</strong> BENNA SARL<br>
                            <strong>RIB :</strong> 10 082 0100000012345 67<br>
                            <strong>IBAN :</strong> TN59 1008 2010 0000 0123 4567 89<br>
                            <strong>Montant :</strong> <?= number_format($prix, 3) ?> TND<br>
                            <strong>Référence :</strong> VIP-<?= strtoupper($niveau) ?>-<?= $_SESSION['user_id'] ?>
                        </p>
                        <p style="font-size:0.75rem; color:#dc2626; margin-top:0.5rem;">
                            Le virement sera vérifié sous 24-48h. L'abonnement sera activé après confirmation.
                        </p>
                    </div>

                    <button type="submit" class="pay-vip-btn" id="payBtn">
                        Payer <?= number_format($prix, 3) ?> TND
                    </button>
                    <div class="secure-badge">
                        Paiement 100% sécurisé · SSL chiffré
                    </div>
                </form>
            </div>

            <!-- Order Summary -->
            <div class="pay-vip-summary fade-in">
                <h3> Récapitulatif</h3>
                <div class="vip-plan-badge" style="background: <?= $niveauInfo['couleur'] ?>20; color: <?= $niveauInfo['couleur'] ?>;">
                    <span><?= $niveauInfo['emoji'] ?></span>
                    <span><?= $niveauInfo['label'] ?></span>
                </div>
                <ul class="vip-benefits">
                    <?php foreach ($niveauInfo['benefits'] as $benefit): ?>
                        <li><?= htmlspecialchars($benefit) ?></li>
                    <?php endforeach; ?>
                </ul>
                <div class="pay-vip-price">
                    <?= number_format($prix, 3) ?> TND <small>/mois</small>
                </div>
                <div style="margin-top: 1rem; padding: 0.8rem; background: var(--cream); border-radius: 12px; font-size: 0.8rem;">
                    <strong> Abonnement mensuel</strong><br>
                    Renouvellement automatique chaque mois. Résiliable à tout moment.
                </div>
                <a href="<?= BASE ?>/view/client/vip.php" style="display: block; text-align: center; margin-top: 1rem; color: var(--muted); font-size: 0.85rem;">
                    ← Modifier ma sélection
                </a>
            </div>
        </div>
    </div>
</main>

<script>
function selectMethod(method, element) {
    document.querySelectorAll('.method-card').forEach(c => c.classList.remove('selected'));
    element.classList.add('selected');
    element.querySelector('input').checked = true;
    
    const carteForm = document.getElementById('carteForm');
    const virementInfo = document.getElementById('virementInfo');
    
    if (method === 'carte') {
        carteForm.classList.add('visible');
        virementInfo.classList.remove('visible');
    } else {
        carteForm.classList.remove('visible');
        virementInfo.classList.add('visible');
    }
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
    if (v.length >= 2) {
        v = v.substring(0, 2) + '/' + v.substring(2, 4);
    }
    el.value = v;
    document.getElementById('cardExpDisplay').textContent = el.value || 'MM/AA';
}

// Form validation
document.getElementById('vipPaymentForm').addEventListener('submit', function(e) {
    const method = document.querySelector('input[name="methode"]:checked')?.value;
    
    if (method === 'carte') {
        const num = document.getElementById('cardNumInput').value.replace(/\D/g,'');
        const name = document.getElementById('cardNameInput').value.trim();
        const exp = document.getElementById('cardExpInput').value.trim();
        const cvv = document.querySelector('input[name="card_cvv"]').value.trim();
        
        if (num.length < 13) {
            e.preventDefault();
            alert('Veuillez entrer un numéro de carte valide (13-16 chiffres).');
            return false;
        }
        if (!name) {
            e.preventDefault();
            alert('Veuillez entrer le nom sur la carte.');
            return false;
        }
        if (!/^\d{2}\/\d{2}$/.test(exp)) {
            e.preventDefault();
            alert('Veuillez entrer une date d\'expiration valide (MM/AA).');
            return false;
        }
        if (cvv.length < 3) {
            e.preventDefault();
            alert('Veuillez entrer un CVV valide (3 chiffres).');
            return false;
        }
    }
    
    const btn = document.getElementById('payBtn');
    btn.disabled = true;
    btn.textContent = '⏳ Traitement en cours...';
});
</script>

<?php include "partials/footer.php"; ?>