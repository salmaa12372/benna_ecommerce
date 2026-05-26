<?php
// model/Panier.php
class Panier {
    public $id, $user_id, $produit_id, $quantite;

    public function __construct($user_id, $produit_id, $quantite = 1) {
        $this->user_id    = $user_id;
        $this->produit_id = $produit_id;
        $this->quantite   = $quantite;
    }
}
