<?php
// config/auth.php
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/app.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Require login
 */
function require_login() {
    if (empty($_SESSION['user_id']) || (int)$_SESSION['user_id'] <= 0) {
        header('Location: ' . BASE . '/view/client/signup.php');
        exit();
    }
}

/**
 * Get current user
 */
function current_user() {
    global $cnx;
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    $stmt = $cnx->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Cart count
 */
function cart_count($userId) {
    global $cnx;
    $stmt = $cnx->prepare("SELECT SUM(quantite) FROM panier WHERE user_id = ?");
    $stmt->execute([$userId]);

    return (int) $stmt->fetchColumn();
}

/**
 * Active VIP
 */
function active_vip($userId) {
    global $cnx;
    $stmt = $cnx->prepare("
        SELECT * FROM vip_abonnements 
        WHERE user_id = ? AND actif = 1 AND date_fin >= CURDATE()
        ORDER BY created_at DESC LIMIT 1
    ");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}
