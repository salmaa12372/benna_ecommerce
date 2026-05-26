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

$pageTitle = 'Politique de Confidentialité';
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
  content: '🛡️';
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

.rights-grid {
  display: grid; grid-template-columns: repeat(auto-fit, minmax(200px,1fr));
  gap: 14px; margin-top: 18px;
}
.right-card {
  background: var(--card); border: 1px solid var(--border);
  border-radius: 12px; padding: 16px 18px;
  transition: border-color .2s, transform .2s;
}
.right-card:hover { border-color: var(--green); transform: translateY(-3px); }
.right-card .ri { font-size: 1.4rem; margin-bottom: 8px; }
.right-card h4 { font-family: var(--font-display); font-size: .95rem; font-weight: 600; margin-bottom: 4px; }
.right-card p  { font-size: .75rem; color: var(--muted); line-height: 1.6; }

.highlight-box {
  background: color-mix(in oklab, var(--green) 8%, transparent);
  border: 1px solid color-mix(in oklab, var(--green) 25%, transparent);
  border-radius: 12px; padding: 18px 22px; margin: 18px 0;
  font-size: .88rem; color: color-mix(in oklab,var(--fg) 85%,transparent); line-height: 1.75;
}
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
</style>

<div class="legal-page">
  <div class="legal-hero">
    <div class="legal-hero-inner">
      <div class="legal-breadcrumb"><a href="<?= BASE ?>/index.php">Accueil</a> / Confidentialité</div>
      <div class="legal-tag">Politique de Confidentialité</div>
      <h1>Vos données, <em>notre responsabilité</em></h1>
      <p>Benna s'engage à protéger votre vie privée avec la plus grande transparence.</p>
      <div class="legal-date">Dernière mise à jour : 1er janvier 2025</div>
    </div>
  </div>

  <div class="legal-body">

    <div class="legal-toc">
      <h3> Sommaire</h3>
      <ol>
        <li><a href="#who">Qui sommes-nous ?</a></li>
        <li><a href="#collect">Données que nous collectons</a></li>
        <li><a href="#use">Comment nous utilisons vos données</a></li>
        <li><a href="#share">Partage des données</a></li>
        <li><a href="#retention">Conservation des données</a></li>
        <li><a href="#rights">Vos droits</a></li>
        <li><a href="#security">Sécurité</a></li>
        <li><a href="#contact">Nous contacter</a></li>
      </ol>
    </div>

    <div class="legal-section" id="who">
      <h2><span class="s-num">1</span> Qui sommes-nous ?</h2>
      <p>Benna est une marque tunisienne spécialisée dans les snacks artisanaux sains, sans gluten, sans lactose et 100 % naturels. Notre siège social est situé à Sousse, Tunisie.</p>
      <p>En tant que responsable du traitement de vos données personnelles, Benna s'engage à respecter la législation tunisienne sur la protection des données (Loi n° 2004-63) ainsi que les meilleures pratiques internationales.</p>
    </div>

    <div class="legal-section" id="collect">
      <h2><span class="s-num">2</span> Données que nous collectons</h2>
      <p>Nous collectons uniquement les données nécessaires à la fourniture de nos services :</p>
      <ul>
        <li><strong>Données d'identification</strong> : nom, prénom, adresse email, numéro de téléphone.</li>
        <li><strong>Données de livraison</strong> : adresse postale, ville, code postal.</li>
        <li><strong>Données de commande</strong> : historique d'achats, préférences alimentaires, abonnement VIP.</li>
        <li><strong>Données de connexion</strong> : adresse IP, type de navigateur, pages visitées, durée de session.</li>
        <li><strong>Données de paiement</strong> : traitées exclusivement par nos partenaires de paiement sécurisé. Benna ne stocke jamais vos données bancaires.</li>
      </ul>
    </div>

    <div class="legal-section" id="use">
      <h2><span class="s-num">3</span> Comment nous utilisons vos données</h2>
      <p>Vos données sont utilisées dans les buts suivants :</p>
      <ul>
        <li>Traitement et livraison de vos commandes.</li>
        <li>Gestion de votre compte client et abonnement VIP.</li>
        <li>Communication sur l'état de votre commande par email ou SMS.</li>
        <li>Personnalisation de votre expérience et recommandations produits.</li>
        <li>Envoi d'offres promotionnelles (avec votre consentement).</li>
        <li>Amélioration de notre site et de nos services.</li>
        <li>Respect de nos obligations légales et fiscales.</li>
      </ul>
      <div class="highlight-box">
        💚 Benna ne vend jamais vos données personnelles à des tiers à des fins commerciales.
      </div>
    </div>

    <div class="legal-section" id="share">
      <h2><span class="s-num">4</span> Partage des données</h2>
      <p>Vos données peuvent être partagées uniquement avec :</p>
      <ul>
        <li><strong>Prestataires logistiques</strong> — pour assurer la livraison de vos commandes.</li>
        <li><strong>Partenaires de paiement</strong> — pour sécuriser vos transactions.</li>
        <li><strong>Outils d'analyse</strong> — Google Analytics (données anonymisées).</li>
        <li><strong>Autorités compétentes</strong> — uniquement si la loi l'exige.</li>
      </ul>
      <p>Tous nos partenaires sont soumis à des obligations contractuelles strictes de confidentialité.</p>
    </div>

    <div class="legal-section" id="retention">
      <h2><span class="s-num">5</span> Conservation des données</h2>
      <p>Nous conservons vos données personnelles pendant les durées suivantes :</p>
      <ul>
        <li><strong>Données de compte</strong> : tant que votre compte est actif + 3 ans après la dernière activité.</li>
        <li><strong>Données de commande</strong> : 10 ans (obligation légale comptable).</li>
        <li><strong>Données marketing</strong> : 3 ans à compter de votre dernier contact.</li>
        <li><strong>Logs de connexion</strong> : 12 mois.</li>
      </ul>
    </div>

    <div class="legal-section" id="rights">
      <h2><span class="s-num">6</span> Vos droits</h2>
      <p>Conformément à la loi, vous disposez des droits suivants sur vos données personnelles :</p>
      <div class="rights-grid">
        <div class="right-card"><div class="ri"></div><h4>Accès</h4><p>Consulter toutes vos données que nous détenons.</p></div>
        <div class="right-card"><div class="ri"></div><h4>Rectification</h4><p>Corriger des informations inexactes ou incomplètes.</p></div>
        <div class="right-card"><div class="ri"></div><h4>Suppression</h4><p>Demander l'effacement de vos données personnelles.</p></div>
        <div class="right-card"><div class="ri"></div><h4>Limitation</h4><p>Restreindre le traitement de vos données.</p></div>
        <div class="right-card"><div class="ri"></div><h4>Portabilité</h4><p>Recevoir vos données dans un format lisible par machine.</p></div>
        <div class="right-card"><div class="ri"></div><h4>Opposition</h4><p>Vous opposer au traitement à des fins marketing.</p></div>
      </div>
      <p style="margin-top:18px;">Pour exercer vos droits, contactez-nous à <strong>privacy@benna.tn</strong>. Nous répondrons dans un délai de 30 jours.</p>
    </div>

    <div class="legal-section" id="security">
      <h2><span class="s-num">7</span> Sécurité</h2>
      <p>Benna met en œuvre des mesures techniques et organisationnelles appropriées pour protéger vos données contre tout accès non autorisé, perte ou divulgation :</p>
      <ul>
        <li>Chiffrement SSL/TLS de toutes les communications.</li>
        <li>Mots de passe stockés sous forme hachée (bcrypt).</li>
        <li>Accès aux données restreint au personnel autorisé.</li>
        <li>Sauvegardes régulières et sécurisées.</li>
      </ul>
    </div>

    <div class="legal-section" id="contact">
      <h2><span class="s-num">8</span> Nous contacter</h2>
      <p>Pour toute question relative à vos données personnelles ou pour exercer vos droits :</p>
      <p>📧 <strong>privacy@benna.tn</strong><br>📍 Benna, Sousse, Tunisie<br>📞 +216 XX XXX XXX</p>
      <p>Vous avez également le droit d'introduire une réclamation auprès de l'Instance Nationale de Protection des Données Personnelles (INPDP) de Tunisie.</p>
    </div>

  </div>
</div>

<?php include "partials/footer.php"; ?>