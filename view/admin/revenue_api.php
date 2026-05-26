<?php
header('Content-Type: application/json');
require_once __DIR__ . "/../../config/database.php";

// Use the same connection as your dashboard
global $cnx;
if (!isset($cnx) || !$cnx) {
    $cnx = new PDO("mysql:host=localhost;dbname=benna_db;charset=utf8", 'root', '');
    $cnx->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}

// Force MySQL to use Tunis timezone
$cnx->exec("SET time_zone = '+01:00'");

// Now your original query works perfectly with Tunis dates
$sparkline = $cnx->query("
    SELECT DATE(date_commande) AS jour, COALESCE(SUM(total),0) AS rev
    FROM commandes
    WHERE paiement_statut = 'paye' 
      AND date_commande >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY jour ORDER BY jour
")->fetchAll(PDO::FETCH_KEY_PAIR);

$sparkVals = [];
$sparkLabels = [];
for ($d = 29; $d >= 0; $d--) {
    $key = date('Y-m-d', strtotime("-$d days"));
    $sparkVals[] = round((float)($sparkline[$key] ?? 0), 3);
    $sparkLabels[] = date('d/m', strtotime("-$d days"));
}

echo json_encode(['labels' => $sparkLabels, 'values' => $sparkVals]);