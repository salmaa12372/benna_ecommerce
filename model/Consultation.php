<?php
// model/Consultation.php
class Consultation {
    public $id, $nutritionniste_id, $client_id, $titre, $date_heure;
    public $duree_min, $type, $statut, $lien_visio;
    public $notes_avant, $notes_apres, $objectifs, $created_at;

    // type: chat | visio
    // statut: planifiee | en_cours | terminee | annulee
    public function __construct($nutritionniste_id, $client_id, $date_heure, $type = 'visio', $titre = 'Consultation nutritionnelle') {
        $this->nutritionniste_id = $nutritionniste_id;
        $this->client_id         = $client_id;
        $this->titre             = $titre;
        $this->date_heure        = $date_heure;
        $this->type              = $type;
        $this->duree_min         = 30;
        $this->statut            = 'planifiee';
    }
}
