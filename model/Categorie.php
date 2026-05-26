<?php
// model/Categorie.php
class Categorie {
    public $id, $nom, $description, $icone;

    public function __construct($nom, $description = null, $icone = '?') {
        $this->nom         = $nom;
        $this->description = $description;
        $this->icone       = $icone;
    }
}
