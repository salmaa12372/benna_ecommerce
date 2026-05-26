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

$pageTitle = 'Politique des Cookies';
include "partials/header.php";
?>

<style>
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
  color: rgba(14, 35, 18, 0.95) !important;
  text-shadow: none !important;
}

body.dark .navbar .nav-links a,
body.dark .navbar .nav-links2 a{
  color:rgba(248, 248, 247, 0.95) !important;
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

<div class="legal-page">
  <div class="legal-hero">
    <div class="legal-hero-inner">
      <div class="legal-breadcrumb"><a href="<?= BASE ?>/index.php">Accueil</a> / Cookies</div>
      <div class="legal-tag"> Politique des Cookies</div>
      <h1>Cookies & <em>Confidentialité</em></h1>
      <p>Comment nous utilisons les cookies pour améliorer votre expérience sur Benna.</p>
      <div class="legal-date">Dernière mise à jour : 1er janvier 2025</div>
    </div>
  </div>

  <div class="legal-body">

    <div class="legal-toc">
      <h3>Sommaire</h3>
      <ol>
        <li><a href="#what">Qu'est-ce qu'un cookie ?</a></li>
        <li><a href="#types">Types de cookies utilisés</a></li>
        <li><a href="#list">Liste détaillée des cookies</a></li>
        <li><a href="#prefs">Gérer vos préférences</a></li>
        <li><a href="#contact">Nous contacter</a></li>
      </ol>
    </div>

    <div class="legal-section" id="what">
      <h2><span class="s-num">1</span> Qu'est-ce qu'un cookie ?</h2>
      <p>Un cookie est un petit fichier texte déposé sur votre appareil (ordinateur, smartphone ou tablette) lorsque vous visitez notre site. Il permet à Benna de mémoriser vos préférences, de sécuriser votre session et d'améliorer continuellement votre expérience d'achat.</p>
      <p>Les cookies ne contiennent aucune information permettant de vous identifier directement. Ils ne stockent pas de mot de passe, de numéro de carte bancaire ou de données sensibles.</p>
    </div>

    <div class="legal-section" id="types">
      <h2><span class="s-num">2</span> Types de cookies utilisés</h2>
      <p>Benna utilise trois catégories de cookies, chacune ayant un rôle précis :</p>
      <p><strong>Cookies essentiels</strong> — indispensables au fonctionnement du site. Ils gèrent votre session, votre panier et la sécurité de votre compte. Ils ne peuvent pas être désactivés.</p>
      <p><strong>Cookies analytiques</strong> — nous aident à comprendre comment vous naviguez sur Benna afin d'améliorer nos pages et nos produits. Toutes les données sont anonymisées.</p>
      <p><strong>Cookies marketing</strong> — utilisés pour vous proposer des offres personnalisées et des publicités pertinentes sur d'autres sites. Ils nécessitent votre consentement.</p>
    </div>

    <div class="legal-section" id="list">
      <h2><span class="s-num">3</span> Liste détaillée des cookies</h2>
      <div class="cookie-table-wrap">
        <table class="cookie-table">
          <thead>
            <tr>
              <th>Nom</th>
              <th>Type</th>
              <th>Durée</th>
              <th>Finalité</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><code>PHPSESSID</code></td>
              <td><span class="ct-badge ct-essential">Essentiel</span></td>
              <td>Session</td>
              <td>Gestion de la session utilisateur</td>
            </tr>
            <tr>
              <td><code>bena-theme</code></td>
              <td><span class="ct-badge ct-essential">Essentiel</span></td>
              <td>1 an</td>
              <td>Mémorisation du thème clair/sombre</td>
            </tr>
            <tr>
              <td><code>bena-lang</code></td>
              <td><span class="ct-badge ct-essential">Essentiel</span></td>
              <td>1 an</td>
              <td>Mémorisation de la langue choisie</td>
            </tr>
            <tr>
              <td><code>_ga</code></td>
              <td><span class="ct-badge ct-analytics">Analytique</span></td>
              <td>2 ans</td>
              <td>Google Analytics — statistiques de visite anonymes</td>
            </tr>
            <tr>
              <td><code>_gid</code></td>
              <td><span class="ct-badge ct-analytics">Analytique</span></td>
              <td>24h</td>
              <td>Google Analytics — distinction des sessions</td>
            </tr>
            <tr>
              <td><code>fbp</code></td>
              <td><span class="ct-badge ct-marketing">Marketing</span></td>
              <td>3 mois</td>
              <td>Facebook Pixel — publicités personnalisées</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="legal-section" id="prefs">
      <h2><span class="s-num">4</span> Gérer vos préférences</h2>
      <p>Vous pouvez modifier vos préférences à tout moment. Les cookies essentiels sont toujours actifs pour garantir le bon fonctionnement du site.</p>
      <div class="cookie-pref-card">
        <h3> Centre de préférences</h3>
        <p>Activez ou désactivez les catégories de cookies selon vos souhaits.</p>
        <div class="cpref-row">
          <div class="cpref-info">
            <h4>Cookies essentiels</h4>
            <p>Nécessaires au fonctionnement du site — ne peuvent pas être désactivés.</p>
          </div>
          <label class="sw sw-disabled">
            <input type="checkbox" checked disabled/>
            <span class="sw-track"></span>
          </label>
        </div>
        <div class="cpref-row">
          <div class="cpref-info">
            <h4>Cookies analytiques</h4>
            <p>Nous aident à améliorer le site grâce à des données anonymes.</p>
          </div>
          <label class="sw">
            <input type="checkbox" id="pref-analytics" checked/>
            <span class="sw-track"></span>
          </label>
        </div>
        <div class="cpref-row">
          <div class="cpref-info">
            <h4>Cookies marketing</h4>
            <p>Publicités personnalisées sur d'autres plateformes.</p>
          </div>
          <label class="sw">
            <input type="checkbox" id="pref-marketing"/>
            <span class="sw-track"></span>
          </label>
        </div>
        <button class="btn-save-pref" onclick="saveCookiePrefs()"> Enregistrer mes choix</button>
      </div>
    </div>

    <div class="legal-section" id="contact">
      <h2><span class="s-num">5</span> Nous contacter</h2>
      <p>Pour toute question relative à notre politique de cookies, vous pouvez nous écrire à <strong>privacy@benna.tn</strong> ou nous contacter via le formulaire de notre page <a href="<?= BASE ?>/index.php#contact" style="color:var(--green);">Contact</a>.</p>
      <p>Benna — Sousse, Tunisie · SIRET 123 456 789</p>
    </div>

  </div>
</div>

<script>
function saveCookiePrefs() {
  const analytics = document.getElementById('pref-analytics').checked;
  const marketing = document.getElementById('pref-marketing').checked;
  localStorage.setItem('bena-cookies-analytics', analytics ? '1' : '0');
  localStorage.setItem('bena-cookies-marketing', marketing ? '1' : '0');
  const btn = event.target;
  btn.textContent = ' Préférences enregistrées !';
  setTimeout(() => btn.textContent = ' Enregistrer mes choix', 2500);
}
(function() {
  const a = localStorage.getItem('bena-cookies-analytics');
  const m = localStorage.getItem('bena-cookies-marketing');
  if (a !== null) document.getElementById('pref-analytics').checked = a === '1';
  if (m !== null) document.getElementById('pref-marketing').checked = m === '1';
})();
</script>

<?php include "partials/footer.php"; ?>