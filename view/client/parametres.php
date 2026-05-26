<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../../config/app.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../config/auth.php";
require_login();

$user        = current_user();
$userId      = (int) $_SESSION['user_id'];
$userInitial = mb_strtoupper(mb_substr($user['nom'], 0, 1));

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    /* ── CHANGE PASSWORD ── */
    if ($action === 'password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password']     ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (!password_verify($current, $user['password'])) {
            $error = 'Mot de passe actuel incorrect.';
        } elseif (strlen($new) < 8) {
            $error = 'Le nouveau mot de passe doit contenir au moins 8 caractères.';
        } elseif ($new !== $confirm) {
            $error = 'Les mots de passe ne correspondent pas.';
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $cnx->prepare("UPDATE users SET password=? WHERE id=?");
            $stmt->execute([$hash, $userId]);

            send_benna_email('password', $user);   // ← beautiful email

            $success = 'Mot de passe mis à jour. Un email de confirmation a été envoyé.';
        }
    }

    /* ── NOTIFICATION PREFERENCES ── */
    if ($action === 'notifications') {
        $notif_email = isset($_POST['notif_email']) ? 'Activées' : 'Désactivées';
        $notif_sms   = isset($_POST['notif_sms'])   ? 'Activées' : 'Désactivées';
        $notif_promo = isset($_POST['notif_promo']) ? 'Activées' : 'Désactivées';

        send_benna_email('notifications', $user, [   // ← beautiful email
            'notif_email' => $notif_email,
            'notif_sms'   => $notif_sms,
            'notif_promo' => $notif_promo,
        ]);

        $success = 'Préférences enregistrées. Un email de confirmation a été envoyé à ' . htmlspecialchars($user['email']) . '.';
    }

    /* ── CONTACT INFO ── */
    if ($action === 'contact') {
        $tel     = trim($_POST['telephone'] ?? '');
        $adresse = trim($_POST['adresse']   ?? '');
        $stmt    = $cnx->prepare("UPDATE users SET telephone=?, adresse=? WHERE id=?");
        $stmt->execute([$tel ?: null, $adresse ?: null, $userId]);
        $user['telephone'] = $tel;
        $user['adresse']   = $adresse;

        send_benna_email('contact', $user, [   // ← beautiful email
            'telephone' => $tel,
            'adresse'   => $adresse,
        ]);

        $success = 'Informations de contact mises à jour. Un email de confirmation a été envoyé.';
    }
}

$pageTitle = 'Paramètres';
include "partials/header.php";
?>

<style>
.settings-page { padding-top: 110px; min-height: 100vh; }

.settings-hero {
  background: linear-gradient(135deg, hsl(142,38%,12%) 0%, hsl(30,20%,10%) 100%);
  padding: 48px 0 36px;
  border-bottom: 1px solid rgba(212,170,60,0.15);
}
.settings-hero-inner { max-width: 900px; margin: 0 auto; padding: 0 24px; }
.settings-breadcrumb {
  font-size: .72rem; letter-spacing: .15em; text-transform: uppercase;
  color: rgba(255,255,255,0.4); margin-bottom: 16px;
}
.settings-breadcrumb a { color: var(--gold); text-decoration: none; }
.settings-hero h1 {
  font-family: var(--font-display); font-size: clamp(2rem,4vw,3rem);
  font-weight: 400; color: white; line-height: 1.1;
}
.settings-hero h1 em { font-style: italic; color: var(--gold); }
.settings-hero p { color: rgba(200,220,190,.65); font-size: .9rem; margin-top: 8px; }

.settings-body { max-width: 900px; margin: 0 auto; padding: 40px 24px 80px; }

