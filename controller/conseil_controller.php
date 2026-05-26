<?php
// controller/conseil_controller.php
session_start();
include_once __DIR__ . "/../config/database.php";
include_once __DIR__ . "/../config/app.php";
include_once __DIR__ . "/../controller/traitement.php";

$action = $_GET['action'] ?? '';
$role   = $_SESSION['role'] ?? '';

function nutriOnly() {
    if (!in_array($_SESSION['role'] ?? '', ['admin', 'nutritionniste'])) {
        header("Location: " . BASE . "/view/client/signup.php"); exit();
    }
}

// ── Publier un conseil ────────────────────────────────
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    nutriOnly();
    $ok = addConseil(
        $cnx,
        $_SESSION['user_id'],
        $_POST['titre']     ?? '',
        $_POST['contenu']   ?? '',
        !empty($_POST['produit_id']) ? (int)$_POST['produit_id'] : null,
        isset($_POST['public']) ? 1 : 0
    );
    $_SESSION[$ok ? 'success' : 'error'] = $ok ? "Conseil publié." : "Erreur.";
    header("Location: " . BASE . "/view/nutritionniste/conseils.php"); exit();
}

// ── Supprimer un conseil ──────────────────────────────
if ($action === 'delete' && isset($_GET['id'])) {
    nutriOnly();
    $ok = deleteConseil($cnx, (int)$_GET['id']);
    $_SESSION[$ok ? 'success' : 'error'] = $ok ? "Conseil supprimé." : "Erreur.";
    header("Location: " . BASE . "/view/nutritionniste/conseils.php"); exit();
}

// ── Valider un avis (nutritionniste/admin) ────────────
if ($action === 'valider_avis' && isset($_GET['id'])) {
    nutriOnly();
    $ok = validerAvis($cnx, (int)$_GET['id']);
    $_SESSION[$ok ? 'success' : 'error'] = $ok ? "Avis validé et publié." : "Erreur.";
    $back = ($role === 'admin')
        ? BASE . '/view/admin/avis.php'
        : BASE . '/view/nutritionniste/avis.php';
    header("Location: $back"); exit();
}

// ── Client : soumettre un avis depuis mes_commandes ───
if ($action === 'add_avis' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        header("Location: " . BASE . "/view/client/signup.php"); exit();
    }
    $ok = addAvis(
        $cnx,
        $_SESSION['user_id'],
        (int)$_POST['produit_id'],
        (int)$_POST['note'],
        $_POST['commentaire'] ?? ''
    );
    $_SESSION[$ok ? 'success' : 'error'] = $ok
        ? "Merci pour votre avis ! Il sera visible après validation."
        : "Vous avez déjà laissé un avis pour ce produit.";
    header("Location: " . BASE . "/view/client/mes_commandes.php"); exit();
}

if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    nutriOnly();
    $id     = (int)($_POST['id'] ?? 0);
    $pid    = !empty($_POST['produit_id']) ? (int)$_POST['produit_id'] : null;
    $public = isset($_POST['public']) ? 1 : 0;
    $req = $cnx->prepare("UPDATE conseils SET titre=:t, contenu=:c, produit_id=:p, public=:pub WHERE id=:id");
    $ok  = $req->execute([':t'=>htmlspecialchars($_POST['titre']),':c'=>htmlspecialchars($_POST['contenu']),':p'=>$pid,':pub'=>$public,':id'=>$id]);
    $_SESSION[$ok ? 'success' : 'error'] = $ok ? "Conseil modifié." : "Erreur.";
    header("Location: " . BASE . "/view/nutritionniste/conseils.php"); exit();
}