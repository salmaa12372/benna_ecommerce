<?php
// controller/traitement.php
if (defined('TRAITEMENT_LOADED')) return;
define('TRAITEMENT_LOADED', true);

include_once __DIR__ . "/../config/database.php";
include_once __DIR__ . "/../config/app.php";

// ─────────────────────────────────────────────────────────────
//  AUTH & USERS
// ─────────────────────────────────────────────────────────────
if (!function_exists('registerUser')) {
    function registerUser($cnx, $data) {
        $chk = $cnx->prepare("SELECT id FROM users WHERE email=:e");
        $chk->execute([':e' => $data['email']]);
        if ($chk->fetch()) return false;
        $pw = password_hash($data['password'], PASSWORD_DEFAULT);
        $req = $cnx->prepare("INSERT INTO users (nom,email,password,telephone,adresse) VALUES (:n,:e,:p,:t,:a)");
        return $req->execute([
            ':n'=>htmlspecialchars($data['user_name']),
            ':e'=>$data['email'],
            ':p'=>$pw,
            ':t'=>$data['telephone']??'',
            ':a'=>$data['adresse']??''
        ]);
    }
}

if (!function_exists('loginUser')) {
    function loginUser($cnx, $email, $password) {
        $req = $cnx->prepare("SELECT * FROM users WHERE email=:e AND actif=1");
        $req->execute([':e' => $email]);
        $user = $req->fetch(PDO::FETCH_ASSOC);
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return false;
    }
}

if (!function_exists('getAllUsers')) {
    function getAllUsers($cnx, $role = null) {
        if ($role) {
            $req = $cnx->prepare("SELECT * FROM users WHERE role=:r ORDER BY created_at DESC");
            $req->execute([':r'=>$role]);
        } else {
            $req = $cnx->prepare("SELECT * FROM users ORDER BY created_at DESC");
            $req->execute();
        }
        return $req->fetchAll();
    }
}

if (!function_exists('updateUser')) {
    function updateUser($cnx, $data) {
        $old = $cnx->prepare("SELECT password FROM users WHERE id=:id");
        $old->execute([':id'=>$data['idu']]);
        $oldData = $old->fetch();
        $pw = (!empty($data['password'])) ? password_hash($data['password'], PASSWORD_DEFAULT) : $oldData['password'];
        $req = $cnx->prepare("UPDATE users SET nom=:n,email=:e,password=:p,telephone=:t,adresse=:a,role=:r WHERE id=:id");
        return $req->execute([
            ':n'=>htmlspecialchars($data['user_name']),
            ':e'=>$data['email'],
            ':p'=>$pw,
            ':t'=>$data['telephone']??'',
            ':a'=>$data['adresse']??'',
            ':r'=>$data['role']??'client',
            ':id'=>$data['idu']
        ]);
    }
}

if (!function_exists('deleteUser')) {
    function deleteUser($cnx, $id) {
        $req = $cnx->prepare("DELETE FROM users WHERE id=:id");
        return $req->execute([':id'=>$id]);
    }
}

if (!function_exists('searchUsers')) {
    function searchUsers($cnx, $term) {
        $req = $cnx->prepare("SELECT * FROM users WHERE nom LIKE :t OR email LIKE :t");
        $req->execute([':t'=>"%$term%"]);
        return $req->fetchAll();
    }
}

if (!function_exists('toggleUserActif')) {
    function toggleUserActif($cnx, $id) {
        $req = $cnx->prepare("UPDATE users SET actif = NOT actif WHERE id=:id");
        return $req->execute([':id'=>$id]);
    }
}

