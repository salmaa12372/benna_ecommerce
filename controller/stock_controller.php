<?php
// controller/stock_controller.php
session_start();
include_once __DIR__ . "/../config/database.php";
include_once __DIR__ . "/../config/app.php";
include_once __DIR__ . "/../controller/traitement.php";

$action = $_GET['action'] ?? '';
$role   = $_SESSION['role'] ?? '';

function usineOrAdmin() {
    if (!in_array($_SESSION['role'] ?? '', ['admin', 'usine'])) {
        header("Location: " . BASE . "/view/client/signup.php"); exit();
    }
}

if ($action === 'update_stock' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    usineOrAdmin();
    $ok = updateStock($cnx, (int)$_POST['produit_id'], (int)$_POST['quantite']);
    $_SESSION[$ok ? 'success' : 'error'] = $ok ? "Stock mis à jour." : "Erreur.";
    header("Location: " . BASE . "/view/usine/stock.php"); exit();
}

if ($action === 'creer_ordre' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    usineOrAdmin();
    $ok = creerOrdreProduction($cnx, (int)$_POST['produit_id'], (int)$_POST['quantite'], $_SESSION['user_id']);
    $_SESSION[$ok ? 'success' : 'error'] = $ok ? "Ordre de production créé." : "Erreur.";
    $back = ($role === 'usine')
        ? BASE . '/view/usine/production.php'
        : BASE . '/view/admin/production.php';
    header("Location: $back"); exit();
}

if ($action === 'update_ordre' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    usineOrAdmin();

    if (!isset($_POST['ordre_id'], $_POST['statut'])) {
        $_SESSION['error'] = "Requête invalide.";
        header("Location: " . BASE . "/view/usine/production.php");
        exit();
    }

    $ordre_id = (int) $_POST['ordre_id'];
    $statut   = $_POST['statut'];

    // sécurité : autoriser seulement ces statuts
    if (!in_array($statut, ['demande', 'en_cours', 'termine', 'annule'])) {
        $_SESSION['error'] = "Statut invalide.";
        header("Location: " . BASE . "/view/usine/production.php");
        exit();
    }

    $ok = updateOrdreProduction($cnx, $ordre_id, $statut);

    $_SESSION[$ok ? 'success' : 'error'] =
        $ok ? "Ordre mis à jour." : "Erreur lors de la mise à jour.";

    header("Location: " . BASE . "/view/usine/production.php");

    
    exit();
}
