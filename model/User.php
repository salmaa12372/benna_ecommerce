<?php
// model/User.php
class User {
    public $id, $nom, $email, $password, $role, $telephone, $adresse, $avatar, $actif, $created_at;
    public function __construct($nom, $email, $password, $role = 'client', $telephone = '', $adresse = '') {
        $this->nom = $nom; $this->email = $email; $this->password = $password;
        $this->role = $role; $this->telephone = $telephone; $this->adresse = $adresse;
    }
}

