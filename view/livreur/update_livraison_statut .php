<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

$data = json_decode(file_get_contents('php://input'), true);
$id = (int)($data['id'] ?? 0);
$nouveau = $data['statut'] ?? '';

$allowed = ['livree', 'echec']; // Seulement ces deux via AJAX
if (!$id || !in_array($nouveau, $allowed)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Paramètres invalides']);
    exit;
}

try {
    $check = $cnx->prepare("SELECT id FROM livraisons WHERE id = ? AND livreur_id = ?");
    $check->execute([$id, $_SESSION['user_id']]);
    if (!$check->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Livraison non autorisée']);
        exit;
    }

    $stmt = $cnx->prepare("UPDATE livraisons SET statut = ? WHERE id = ?");
    $stmt->execute([$nouveau, $id]);
    echo json_encode(['success' => true, 'message' => 'Statut mis à jour']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}