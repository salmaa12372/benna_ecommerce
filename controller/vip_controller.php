<?php
// controller/vip_controller.php
session_start();
include_once __DIR__ . "/../config/database.php";
include_once __DIR__ . "/../config/app.php";
include_once __DIR__ . "/../controller/traitement.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE . "/view/client/signup.php");
    exit();
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$userId = $_SESSION['user_id'];

// ── Unread message count (JSON, used by dashboard polling) ──
if ($action === 'unread_count') {
    header('Content-Type: application/json');
    echo json_encode(['count' => countUnreadMessages($cnx, $userId)]);
    exit();
}

// ── Process payment from payment page ───────────────────────
if ($action === 'process_payment' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $niveau  = $_POST['niveau']  ?? '';
    $prix    = (float)($_POST['prix'] ?? 0);
    $methode = $_POST['methode'] ?? 'carte';

    $niveaux_valides = ['basic', 'premium', 'elite'];
    if (!in_array($niveau, $niveaux_valides) || $prix <= 0) {
        $_SESSION['vip_error'] = "Sélection invalide.";
        header("Location: " . BASE . "/view/client/vip.php");
        exit();
    }

    // Reject if an active subscription already exists
    $stmt = $cnx->prepare("SELECT id FROM vip_abonnements WHERE user_id = ? AND actif = 1 AND date_fin >= CURDATE()");
    $stmt->execute([$userId]);
    if ($stmt->rowCount() > 0) {
        $_SESSION['vip_error'] = "Vous avez déjà un abonnement actif.";
        header("Location: " . BASE . "/view/client/vip_espace.php");
        exit();
    }

    // Deactivate previous subscription
    $cnx->prepare("UPDATE vip_abonnements SET actif = 0 WHERE user_id = ?")->execute([$userId]);

    $req = $cnx->prepare("
        INSERT INTO vip_abonnements (user_id, niveau, prix_mensuel, date_debut, date_fin)
        VALUES (?, ?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY))
    ");
    $ok = $req->execute([$userId, $niveau, $prix]);

    if ($ok) {
        $aboid = $cnx->lastInsertId();
        $cnx->prepare("
            INSERT INTO vip_paiements (user_id, abonnement_id, montant, methode, statut, reference)
            VALUES (?, ?, ?, ?, 'paye', ?)
        ")->execute([$userId, $aboid, $prix, $methode, 'VIP-' . strtoupper(uniqid())]);

        $_SESSION['vip_success'] = "🎉 Bienvenue dans le Club VIP " . ucfirst($niveau) . " ! Votre abonnement est actif.";
        header("Location: " . BASE . "/view/client/vip_espace.php");
    } else {
        $_SESSION['vip_error'] = "Erreur lors de l'activation de l'abonnement.";
        header("Location: " . BASE . "/view/client/vip.php");
    }
    exit();
}

// ── Souscrire — redirect to payment page ────────────────────
if ($action === 'souscrire' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $niveau  = $_POST['niveau']  ?? '';
    $prix    = (float)($_POST['prix'] ?? 0);
    $methode = $_POST['methode'] ?? 'carte';

    $niveaux_valides = ['basic', 'premium', 'elite'];
    if (!in_array($niveau, $niveaux_valides) || $prix <= 0) {
        $_SESSION['vip_error'] = "Sélection invalide.";
        header("Location: " . BASE . "/view/client/vip.php");
        exit();
    }

    header("Location: " . BASE . "/view/client/vip_paiement.php?niveau=" . urlencode($niveau) . "&prix=" . $prix . "&methode=" . urlencode($methode));
    exit();
}

// ── Annuler abonnement ───────────────────────────────────────
if ($action === 'annuler') {
    // Désactiver immédiatement
    $req = $cnx->prepare("UPDATE vip_abonnements SET actif = 0, date_fin = CURDATE() WHERE user_id = ? AND actif = 1");
    $req->execute([$userId]);
    if ($req->rowCount() > 0) {
        $_SESSION['vip_success'] = "Votre abonnement VIP a été résilié immédiatement.";
    } else {
        $_SESSION['vip_error'] = "Aucun abonnement actif trouvé.";
    }
    header("Location: " . BASE . "/view/client/home.php");
    exit();
}

// ── Envoyer message VIP ──────────────────────────────────────
if ($action === 'message' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $userRole = $_SESSION['role'] ?? 'client';
    $isVip    = isVip($cnx, $userId);

    if (!$isVip && $userRole !== 'nutritionniste' && $userRole !== 'admin') {
        $_SESSION['vip_error'] = "Fonctionnalité réservée aux membres VIP.";
        header("Location: " . BASE . "/view/client/vip.php");
        exit();
    }

    $to      = (int)($_POST['destinataire_id'] ?? 0);
    $contenu = trim($_POST['contenu'] ?? '');

    if ($contenu !== '' && $to > 0) {
        // For VIP clients, always route to the first available nutritionist
        if ($userRole === 'client') {
            $stmt = $cnx->query("SELECT id FROM users WHERE role = 'nutritionniste' LIMIT 1");
            $nutri = $stmt->fetch();
            if ($nutri) $to = (int)$nutri['id'];
        }

        $stmt = $cnx->prepare("INSERT INTO vip_messages (expediteur_id, destinataire_id, contenu, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$userId, $to, htmlspecialchars($contenu)]);
        $_SESSION['vip_success'] = "Message envoyé.";
    }

    $referer = $_SERVER['HTTP_REFERER'] ?? BASE . "/view/client/vip_espace.php";
    header("Location: " . $referer);
    exit();
}

// ── Marquer messages comme lus ───────────────────────────────
if ($action === 'mark_read') {
    $sender_id = (int)($_GET['sender_id'] ?? 0);
    if ($sender_id > 0) {
        $stmt = $cnx->prepare("UPDATE vip_messages SET lu = 1 WHERE expediteur_id = ? AND destinataire_id = ? AND lu = 0");
        $stmt->execute([$sender_id, $userId]);
    }
    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? BASE . "/view/client/vip_chat.php"));
    exit();
}

