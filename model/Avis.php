<?php
// model/Avis.php
class Avis {
    public $id, $user_id, $produit_id, $note, $commentaire, $valide, $created_at;

    // note: 1–5
    public function __construct($user_id, $produit_id, $note, $commentaire = null) {
        $this->user_id     = $user_id;
        $this->produit_id  = $produit_id;
        $this->note        = $note;
        $this->commentaire = $commentaire;
        $this->valide      = 0;
    }
}
