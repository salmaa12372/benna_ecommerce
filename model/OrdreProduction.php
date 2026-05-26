<?php
// model/OrdreProduction.php
class OrdreProduction {
    public $id, $produit_id, $quantite, $statut, $demande_par, $created_at, $termine_at;

    // statut: demande | en_cours | termine
    public function __construct($produit_id, $quantite, $demande_par = null) {
        $this->produit_id  = $produit_id;
        $this->quantite    = $quantite;
        $this->demande_par = $demande_par;
        $this->statut      = 'demande';
    }
}
