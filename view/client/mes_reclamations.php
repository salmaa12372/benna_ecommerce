<?php
// view/client/mes_reclamations.php
session_start();
if (!isset($_SESSION['user_id'])) {
    include_once __DIR__ . "/../../config/app.php";
    header("Location: " . BASE . "/view/client/signup.php"); exit();
}
$pageTitle = "Mes Réclamations";
include "partials/header.php";
$reclamations = getReclamationsByUser($cnx, $_SESSION['user_id']);
$commandes    = getCommandesByUser($cnx, $_SESSION['user_id']);
$cmdId        = $_GET['commande_id'] ?? '';
$badgeClass   = ['ouverte'=>'badge-ov','en_cours'=>'badge-ec','resolue'=>'badge-re','rejetee'=>'badge-rj'];
?>
<style>
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
.recl-page{padding-top:110px;min-height:80vh;}
.recl-form{background:var(--cream);border-radius:20px;padding:2rem;margin-bottom:2rem;box-shadow:var(--shadow,0 4px 20px rgba(0,0,0,.07));}
.recl-form h3{font-family:var(--font-display);font-size:1.3rem;margin:0 0 1.2rem;color:var(--green-dark);}
.frow{margin-bottom:1rem;}
.frow label{display:block;font-family:var(--font-display);font-size:.9rem;color:var(--green-dark);margin-bottom:.3rem;}
.frow input,.frow select,.frow textarea{width:100%;padding:.7rem 1rem;border:2px solid var(--border);border-radius:10px;background:white;font-family:var(--font-body);font-size:.95rem;color:var(--fg);outline:none;transition:.2s;box-sizing:border-box;}
.frow input:focus,.frow select:focus,.frow textarea:focus{border-color:var(--green);}
.submit-btn{width:100%;padding:.85rem;border:none;border-radius:12px;background:linear-gradient(135deg,var(--green),var(--green-dark));color:white;font-family:var(--font-display);font-size:1rem;font-weight:600;cursor:pointer;transition:.2s;}
.submit-btn:hover{transform:translateY(-2px);box-shadow:0 8px 20px rgba(45,106,79,.3);}
.recl-card{background:var(--cream);border-radius:16px;padding:1.5rem;margin-bottom:1rem;box-shadow:var(--shadow,0 4px 20px rgba(0,0,0,.07));}
.recl-head{display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:.5rem;margin-bottom:.6rem;}
.recl-sujet{font-family:var(--font-display);font-size:1.05rem;}
.recl-msg{color:var(--muted);font-size:.9rem;margin:.3rem 0;}
.recl-date{font-size:.78rem;color:var(--gold);}
.recl-reponse{margin-top:.8rem;padding:1rem 1.2rem;background:var(--green);color:white;border-radius:12px;font-size:.9rem;line-height:1.6;}
.recl-reponse strong{display:block;margin-bottom:.2rem;font-size:.82rem;opacity:.8;}
/* Badges statut */
.sbadge{display:inline-block;padding:.25rem .8rem;border-radius:20px;font-size:.78rem;font-weight:700;}
.badge-ov{background:#fef3c7;color:#92400e;}
.badge-ec{background:#dbeafe;color:#1e40af;}
.badge-re{background:#d1fae5;color:#065f46;}
.badge-rj{background:#fee2e2;color:#991b1b;}
/* Dark mode overrides for reclamation page */
body.dark .recl-form,
body.dark .recl-card {
  background: #1e2a2a; /* dark cream alternative */
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

body.dark .recl-form h3,
body.dark .frow label,
body.dark .recl-sujet {
  color: #e2e8e0; /* light mint */
}

body.dark .frow input,
body.dark .frow select,
body.dark .frow textarea {
  background: #2d3a3a;
  border-color: #4a5a5a;
  color: #f0f3ef;
}

body.dark .frow input:focus,
body.dark .frow select:focus,
body.dark .frow textarea:focus {
  border-color: #7ab87e;
}

body.dark .recl-msg {
  color: #b0c4b4;
}

body.dark .recl-date {
  color: #d4af6a; /* keep gold-like but softer */
}

body.dark .recl-reponse {
  background: #2c5a44;
  color: #f9f6e7;
}

/* Status badges in dark mode */
body.dark .badge-ov {
  background: #7a5c2e;
  color: #ffefb9;
}

body.dark .badge-ec {
  background: #2c4f6e;
  color: #c3e0ff;
}

body.dark .badge-re {
  background: #1f5e46;
  color: #d0f0e0;
}

body.dark .badge-rj {
  background: #7a2e2e;
  color: #ffcdcd;
}

/* submit button dark mode (already uses vars, but ensure contrast) */
body.dark .submit-btn {
  background: linear-gradient(135deg, #3c8c5e, #1f4a38);
}

body.dark .submit-btn:hover {
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.5);
}
</style>

<main class="recl-page container">
  <div class="section-header fade-in">
    <p class="section-label">Support</p>
    <h1 class="section-title">Mes <em>Réclamations</em></h1>
    <div class="section-divider"></div>
  </div>

  <!-- FORMULAIRE NOUVELLE RÉCLAMATION -->
  <div class="recl-form fade-in">
    <h3>Nouvelle réclamation</h3>
    <form action="<?= BASE ?>/controller/reclamation_controller.php?action=add" method="POST">
      <div class="frow">
        <label>Commande concernée (optionnel)</label>
        <select name="commande_id">
          <option value="">– Aucune commande spécifique –</option>
          <?php foreach ($commandes as $c): ?>
            <option value="<?= $c['id'] ?>" <?= $cmdId == $c['id'] ? 'selected' : '' ?>>
              Commande #<?= $c['id'] ?> – <?= date('d/m/Y', strtotime($c['date_commande'])) ?> – <?= number_format($c['total'],3) ?> TND
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="frow">
        <label>Sujet *</label>
        <input type="text" name="sujet" placeholder="Ex: Produit abîmé à la livraison" required/>
      </div>
      <div class="frow">
        <label>Message *</label>
        <textarea name="message" rows="4" placeholder="Décrivez votre problème en détail..." required></textarea>
      </div>
      <button type="submit" class="submit-btn">Envoyer la réclamation →</button>
    </form>
  </div>

  <!-- LISTE MES RÉCLAMATIONS -->
  <?php if (!empty($reclamations)): ?>
    <?php foreach ($reclamations as $i => $r): ?>
    <div class="recl-card fade-in" style="--delay:<?= $i * 0.07 ?>s">
      <div class="recl-head">
        <div>
          <div class="recl-sujet"><?= htmlspecialchars($r['sujet']) ?></div>
          <?php if ($r['commande_id']): ?><div style="font-size:.8rem;color:var(--muted);">Commande #<?= $r['commande_id'] ?></div><?php endif; ?>
        </div>
        <span class="sbadge <?= $badgeClass[$r['statut']] ?? 'badge-ov' ?>">
          <?= match($r['statut']) {
            'ouverte'  => '🔴 Ouverte',
            'en_cours' => '🔵 En cours',
            'resolue'  => '🟢 Résolue',
            'rejetee'  => '⛔ Rejetée',
            default    => $r['statut']
          } ?>
        </span>
      </div>
      <p class="recl-msg"><?= htmlspecialchars($r['message']) ?></p>
      <div class="recl-date">📅 <?= date('d/m/Y à H:i', strtotime($r['created_at'])) ?></div>
      <?php if (!empty($r['reponse'])): ?>
      <div class="recl-reponse">
        <strong>✅ Réponse de l'équipe Benna</strong>
        <?= htmlspecialchars($r['reponse']) ?>
      </div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  <?php else: ?>
    <div style="text-align:center;padding:3rem;color:var(--muted);">
      <div style="font-size:3rem;margin-bottom:.8rem;">📭</div>
      Aucune réclamation pour l'instant. Tout va bien !
    </div>
  <?php endif; ?>
</main>

<?php include "partials/footer.php"; ?>