// ─────────────────────────────────────────────────────────────
//  PRODUITS
// ─────────────────────────────────────────────────────────────
if (!function_exists('getAllProduits')) {
    function getAllProduits($cnx) {
        $req = $cnx->prepare("
            SELECT p.*,c.nom AS cat_nom,c.icone AS cat_icone
            FROM produits p 
            LEFT JOIN categories c ON p.categorie_id=c.id
            WHERE p.est_actif=1 AND p.est_valide=1 
            ORDER BY p.created_at DESC
        ");
        $req->execute();
        return $req->fetchAll();
    }
}

if (!function_exists('getAllProduitsAdmin')) {
    function getAllProduitsAdmin($cnx) {
        $req = $cnx->prepare("
            SELECT p.*,c.nom AS cat_nom,c.icone AS cat_icone
            FROM produits p 
            LEFT JOIN categories c ON p.categorie_id=c.id
            WHERE p.est_actif=1 
            ORDER BY p.created_at DESC
        ");
        $req->execute();
        return $req->fetchAll();
    }
}

if (!function_exists('getProduitById')) {
    function getProduitById($cnx, $id) {
        $req = $cnx->prepare("
            SELECT p.*,c.nom AS cat_nom 
            FROM produits p
            LEFT JOIN categories c ON p.categorie_id=c.id 
            WHERE p.id=:id
        ");
        $req->execute([':id'=>$id]);
        $p = $req->fetch();
        if ($p) {
            $ar = $cnx->prepare("
                SELECT a.* 
                FROM allergenes a
                JOIN produit_allergenes pa ON a.id=pa.allergene_id 
                WHERE pa.produit_id=:id
            ");
            $ar->execute([':id'=>$id]);
            $p['allergenes'] = $ar->fetchAll();
    
            $avis = $cnx->prepare("
                SELECT a.*,u.nom AS auteur 
                FROM avis a 
                JOIN users u ON a.user_id=u.id
                WHERE a.produit_id=:id AND a.valide=1 
                ORDER BY a.created_at DESC
            ");
            $avis->execute([':id'=>$id]);
            $p['avis'] = $avis->fetchAll();
    
            $note = $cnx->prepare("SELECT AVG(note) as avg FROM avis WHERE produit_id=:id AND valide=1");
            $note->execute([':id'=>$id]);
            $p['note_moyenne'] = round($note->fetch()['avg'] ?? 0, 1);
            $p['nb_avis'] = count($p['avis']);
        }
        return $p;
    }
}

if (!function_exists('searchProduits')) {
    function searchProduits($cnx, $filters = []) {
        $sql = "SELECT p.*,c.nom AS cat_nom 
                FROM produits p 
                LEFT JOIN categories c ON p.categorie_id=c.id 
                WHERE p.est_actif=1 AND p.est_valide=1";
        $params = [];
        if (!empty($filters['search'])) {
            $sql .= " AND (p.nom LIKE :s OR p.description LIKE :s)";
            $params[':s'] = "%{$filters['search']}%";
        }
        if (!empty($filters['categorie_id'])) {
            $sql .= " AND p.categorie_id=:cat";
            $params[':cat']=$filters['categorie_id'];
        }
        if (!empty($filters['regime'])) {
            $sql .= " AND p.regime LIKE :reg";
            $params[':reg']="%{$filters['regime']}%";
        }
        if (!empty($filters['prix_max'])) {
            $sql .= " AND p.prix<=:pm";
            $params[':pm']=$filters['prix_max'];
        }
        if (!empty($filters['prix_min'])) {
            $sql .= " AND p.prix>=:pmin";
            $params[':pmin']=$filters['prix_min'];
        }
        $sql .= " ORDER BY p.created_at DESC";
        $req = $cnx->prepare($sql);
        $req->execute($params);
        return $req->fetchAll();
    }
}

if (!function_exists('addProduit')) {
    function addProduit($cnx, $data, $img = '') {
        $req = $cnx->prepare("
            INSERT INTO produits (nom,description,prix,stock,image,categorie_id,regime,calories,proteines,glucides,lipides,est_nouveau,est_bestseller)
            VALUES (:n,:d,:p,:s,:i,:c,:r,:cal,:prot,:gluc,:lip,:new,:best)
        ");
        $ok = $req->execute([
            ':n'=>htmlspecialchars($data['nom']),
            ':d'=>htmlspecialchars($data['description']),
            ':p'=>$data['prix'],
            ':s'=>$data['stock'],
            ':i'=>$img,
            ':c'=>$data['categorie_id'],
            ':r'=>$data['regime']??'',
            ':cal'=>$data['calories']??0,
            ':prot'=>$data['proteines']??0,
            ':gluc'=>$data['glucides']??0,
            ':lip'=>$data['lipides']??0,
            ':new'=>$data['est_nouveau']??0,
            ':best'=>$data['est_bestseller']??0
        ]);
        if ($ok && !empty($data['allergenes'])) {
            attachAllergenes($cnx, $cnx->lastInsertId(), $data['allergenes']);
        }
        return $ok;
    }
}

if (!function_exists('updateProduit')) {
    function updateProduit($cnx, $data, $img = null) {
        $imgClause = $img ? ",image=:i" : "";
        $params = [
            ':n'=>htmlspecialchars($data['nom']),
            ':d'=>htmlspecialchars($data['description']),
            ':p'=>$data['prix'],
            ':s'=>$data['stock'],
            ':c'=>$data['categorie_id'],
            ':r'=>$data['regime']??'',
            ':cal'=>$data['calories']??0,
            ':prot'=>$data['proteines']??0,
            ':gluc'=>$data['glucides']??0,
            ':lip'=>$data['lipides']??0,
            ':new'=>$data['est_nouveau']??0,
            ':best'=>$data['est_bestseller']??0,
            ':id'=>$data['id']
        ];
        if ($img) $params[':i'] = $img;
        $req = $cnx->prepare("
            UPDATE produits SET 
                nom=:n,description=:d,prix=:p,stock=:s,categorie_id=:c,
                regime=:r,calories=:cal,proteines=:prot,glucides=:gluc,lipides=:lip,
                est_nouveau=:new,est_bestseller=:best $imgClause 
            WHERE id=:id
        ");
        $ok = $req->execute($params);
        if ($ok && isset($data['allergenes'])) {
            $cnx->prepare("DELETE FROM produit_allergenes WHERE produit_id=:id")->execute([':id'=>$data['id']]);
            if (!empty($data['allergenes'])) attachAllergenes($cnx, $data['id'], $data['allergenes']);
        }
        return $ok;
    }
}

if (!function_exists('deleteProduit')) {
    function deleteProduit($cnx, $id) {
        $req = $cnx->prepare("UPDATE produits SET est_actif=0 WHERE id=:id");
        return $req->execute([':id'=>$id]);
    }
}

if (!function_exists('attachAllergenes')) {
    function attachAllergenes($cnx, $pid, $ids) {
        $req = $cnx->prepare("INSERT IGNORE INTO produit_allergenes (produit_id,allergene_id) VALUES (:p,:a)");
        foreach ($ids as $aid) $req->execute([':p'=>$pid,':a'=>$aid]);
    }
}

if (!function_exists('getAllCategories')) {
    function getAllCategories($cnx) {
        $req = $cnx->prepare("SELECT * FROM categories");
        $req->execute();
        return $req->fetchAll();
    }
}

if (!function_exists('getAllAllergenes')) {
    function getAllAllergenes($cnx) {
        $req = $cnx->prepare("SELECT * FROM allergenes");
        $req->execute();
        return $req->fetchAll();
    }
}

// ══════════════════════════════════════════════════════════
//  PANIER (FONCTIONS MANQUANTES AJOUTÉES)
// ══════════════════════════════════════════════════════════

if (!function_exists('getPanier')) {
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
}

if (!function_exists('getTotalPanier')) {
    function getTotalPanier($cnx, $uid) {
        $items = getPanier($cnx, $uid);
        $total = 0;
        foreach ($items as $item) {
            $total += $item['prix'] * $item['quantite'];
        }
        return $total;
    }
}

if (!function_exists('clearPanier')) {
    function clearPanier($cnx, $uid) {
        $req = $cnx->prepare("DELETE FROM panier WHERE user_id = :uid");
        return $req->execute([':uid' => $uid]);
    }
}

if (!function_exists('addToPanier')) {
    function addToPanier($cnx, $uid, $produit_id, $quantite = 1) {
        $check = $cnx->prepare("SELECT id, quantite FROM panier WHERE user_id = :uid AND produit_id = :pid");
        $check->execute([':uid' => $uid, ':pid' => $produit_id]);
        $existing = $check->fetch(PDO::FETCH_ASSOC);
        if ($existing) {
            $newQty = $existing['quantite'] + $quantite;
            $upd = $cnx->prepare("UPDATE panier SET quantite = :q WHERE id = :id");
            return $upd->execute([':q' => $newQty, ':id' => $existing['id']]);
        } else {
            $ins = $cnx->prepare("INSERT INTO panier (user_id, produit_id, quantite) VALUES (:u, :p, :q)");
            return $ins->execute([':u' => $uid, ':p' => $produit_id, ':q' => $quantite]);
        }
    }
}

if (!function_exists('updatePanierQty')) {
    function updatePanierQty($cnx, $panier_id, $quantite) {
        if ($quantite <= 0) {
            return removePanierItem($cnx, $panier_id);
        }
        $req = $cnx->prepare("UPDATE panier SET quantite = :q WHERE id = :id");
        return $req->execute([':q' => $quantite, ':id' => $panier_id]);
    }
}

if (!function_exists('removePanierItem')) {
    function removePanierItem($cnx, $panier_id) {
        $req = $cnx->prepare("DELETE FROM panier WHERE id = :id");
        return $req->execute([':id' => $panier_id]);
    }
}

if (!function_exists('countPanier')) {
    function countPanier($cnx, $uid) {
        $req = $cnx->prepare("SELECT SUM(quantite) as total FROM panier WHERE user_id = :uid");
        $req->execute([':uid' => $uid]);
        $row = $req->fetch(PDO::FETCH_ASSOC);
        return $row['total'] ?? 0;
    }
}

// ══════════════════════════════════════════════════════════
//  COMMANDES
// ══════════════════════════════════════════════════════════

function passerCommande($cnx, $uid, $adresse, $note = '') {
    try {
        $items = getPanier($cnx, $uid);
        if (empty($items)) {
            error_log("Panier vide pour l'utilisateur $uid");
            return false;
        }

        $total = getTotalPanier($cnx, $uid);
        $deliveryFee = ($total >= 150) ? 0 : 5;
        $grandTotal = $total + $deliveryFee;

        $cnx->beginTransaction();

        $req = $cnx->prepare("
            INSERT INTO commandes (user_id, total, adresse_livraison, note_client, statut, paiement_statut, date_commande)
            VALUES (:u, :t, :a, :n, 'en_attente', 'en_attente', NOW())
        ");
        if (!$req->execute([':u' => $uid, ':t' => $grandTotal, ':a' => $adresse, ':n' => $note])) {
            throw new Exception("Échec insertion commande : " . print_r($req->errorInfo(), true));
        }
        $cid = $cnx->lastInsertId();

        $det = $cnx->prepare("
            INSERT INTO commande_details (commande_id, produit_id, quantite, prix_unitaire) 
            VALUES (:c, :p, :q, :pu)
        ");
        foreach ($items as $item) {
            $produit_id = $item['produit_id'] ?? $item['id'] ?? null;
            if (!$produit_id) {
                throw new Exception("ID produit manquant dans l'item : " . print_r($item, true));
            }
            $det->execute([
                ':c' => $cid,
                ':p' => $produit_id,
                ':q' => $item['quantite'],
                ':pu' => $item['prix']
            ]);
            $cnx->prepare("UPDATE produits SET stock = stock - :q WHERE id = :p")
                ->execute([':q' => $item['quantite'], ':p' => $produit_id]);
        }

        $liv = $cnx->prepare("
            INSERT INTO livraisons (commande_id, livreur_id, statut, updated_at) 
            VALUES (:cid, NULL, 'assignee', NOW())
        ");
        $liv->execute([':cid' => $cid]);

        clearPanier($cnx, $uid);
        $cnx->commit();
        return $cid;
    } catch (Exception $e) {
        $cnx->rollBack();
        error_log("Erreur passerCommande : " . $e->getMessage());
        return false;
    }
}

if (!function_exists('getCommandesByUser')) {
    function getCommandesByUser($cnx, $uid) {
        $req = $cnx->prepare("SELECT * FROM commandes WHERE user_id=:uid ORDER BY date_commande DESC");
        $req->execute([':uid'=>$uid]);
        return $req->fetchAll();
    }
}

if (!function_exists('getCommandeById')) {
    function getCommandeById($cnx, $id) {
        $req = $cnx->prepare("
            SELECT c.*, u.nom AS client_nom, u.email AS client_email, u.telephone
            FROM commandes c
            JOIN users u ON c.user_id = u.id
            WHERE c.id = :id
        ");
        $req->execute([':id' => $id]);
        $c = $req->fetch(PDO::FETCH_ASSOC);
        if ($c) {
            $det = $cnx->prepare("
                SELECT cd.*, p.nom, p.image 
                FROM commande_details cd
                JOIN produits p ON cd.produit_id = p.id 
                WHERE cd.commande_id = :id
            ");
            $det->execute([':id' => $id]);
            $c['details'] = $det->fetchAll(PDO::FETCH_ASSOC);
        }
        return $c;
    }
}

if (!function_exists('getCommandesByStatut')) {
    function getCommandesByStatut($cnx, $statut) {
        $req = $cnx->prepare("SELECT c.*,u.nom AS client_nom,u.adresse FROM commandes c
                              JOIN users u ON c.user_id=u.id WHERE c.statut=:s ORDER BY c.date_commande DESC");
        $req->execute([':s'=>$statut]);
        return $req->fetchAll();
    }
}

if (!function_exists('updateStatutCommande')) {
    function updateStatutCommande($cnx, $id, $statut) {
        $req = $cnx->prepare("UPDATE commandes SET statut=:s WHERE id=:id");
        return $req->execute([':s'=>$statut, ':id'=>$id]);
    }
}

if (!function_exists('updatePaiementStatut')) {
    function updatePaiementStatut($cnx, $commande_id, $statut) {
        $req = $cnx->prepare("UPDATE commandes SET paiement_statut=:s WHERE id=:id");
        return $req->execute([':s'=>$statut, ':id'=>$commande_id]);
    }
}

// ══════════════════════════════════════════════════════════
//  AVIS
// ══════════════════════════════════════════════════════════
if (!function_exists('addAvis')) {
    function addAvis($cnx, $uid, $pid, $note, $comment) {
        $check = $cnx->prepare("
            SELECT 1 FROM commande_details cd
            JOIN commandes c ON c.id = cd.commande_id
            WHERE c.user_id = :uid AND cd.produit_id = :pid AND c.statut = 'livre'
            LIMIT 1
        ");
        $check->execute([':uid'=>$uid, ':pid'=>$pid]);
        if (!$check->fetch()) return false;

        $req = $cnx->prepare("
            INSERT INTO avis (user_id, produit_id, note, commentaire, valide, created_at) 
            VALUES (:u, :p, :n, :c, 0, NOW())
        ");
        $ok = $req->execute([
            ':u'=>$uid,
            ':p'=>$pid,
            ':n'=>$note,
            ':c'=>htmlspecialchars($comment)
        ]);
        if ($ok) recalcNotesProduit($cnx, $pid);
        return $ok;
    }
}

if (!function_exists('validerAvis')) {
    function validerAvis($cnx, $id) {
        $req = $cnx->prepare("UPDATE avis SET valide=1 WHERE id=:id");
        $ok = $req->execute([':id'=>$id]);
        if ($ok) {
            $r = $cnx->prepare("SELECT produit_id FROM avis WHERE id=:id");
            $r->execute([':id'=>$id]);
            $row=$r->fetch();
            if($row) recalcNotesProduit($cnx,$row['produit_id']);
        }
        return $ok;
    }
}

if (!function_exists('getAllAvis')) {
    function getAllAvis($cnx) {
        $req = $cnx->prepare("
            SELECT a.*, u.nom AS auteur, p.nom AS produit_nom 
            FROM avis a
            JOIN users u ON a.user_id=u.id 
            JOIN produits p ON a.produit_id=p.id 
            ORDER BY a.created_at DESC
        ");
        $req->execute();
        return $req->fetchAll();
    }
}

if (!function_exists('recalcNotesProduit')) {
    function recalcNotesProduit($cnx, $pid) {
        $r = $cnx->prepare("SELECT AVG(note) AS avg, COUNT(*) AS nb FROM avis WHERE produit_id=:p AND valide=1");
        $r->execute([':p'=>$pid]);
        $row=$r->fetch();
        $cnx->prepare("UPDATE produits SET note_moyenne=:a, nb_avis=:n WHERE id=:p")
            ->execute([':a'=>round($row['avg'],2),':n'=>$row['nb'],':p'=>$pid]);
    }
}

if (!function_exists('deleteAvis')) {
    function deleteAvis($cnx, $id) {
        $req = $cnx->prepare("DELETE FROM avis WHERE id=:id");
        return $req->execute([':id'=>$id]);
    }
}

// ─────────────────────────────────────────────────────────────
//  LIVRAISONS
// ─────────────────────────────────────────────────────────────
if (!function_exists('getLivraisonInfo')) {
    function getLivraisonInfo($cnx, $commande_id) {
        $req = $cnx->prepare("
            SELECT l.*, u.nom AS livreur_nom 
            FROM livraisons l 
            LEFT JOIN users u ON l.livreur_id = u.id 
            WHERE l.commande_id = :id
        ");
        $req->execute([':id'=>$commande_id]);
        return $req->fetch();
    }
}

if (!function_exists('getLivraisonsParLivreur')) {
    function getLivraisonsParLivreur($cnx, $livreur_id) {
        $req = $cnx->prepare("
            SELECT l.*,c.adresse_livraison,c.total,c.statut AS statut_commande,u.nom AS client_nom
            FROM livraisons l 
            JOIN commandes c ON l.commande_id=c.id
            JOIN users u ON c.user_id=u.id 
            WHERE l.livreur_id=:lid 
            ORDER BY l.updated_at DESC
        ");
        $req->execute([':lid'=>$livreur_id]);
        return $req->fetchAll();
    }
}

if (!function_exists('getAllLivraisons')) {
    function getAllLivraisons($cnx) {
        $req = $cnx->prepare("
            SELECT l.*,c.adresse_livraison,c.total,u.nom AS client_nom,
                   lu.nom AS livreur_nom 
            FROM livraisons l
            JOIN commandes c ON l.commande_id=c.id 
            JOIN users u ON c.user_id=u.id
            LEFT JOIN users lu ON l.livreur_id=lu.id 
            ORDER BY l.updated_at DESC
        ");
        $req->execute();
        return $req->fetchAll();
    }
}

if (!function_exists('assignerLivreur')) {
    function assignerLivreur($cnx, $commande_id, $livreur_id) {
        $chk = $cnx->prepare("SELECT id FROM livraisons WHERE commande_id=:cid");
        $chk->execute([':cid'=>$commande_id]);
        if ($chk->fetch()) {
            $req = $cnx->prepare("UPDATE livraisons SET livreur_id=:lid WHERE commande_id=:cid");
        } else {
            $req = $cnx->prepare("INSERT INTO livraisons (commande_id,livreur_id) VALUES (:cid,:lid)");
        }
        $ok = $req->execute([':cid'=>$commande_id,':lid'=>$livreur_id]);
        if ($ok) updateStatutCommande($cnx, $commande_id, 'en_livraison');
        return $ok;
    }
}

if (!function_exists('updateStatutLivraison')) {
    function updateStatutLivraison($cnx, $livraison_id, $statut, $lat = null, $lng = null) {
        $req = $cnx->prepare("UPDATE livraisons SET statut=:s,latitude=:lat,longitude=:lng WHERE id=:id");
        $ok = $req->execute([':s'=>$statut,':lat'=>$lat,':lng'=>$lng,':id'=>$livraison_id]);
        if ($ok && $statut === 'livree') {
            $chk = $cnx->prepare("SELECT commande_id FROM livraisons WHERE id=:id");
            $chk->execute([':id'=>$livraison_id]);
            $row = $chk->fetch();
            if ($row) updateStatutCommande($cnx, $row['commande_id'], 'livre');
        }
        return $ok;
    }
}

if (!function_exists('accepterLivraison')) {
    function accepterLivraison($cnx, $livraison_id, $livreur_id) {
        $req = $cnx->prepare("UPDATE livraisons SET statut='acceptee' WHERE id=:id AND livreur_id=:lid");
        return $req->execute([':id'=>$livraison_id,':lid'=>$livreur_id]);
    }
}

if (!function_exists('signalerProbleme')) {
    function signalerProbleme($cnx, $livraison_id, $probleme) {
        $req = $cnx->prepare("UPDATE livraisons SET probleme=:p, statut='echec' WHERE id=:id");
        return $req->execute([':p'=>htmlspecialchars($probleme),':id'=>$livraison_id]);
    }
}

// ══════════════════════════════════════════════════════════
//  STOCK / PRODUCTION (extrait)
// ══════════════════════════════════════════════════════════
if (!function_exists('getStockComplet')) {
    function getStockComplet($cnx) {
        $req = $cnx->prepare("
            SELECT s.*,p.nom,p.image,p.categorie_id 
            FROM stock s
            JOIN produits p ON s.produit_id=p.id 
            ORDER BY s.quantite ASC
        ");
        $req->execute();
        return $req->fetchAll();
    }
}

if (!function_exists('updateStock')) {
    function updateStock($cnx, $produit_id, $quantite) {
        $req = $cnx->prepare("UPDATE stock SET quantite=:q WHERE produit_id=:pid");
        $ok = $req->execute([':q'=>$quantite,':pid'=>$produit_id]);
        if ($ok) {
            $cnx->prepare("UPDATE produits SET stock=:q WHERE id=:pid")
                ->execute([':q'=>$quantite,':pid'=>$produit_id]);
        }
        return $ok;
    }
}

if (!function_exists('getOrdresProduction')) {
    function getOrdresProduction($cnx) {
        $req = $cnx->prepare("
            SELECT op.*,p.nom AS produit_nom,u.nom AS demande_par_nom
            FROM ordres_production op 
            JOIN produits p ON op.produit_id=p.id
            LEFT JOIN users u ON op.demande_par=u.id 
            ORDER BY op.created_at DESC
        ");
        $req->execute();
        return $req->fetchAll();
    }
}

if (!function_exists('creerOrdreProduction')) {
    function creerOrdreProduction($cnx, $produit_id, $quantite, $demande_par) {
        $req = $cnx->prepare("
            INSERT INTO ordres_production (produit_id,quantite,demande_par) 
            VALUES (:p,:q,:d)
        ");
        return $req->execute([':p'=>$produit_id,':q'=>$quantite,':d'=>$demande_par]);
    }
}

if (!function_exists('updateOrdreProduction')) {
    function updateOrdreProduction($cnx, $id, $statut) {
        $req = $cnx->prepare("
            UPDATE ordres_production 
            SET statut=:s,termine_at=IF(:s='termine',NOW(),NULL) 
            WHERE id=:id
        ");
        $ok = $req->execute([':s'=>$statut,':id'=>$id]);
        if ($ok && $statut === 'termine') {
            $ordre = $cnx->prepare("SELECT produit_id,quantite FROM ordres_production WHERE id=:id");
            $ordre->execute([':id'=>$id]);
            $row = $ordre->fetch();
            if ($row) {
                $cnx->prepare("UPDATE stock SET quantite=quantite+:q WHERE produit_id=:p")
                    ->execute([':q'=>$row['quantite'],':p'=>$row['produit_id']]);
                $cnx->prepare("UPDATE produits SET stock=stock+:q WHERE id=:p")
                    ->execute([':q'=>$row['quantite'],':p'=>$row['produit_id']]);
            }
        }
        return $ok;
    }
}

// ══════════════════════════════════════════════════════════
//  RÉCLAMATIONS
// ══════════════════════════════════════════════════════════
if (!function_exists('addReclamation')) {
    function addReclamation($cnx, $uid, $sujet, $msg, $cid = null) {
        $req = $cnx->prepare("
            INSERT INTO reclamations (user_id,commande_id,sujet,message) 
            VALUES (:u,:c,:s,:m)
        ");
        return $req->execute([':u'=>$uid,':c'=>$cid,':s'=>htmlspecialchars($sujet),':m'=>htmlspecialchars($msg)]);
    }
}

if (!function_exists('getAllReclamations')) {
    function getAllReclamations($cnx) {
        $req = $cnx->prepare("
            SELECT r.*,u.nom AS client_nom 
            FROM reclamations r
            JOIN users u ON r.user_id=u.id 
            ORDER BY r.created_at DESC
        ");
        $req->execute();
        return $req->fetchAll();
    }
}

if (!function_exists('getReclamationsByUser')) {
    function getReclamationsByUser($cnx, $uid) {
        $req = $cnx->prepare("
            SELECT * FROM reclamations WHERE user_id=:uid 
            ORDER BY created_at DESC
        ");
        $req->execute([':uid'=>$uid]);
        return $req->fetchAll();
    }
}

if (!function_exists('repondreReclamation')) {
    function repondreReclamation($cnx, $id, $reponse, $statut, $par) {
        $req = $cnx->prepare("
            UPDATE reclamations 
            SET reponse=:r,statut=:s,repondu_par=:p 
            WHERE id=:id
        ");
        return $req->execute([':r'=>$reponse,':s'=>$statut,':p'=>$par,':id'=>$id]);
    }
}

if (!function_exists('transmettreReclamationUsine')) {
    function transmettreReclamationUsine($cnx, $id) {
        $req = $cnx->prepare("
            UPDATE reclamations 
            SET statut='transmise_usine',transmis_usine=1 
            WHERE id=:id
        ");
        return $req->execute([':id'=>$id]);
    }
}

if (!function_exists('getReclamationsUsine')) {
    function getReclamationsUsine($cnx) {
        $req = $cnx->prepare("
            SELECT r.*,u.nom AS client_nom 
            FROM reclamations r 
            JOIN users u ON r.user_id=u.id 
            WHERE r.transmis_usine=1 
            ORDER BY r.created_at DESC
        ");
        $req->execute();
        return $req->fetchAll();
    }
}

// ══════════════════════════════════════════════════════════
//  CONSEILS NUTRITIONNELS
// ══════════════════════════════════════════════════════════
if (!function_exists('getAllConseils')) {
    function getAllConseils($cnx, $publicOnly = false) {
        $sql = "
            SELECT c.*,u.nom AS nutri_nom,p.nom AS produit_nom 
            FROM conseils c
            JOIN users u ON c.nutritionniste_id=u.id 
            LEFT JOIN produits p ON c.produit_id=p.id
        ";
        if ($publicOnly) $sql .= " WHERE c.public=1";
        $sql .= " ORDER BY c.created_at DESC";
        $req = $cnx->prepare($sql);
        $req->execute();
        return $req->fetchAll();
    }
}

if (!function_exists('addConseil')) {
    function addConseil($cnx, $nutri_id, $titre, $contenu, $pid = null, $pub = 1) {
        $req = $cnx->prepare("
            INSERT INTO conseils (nutritionniste_id,produit_id,titre,contenu,public) 
            VALUES (:n,:p,:t,:c,:pub)
        ");
        return $req->execute([
            ':n'=>$nutri_id,
            ':p'=>$pid,
            ':t'=>htmlspecialchars($titre),
            ':c'=>htmlspecialchars($contenu),
            ':pub'=>$pub
        ]);
    }
}

if (!function_exists('deleteConseil')) {
    function deleteConseil($cnx, $id) {
        $req = $cnx->prepare("DELETE FROM conseils WHERE id=:id");
        return $req->execute([':id'=>$id]);
    }
}

// ══════════════════════════════════════════════════════════
//  UPLOAD IMAGE
// ══════════════════════════════════════════════════════════
if (!function_exists('uploadImage')) {
    function uploadImage($file, $folder = 'produits') {
        $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
        if (!in_array($file['type'], $allowed) || $file['error'] !== 0) return false;
        $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('img_') . '.' . $ext;
        $dest     = __DIR__ . "/../public/uploads/$folder/$filename";
        if (!is_dir(dirname($dest))) mkdir(dirname($dest), 0755, true);
        return move_uploaded_file($file['tmp_name'], $dest) ? "uploads/$folder/$filename" : false;
    }
}

// ══════════════════════════════════════════════════════════
//  PAGINATION
// ══════════════════════════════════════════════════════════
if (!function_exists('getProduitsPaginated')) {
    function getProduitsPaginated($cnx, $limit, $offset) {
        $stmt = $cnx->prepare("SELECT * FROM produits LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}

if (!function_exists('countProduits')) {
    function countProduits($cnx) {
        $stmt = $cnx->query("SELECT COUNT(*) FROM produits");
        return $stmt->fetchColumn();
    }
}

// ══════════════════════════════════════════════════════════
//  EMAIL HELPERS
// ══════════════════════════════════════════════════════════
if (!function_exists('send_benna_email')) {
    function send_benna_email($type, $user, $extra = []) {
        error_log("Email would be sent for type: $type to user: " . ($user['email'] ?? 'unknown'));
        return true;
    }
}

// ══════════════════════════════════════════════════════════
//  PLANS ALIMENTAIRES (nutritionniste)
// ══════════════════════════════════════════════════════════
if (!function_exists('getAllPlans')) {
    function getAllPlans($cnx, $nutri_id = null) {
        $sql = "SELECT p.*, u.nom AS client_nom, n.nom AS nutri_nom 
                FROM plans_alimentaires p
                JOIN users u ON p.client_id = u.id 
                JOIN users n ON p.nutritionniste_id = n.id";
        if ($nutri_id) {
            $sql .= " WHERE p.nutritionniste_id = :nid";
        }
        $sql .= " ORDER BY p.created_at DESC";
    
        $req = $cnx->prepare($sql);
        if ($nutri_id) {
            $req->execute([':nid' => $nutri_id]);
        } else {
            $req->execute();
        }
        return $req->fetchAll(PDO::FETCH_ASSOC);
    }
}

if (!function_exists('getPlanById')) {
    function getPlanById($cnx, $id) {
        $req = $cnx->prepare("
            SELECT p.*, u.nom AS client_nom 
            FROM plans_alimentaires p 
            JOIN users u ON p.client_id = u.id 
            WHERE p.id = :id
        ");
        $req->execute([':id' => $id]);
        $plan = $req->fetch(PDO::FETCH_ASSOC);
    
        if ($plan) {
            $rep = $cnx->prepare("
                SELECT * FROM plan_repas 
                WHERE plan_id = :id 
                ORDER BY FIELD(jour,'Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi','Dimanche'), 
                         FIELD(moment,'matin','midi','soir','collation')
            ");
            $rep->execute([':id' => $id]);
            $plan['repas'] = $rep->fetchAll(PDO::FETCH_ASSOC);
        }
        return $plan;
    }
}

if (!function_exists('createPlan')) {
    function createPlan($cnx, $nutri_id, $client_id, $titre, $objectif) {
        $req = $cnx->prepare("
            INSERT INTO plans_alimentaires (nutritionniste_id, client_id, titre, objectif, created_at) 
            VALUES (:n, :c, :t, :o, NOW())
        ");
        $ok = $req->execute([
            ':n' => $nutri_id,
            ':c' => $client_id,
            ':t' => htmlspecialchars($titre),
            ':o' => htmlspecialchars($objectif)
        ]);
        return $ok ? $cnx->lastInsertId() : false;
    }
}

if (!function_exists('addRepas')) {
    function addRepas($cnx, $plan_id, $jour, $moment, $description, $calories = 0) {
        $req = $cnx->prepare("
            INSERT INTO plan_repas (plan_id, jour, moment, description, calories) 
            VALUES (:p, :j, :m, :d, :c)
        ");
        return $req->execute([
            ':p' => $plan_id,
            ':j' => $jour,
            ':m' => $moment,
            ':d' => htmlspecialchars($description),
            ':c' => (int)$calories
        ]);
    }
}

if (!function_exists('deleteRepas')) {
    function deleteRepas($cnx, $repas_id) {
        $req = $cnx->prepare("DELETE FROM plan_repas WHERE id = :id");
        return $req->execute([':id' => $repas_id]);
    }
}

if (!function_exists('deletePlan')) {
    function deletePlan($cnx, $plan_id) {
        $cnx->prepare("DELETE FROM plan_repas WHERE plan_id = :id")->execute([':id' => $plan_id]);
        $req = $cnx->prepare("DELETE FROM plans_alimentaires WHERE id = :id");
        return $req->execute([':id' => $plan_id]);
    }
}

// ══════════════════════════════════════════════════════════
//  CONSULTATIONS (nutritionniste)
// ══════════════════════════════════════════════════════════
if (!function_exists('getConsultationsNutri')) {
    function getConsultationsNutri($cnx, $nutri_id) {
        $stmt = $cnx->prepare("
            SELECT c.*,
                   u.nom   AS client_nom,
                   u.email AS client_email
            FROM consultations c
            JOIN users u ON u.id = c.client_id
            WHERE c.nutritionniste_id = :nid
            ORDER BY c.date_heure DESC
        ");
        $stmt->execute([':nid' => $nutri_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// ══════════════════════════════════════════════════════════
//  CLIENTS (pour nutritionniste)
// ══════════════════════════════════════════════════════════
if (!function_exists('getClientsAvecCommandes')) {
    function getClientsAvecCommandes($cnx) {
        $req = $cnx->prepare("
            SELECT u.*, 
                   COUNT(DISTINCT c.id)    AS nb_commandes, 
                   COALESCE(SUM(c.total),0) AS total_depense,
                   COUNT(DISTINCT p.id)    AS nb_plans,
                   COUNT(DISTINCT cons.id) AS nb_consultations
            FROM users u 
            LEFT JOIN commandes c          ON u.id = c.user_id 
            LEFT JOIN plans_alimentaires p ON u.id = p.client_id
            LEFT JOIN consultations cons   ON u.id = cons.client_id
            WHERE u.role = 'client' 
            GROUP BY u.id 
            ORDER BY nb_commandes DESC
        ");
        $req->execute();
        return $req->fetchAll(PDO::FETCH_ASSOC);
    }
}

// ══════════════════════════════════════════════════════════
//  ALERTES NUTRITIONNELLES
// ══════════════════════════════════════════════════════════
if (!function_exists('getAlertesNutrition')) {
    function getAlertesNutrition($cnx, $client_id = null) {
        if ($client_id) {
            $req = $cnx->prepare("
                SELECT a.*, u.nom AS client_nom, n.nom AS nutri_nom 
                FROM alertes_nutritionnelles a 
                LEFT JOIN users u ON a.client_id = u.id 
                JOIN users n ON a.nutritionniste_id = n.id 
                WHERE a.client_id = :c OR a.client_id IS NULL
                ORDER BY a.created_at DESC
            ");
            $req->execute([':c' => $client_id]);
        } else {
            $req = $cnx->prepare("
                SELECT a.*, u.nom AS client_nom, n.nom AS nutri_nom 
                FROM alertes_nutritionnelles a 
                LEFT JOIN users u ON a.client_id = u.id 
                JOIN users n ON a.nutritionniste_id = n.id 
                ORDER BY a.created_at DESC
                LIMIT 20
            ");
            $req->execute();
        }
        return $req->fetchAll(PDO::FETCH_ASSOC);
    }
}

if (!function_exists('addAlerteNutrition')) {
    function addAlerteNutrition($cnx, $nutri_id, $client_id, $titre, $message, $gravite = 'info') {
        $req = $cnx->prepare("
            INSERT INTO alertes_nutritionnelles (nutritionniste_id, client_id, titre, message, gravite, created_at) 
            VALUES (:n, :c, :t, :m, :g, NOW())
        ");
        return $req->execute([
            ':n' => $nutri_id,
            ':c' => $client_id ?: null,
            ':t' => htmlspecialchars($titre),
            ':m' => htmlspecialchars($message),
            ':g' => $gravite
        ]);
    }
}

// ══════════════════════════════════════════════════════════
//  VALIDATION PRODUITS (nutritionniste)
// ══════════════════════════════════════════════════════════
if (!function_exists('getProduitsPourValidation')) {
    function getProduitsPourValidation($cnx) {
        $req = $cnx->prepare("
            SELECT p.*, c.nom AS cat_nom 
            FROM produits p 
            LEFT JOIN categories c ON p.categorie_id = c.id 
            WHERE p.est_valide = 0 AND p.est_actif = 1
            ORDER BY p.created_at DESC
        ");
        $req->execute();
        return $req->fetchAll(PDO::FETCH_ASSOC);
    }
}

if (!function_exists('validerProduit')) {
    function validerProduit($cnx, $produit_id) {
        $req = $cnx->prepare("
            UPDATE produits 
            SET est_valide = 1, est_actif = 1 
            WHERE id = :id
        ");
        return $req->execute([':id' => $produit_id]);
    }
}

if (!function_exists('rejeterProduit')) {
    function rejeterProduit($cnx, $produit_id, $motif = '') {
        $req = $cnx->prepare("UPDATE produits SET est_valide=-1, est_actif=0 WHERE id=:id");
        return $req->execute([':id' => $produit_id]);
    }
}

if (!function_exists('getProduitsRejetes')) {
    function getProduitsRejetes($cnx) {
        $req = $cnx->prepare("SELECT p.*,c.nom AS cat_nom FROM produits p LEFT JOIN categories c ON p.categorie_id=c.id WHERE p.est_valide=-1 ORDER BY p.created_at DESC");
        $req->execute();
        return $req->fetchAll();
    }
}

// ══════════════════════════════════════════════════════════
//  TRAITEMENT DES ACTIONS (add_avis, valider_avis, delete_avis)
// ══════════════════════════════════════════════════════════
if (isset($_GET['action']) && basename($_SERVER['SCRIPT_NAME']) == 'traitement.php') {
    $action = $_GET['action'];
    if ($action === 'add_avis' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        session_start();
        if (!isset($_SESSION['user_id'])) die("Non autorisé");
        $uid = $_SESSION['user_id'];
        $pid = (int)$_POST['produit_id'];
        $note = (int)$_POST['note'];
        $comment = $_POST['commentaire'] ?? '';
        if ($note >= 1 && $note <= 5 && $pid > 0) {
            addAvis($cnx, $uid, $pid, $note, $comment);
            $_SESSION['pay_success'] = "Merci pour votre avis ! Il sera publié après validation.";
        }
        header("Location: " . BASE . "/view/client/mes_commandes.php");
        exit();
    }
    if ($action === 'valider_avis' && isset($_GET['id'])) {
        session_start();
        $id = (int)$_GET['id'];
        validerAvis($cnx, $id);
        header("Location: " . $_SERVER['HTTP_REFERER'] ?? BASE . "/view/admin/avis.php");
        exit();
    }
    if ($action === 'delete_avis' && isset($_GET['id'])) {
        session_start();
        $id = (int)$_GET['id'];
        deleteAvis($cnx, $id);
        header("Location: " . $_SERVER['HTTP_REFERER'] ?? BASE . "/view/admin/avis.php");
        exit();
    }
}