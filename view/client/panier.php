<?php
// view/client/panier.php
session_start();
include_once __DIR__ . "/../../config/database.php";
include_once __DIR__ . "/../../config/app.php";
include_once __DIR__ . "/../../controller/traitement.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE . "/view/client/signup.php"); exit();
}

// Traitement checkout AVANT tout output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adresse_livraison'])) {
    $userId  = $_SESSION['user_id'];
    $adresse = trim($_POST['adresse_livraison']);
    $note    = trim($_POST['note_client'] ?? '');

    if (empty($adresse)) {
        $_SESSION['cart_error'] = "L'adresse de livraison est obligatoire.";
    } else {
        $commande_id = passerCommande($cnx, $userId, $adresse, $note);
        if ($commande_id) {
            header("Location: " . BASE . "/view/client/paiement.php?commande_id=" . $commande_id);
            exit();
        } else {
            $_SESSION['cart_error'] = "Erreur lors de la création de la commande. Votre panier est peut-être vide.";
        }
    }
}

$pageTitle    = "Mon Panier";
$items        = getPanier($cnx, $_SESSION['user_id']);
$total        = getTotalPanier($cnx, $_SESSION['user_id']);
$userData     = getUserById($cnx, $_SESSION['user_id']);
$adresseDefaut = $userData['adresse'] ?? '';
$cartError    = $_SESSION['cart_error'] ?? '';
unset($_SESSION['cart_error']);

include "partials/header.php";
?>
<style> 
<style>
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
  color: rgba(20, 42, 27, 0.95) !important;
  text-shadow: none !important;
}

body.dark .navbar .nav-links a,
.navbar .nav-links2 a {
  color: rgba(234, 237, 234, 0.95) !important;
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
  color: #e0e6e2 !important;
  text-shadow: none !important;
}
.navbar.scrolled .nav-logo {
  color: #ffffff !important;
  text-shadow: none !important;
}

</style>

<main class="panier-wrap container">
  <div class="section-header fade-in">
    <p class="section-label">Votre Sélection</p>
    <h1 class="section-title">Mon <em>Panier</em></h1>
    <div class="section-divider"></div>
  </div>

  <?php if ($cartError): ?>
    <div class="alert alert-error" style="background:#fee2e2;color:#dc2626;padding:1rem;border-radius:12px;margin-bottom:1rem;">
      <?= htmlspecialchars($cartError) ?>
    </div>
  <?php endif; ?>

  <?php if (empty($items)): ?>
    <div class="empty-box fade-in">
      <div class="ei">🛒</div>
      <h2>Votre panier est vide</h2>
      <p style="color:var(--muted);margin:.5rem 0 1.5rem;">Découvrez nos produits sains et savoureux.</p>
      <a href="<?= BASE ?>/view/client/produits.php" class="nav-join">
        <strong>Aller à la boutique →</strong>
        <span class="star-1"></span><span class="star-2"></span><span class="star-3"></span>
      </a>
    </div>
  <?php else: ?>
  <div class="cart-page-content fade-in" style="--delay:.2s">

    <div class="cart-items-list">
      <?php foreach ($items as $item): ?>
      <div class="cart-item">
        <img src="<?= BASE ?>/<?= htmlspecialchars($item['image'] ?? '') ?>"
             alt="<?= htmlspecialchars($item['nom']) ?>"
             onerror="this.src='https://placehold.co/80x80/e8f0e3/2c5e2e?text=🌿'"
             style="width:80px;height:80px;object-fit:cover;border-radius:12px;">
        <div class="cart-item-details">
          <h3 class="cart-item-title"><?= htmlspecialchars($item['nom']) ?></h3>
          <p class="cart-item-desc"><?= number_format($item['prix'], 3) ?> TND / unité</p>
          <div class="cart-item-actions">
            <form action="<?= BASE ?>/controller/panier_controller.php?action=update" method="POST">
              <input type="hidden" name="panier_id" value="<?= $item['panier_id'] ?>"/>
              <button type="submit" name="quantite" value="<?= $item['quantite'] - 1 ?>" class="qty-btn">−</button>
            </form>
            <span class="qty-val"><?= $item['quantite'] ?></span>
            <form action="<?= BASE ?>/controller/panier_controller.php?action=update" method="POST">
              <input type="hidden" name="panier_id" value="<?= $item['panier_id'] ?>"/>
              <button type="submit" name="quantite" value="<?= $item['quantite'] + 1 ?>" class="qty-btn">+</button>
            </form>
          </div>
        </div>
        <div class="cart-item-price"><?= number_format($item['sous_total'], 3) ?> TND</div>
        <a href="<?= BASE ?>/controller/panier_controller.php?action=remove&id=<?= $item['panier_id'] ?>"
           class="cart-remove-btn" aria-label="Supprimer">×</a>
      </div>
      <?php endforeach; ?>

      <div style="text-align:right;margin-top:.3rem;">
        <a href="<?= BASE ?>/controller/panier_controller.php?action=clear"
           style="color:var(--terracotta);font-size:.83rem;text-decoration:none;"
           onclick="return confirm('Vider tout le panier ?')">🗑 Vider le panier</a>
      </div>
    </div>

    <div class="cart-summary">
      <h3 class="summary-title">Récapitulatif</h3>
      <div class="summary-line"><span>Sous-total</span><span><?= number_format($total, 3) ?> TND</span></div>
      <div class="summary-line">
        <span>Livraison</span>
        <span><?= $total >= 150 ? '<span style="color:var(--green);">Gratuite 🎉</span>' : '5.000 TND' ?></span>
      </div>
      <div class="summary-line summary-total">
        <span>Total</span>
        <span><?= number_format($total >= 150 ? $total : $total + 5, 3) ?> TND</span>
      </div>

      <form method="POST" class="checkout-form" style="margin-top:1.2rem;">
        <label class="checkout-label">Adresse de livraison *</label>
        <input type="text" name="adresse_livraison" 
               placeholder="Rue, Ville, Code postal" style="background:white;
               value="<?= htmlspecialchars($adresseDefaut) ?>" required/>
        <label class="checkout-label"> Note (optionnel)</label>
        <textarea name="note_client" rows="2" style="background:white;
                  placeholder="Instructions spéciales pour la livraison..."></textarea>
        <button type="submit" class="checkout-btn">Confirmer la commande →</button>
      </form>
      <a href="<?= BASE ?>/view/client/produits.php"
         style="display:block;text-align:center;margin-top:1rem;color:var(--muted);font-size:.85rem;text-decoration:none;">
        ← Continuer mes achats
      </a>
    </div>
  </div>
  <?php endif; ?>
</main>

<?php include "partials/footer.php"; ?>