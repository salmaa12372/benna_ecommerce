<?php
// model/VipObjectif.php
class VipObjectif {
    public $id, $client_id, $nutri_id, $titre;
    public $valeur_cible, $valeur_actuelle, $unite, $deadline, $atteint, $created_at;

    public function __construct($client_id, $nutri_id, $titre, $valeur_cible = null, $unite = 'kg', $deadline = null) {
        $this->client_id      = $client_id;
        $this->nutri_id       = $nutri_id;
        $this->titre          = $titre;
        $this->valeur_cible   = $valeur_cible;
        $this->unite          = $unite;
        $this->deadline       = $deadline;
        $this->atteint        = 0;
    }
}
