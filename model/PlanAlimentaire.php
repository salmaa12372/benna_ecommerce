<?php
// model/PlanAlimentaire.php
class PlanAlimentaire {
    public $id, $nutritionniste_id, $client_id, $titre, $objectif, $created_at;

    public function __construct($nutritionniste_id, $client_id, $titre, $objectif = null) {
        $this->nutritionniste_id = $nutritionniste_id;
        $this->client_id         = $client_id;
        $this->titre             = $titre;
        $this->objectif          = $objectif;
    }
}
