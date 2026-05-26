<?php
// model/Produit.php
class Produit {
    public $id, $nom, $description, $prix, $stock, $image, $categorie_id;
    public $regime, $calories, $proteines, $glucides, $lipides;
    public $note_moyenne, $nb_avis, $est_actif, $est_nouveau, $est_bestseller, $created_at;
    public $allergenes = [];
    public function __construct($nom, $description, $prix, $stock, $image, $categorie_id, $regime = '') {
        $this->nom = $nom; $this->description = $description; $this->prix = $prix;
        $this->stock = $stock; $this->image = $image; $this->categorie_id = $categorie_id;
        $this->regime = $regime; $this->est_actif = 1;
    }
}