.settings-alert {
  padding: 14px 18px; border-radius: 10px; font-size: .88rem;
  margin-bottom: 24px; display: flex; align-items: center; gap: 10px;
}
.settings-alert.success { background: rgba(34,197,94,.12); border: 1px solid rgba(34,197,94,.3); color: #22c55e; }
.settings-alert.error   { background: rgba(239,68,68,.1);  border: 1px solid rgba(239,68,68,.3);  color: #f87171; }

.settings-grid { display: grid; gap: 24px; }

.settings-card {
  background: var(--card); border: 1px solid var(--border);
  border-radius: 18px; overflow: hidden; box-shadow: var(--shadow);
}
.sc-head {
  padding: 22px 28px; border-bottom: 1px solid var(--border);
  display: flex; align-items: center; gap: 14px;
  background: color-mix(in oklab, var(--cream-dark) 60%, transparent);
}
.sc-head-icon { font-size: 1.4rem; }
.sc-head h2 { font-family: var(--font-display); font-size: 1.2rem; font-weight: 600; }
.sc-head p  { font-size: .78rem; color: var(--muted); margin-top: 2px; }
.sc-body { padding: 28px; }

.form-row { display: grid; gap: 16px; margin-bottom: 20px; }
.form-row.cols-2 { grid-template-columns: 1fr 1fr; }

.field label {
  display: block; font-size: .72rem; letter-spacing: .1em;
  text-transform: uppercase; color: var(--muted); margin-bottom: 7px;
}
.field input {
  width: 100%; padding: 12px 14px;
  background: color-mix(in oklab, var(--bg) 60%, transparent);
  border: 1px solid var(--border); border-radius: 10px;
  color: var(--fg); font-family: var(--font-body); font-size: .9rem;
  transition: border-color .2s, box-shadow .2s; outline: none;
}
.field input:focus {
  border-color: var(--green);
  box-shadow: 0 0 0 3px color-mix(in oklab, var(--green) 15%, transparent);
}
.field input::placeholder { color: var(--muted); }
.form-hint { font-size: .72rem; color: var(--muted); margin-top: 5px; }

.btn-save {
  display: inline-flex; align-items: center; gap: 8px;
  padding: 11px 26px; border-radius: 10px; border: none; cursor: pointer;
  background: linear-gradient(135deg, var(--green), var(--green-dark));
  color: white; font-family: var(--font-display); font-size: .9rem;
  font-weight: 700; letter-spacing: .05em;
  transition: transform .2s, box-shadow .2s;
}
.btn-save:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,.2); }

.notif-row {
  display: flex; align-items: center; justify-content: space-between;
  padding: 16px 0; border-bottom: 1px solid var(--border);
}
.notif-row:last-of-type { border-bottom: none; }
.notif-info h4 { font-size: .95rem; font-weight: 600; margin-bottom: 2px; }
.notif-info p  { font-size: .78rem; color: var(--muted); }

.sw { position: relative; width: 48px; height: 26px; flex-shrink: 0; }
.sw input { opacity: 0; width: 0; height: 0; }
.sw-track {
  position: absolute; inset: 0; border-radius: 999px;
  background: var(--border); cursor: pointer; transition: background .25s;
}
.sw input:checked + .sw-track { background: var(--green); }
.sw-track::after {
  content: ''; position: absolute; top: 3px; left: 3px;
  width: 20px; height: 20px; border-radius: 50%; background: white;
  transition: transform .25s; box-shadow: 0 2px 6px rgba(0,0,0,.2);
}
.sw input:checked + .sw-track::after { transform: translateX(22px); }

