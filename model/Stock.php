<?php
// model/Stock.php
class Stock {
    public $id, $produit_id, $quantite, $seuil_alerte, $en_production, $updated_at;

    public function __construct($produit_id, $quantite = 0, $seuil_alerte = 20) {
        $this->produit_id    = $produit_id;
        $this->quantite      = $quantite;
        $this->seuil_alerte  = $seuil_alerte;
        $this->en_production = 0;
    }
}
