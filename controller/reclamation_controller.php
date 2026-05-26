<?php
// controller/reclamation_controller.php
session_start();
include_once __DIR__ . "/../config/database.php";
include_once __DIR__ . "/../config/app.php";
include_once __DIR__ . "/../controller/traitement.php";

$action = $_GET['action'] ?? '';
$role   = $_SESSION['role'] ?? '';

// ── Client : envoyer réclamation ─────────────────────
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        header("Location: " . BASE . "/view/client/signup.php"); exit();
    }
    $ok = addReclamation(
        $cnx,
        $_SESSION['user_id'],
        $_POST['sujet']      ?? '',
        $_POST['message']    ?? '',
        !empty($_POST['commande_id']) ? (int)$_POST['commande_id'] : null
    );
    $_SESSION[$ok ? 'success' : 'error'] = $ok
        ? "Réclamation envoyée. Nous vous répondrons rapidement."
        : "Erreur lors de l'envoi.";
    header("Location: " . BASE . "/view/client/mes_reclamations.php"); exit();
}

// ── Admin/Nutritionniste : répondre ──────────────────
if ($action === 'repondre' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!in_array($role, ['admin', 'nutritionniste'])) {
        header("Location: " . BASE . "/view/client/signup.php"); exit();
    }
    $ok = repondreReclamation(
        $cnx,
        (int)$_POST['id'],
        $_POST['reponse'] ?? '',
        $_POST['statut']  ?? 'en_cours',
        $_SESSION['user_id']
    );
    $_SESSION[$ok ? 'success' : 'error'] = $ok ? "Réponse envoyée au client." : "Erreur.";
    $back = ($role === 'admin')
        ? BASE . '/view/admin/reclamations.php'
        : BASE . '/view/nutritionniste/dashboard.php';
    header("Location: $back"); exit();
}

// Admin : transmettre réclamation à l'usine
if ($action === 'transmettre_usine' && isset($_GET['id'])) {
    if ($role !== 'admin') { header("Location: " . BASE . "/view/client/signup.php"); exit(); }
    $ok = transmettreReclamationUsine($cnx, (int)$_GET['id']);
    $_SESSION[$ok ? 'success' : 'error'] = $ok ? "Réclamation transmise à l'usine." : "Erreur.";
    header("Location: " . BASE . "/view/admin/reclamations.php"); exit();
}
if ($action === 'transmettre_livreur' && isset($_GET['id'])) {
    if ($role !== 'admin') {
        header("Location: " . BASE . "/view/client/signup.php"); exit();
    }
    $livreur_id = !empty($_POST['livreur_id']) ? (int)$_POST['livreur_id'] : null;
    if (!$livreur_id) {
        $_SESSION['error'] = "Veuillez sélectionner un livreur.";
        header("Location: " . BASE . "/view/admin/reclamations.php"); exit();
    }
    $ok = transmettreReclamationLivreur($cnx, (int)$_GET['id'], $livreur_id);
    $_SESSION[$ok ? 'success' : 'error'] = $ok
        ? "Réclamation transmise au livreur."
        : "Erreur lors de la transmission.";
    header("Location: " . BASE . "/view/admin/reclamations.php"); exit();
}
