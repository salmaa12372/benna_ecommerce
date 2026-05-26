<?php
// model/VipPaiement.php
class VipPaiement {
    public $id, $user_id, $abonnement_id, $montant, $methode, $statut, $reference, $created_at;

    // methode: carte | virement | cash
    // statut: en_attente | paye | echoue
    public function __construct($user_id, $abonnement_id, $montant, $methode = 'carte') {
        $this->user_id        = $user_id;
        $this->abonnement_id  = $abonnement_id;
        $this->montant        = $montant;
        $this->methode        = $methode;
        $this->statut         = 'en_attente';
    }
}
