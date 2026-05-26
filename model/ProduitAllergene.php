<?php
// model/ProduitAllergene.php
class ProduitAllergene {
    public $produit_id, $allergene_id;

    public function __construct($produit_id, $allergene_id) {
        $this->produit_id   = $produit_id;
        $this->allergene_id = $allergene_id;
    }
}
