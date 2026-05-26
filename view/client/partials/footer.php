<?php // view/client/partials/footer.php ?>
<footer class="footer">
  <div class="container">
    <div class="footer-grid">
      <div>
        <h4>Benna</h4>
        <p>Alimentation saine &amp; artisanale depuis Sousse, Tunisie.<br/>Fait avec amour, sans compromis.</p><br>
        <div class="social">
<a href="https://www.instagram.com/benna_tn__/" aria-label="Instagram" target="_blank"><i class="fab fa-instagram"></i></a>        
  <a href="https://www.facebook.com/benna.tn__" aria-label="Facebook" target="_blank"><i class="fab fa-facebook"></i></a>
          <a href="https://www.tiktok.com/@benna_tn__" aria-label="TikTok" target="_blank"><i class="fab fa-tiktok"></i></a>
        </div>
      </div>
      <div>
        <h4>Boutique</h4>
        <div class="footer-col-links">
          <a href="<?= BASE ?>/view/client/produits.php">Tous les produits</a><br>
          <a href="<?= BASE ?>/view/client/conseils.php">Conseils santé</a><br>
          <a href="<?= BASE ?>/view/client/vip.php">Club VIP </a>
        </div>
      </div>
      <div>
        <h4>Mon Compte</h4>
        <div class="footer-col-links">
          <a href="<?= BASE ?>/view/client/signup.php">Connexion</a><br>
          <a href="<?= BASE ?>/view/client/mes_commandes.php">Mes commandes</a><br>
          <a href="<?= BASE ?>/view/client/panier.php">Mon panier</a><br>
          <a href="<?= BASE ?>/view/client/profil.php">Mon profil</a>
        </div>
      </div>
      <div>
        <h4>Contact</h4>
        <div class="footer-col-links"style="display:flex;flex-direction:column;gap:7px;">
          <span style= "color:white;">📍 Sousse, Tunisie</span>
          <span style= "color:white;">📞 +216 71 234 567</span>
          <span style= "color:white;font-size:.86rem;">✉️ welcome@benna.tn</span>
          <span style= "color:white;">🕐 Lun–Sam 8h–20h</span>
        </div>
        <div class="footer-col-links" style="margin-top:15px;display:flex;flex-direction:column;gap:7px;">
          <a href="#" style="margin-right:10px;">FAQ</a>
          <a href="#" style="margin-right:10px;">Livraison</a>
          <a href="#">CGV</a>
        </div>
      </div>
    </div>
    <div class="footer-bottom" style="margin-top:40px;text-align:center;font-size:.8rem;color:#fff;">
      <span>© 2026 Benna – Tous droits réservés</span>
      <span style="margin:0 10px;opacity:.3;">·</span>
      <span>Fait avec amour </span>
    </div>
  </div>
</footer>
<script src="<?= BASE ?>/public/js/script.js"></script>
<?php if (!empty($extraJs)): ?>
<script src="<?= BASE ?>/public/js/<?= $extraJs ?>"></script>
<?php endif; ?>
</body>
</html>
