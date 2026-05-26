<?php
// controller/panier_controller.php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include_once __DIR__ . "/../config/database.php";
include_once __DIR__ . "/../config/app.php";
include_once __DIR__ . "/../controller/traitement.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE . "/view/client/signup.php"); 
    exit();
}

$action = $_GET['action'] ?? '';
$uid    = $_SESSION['user_id'];
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $qty = max(1, intval($_POST['quantite'] ?? 1));
    $pid_check = (int)$_POST['produit_id'];

    // Vérifier que le produit est validé par le nutritionniste
    $vStmt = $cnx->prepare("SELECT id FROM produits WHERE id=:id AND est_actif=1 AND est_valide=1");
    $vStmt->execute([':id' => $pid_check]);
    if (!$vStmt->fetch()) {
        $_SESSION['error'] = "Ce produit n'est pas encore disponible (en attente de validation nutritionniste).";
        header("Location: " . BASE . "/view/client/produits.php"); exit();
    }

    addToPanier($cnx, $uid, $pid_check, $qty);
    
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || 
        strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false ||
        isset($_SERVER['HTTP_FETCH_MODE'])) {
        header('Content-Type: application/json');
        $count = countPanier($cnx, $uid);
        echo json_encode(['success' => true, 'count' => $count]);
        exit();
    }
    
    header("Location: " . BASE . "/view/client/panier.php"); 
    exit();
}

if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    updatePanierQty($cnx, (int)$_POST['panier_id'], intval($_POST['quantite']));
    header("Location: " . BASE . "/view/client/panier.php"); 
    exit();
}

if ($action === 'remove' && isset($_GET['id'])) {
    removePanierItem($cnx, (int)$_GET['id']);
    header("Location: " . BASE . "/view/client/panier.php"); 
    exit();
}

if ($action === 'add_prebuilt') {
    $type = $_GET['type'] ?? '';
    $size = $_GET['size'] ?? 'moyenne';

    $boxPrices = [
        'petite'  => 12.900,
        'moyenne' => 22.900,
        'grande'  => 34.900,
    ];

    $boxLabels = [
        'sans-gluten'  => 'Box Sans Gluten',
        'sans-lactose' => 'Box Sans Lactose',
        'sans-sucre'   => 'Box Sans Sucre',
        'vegan'        => 'Box Vegan',
        'bio'          => 'Box Bio',
        'protein-rich' => 'Box Sport',
        'low-calorie'  => 'Box Low Calorie',
        'mystery'      => 'Boîte Mystère',
    ];

    $sizeLabels = ['petite' => 'Petite', 'moyenne' => 'Moyenne', 'grande' => 'Grande'];
    $nomBox = ($boxLabels[$type] ?? 'Box') . '  ' . ($sizeLabels[$size] ?? ucfirst($size));
    $prix   = $boxPrices[$size] ?? 22.900;

    $stmt = $cnx->prepare("SELECT id FROM produits WHERE nom = :nom AND est_actif = 1 LIMIT 1");
    $stmt->execute([':nom' => $nomBox]);
    $produit = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($produit) {
        $produit_id = $produit['id'];
        $cnx->prepare("UPDATE produits SET prix = :prix WHERE id = :id")
            ->execute([':prix' => $prix, ':id' => $produit_id]);
    } else {
        $cnx->prepare("INSERT INTO produits (nom, prix, stock, est_actif, description) VALUES (:nom, :prix, 9999, 1, 'box')")
            ->execute([':nom' => $nomBox, ':prix' => $prix]);
        $produit_id = (int)$cnx->lastInsertId();
    }

    $check = $cnx->prepare("SELECT id FROM panier WHERE user_id = :uid AND produit_id = :pid");
    $check->execute([':uid' => $uid, ':pid' => $produit_id]);

    if ($check->rowCount() > 0) {
        $cnx->prepare("UPDATE panier SET quantite = quantite + 1 WHERE user_id = :uid AND produit_id = :pid")
            ->execute([':uid' => $uid, ':pid' => $produit_id]);
    } else {
        $cnx->prepare("INSERT INTO panier (user_id, produit_id, quantite) VALUES (:uid, :pid, 1)")
            ->execute([':uid' => $uid, ':pid' => $produit_id]);
    }

    header("Location: " . BASE . "/view/client/panier.php"); 
    exit();
}

if ($action === 'clear') {
    clearPanier($cnx, $uid);
    header("Location: " . BASE . "/view/client/panier.php"); 
    exit();
}

// FIXED CHECKOUT
if ($action === 'checkout' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $adresse = trim($_POST['adresse_livraison'] ?? '');
    $note    = trim($_POST['note_client'] ?? '');
    
    if (empty($adresse)) {
        $_SESSION['checkout_error'] = "Veuillez indiquer une adresse de livraison.";
        header("Location: " . BASE . "/view/client/panier.php"); 
        exit();
    }
    
    $commandeId = passerCommande($cnx, $uid, $adresse, $note);
    
    if ($commandeId) {
        $_SESSION['checkout_success'] = "Commande #$commandeId créée avec succès !";
        header("Location: " . BASE . "/view/client/mes_commandes.php");
    } else {
        $_SESSION['checkout_error'] = "Erreur lors de la commande. Vérifiez votre panier.";
        header("Location: " . BASE . "/view/client/panier.php");
    }
    exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'add_box') {
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../view/client/login.php");
        exit;
    }

    $user_id = $_SESSION['user_id'];

    if (!isset($_GET['ids'])) {
        header("Location: ../view/client/box.php");
        exit;
    }

    $ids = explode(',', $_GET['ids']);

    foreach ($ids as $id) {
        $id = (int)$id;

        $stmt = $cnx->prepare("SELECT * FROM panier WHERE user_id = ? AND produit_id = ?");
        $stmt->execute([$user_id, $id]);

        if ($stmt->rowCount() > 0) {
            $cnx->prepare("UPDATE panier SET quantite = quantite + 1 WHERE user_id = ? AND produit_id = ?")
                ->execute([$user_id, $id]);
        } else {
            $cnx->prepare("INSERT INTO panier (user_id, produit_id, quantite) VALUES (?, ?, 1)")
                ->execute([$user_id, $id]);
        }
    }

    header("Location: ../view/client/panier.php");
    exit;
}
?>