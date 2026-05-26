<?php
// config/database.php

$db_server   = "127.0.0.1";
$db_username = "root";
$db_pwd      = "";
$db_name     = "db_benna";

try {
    $cnx = new PDO(
        "mysql:host=$db_server;dbname=$db_name;charset=utf8mb4",
        $db_username,
        $db_pwd,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die("Connexion DB échouée : " . $e->getMessage());
}

