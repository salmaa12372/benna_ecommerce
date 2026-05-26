<?php
// model/AlerteNutritionnelle.php
class AlerteNutritionnelle {
    public $id, $nutritionniste_id, $client_id, $titre, $message, $gravite, $lu, $created_at;

    // gravite: info | attention | urgent
    public function __construct($nutritionniste_id, $titre, $message, $gravite = 'info', $client_id = null) {
        $this->nutritionniste_id = $nutritionniste_id;
        $this->client_id         = $client_id;
        $this->titre             = $titre;
        $this->message           = $message;
        $this->gravite           = $gravite;
        $this->lu                = 0;
    }
}
