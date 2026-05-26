<?php
// controller/commande_controller.php
session_start();
include_once __DIR__ . "/../config/database.php";
include_once __DIR__ . "/../config/app.php";
include_once __DIR__ . "/../controller/traitement.php";

$action = $_GET['action'] ?? '';
$role   = $_SESSION['role'] ?? '';

// ── Admin/Usine : changer statut commande ────────────
if ($action === 'update_statut' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!in_array($role, ['admin', 'usine'])) {
        header("Location: " . BASE . "/view/client/signup.php"); exit();
    }
    $ok = updateStatutCommande($cnx, (int)$_POST['commande_id'], $_POST['statut']);
    $_SESSION[$ok ? 'success' : 'error'] = $ok ? "Statut mis à jour." : "Erreur.";
    $back = ($role === 'usine')
        ? BASE . '/view/usine/commandes.php'
        : BASE . '/view/admin/commandes.php';
    header("Location: $back"); exit();
}

// ── Admin : assigner livreur ──────────────────────────
if ($action === 'assigner_livreur' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($role !== 'admin') {
        header("Location: " . BASE . "/view/client/signup.php"); exit();
    }
    $ok = assignerLivreur($cnx, (int)$_POST['commande_id'], (int)$_POST['livreur_id']);
    $_SESSION[$ok ? 'success' : 'error'] = $ok ? "Livreur assigné." : "Erreur.";
    header("Location: " . BASE . "/view/admin/commandes.php"); exit();
}

// ── Livreur : mettre à jour statut livraison ──────────
if ($action === 'update_livraison' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($role !== 'livreur') {
        header("Location: " . BASE . "/view/client/signup.php"); exit();
    }
    $lat = !empty($_POST['latitude'])  ? (float)$_POST['latitude']  : null;
    $lng = !empty($_POST['longitude']) ? (float)$_POST['longitude'] : null;
    $ok  = updateStatutLivraison($cnx, (int)$_POST['livraison_id'], $_POST['statut'], $lat, $lng);
    $_SESSION[$ok ? 'success' : 'error'] = $ok ? "Livraison mise à jour." : "Erreur.";
    header("Location: " . BASE . "/view/livreur/dashboard.php"); exit();
}

// ── Admin : supprimer avis ────────────────────────────
if ($action === 'delete_avis' && isset($_GET['id'])) {
    if ($role !== 'admin') {
        header("Location: " . BASE . "/view/client/signup.php"); exit();
    }
    $ok = deleteAvis($cnx, (int)$_GET['id']);
    $_SESSION[$ok ? 'success' : 'error'] = $ok ? "Avis supprimé." : "Erreur.";
    header("Location: " . BASE . "/view/admin/avis.php"); exit();
}
