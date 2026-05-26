<?php
// model/Allergene.php
class Allergene {
    public $id, $nom, $icone;

    public function __construct($nom, $icone = '⚠️') {
        $this->nom   = $nom;
        $this->icone = $icone;
    }
}
