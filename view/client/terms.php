<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../../config/app.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../config/auth.php";

$userInitial = '?';
$u = null;
$isLoggedIn = isset($_SESSION['user_id']);
if ($isLoggedIn) {
    require_once __DIR__ . "/../../controller/traitement.php";
    $u = getUserById($cnx, $_SESSION['user_id']);
    $userInitial = mb_strtoupper(mb_substr($_SESSION['nom'] ?? 'U', 0, 1));
}

$pageTitle = "Conditions d'Utilisation";
include "partials/header.php";
?>

<style>
.legal-page { padding-top: 110px; min-height: 100vh; }
.legal-hero {
  background: linear-gradient(135deg, hsl(142,38%,12%) 0%, hsl(30,20%,10%) 100%);
  padding: 56px 0 44px;
  border-bottom: 1px solid rgba(212,170,60,0.15);
  position: relative; overflow: hidden;
}
.legal-hero::before {
  position: absolute; right: 8%; top: 50%; transform: translateY(-50%);
  font-size: 8rem; opacity: 0.07; pointer-events: none;
}
.legal-hero-inner { max-width: 860px; margin: 0 auto; padding: 0 24px; }
.legal-breadcrumb {
  font-size: .72rem; letter-spacing: .15em; text-transform: uppercase;
  color: rgba(255,255,255,0.4); margin-bottom: 16px;
}
.legal-breadcrumb a { color: var(--gold); text-decoration: none; }
.legal-tag {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 5px 14px; border-radius: 999px;
  background: rgba(212,170,60,.12); border: 1px solid rgba(212,170,60,.25);
  color: var(--gold); font-size: .72rem; letter-spacing: .1em;
  text-transform: uppercase; margin-bottom: 14px;
}
.legal-hero h1 {
  font-family: var(--font-display); font-size: clamp(2rem,4vw,3rem);
  font-weight: 400; color: white; line-height: 1.1; margin-bottom: 12px;
}
.legal-hero h1 em { font-style: italic; color: var(--gold); }
.legal-hero p { color: rgba(200,220,190,.65); font-size: .9rem; }
.legal-date { font-size: .75rem; color: rgba(255,255,255,.3); margin-top: 16px; }

.legal-body { max-width: 860px; margin: 0 auto; padding: 48px 24px 80px; }

.legal-toc {
  background: var(--card); border: 1px solid var(--border);
  border-radius: 14px; padding: 22px 26px; margin-bottom: 40px;
}
.legal-toc h3 { font-family: var(--font-display); font-size: 1rem; font-weight: 600; color: var(--green); margin-bottom: 12px; }
.legal-toc ol { padding-left: 18px; display: flex; flex-direction: column; gap: 6px; }
.legal-toc li a { font-size: .85rem; color: var(--muted); text-decoration: none; transition: color .2s; }
.legal-toc li a:hover { color: var(--green); }

.legal-section { margin-bottom: 44px; }
.legal-section h2 {
  font-family: var(--font-display); font-size: 1.5rem; font-weight: 600;
  color: var(--fg); margin-bottom: 14px; padding-bottom: 10px;
  border-bottom: 2px solid var(--border); display: flex; align-items: center; gap: 10px;
}
.legal-section h2 .s-num {
  width: 30px; height: 30px; border-radius: 50%;
  background: var(--green); color: white;
  display: flex; align-items: center; justify-content: center;
  font-size: .75rem; font-weight: 700; flex-shrink: 0;
}
.legal-section p { font-size: .92rem; color: color-mix(in oklab,var(--fg) 80%,transparent); line-height: 1.85; margin-bottom: 12px; }
.legal-section ul { padding-left: 20px; display: flex; flex-direction: column; gap: 8px; margin-bottom: 14px; }
.legal-section ul li { font-size: .9rem; color: color-mix(in oklab,var(--fg) 80%,transparent); line-height: 1.7; }