// ── Planifier consultation (nutritionniste) ──────────────────
// FIX: consultations.php sends name="duree" not name="duree_min"
//      Accept both to be safe.
if ($action === 'planifier' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_SESSION['role'] ?? '') !== 'nutritionniste') {
        header("Location: " . BASE . "/view/client/signup.php");
        exit();
    }

    $client_id   = (int)($_POST['client_id'] ?? 0);
    $titre       = trim($_POST['titre'] ?? 'Consultation nutritionnelle');
    $date_heure  = $_POST['date_heure'] ?? '';
    // Accept both field names (dashboard modal uses duree_min, consultations.php uses duree)
    $duree_min   = (int)($_POST['duree_min'] ?? $_POST['duree'] ?? 30);
    $type        = $_POST['type'] ?? 'visio';
    $objectifs   = trim($_POST['objectifs'] ?? '');
    $notes_avant = trim($_POST['notes_avant'] ?? '');

    if (!$client_id || !$date_heure) {
        $_SESSION['vip_error'] = "Client et date sont obligatoires.";
        header("Location: " . BASE . "/view/nutritionniste/consultations.php");
        exit();
    }

    // Auto-generate a Jitsi link for visio consultations
    $lien_visio = null;
    if ($type === 'visio') {
        $room = 'benna-' . $client_id . '-' . time();
        $lien_visio = 'https://meet.jit.si/' . $room;
    }

    $stmt = $cnx->prepare("
        INSERT INTO consultations
            (nutritionniste_id, client_id, titre, date_heure, duree_min, type, statut, objectifs, notes_avant, lien_visio)
        VALUES (?, ?, ?, ?, ?, ?, 'planifiee', ?, ?, ?)
    ");
    $ok = $stmt->execute([
        $_SESSION['user_id'], $client_id, htmlspecialchars($titre),
        $date_heure, $duree_min, $type,
        htmlspecialchars($objectifs), htmlspecialchars($notes_avant),
        $lien_visio
    ]);

    $_SESSION[$ok ? 'vip_success' : 'vip_error'] = $ok ? "Consultation planifiée !" : "Erreur lors de la planification.";
    header("Location: " . BASE . "/view/nutritionniste/consultations.php");
    exit();
}

