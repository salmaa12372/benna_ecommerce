<?php
// controller/auth_controller.php
session_start();
include_once __DIR__ . "/../config/database.php";
include_once __DIR__ . "/../config/app.php";
include_once __DIR__ . "/../controller/traitement.php";

$action = $_GET['action'] ?? '';

// ── INSCRIPTION ──────────────────────────────────────
if ($action === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $ok = registerUser($cnx, $_POST);
    if ($ok) {
        $_SESSION['signup_success'] = "Compte créé avec succès ! Vous pouvez maintenant vous connecter.";
        // Rediriger vers signup.php avec le panneau d'inscription actif
        header("Location: " . BASE . "/view/client/signup.php?show=signup");
    } else {
        $_SESSION['signup_error'] = "Cet email est déjà utilisé. Veuillez en choisir un autre.";
        header("Location: " . BASE . "/view/client/signup.php?show=signup");
    }
    exit();
}

// ── CONNEXION ────────────────────────────────────────
if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = loginUser($cnx, $_POST['email'], $_POST['password']);
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['nom']     = $user['nom'];
        $_SESSION['email']   = $user['email'];
        $_SESSION['role']    = $user['role'];

        $redirects = [
            'admin'          => BASE . '/view/admin/dashboard.php',
            'nutritionniste' => BASE . '/view/nutritionniste/dashboard.php',
            'usine'          => BASE . '/view/usine/dashboard.php',
            'livreur'        => BASE . '/view/livreur/dashboard.php',
            'client'         => BASE . '/view/client/home.php',
        ];
        header("Location: " . ($redirects[$user['role']] ?? BASE . '/view/client/home.php'));
    } else {
        $_SESSION['signin_error'] = "Email ou mot de passe incorrect. Veuillez réessayer.";
        header("Location: " . BASE . "/view/client/signup.php?show=signin");
    }
    exit();
}

// ── CRÉER UTILISATEUR (admin) ─────────────────────────
if ($action === 'register_admin' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!in_array($_SESSION['role'] ?? '', ['admin'])) {
        header("Location: " . BASE . "/view/client/signup.php"); exit();
    }
    // Force le rôle choisi par l'admin
    $data = $_POST;
    $ok = registerUser($cnx, $data);
    if ($ok && !empty($data['role'])) {
        // Met à jour le rôle (registerUser crée toujours en 'client')
        $cnx->prepare("UPDATE users SET role=:r WHERE email=:e")
            ->execute([':r' => $data['role'], ':e' => $data['email']]);
    }
    $_SESSION[$ok ? 'success' : 'error'] = $ok ? "Utilisateur créé." : "Email déjà utilisé.";
    header("Location: " . BASE . "/view/admin/users.php");
    exit();
}

// ── MODIFIER UTILISATEUR (admin) ──────────────────────
if ($action === 'update_user' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!in_array($_SESSION['role'] ?? '', ['admin'])) {
        header("Location: " . BASE . "/view/client/signup.php"); exit();
    }
    $ok = updateUser($cnx, $_POST);
    $_SESSION[$ok ? 'success' : 'error'] = $ok ? "Utilisateur mis à jour." : "Erreur.";
    header("Location: " . BASE . "/view/admin/users.php");
    exit();
}

// ── DÉCONNEXION ──────────────────────────────────────
if ($action === 'logout') {
    session_destroy();
    header("Location: " . BASE . "/view/client/signup.php");
    exit();
}