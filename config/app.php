<?php
// config/app.php

// Prevent multiple inclusions
if (defined('APP_LOADED')) {
    return;
}
define('APP_LOADED', true);

if (!function_exists('baseUrl')) {
    function baseUrl() {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host     = $_SERVER['HTTP_HOST'];
        $script   = $_SERVER['SCRIPT_NAME'];

        $known = ['view', 'controller', 'config', 'model', 'public'];
        $parts = explode('/', trim($script, '/'));

        $rootParts = [];
        foreach ($parts as $part) {
            if (in_array($part, $known)) break;
            $rootParts[] = $part;
        }

        $root = !empty($rootParts) ? '/' . implode('/', $rootParts) : '';

        return $protocol . '://' . $host . $root;
    }
}

if (!defined('BASE')) {
    define('BASE', baseUrl());
}

// ══════════════════════════════════════════════════════════
//  FONCTIONS PANIER
// ══════════════════════════════════════════════════════════

function getPanier($cnx, $uid) {
    $sql = "
        SELECT 
            p.id AS panier_id,
            p.quantite,
            pr.id AS produit_id,
            pr.nom,
            pr.prix,
            pr.image,
            pr.stock
        FROM panier p
        INNER JOIN produits pr ON p.produit_id = pr.id
        WHERE p.user_id = :uid
    ";
    $stmt = $cnx->prepare($sql);
    $stmt->execute([':uid' => $uid]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($items as &$item) {
        $item['sous_total'] = $item['prix'] * $item['quantite'];
    }
    return $items;
}
if (!function_exists('getTotalPanier')) {
    function getTotalPanier($cnx, $userId) {
        $stmt = $cnx->prepare("
            SELECT SUM(p.prix * pa.quantite) as total
            FROM panier pa
            JOIN produits p ON p.id = pa.produit_id
            WHERE pa.user_id = ?
        ");
        $stmt->execute([$userId]);
        return (float)($stmt->fetchColumn() ?? 0);
    }
}

if (!function_exists('countPanier')) {
    function countPanier($cnx, $userId) {
        $stmt = $cnx->prepare("SELECT SUM(quantite) as total FROM panier WHERE user_id = ?");
        $stmt->execute([$userId]);
        return (int)($stmt->fetchColumn() ?? 0);
    }
}

if (!function_exists('addToPanier')) {
    function addToPanier($cnx, $userId, $productId, $qty = 1) {
        $stmt = $cnx->prepare("INSERT INTO panier (user_id, produit_id, quantite) 
                               VALUES (?, ?, ?) 
                               ON DUPLICATE KEY UPDATE quantite = quantite + ?");
        return $stmt->execute([$userId, $productId, $qty, $qty]);
    }
}

if (!function_exists('updatePanierQty')) {
    function updatePanierQty($cnx, $panierId, $qty) {
        if ($qty <= 0) {
            return removePanierItem($cnx, $panierId);
        }
        $stmt = $cnx->prepare("UPDATE panier SET quantite = ? WHERE id = ?");
        return $stmt->execute([$qty, $panierId]);
    }
}

if (!function_exists('removePanierItem')) {
    function removePanierItem($cnx, $panierId) {
        $stmt = $cnx->prepare("DELETE FROM panier WHERE id = ?");
        return $stmt->execute([$panierId]);
    }
}

if (!function_exists('clearPanier')) {
    function clearPanier($cnx, $userId) {
        $stmt = $cnx->prepare("DELETE FROM panier WHERE user_id = ?");
        return $stmt->execute([$userId]);
    }
}

// ══════════════════════════════════════════════════════════
//  FONCTIONS VIP
// ══════════════════════════════════════════════════════════

if (!function_exists('isVip')) {
    function isVip($cnx, $userId) {
        $stmt = $cnx->prepare("SELECT id FROM vip_abonnements WHERE user_id = ? AND actif = 1 AND date_fin >= CURDATE()");
        $stmt->execute([$userId]);
        return $stmt->rowCount() > 0;
    }
}

if (!function_exists('getConversation')) {
    function getConversation($cnx, $userId, $withId) {
        $stmt = $cnx->prepare("
            SELECT * FROM vip_messages 
            WHERE (expediteur_id = ? AND destinataire_id = ?) 
               OR (expediteur_id = ? AND destinataire_id = ?) 
            ORDER BY created_at ASC
        ");
        $stmt->execute([$userId, $withId, $withId, $userId]);
        return $stmt->fetchAll();
    }
}

if (!function_exists('markMessagesLu')) {
    function markMessagesLu($cnx, $senderId, $receiverId) {
        $stmt = $cnx->prepare("
            UPDATE vip_messages SET lu = 1 
            WHERE expediteur_id = ? AND destinataire_id = ? AND lu = 0
        ");
        return $stmt->execute([$senderId, $receiverId]);
    }
}

if (!function_exists('getAbonnementUser')) {
    function getAbonnementUser($cnx, $userId) {
        $stmt = $cnx->prepare("
            SELECT v.*, DATEDIFF(v.date_fin, CURDATE()) AS jours_restants
            FROM vip_abonnements v 
            WHERE v.user_id = ? AND v.actif = 1 AND v.date_fin >= CURDATE() 
            ORDER BY v.id DESC LIMIT 1
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result && isset($result['date_fin'])) {
            $result['jours_restants'] = max(0, floor((strtotime($result['date_fin']) - time()) / 86400));
        }
        return $result;
    }
}

if (!function_exists('souscrireVip')) {
    function souscrireVip($cnx, $user_id, $niveau, $prix, $methode = 'carte') {
        $cnx->prepare("UPDATE vip_abonnements SET actif = 0 WHERE user_id = ?")->execute([$user_id]);
        $req = $cnx->prepare("
            INSERT INTO vip_abonnements (user_id, niveau, prix_mensuel, date_debut, date_fin)
            VALUES (?, ?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY))
        ");
        $ok = $req->execute([$user_id, $niveau, $prix]);
        if ($ok) {
            $aboid = $cnx->lastInsertId();
            $cnx->prepare("
                INSERT INTO vip_paiements (user_id, abonnement_id, montant, methode, statut, reference)
                VALUES (?, ?, ?, ?, 'paye', ?)
            ")->execute([$user_id, $aboid, $prix, $methode, 'VIP-' . strtoupper(uniqid())]);
        }
        return $ok;
    }
}

if (!function_exists('annulerVip')) {
    function annulerVip($cnx, $user_id) {
        $req = $cnx->prepare("UPDATE vip_abonnements SET actif = 0, renouvellement = 0 WHERE user_id = ?");
        return $req->execute([$user_id]);
    }
}

if (!function_exists('getAllVipClients')) {
    function getAllVipClients($cnx) {
        $req = $cnx->prepare("
            SELECT u.*, v.niveau, v.prix_mensuel, v.date_fin,
                   DATEDIFF(v.date_fin, CURDATE()) AS jours_restants
            FROM vip_abonnements v 
            JOIN users u ON v.user_id = u.id
            WHERE v.actif = 1 AND v.date_fin >= CURDATE() 
            ORDER BY v.niveau DESC, v.date_debut DESC
        ");
        $req->execute();
        return $req->fetchAll();
    }
}

if (!function_exists('canConsulter')) {
    function canConsulter($cnx, $user_id) {
        $abo = getAbonnementUser($cnx, $user_id);
        return $abo && in_array($abo['niveau'], ['premium', 'elite']);
    }
}

if (!function_exists('sendVipMessage')) {
    function sendVipMessage($cnx, $from, $to, $contenu, $consult_id = null) {
        $req = $cnx->prepare("
            INSERT INTO vip_messages (expediteur_id, destinataire_id, contenu, consultation_id)
            VALUES (?, ?, ?, ?)
        ");
        return $req->execute([$from, $to, htmlspecialchars($contenu), $consult_id]);
    }
}

if (!function_exists('countUnreadMessages')) {
    function countUnreadMessages($cnx, $user_id) {
        $req = $cnx->prepare("SELECT COUNT(*) FROM vip_messages WHERE destinataire_id = ? AND lu = 0");
        $req->execute([$user_id]);
        return (int)$req->fetchColumn();
    }
}

if (!function_exists('getConsultationsClient')) {
    function getConsultationsClient($cnx, $client_id) {
        $stmt = $cnx->prepare("
            SELECT c.*, u.nom as nutri_nom 
            FROM consultations c
            JOIN users u ON u.id = c.nutritionniste_id
            WHERE c.client_id = ?
            ORDER BY c.date_heure DESC
        ");
        $stmt->execute([$client_id]);
        return $stmt->fetchAll();
    }
}

if (!function_exists('getObjectifsClient')) {
    function getObjectifsClient($cnx, $client_id) {
        $stmt = $cnx->prepare("
            SELECT * FROM vip_objectifs 
            WHERE client_id = ? 
            ORDER BY deadline ASC
        ");
        $stmt->execute([$client_id]);
        return $stmt->fetchAll();
    }
}

// ══════════════════════════════════════════════════════════
//  AUTRES FONCTIONS UTILITAIRES
// ══════════════════════════════════════════════════════════

if (!function_exists('getUserById')) {
    function getUserById($cnx, $userId) {
        $stmt = $cnx->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

if (!function_exists('updateLastActivity')) {
    function updateLastActivity($cnx, $userId) {
        $stmt = $cnx->prepare("UPDATE users SET last_activity = NOW() WHERE id = ?");
        return $stmt->execute([$userId]);
    }
}

if (!function_exists('getUserSubscriptionLevel')) {
    function getUserSubscriptionLevel($cnx, $userId) {
        $abo = getAbonnementUser($cnx, $userId);
        return $abo ? $abo['niveau'] : 'normal';
    }
}

if (!function_exists('getVipBadge')) {
    function getVipBadge($cnx, $userId) {
        $abo = getAbonnementUser($cnx, $userId);
        if (!$abo) return '';
        $badges = [
            'basic'   => ['emoji' => '🟢', 'label' => 'VIP Basic',   'color' => '#22c55e'],
            'premium' => ['emoji' => '🔵', 'label' => 'VIP Premium', 'color' => '#3b82f6'],
            'elite'   => ['emoji' => '🟣', 'label' => 'VIP Elite',   'color' => '#8b5cf6'],
        ];
        $badge = $badges[$abo['niveau']] ?? $badges['basic'];
        return '<span class="vip-badge" style="background:' . $badge['color'] . '20;color:' . $badge['color'] . ';padding:2px 8px;border-radius:20px;font-size:0.75rem;">'
             . $badge['emoji'] . ' ' . $badge['label'] . '</span>';
    }
}

if (!function_exists('formatPrice')) {
    function formatPrice($price) {
        return number_format($price, 2, ',', ' ') . ' TND';
    }
}

if (!function_exists('getSetting')) {
    function getSetting($cnx, $key, $default = null) {
        $stmt = $cnx->prepare("SELECT value FROM settings WHERE `key` = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['value'] : $default;
    }
}

if (!function_exists('setSetting')) {
    function setSetting($cnx, $key, $value) {
        $stmt = $cnx->prepare("INSERT INTO settings (`key`, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = ?");
        return $stmt->execute([$key, $value, $value]);
    }
}

if (!function_exists('getAllCommandes')) {
    function getAllCommandes($cnx) {
        $stmt = $cnx->prepare("
            SELECT c.*, u.nom as client_nom 
            FROM commandes c
            JOIN users u ON u.id = c.user_id
            ORDER BY c.date_commande DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
}

if (!function_exists('getStockAlertes')) {
    function getStockAlertes($cnx) {
        $stmt = $cnx->prepare("
            SELECT s.*, p.nom 
            FROM stock s
            JOIN produits p ON p.id = s.produit_id
            WHERE s.quantite <= s.seuil_alerte
            ORDER BY s.quantite ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
}

if (!function_exists('getStats')) {
    function getStats($cnx) {
        $stats = [];
        $stats['clients']        = $cnx->query("SELECT COUNT(*) FROM users WHERE role = 'client'")->fetchColumn();
        $stats['produits']       = $cnx->query("SELECT COUNT(*) FROM produits WHERE est_actif = 1 AND est_valide = 1")->fetchColumn();
        $stats['commandes']      = $cnx->query("SELECT COUNT(*) FROM commandes")->fetchColumn();
        $stats['en_preparation'] = $cnx->query("SELECT COUNT(*) FROM commandes WHERE statut = 'en_preparation'")->fetchColumn();
        $stats['revenu']         = $cnx->query("SELECT COALESCE(SUM(total), 0) FROM commandes WHERE statut = 'livre'")->fetchColumn();
        $stats['reclamations']   = $cnx->query("SELECT COUNT(*) FROM reclamations WHERE statut = 'ouverte'")->fetchColumn();
        $stats['avis_a_valider'] = $cnx->query("SELECT COUNT(*) FROM avis WHERE valide = 0")->fetchColumn();
        return $stats;
    }
}