<?php
// model/Commande.php
class Commande {
    public $id, $user_id, $total, $statut, $adresse_livraison;
    public $note_client, $date_commande, $date_livraison_estimee;
    public $details = [];
    public function __construct($user_id, $total, $adresse_livraison) {
        $this->user_id = $user_id; $this->total = $total;
        $this->adresse_livraison = $adresse_livraison; $this->statut = 'en_attente';
    }
}