// ── Mettre à jour statut / notes consultation ────────────────
// FIX: consultations.php notes modal sends name="notes", not name="notes_apres"
//      Accept both field names.
if ($action === 'update_consult' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $consult_id  = (int)($_POST['id'] ?? 0);
    $statut      = $_POST['statut'] ?? '';
    // Accept both field names
    $notes_apres = trim($_POST['notes_apres'] ?? $_POST['notes'] ?? '');

    if (!$consult_id || !$statut) {
        $_SESSION['vip_error'] = "Données manquantes.";
        header("Location: " . ($_SERVER['HTTP_REFERER'] ?? BASE . "/view/nutritionniste/consultations.php"));
        exit();
    }

    $stmt = $cnx->prepare("UPDATE consultations SET statut = ?, notes_apres = ? WHERE id = ?");
    $ok   = $stmt->execute([$statut, htmlspecialchars($notes_apres), $consult_id]);

    $_SESSION[$ok ? 'vip_success' : 'vip_error'] = $ok ? "Consultation mise à jour." : "Erreur.";

    $back = (($_SESSION['role'] ?? '') === 'nutritionniste')
        ? BASE . '/view/nutritionniste/consultations.php'
        : BASE . '/view/client/vip_espace.php';
    header("Location: $back");
    exit();
}

// ── Ajouter objectif (nutritionniste) ───────────────────────
if ($action === 'add_objectif' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_SESSION['role'] ?? '') !== 'nutritionniste') {
        header("Location: " . BASE . "/view/client/signup.php");
        exit();
    }

    $client_id       = (int)($_POST['client_id'] ?? 0);
    $titre           = trim($_POST['titre'] ?? '');
    $valeur_cible    = (float)($_POST['valeur_cible'] ?? 0);
    $valeur_actuelle = (float)($_POST['valeur_actuelle'] ?? 0);
    $unite           = trim($_POST['unite'] ?? 'kg');
    $deadline        = $_POST['deadline'] ?: null;

    $stmt = $cnx->prepare("
        INSERT INTO vip_objectifs (client_id, nutri_id, titre, valeur_cible, valeur_actuelle, unite, deadline)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $ok = $stmt->execute([$client_id, $_SESSION['user_id'], htmlspecialchars($titre), $valeur_cible, $valeur_actuelle, $unite, $deadline]);

    $_SESSION[$ok ? 'vip_success' : 'vip_error'] = $ok ? "Objectif ajouté." : "Erreur.";
    header("Location: " . BASE . "/view/nutritionniste/consultations.php?client=" . $client_id);
    exit();
}

// ── Mettre à jour objectif ───────────────────────────────────
if ($action === 'update_objectif' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $objectif_id     = (int)($_POST['objectif_id'] ?? 0);
    $valeur_actuelle = (float)($_POST['valeur_actuelle'] ?? 0);
    $atteint         = isset($_POST['atteint']) ? 1 : 0;

    $stmt = $cnx->prepare("UPDATE vip_objectifs SET valeur_actuelle = ?, atteint = ? WHERE id = ?");
    $ok   = $stmt->execute([$valeur_actuelle, $atteint, $objectif_id]);

    $_SESSION[$ok ? 'vip_success' : 'vip_error'] = $ok ? "Objectif mis à jour." : "Erreur.";
    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? BASE . "/view/client/vip_espace.php"));
    exit();
}
// ── Modifier consultation ─────────────────────────────
if ($action === 'modifier_consult' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_SESSION['role'] !== 'nutritionniste') {
        header("Location: " . BASE . "/view/client/signup.php"); exit();
    }
    $stmt = $cnx->prepare("
        UPDATE consultations
        SET titre       = :titre,
            date_heure  = :date_heure,
            duree_min   = :duree,
            notes_avant = :notes_avant
        WHERE id = :id AND nutritionniste_id = :nid
    ");
    $ok = $stmt->execute([
        ':titre'       => $_POST['titre']      ?? '',
        ':date_heure'  => $_POST['date_heure'] ?? '',
        ':duree'       => (int)($_POST['duree'] ?? 30),
        ':notes_avant' => $_POST['notes_avant'] ?? '',
        ':id'          => (int)$_POST['id'],
        ':nid'         => $_SESSION['user_id'],
    ]);
    $_SESSION[$ok ? 'success' : 'error'] = $ok ? "Consultation modifiée." : "Erreur.";
    header("Location: " . BASE . "/view/nutritionniste/consultations.php"); exit();
}

// Default fallback
header("Location: " . BASE . "/view/client/vip_espace.php");
exit();