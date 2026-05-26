<?php
// model/VipMessage.php
class VipMessage {
    public $id, $expediteur_id, $destinataire_id, $contenu, $lu, $consultation_id, $created_at;

    public function __construct($expediteur_id, $destinataire_id, $contenu, $consultation_id = null) {
        $this->expediteur_id    = $expediteur_id;
        $this->destinataire_id  = $destinataire_id;
        $this->contenu          = $contenu;
        $this->consultation_id  = $consultation_id;
        $this->lu               = 0;
    }
}
