<?php
// model/CommandeDetail.php
class CommandeDetail {
    public $id, $commande_id, $produit_id, $quantite, $prix_unitaire;

    public function __construct($commande_id, $produit_id, $quantite, $prix_unitaire) {
        $this->commande_id   = $commande_id;
        $this->produit_id    = $produit_id;
        $this->quantite      = $quantite;
        $this->prix_unitaire = $prix_unitaire;
    }
}
