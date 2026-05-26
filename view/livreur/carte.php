<?php
$pageTitle = "Carte des tournées";
include "partials/livreur_header.php";
require_once __DIR__ . "/../../config/database.php";

// $cnx, $livreur, $id already set

// Fetch stops for this livreur with GROUP BY to avoid duplicates
$sql = "
    SELECT
        l.id                        AS id,
        u.nom                       AS client,
        c.adresse_livraison         AS adresse,
        COALESCE(l.latitude,  0)    AS lat,
        COALESCE(l.longitude, 0)    AS lng,
        l.statut                    AS statut,
        DATE_FORMAT(c.date_livraison_estimee, '%H:%i') AS heure
    FROM livraisons l
    JOIN commandes  c ON c.id      = l.commande_id
    JOIN users      u ON u.id      = c.user_id
    WHERE l.livreur_id = ?
      AND l.statut NOT IN ('livree', 'echec')
    GROUP BY l.id
    ORDER BY c.date_livraison_estimee ASC
";
$stmtStops = $cnx->prepare($sql);
$stmtStops->execute([$id]);
$stops = $stmtStops->fetchAll(PDO::FETCH_ASSOC);

foreach ($stops as &$s) {
    if (!in_array($s['statut'], ['assignee','acceptee','en_cours'])) {
        $s['statut'] = 'en_attente';
    }
    $s['heure'] = $s['heure'] ?: '--:--';
}
unset($s);
?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
.content-grid  { display:grid; grid-template-columns:1fr 320px; gap:20px; }
.map-card      { background:var(--card-bg); border-radius:var(--radius); border:1px solid var(--border); overflow:hidden; display:flex; flex-direction:column; }
.map-card-hdr  { padding:14px 20px; border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; }
#map           { height:560px; }
.stops-card    { background:var(--card-bg); border-radius:var(--radius); border:1px solid var(--border); overflow:hidden; display:flex; flex-direction:column; }
.stops-hdr     { padding:14px 20px; border-bottom:1px solid var(--border); font-size:0.875rem; font-weight:700; color:var(--text); }
.stops-list    { flex:1; overflow-y:auto; max-height:560px; }
.stop-item     { padding:14px 18px; border-bottom:1px solid var(--border); cursor:pointer; transition:background 0.12s; }
.stop-item:last-child { border-bottom:none; }
.stop-item:hover { background:#f7faf7; }
.stop-num      { width:26px; height:26px; border-radius:50%; background:var(--accent); color:#fff; font-size:12px; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.stop-num.enc  { background:var(--blue); }
.nav-btn       { display:block; margin-top:8px; padding:7px 12px; background:var(--accent); color:#fff; border:none; border-radius:8px; font-family:inherit; font-size:12px; font-weight:700; cursor:pointer; text-align:center; text-decoration:none; transition:background 0.15s; }
.nav-btn:hover { background:var(--accent-hover); }
</style>

<div class="topbar">
    <h1>Carte des Tournées</h1>
    <span class="pill"><i class="fas fa-map-marked-alt"></i> <?= count($stops) ?> arrêt<?= count($stops) !== 1 ? 's' : '' ?></span>
</div>

<div class="page-body">
<div class="content-grid">

    <!-- MAP -->
    <div class="map-card">
        <div class="map-card-hdr">
            <span style="font-weight:700; font-size:0.875rem;"><i class="fas fa-route" style="color:var(--accent);"></i> Itinéraire du jour</span>
            <span class="badge badge-gray"><?= count($stops) ?> arrêt<?= count($stops) !== 1 ? 's' : '' ?></span>
        </div>
        <div id="map"></div>
    </div>

    <!-- STOPS -->
    <div class="stops-card">
        <div class="stops-hdr"><i class="fas fa-map-marker-alt" style="color:var(--accent);"></i> Arrêts de la tournée</div>
        <div class="stops-list">
            <?php if (empty($stops)): ?>
                <div style="text-align:center; padding:40px 16px; color:var(--muted);">
                    <div style="font-size:0.875rem;">Aucune livraison en cours aujourd'hui.</div>
                </div>
            <?php else: ?>
                <?php foreach ($stops as $i => $stop): ?>
                <div class="stop-item"
                     onclick="focusStop(<?= (float)$stop['lat'] ?>, <?= (float)$stop['lng'] ?>, '<?= htmlspecialchars($stop['client'], ENT_QUOTES) ?>')">
                    <div style="display:flex; align-items:center; gap:10px; margin-bottom:6px;">
                        <div class="stop-num <?= $stop['statut'] === 'en_cours' ? 'enc' : '' ?>"><?= $i + 1 ?></div>
                        <div style="font-weight:700; font-size:0.875rem; color:var(--text);">
                            <?= htmlspecialchars($stop['client']) ?>
                        </div>
                    </div>
                    <div style="font-size:0.78rem; color:var(--muted); margin-left:36px;">
                        <i class="fas fa-map-marker-alt" style="color:var(--accent);"></i>
                        <?= htmlspecialchars($stop['adresse']) ?>
                    </div>
                    <div style="display:flex; align-items:center; gap:8px; margin-top:5px; margin-left:36px;">
                        <span class="badge badge-gray"><i class="fas fa-clock"></i> <?= htmlspecialchars($stop['heure']) ?></span>
                        <?php if ($stop['statut'] === 'en_cours'): ?>
                            <span class="badge badge-blue">En cours</span>
                        <?php else: ?>
                            <span class="badge badge-yellow">En attente</span>
                        <?php endif; ?>
                    </div>
                    <?php if ($stop['lat'] != 0 && $stop['lng'] != 0): ?>
                    <a class="nav-btn"
                       href="https://www.google.com/maps/dir/?api=1&destination=<?= (float)$stop['lat'] ?>,<?= (float)$stop['lng'] ?>"
                       target="_blank">
                        <i class="fas fa-compass"></i> Naviguer
                    </a>
                    <?php else: ?>
                    <a class="nav-btn"
                       href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($stop['adresse']) ?>"
                       target="_blank">
                        <i class="fas fa-search"></i> Rechercher l'adresse
                    </a>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</div>
</div><!-- /.page-body -->

<script>
const stops = <?= json_encode(array_values($stops)) ?>;
const defaultCenter = [36.8178, 10.1718];
const map = L.map('map').setView(defaultCenter, 12);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors'
}).addTo(map);

const latlngs = [];
stops.forEach((stop, i) => {
    const lat = parseFloat(stop.lat);
    const lng = parseFloat(stop.lng);
    if (lat === 0 && lng === 0) return; // Ignore les points sans coordonnées

    const color = stop.statut === 'en_cours' ? '#2563eb' : '#3d7a3d';
    const icon = L.divIcon({
        html: `<div style="background:${color};color:#fff;width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;border:2px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,0.2)">${i+1}</div>`,
        className: '',
        iconAnchor: [14, 14]
    });
    const marker = L.marker([lat, lng], { icon }).addTo(map);
    marker.bindPopup(`<b>${stop.client}</b><br>${stop.adresse}<br>⏰ ${stop.heure}`);
    latlngs.push([lat, lng]);
});

if (latlngs.length > 1) {
    L.polyline(latlngs, { color: '#3d7a3d', weight: 3, dashArray: '6,8', opacity: 0.7 }).addTo(map);
    map.fitBounds(latlngs, { padding: [40, 40] });
} else if (latlngs.length === 1) {
    map.setView(latlngs[0], 14);
}

function focusStop(lat, lng, name) {
    if (lat === 0 && lng === 0) return;
    map.setView([lat, lng], 15);
}
</script>

<?php include "partials/livreur_footer.php"; ?>