<?php
// model/Reclamation.php
class Reclamation {
    public $id, $user_id, $commande_id, $sujet, $message;
    public $statut, $reponse, $repondu_par, $transmis_usine, $created_at;

    // statut: ouverte | en_cours | transmise_usine | resolue | rejetee
    public function __construct($user_id, $sujet, $message, $commande_id = null) {
        $this->user_id        = $user_id;
        $this->commande_id    = $commande_id;
        $this->sujet          = $sujet;
        $this->message        = $message;
        $this->statut         = 'ouverte';
        $this->transmis_usine = 0;
    }
}
