<?php
// controller/produit_controller.php
session_start();
include_once __DIR__ . "/../config/database.php";
include_once __DIR__ . "/../config/app.php";
include_once __DIR__ . "/../controller/traitement.php";

function adminOnly() {
    if (!in_array($_SESSION['role'] ?? '', ['admin'])) {
        header("Location: " . BASE . "/view/client/home.php"); exit();
    }
}

$action = $_GET['action'] ?? '';

if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    adminOnly();
    $img = (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === 0)
        ? uploadImage($_FILES['image']) : '';
    $ok = addProduit($cnx, $_POST, $img ?: '');
    $_SESSION[$ok ? 'success' : 'error'] = $ok ? "Produit ajouté." : "Erreur lors de l'ajout.";
    header("Location: " . BASE . "/view/admin/produits.php"); exit();
}

if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    adminOnly();
    $img = (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === 0)
        ? uploadImage($_FILES['image']) : null;
    $ok = updateProduit($cnx, $_POST, $img);
    $_SESSION[$ok ? 'success' : 'error'] = $ok ? "Produit mis à jour." : "Erreur mise à jour.";
    header("Location: " . BASE . "/view/admin/produits.php"); exit();
}

if ($action === 'delete' && isset($_GET['id'])) {
    adminOnly();
    $ok = deleteProduit($cnx, (int)$_GET['id']);
    $_SESSION[$ok ? 'success' : 'error'] = $ok ? "Produit supprimé." : "Erreur suppression.";
    header("Location: " . BASE . "/view/admin/produits.php"); exit();
}
