<?php
// model/Livraison.php
class Livraison {
    public $id, $commande_id, $livreur_id, $statut;
    public $latitude, $longitude, $note_livreur, $probleme, $updated_at;

    // statut: assignee | acceptee | en_cours | livree | echec
    public function __construct($commande_id, $livreur_id = null) {
        $this->commande_id = $commande_id;
        $this->livreur_id  = $livreur_id;
        $this->statut      = 'assignee';
    }
}