.highlight-box {
  background: color-mix(in oklab, var(--green) 8%, transparent);
  border: 1px solid color-mix(in oklab, var(--green) 25%, transparent);
  border-radius: 12px; padding: 18px 22px; margin: 18px 0;
  font-size: .88rem; color: color-mix(in oklab,var(--fg) 85%,transparent); line-height: 1.75;
}
.warning-box {
  background: rgba(212,170,60,.08);
  border: 1px solid rgba(212,170,60,.25);
  border-radius: 12px; padding: 18px 22px; margin: 18px 0;
  font-size: .88rem; color: color-mix(in oklab,var(--fg) 85%,transparent); line-height: 1.75;
}
/* Force navbar background & text colors on profile page */
.navbar {
  background: transparent !important;  /* solid green background */
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
.delivery-grid {
  display: grid; grid-template-columns: repeat(auto-fit,minmax(180px,1fr));
  gap: 14px; margin-top: 18px;
}
.delivery-card {
  background: var(--card); border: 1px solid var(--border);
  border-radius: 12px; padding: 18px; text-align: center;
  transition: border-color .2s, transform .2s;
}
.delivery-card:hover { border-color: var(--gold); transform: translateY(-3px); }
.delivery-card .di { font-size: 1.8rem; margin-bottom: 8px; }
.delivery-card h4 { font-family: var(--font-display); font-size: 1rem; margin-bottom: 4px; color: var(--gold); }
.delivery-card p  { font-size: .75rem; color: var(--muted); }
</style>

<div class="legal-page">
  <div class="legal-hero">
    <div class="legal-hero-inner">
      <div class="legal-breadcrumb"><a href="<?= BASE ?>/index.php">Accueil</a> / Conditions d'utilisation</div>
      <div class="legal-tag"> CGU</div>
      <h1>Conditions <em>d'Utilisation</em></h1>
      <p>Les règles qui régissent l'utilisation de la plateforme Benna et vos achats en ligne.</p>
      <div class="legal-date">Dernière mise à jour : 1er janvier 2025 — Version 2.0</div>
    </div>
  </div>

  <div class="legal-body">

    <div class="legal-toc">
      <h3>Sommaire</h3>
      <ol>
        <li><a href="#acceptance">Acceptation des conditions</a></li>
        <li><a href="#account">Compte utilisateur</a></li>
        <li><a href="#orders">Commandes et paiement</a></li>
        <li><a href="#delivery">Livraison</a></li>
        <li><a href="#returns">Retours et remboursements</a></li>
        <li><a href="#vip">Abonnement VIP</a></li>
        <li><a href="#ip">Propriété intellectuelle</a></li>
        <li><a href="#liability">Limitation de responsabilité</a></li>
        <li><a href="#law">Droit applicable</a></li>
        <li><a href="#contact">Contact</a></li>
      </ol>
    </div>

    <div class="legal-section" id="acceptance">
      <h2><span class="s-num">1</span> Acceptation des conditions</h2>
      <p>En accédant au site Benna ou en passant une commande, vous acceptez sans réserve les présentes Conditions Générales d'Utilisation (CGU). Si vous n'acceptez pas ces conditions, veuillez ne pas utiliser notre site.</p>
      <p>Benna se réserve le droit de modifier ces conditions à tout moment. Les modifications entrent en vigueur dès leur publication sur le site. Il vous appartient de consulter régulièrement cette page.</p>
      <div class="highlight-box">
        ℹ Ces CGU s'appliquent à toute personne physique majeure (18 ans et plus) utilisant la plateforme Benna depuis la Tunisie ou depuis l'étranger.
      </div>
    </div>

    <div class="legal-section" id="account">
      <h2><span class="s-num">2</span> Compte utilisateur</h2>
      <p>La création d'un compte est nécessaire pour passer commande. Vous vous engagez à :</p>
      <ul>
        <li>Fournir des informations exactes, complètes et à jour.</li>
        <li>Garder votre mot de passe confidentiel et sécurisé.</li>
        <li>Notifier immédiatement Benna de toute utilisation non autorisée de votre compte.</li>
        <li>Ne créer qu'un seul compte par personne.</li>
      </ul>
      <p>Benna se réserve le droit de suspendre ou supprimer tout compte en cas d'utilisation frauduleuse, de fausse identité ou de violation des présentes CGU.</p>
    </div>

    <div class="legal-section" id="orders">
      <h2><span class="s-num">3</span> Commandes et paiement</h2>
      <p>Toute commande passée sur Benna constitue une offre d'achat ferme. La vente est conclue à réception de notre confirmation de commande par email.</p>
      <p>Les prix affichés sont en Dinars Tunisiens (TND), toutes taxes comprises. Benna se réserve le droit de modifier ses prix à tout moment, mais les commandes déjà confirmées ne sont pas affectées.</p>
      <ul>
        <li><strong>Paiement à la livraison (Cash)</strong> — disponible pour toute la Tunisie.</li>
        <li><strong>Virement bancaire</strong> — coordonnées communiquées par email.</li>
        <li><strong>Paiement en ligne</strong> — via notre partenaire de paiement sécurisé.</li>
      </ul>
      <div class="warning-box">
         En cas de non-paiement à la livraison, Benna se réserve le droit de suspendre votre accès à la commande à la livraison pour les futures commandes.
      </div>
    </div>

    <div class="legal-section" id="delivery">
      <h2><span class="s-num">4</span> Livraison</h2>
      <p>Benna livre dans toute la Tunisie. Les délais sont indicatifs et peuvent varier selon votre région et les conditions logistiques.</p>
      <div class="delivery-grid">
        <div class="delivery-card">
          <h4>Grand Tunis</h4>
          <p>24 à 48h ouvrables</p>
        </div>
        <div class="delivery-card">
          <h4>Autres villes</h4>
          <p>48 à 72h ouvrables</p>
        </div>
        <div class="delivery-card">
          <h4>Zones éloignées</h4>
          <p>3 à 5 jours ouvrables</p>
        </div>
        <div class="delivery-card">
          <h4>VIP Elite</h4>
          <p>Livraison prioritaire</p>
        </div>
      </div>
      <p style="margin-top:18px;">Les frais de livraison sont calculés selon votre région et affichés au moment de la commande. La livraison est offerte pour toute commande VIP Elite.</p>
    </div>

    <div class="legal-section" id="returns">
      <h2><span class="s-num">5</span> Retours et remboursements</h2>
      <p>En raison de la nature périssable de nos produits alimentaires artisanaux, les retours sont acceptés uniquement dans les cas suivants :</p>
      <ul>
        <li>Produit reçu endommagé ou détérioré lors du transport.</li>
        <li>Produit non conforme à la commande passée.</li>
        <li>Produit présentant un défaut de fabrication.</li>
      </ul>
      <p>Pour toute réclamation, contactez-nous dans les <strong>24 heures</strong> suivant la réception, avec photo à l'appui, à l'adresse <strong>support@benna.tn</strong>. Le remboursement ou le remplacement sera effectué dans un délai de 5 à 7 jours ouvrables.</p>
    </div>

    <div class="legal-section" id="vip">
      <h2><span class="s-num">6</span> Abonnement VIP</h2>
      <p>Le Club VIP Benna propose trois formules d'abonnement (Basic, Premium, Elite) avec des avantages exclusifs : réductions, consultations nutritionnelles, livraisons prioritaires.</p>
      <ul>
        <li>L'abonnement est renouvelé automatiquement à l'échéance, sauf résiliation.</li>
        <li>La résiliation peut être effectuée à tout moment depuis votre espace client, avec effet à la fin de la période en cours.</li>
        <li>Aucun remboursement n'est accordé pour la période déjà entamée.</li>
        <li>Les réductions VIP ne sont pas cumulables avec d'autres offres promotionnelles.</li>
      </ul>
    </div>

    <div class="legal-section" id="ip">
      <h2><span class="s-num">7</span> Propriété intellectuelle</h2>
      <p>L'ensemble du contenu présent sur le site Benna (textes, images, logos, recettes, vidéos, design) est la propriété exclusive de Benna et est protégé par les lois tunisiennes et internationales sur la propriété intellectuelle.</p>
      <p>Toute reproduction, distribution ou utilisation commerciale sans autorisation écrite préalable est strictement interdite.</p>
    </div>

    <div class="legal-section" id="liability">
      <h2><span class="s-num">8</span> Limitation de responsabilité</h2>
      <p>Benna met tout en œuvre pour assurer la disponibilité et la qualité de ses services. Cependant, la responsabilité de Benna ne peut être engagée en cas de :</p>
      <ul>
        <li>Interruption temporaire du site pour maintenance.</li>
        <li>Retards de livraison imputables au transporteur ou à des événements de force majeure.</li>
        <li>Utilisation incorrecte ou abusive des produits par le client.</li>
        <li>Réactions allergiques à des ingrédients dûment mentionnés sur l'étiquetage.</li>
      </ul>
    </div>

    <div class="legal-section" id="law">
      <h2><span class="s-num">9</span> Droit applicable</h2>
      <p>Les présentes CGU sont régies par le droit tunisien. En cas de litige, et après tentative de résolution amiable, les tribunaux compétents de Sousse seront seuls compétents.</p>
      <p>Conformément à la législation tunisienne sur la protection du consommateur, vous bénéficiez des garanties légales applicables aux produits alimentaires.</p>
    </div>

    <div class="legal-section" id="contact">
      <h2><span class="s-num">10</span> Contact</h2>
      <p>Pour toute question relative aux présentes conditions :</p>
      <p>📧 <strong>legal@benna.tn</strong><br>📍 Benna, Sousse, Tunisie<br>📞 +216 XX XXX XXX</p>
      <p>Nous nous engageons à répondre à toute demande dans un délai de 5 jours ouvrables.</p>
    </div>

  </div>
</div>

<?php include "partials/footer.php"; ?>