.notif-note {
  margin-top: 16px; padding: 12px 16px; border-radius: 10px;
  background: color-mix(in oklab, var(--green) 8%, transparent);
  border: 1px solid color-mix(in oklab, var(--green) 20%, transparent);
  font-size: .78rem; color: var(--muted); line-height: 1.6;
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
.email-readonly {
  display: flex; align-items: center; gap: 10px;
  padding: 12px 16px; border-radius: 10px;
  background: color-mix(in oklab, var(--cream-dark) 60%, transparent);
  border: 1px solid var(--border); font-size: .88rem;
  margin-bottom: 20px; color: var(--muted);
}
.email-readonly strong { color: var(--fg); }

.danger-zone { border-color: rgba(239,68,68,.3) !important; }
.danger-zone .sc-head { background: rgba(239,68,68,.05); border-bottom-color: rgba(239,68,68,.2); }
.btn-danger {
  display: inline-flex; align-items: center; gap: 8px;
  padding: 10px 22px; border-radius: 10px; cursor: pointer;
  background: transparent; border: 1px solid rgba(239,68,68,.5);
  color: #f87171; font-family: var(--font-body); font-size: .88rem;
  transition: background .2s, color .2s;
}
.btn-danger:hover { background: rgba(239,68,68,.1); color: #fca5a5; }

@media (max-width: 600px) {
  .form-row.cols-2 { grid-template-columns: 1fr; }
  .sc-body { padding: 20px; }
}
</style>

<div class="settings-page">

  <div class="settings-hero">
    <div class="settings-hero-inner">
      <div class="settings-breadcrumb">
        <a href="<?= BASE ?>/view/client/home.php">Mon Espace</a> / Paramètres
      </div>
      <h1>Mes <em>Paramètres</em></h1>
      <p>Gérez votre compte, sécurité et préférences de notifications.</p>
    </div>
  </div>

  <div class="settings-body">

    <?php if ($success): ?>
      <div class="settings-alert success">✅ <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="settings-alert error">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="settings-grid">

      <!-- CONTACT INFO -->
      <div class="settings-card">
        <div class="sc-head">
          <span class="sc-head-icon">👤</span>
          <div>
            <h2>Informations de contact</h2>
            <p>Téléphone et adresse de livraison par défaut.</p>
          </div>
        </div>
        <div class="sc-body">
          <div class="email-readonly">
            📧 &nbsp;<strong><?= htmlspecialchars($user['email']) ?></strong>
            &nbsp;— l'adresse email ne peut pas être modifiée ici.
          </div>
          <form method="POST">
            <input type="hidden" name="action" value="contact"/>
            <div class="form-row cols-2">
              <div class="field">
                <label>Téléphone</label>
                <input type="tel" name="telephone"
                       value="<?= htmlspecialchars($user['telephone'] ?? '') ?>"
                       placeholder="+216 XX XXX XXX"/>
              </div>
              <div class="field">
                <label>Adresse de livraison</label>
                <input type="text" name="adresse"
                       value="<?= htmlspecialchars($user['adresse'] ?? '') ?>"
                       placeholder="Rue, Ville, Code postal"/>
              </div>
            </div>
            <button type="submit" class="btn-save"> Enregistrer</button>
          </form>
        </div>
      </div>

      <!-- CHANGE PASSWORD -->
      <div class="settings-card">
        <div class="sc-head">
          <div>
            <h2>Changer le mot de passe</h2>
            <p>Utilisez un mot de passe fort d'au moins 8 caractères.</p>
          </div>
        </div>
        <div class="sc-body">
          <form method="POST">
            <input type="hidden" name="action" value="password"/>
            <div class="form-row">
              <div class="field">
                <label>Mot de passe actuel</label>
                <input type="password" name="current_password" placeholder="••••••••" required/>
              </div>
            </div>
            <div class="form-row cols-2">
              <div class="field">
                <label>Nouveau mot de passe</label>
                <input type="password" name="new_password" placeholder="••••••••" required/>
                <p class="form-hint">Minimum 8 caractères</p>
              </div>
              <div class="field">
                <label>Confirmer</label>
                <input type="password" name="confirm_password" placeholder="••••••••" required/>
              </div>
            </div>
            <button type="submit" class="btn-save"> Mettre à jour</button>
          </form>
        </div>
      </div>

      <!-- NOTIFICATIONS -->
      <div class="settings-card">
        <div class="sc-head">
          <div>
            <h2>Préférences de notifications</h2>
            <p>Un email récapitulatif sera envoyé à <?= htmlspecialchars($user['email']) ?>.</p>
          </div>
        </div>
        <div class="sc-body">
          <form method="POST">
            <input type="hidden" name="action" value="notifications"/>

            <div class="notif-row">
              <div class="notif-info">
                <h4>Notifications par email</h4>
                <p>Confirmations de commande et mises à jour de livraison.</p>
              </div>
              <label class="sw">
                <input type="checkbox" name="notif_email" checked/>
                <span class="sw-track"></span>
              </label>
            </div>

            <div class="notif-row">
              <div class="notif-info">
                <h4>Notifications SMS</h4>
                <p>Alertes de livraison en temps réel.</p>
              </div>
              <label class="sw">
                <input type="checkbox" name="notif_sms"/>
                <span class="sw-track"></span>
              </label>
            </div>

            <div class="notif-row">
              <div class="notif-info">
                <h4>Offres et promotions</h4>
                <p>Nouveautés, offres exclusives, conseils nutrition.</p>
              </div>
              <label class="sw">
                <input type="checkbox" name="notif_promo" checked/>
                <span class="sw-track"></span>
              </label>
            </div>

            <div class="notif-note">
               Vos choix seront confirmés par email à <strong><?= htmlspecialchars($user['email']) ?></strong>.
              Ces préférences nous aident à personnaliser vos communications.
            </div>

            <div style="margin-top:18px;">
              <button type="submit" class="btn-save"> Enregistrer &amp; Confirmer par email</button>
            </div>
          </form>
        </div>
      </div>

      <!-- DANGER ZONE -->
      <div class="settings-card danger-zone">
        <div class="sc-head">
          <div>
            <h2>Zone dangereuse</h2>
            <p>Ces actions sont irréversibles. Procédez avec précaution.</p>
          </div>
        </div>
        <div class="sc-body">
          <p style="font-size:.88rem;color:var(--muted);margin-bottom:16px;">
            La suppression de votre compte effacera définitivement toutes vos données,
            commandes et abonnements VIP. Cette action ne peut pas être annulée.
          </p>
          <button class="btn-danger"
            onclick="if(confirm('Êtes-vous sûr ? Cette action est irréversible.')) window.location='<?= BASE ?>/controller/auth_controller.php?action=delete'">
             Supprimer mon compte
          </button>
        </div>
      </div>

    </div>
  </div>
</div>

<?php include "partials/footer.php"; ?>