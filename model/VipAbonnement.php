<?php
// model/VipAbonnement.php
class VipAbonnement {
    public $id, $user_id, $niveau, $prix_mensuel, $date_debut, $date_fin;
    public $actif, $renouvellement, $created_at;

    // niveau: basic | premium | elite
    public function __construct($user_id, $niveau, $prix_mensuel, $date_debut, $date_fin) {
        $this->user_id       = $user_id;
        $this->niveau        = $niveau;
        $this->prix_mensuel  = $prix_mensuel;
        $this->date_debut    = $date_debut;
        $this->date_fin      = $date_fin;
        $this->actif         = 1;
        $this->renouvellement = 1;
    }
}
