<?php
// model/Conseil.php
class Conseil {
    public $id, $nutritionniste_id, $produit_id, $titre, $contenu, $type, $public, $created_at;

    // type: conseil | recette | recommandation | plan_alimentaire
    public function __construct($nutritionniste_id, $titre, $contenu, $type = 'conseil', $produit_id = null) {
        $this->nutritionniste_id = $nutritionniste_id;
        $this->produit_id        = $produit_id;
        $this->titre             = $titre;
        $this->contenu           = $contenu;
        $this->type              = $type;
        $this->public            = 1;
    }
}
