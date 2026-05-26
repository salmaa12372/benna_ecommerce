<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../config/app.php";
require_once __DIR__ . "/../../controller/traitement.php";

$pageTitle = "Composer ma box";
$extraCss  = "stylePro.css";
include "partials/header.php";
include_once __DIR__ . "/../../controller/traitement.php";

$limit  = 15;
$page   = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

$totalProduits = (int)$cnx->query("SELECT COUNT(*) FROM produits WHERE est_actif=1 AND stock>0 AND (description != 'box' OR description IS NULL)")->fetchColumn();$totalPages    = max(1, ceil($totalProduits / $limit));

$stmt = $cnx->prepare("SELECT * FROM produits WHERE est_actif=1 AND stock>0 AND (description != 'box' OR description IS NULL) ORDER BY nom LIMIT :lim OFFSET :off");
$stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
$stmt->bindValue(':off', $offset, PDO::PARAM_INT);
$stmt->execute();
$produits = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
/* ── BOX PAGE ── */
*{  font-family:var(--font-display);}
.box-hero {
  font-family:var(--font-display);
  background: linear-gradient(145deg,#0f2e1f,#1c3d2a,#2c2c1f);
  color:#fff;
  text-align:center;
  padding:80px 20px 50px;
}
.box-hero h1 { font-size:2.4rem; margin-bottom:8px; }
.box-hero p  { opacity:.8; font-size:1rem; }

.box-counter-bar {
  position: sticky;
  top: 70px;
  z-index: 100;
  background: #fff;
  border-bottom: 2px solid #eaf3de;
  padding: 14px 24px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  box-shadow: 0 2px 12px rgba(0,0,0,.07);
}
.box-counter-bar .slots {
  display: flex;
  gap: 8px;
}
body.dark { background: #0d1f0e !important; border-color: #1e3a1e !important; }
.slot {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  border: 2px dashed #aaa;
  background: #f5f5f5;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  transition: all .25s;
}
.slot.filled {
  border: 2px solid #2f5d3a;
  background: #eaf3de;
}
.slot.filled::after { content: '✓'; color: #2f5d3a; font-weight: 700; font-size: 14px; }
.box-validate-btn {
  padding: 10px 28px;
  font-family:var(--font-display);
  background: #2f5d3a;
  color: #fff;
  border: none;
  border-radius: 30px;
  font-weight: 700;
  cursor: pointer;
  font-size: .95rem;
  transition: .2s;
  opacity: .4;
  pointer-events: none;
}
.box-validate-btn.ready {
  opacity: 1;
  pointer-events: all;
  animation: pulse-green .6s ease;
}
@keyframes pulse-green {
  0%   { box-shadow: 0 0 0 0 rgba(47,93,58,.5); }
  70%  { box-shadow: 0 0 0 12px rgba(47,93,58,0); }
  100% { box-shadow: 0 0 0 0 rgba(47,93,58,0); }
}

/* ── UNIFORM PRODUCT GRID ── */
.box-grid {
  display: grid;
  font-family:var(--font-display);
  grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
  gap: 20px;
  padding: 32px 24px;
  max-width: 1300px;
  margin: 0 auto;
}
.box-card {
  background: #fff;
  border-radius: 16px;
  border: 2px solid #eee;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  transition: transform .2s, border-color .2s, box-shadow .2s;
  cursor: pointer;
  height: 100%;
}
.box-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 30px rgba(0,0,0,.1);
}
.box-card.selected {
  border-color: #2f5d3a;
  box-shadow: 0 0 0 3px rgba(47,93,58,.15);
}
.box-card-img {
  width: 100%;
  height: 180px;
  object-fit: contain;
  display: block;
  flex-shrink: 0;
}
.box-card-body {
  padding: 14px;
  flex: 1;
  display: flex;
  flex-direction: column;
}
.box-card-name {
  font-size: .95rem;
  font-weight: 600;
  color: #1a2e1a;
  margin-bottom: 5px;
  line-height: 1.3;
  min-height: 2.6em;
}
.box-card-price {
  color: #2e7d32;
  font-weight: 700;
  font-size: .95rem;
  margin-bottom: 12px;
}
.box-card-btn {
  margin-top: auto;
  width: 100%;
  padding: 9px;
  border: 2px solid #2f5d3a;
  background: transparent;
  color: #2f5d3a;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  transition: .2s;
  font-size: .85rem;
}
.box-card.selected .box-card-btn {
  background: #c0392b;
  border-color: #c0392b;
  color: #fff;
}
.box-card-btn:hover { opacity: .85; }

/* ── MYSTERY BOX PAGE ── */
.mystery-hero {
  background: linear-gradient(145deg,#0f2e1f,#1c3d2a,#2c2c1f);
  color: #fff;
  text-align: center;
  padding: 100px 24px 60px;
}
.mystery-hero h1 { font-size: 2.6rem; margin-bottom: 12px; }
.mystery-hero p  { opacity: .75; max-width: 520px; margin: 0 auto 28px; line-height: 1.7; }
.mystery-cta {
  display: inline-block;
  padding: 14px 34px;
  background: #f5b642;
  color: #1a1a1a;
  border-radius: 30px;
  font-weight: 700;
  font-size: 1rem;
  text-decoration: none;
  transition: .2s;
}
.mystery-cta:hover { background: #e0a030; transform: translateY(-2px); }
.mystery-features {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 24px;
  padding: 48px 24px;
  max-width: 900px;
  margin: 0 auto;
}
.mystery-feat {
  background: #fff;
  border-radius: 16px;
  border: 1px solid #eee;
  padding: 28px 24px;
  text-align: center;
  width: 220px;
  box-shadow: 0 4px 16px rgba(0,0,0,.06);
}
.mystery-feat .mf-icon { font-size: 2.2rem; margin-bottom: 12px; }
.mystery-feat h4 { font-size: 1rem; color: #1a2e1a; margin-bottom: 6px; }
.mystery-feat p  { font-size: .83rem; color: #666; line-height: 1.5; }
.mystery-question {
  background: #f5f0e6;
  border-radius: 20px;
  padding: 40px;
  text-align: center;
  margin: 0 24px 40px;
  font-size: 1.1rem;
  color: #555;
}
.mystery-question .big { font-size: 4rem; display: block; margin-bottom: 16px; }
</style>

<?php if (isset($_GET['type']) && $_GET['type'] === 'mystery'): ?>
<!-- ════════════════ MYSTERY BOX PAGE ════════════════ -->
<div class="mystery-hero">
  <h1> Boîte Mystère Benna</h1>
  <p style="font-size: 1.1rem;">Laissez-nous vous surprendre !<br>
     Notre équipe prépare une box unique avec des produits sains soigneusement sélectionnés selon la saison et votre profil.</p>
     
<div style="display:inline-block;background:rgba(255,255,255,0.12);border:2px solid rgba(255,255,255,0.3);border-radius:16px;padding:14px 36px;margin-bottom:24px;">
  <span style="display:block;font-size:.85rem;opacity:.75;margin-bottom:4px;letter-spacing:.05em;text-transform:uppercase;">Prix</span>
  <span style="font-size:2.2rem;font-weight:800;color:#f5b642;">70 TND</span>
</div>
<br>
  <a href="<?= BASE ?>/controller/panier_controller.php?action=add_prebuilt&type=mystery" class="mystery-cta">
    Je veux ma boîte mystère →
  </a>
  <a href="<?= BASE ?>/view/client/produits.php" 
     style="display:inline-block;margin-top:16px;padding:10px 24px;background:rgba(255,255,255,0.15);color:#fff;border:2px solid rgba(255,255,255,0.5);border-radius:30px;text-decoration:none;font-weight:600;transition:.2s;"
     onmouseover="this.style.background='rgba(255,255,255,0.25)'"
     onmouseout="this.style.background='rgba(255,255,255,0.15)'">
    ← Retour à la boutique
  </a>
</div>

<div class="mystery-features">
  <div class="mystery-feat">
<img  src="<?= BASE ?>/public/uploads/leaf.png"
  alt="natural" 
  style="width: 60px; height: 60px; margin: 0 auto 12px; display: block;"  > 
   <h4>100% Naturel</h4>
    <p>Tous les produits sont sains, sans additifs et faits maison</p>
  </div>
  <div class="mystery-feat">
<img  src="<?= BASE ?>/public/uploads/unique.png"
  alt="unique" 
  style="width: 60px; height: 60px; margin: 0 auto 12px; display: block;"
>    <h4>Unique pour chaque client</h4>
    <p>Chaque box est différente, jamais la même chose deux fois</p>
  </div>
  <div class="mystery-feat">
<img 
  src="<?= BASE ?>/public/uploads/cadeau.png"
  alt="cadeau" 
  style="width: 60px; height: 60px; margin: 0 auto 12px; display: block;">    <h4>Surprise garantie</h4>
    <p>Un produit cadeau est toujours inclus dans chaque boîte</p>
  </div>
  <div class="mystery-feat">
<img 
  src="<?= BASE ?>/public/uploads/livraison.png"
  alt="Livraison" 
  style="width: 60px; height: 60px; margin: 0 auto 12px; display: block;">
    <h4>Livraison 24/48h</h4>
    <p>Votre box arrive fraîche et emballée avec soin</p>
  </div>
</div>

<div class="mystery-question" style="max-width:700px; margin:0 auto 60px;background:#fffafa ;">
  <span class="big"> <img src="<?= BASE ?>/public/uploads/question2.png" alt="Question" style="width: 60px; height: 60px; margin: 0 auto 12px; display: block;"></span>
  Qu'est-ce qu'il y a dedans ? C'est le mystère ! Notre équipe choisit 4 à 6 produits parmi nos meilleurs snacks de la semaine. Vous ne saurez qu'à la livraison 
  <br>  c'est tout l'amusement !
</div>

<?php else: ?>
<!-- ════════════════ BUILD YOUR BOX PAGE ════════════════ -->
<?php if (isset($_GET['type'])): ?>

<?php
$boxType  = $_GET['type'];
$typeLabels = [
  'sans-gluten'  => ['label'=>'Sans gluten',  'desc'=>'Tous nos produits sans gluten, idéaux pour les personnes intolérantes ou sensibles'],
  'sans-lactose' => ['label'=>'Sans lactose', 'desc'=>'Sélection sans produits laitiers — parfaite pour les intolérants au lactose'],
  'sans-sucre'   => ['label'=>'Sans sucre',   'desc'=>'Aucun sucre ajouté — pour ceux qui surveillent leur glycémie'],
  'vegan'        => ['label'=>'Vegan',         'desc'=>'100% végétal, aucun produit d\'origine animale'],
  'bio'          => ['label'=>'Bio',           'desc'=>'Ingrédients cultivés biologiquement, sans pesticides'],
  'protein-rich' => ['label'=>'Sport',         'desc'=>'Riche en protéines pour soutenir vos entraînements'],
  'low-calorie'  => ['label'=>'Low Calorie',   'desc'=>'Faible en calories pour garder la ligne sans se priver'],
];

$boxContents = [
  'sans-gluten'  => [
    'petite'  => ['items'=>'Biscuits au chocolat, barre énergétique, farine d\'amande',                                                                                       'count'=>3],
    'moyenne' => ['items'=>'Biscuits sans sucre au chocolat, barre énergétique, farine d\'amande, préparation pour pancakes, semoule sans gluten, pain sans gluten',          'count'=>6],
    'grande'  => ['items'=>'Biscuits sans sucre au chocolat, barre énergétique, farine d\'amande, préparation pour pancakes, semoule sans gluten, pain sans gluten, préparation pour mloukhia, graines de chia + biscuits variés', 'count'=>10],
  ],
  'sans-lactose' => [
    'petite'  => ['items'=>'Lait de coco artisanal, crackers olive et romarin, lait sans lactose',                                                                            'count'=>3],
    'moyenne' => ['items'=>'Lait de coco, crackers olive et romarin, yaourt végétal, pain aux dattes et amandes, 2 boissons lactées',                                         'count'=>6],
    'grande'  => ['items'=>'Lait de coco (plusieurs formats), crackers, pain aux dattes et amandes, smoothies verts, jus de fruits naturels',                                 'count'=>10],
  ],
  'sans-sucre'   => [
    'petite'  => ['items'=>'Pain aux dattes et amandes, baklawa healthy, graines de chia',                                                                                    'count'=>3],
    'moyenne' => ['items'=>'Muffin sans sucre, biscuits, barre énergétique aux dattes, avoine sans sucre, graines de chia, smoothie vert',                                    'count'=>6],
    'grande'  => ['items'=>'Biscuits variés, boules d\'énergie, pain complet, quinoa, baklawa healthy, smoothies détox et fruits rouges',                                     'count'=>10],
  ],
  'vegan'        => [
    'petite'  => ['items'=>'Lait d\'avoine, granola au zaatar, graines de lin',                                                                                               'count'=>3],
    'moyenne' => ['items'=>'Lait d\'avoine, granola au zaatar, biscuits aux dattes, smoothie détox vert',                                                                     'count'=>6],
    'grande'  => ['items'=>'Lait d\'avoine (plusieurs unités), biscuits aux dattes, granola au zaatar, beurre de noisette, smoothie, graines de lin',                         'count'=>10],
  ],
  'bio'          => [
    'petite'  => ['items'=>'Crackers olive et romarin, pain aux dattes et amandes, smoothie vert bio',                                                                        'count'=>3],
    'moyenne' => ['items'=>'Crackers, lait de coco, smoothie vert, granola au zaatar, préparation pour couscous bio, beurre de noisette',                                     'count'=>6],
    'grande'  => ['items'=>'Crackers, pain complet, lait de coco, smoothie vert, beurre de pistache, beurre de noisette, graines de chia + graines bio variées',             'count'=>10],
  ],
  'protein-rich' => [
    'petite'  => ['items'=>'Barre protéinée amande et miel, shake protéiné, barre énergétique aux dattes',                                                                    'count'=>3],
    'moyenne' => ['items'=>'Barre protéinée, jus de carotte naturel, shake protéiné, mélange de noix grillées, granola fitness, barre énergétique',                          'count'=>6],
    'grande'  => ['items'=>'Barre protéinée, shake vanille, chips de patate douce, mélange de noix, smoothie banane, bol énergétique (quinoa/poulet/légumes), granola fitness', 'count'=>10],
  ],
  'low-calorie'  => [
    'petite'  => ['items'=>'Jus de concombre et menthe, shake protéiné léger, galettes de riz nature',                                                                        'count'=>3],
    'moyenne' => ['items'=>'Boules d\'énergie légères, jus de concombre et menthe, mini salade de quinoa, gaspacho de légumes, galettes de riz, shake',                      'count'=>6],
    'grande'  => ['items'=>'Jus concombre/menthe, boules d\'énergie, mini lentilles, gaspacho, soufflés légers, pudding aux graines de chia, salade méditerranéenne',        'count'=>10],
  ],
];

$info    = $typeLabels[$boxType]   ?? ['label'=>ucfirst($boxType), 'desc'=>'Sélection santé'];
$sizes   = $boxContents[$boxType]  ?? [];

$sizeConfig = [
  'petite'  => ['label'=>'Petite Box',  'price'=>'12.900', 'badge'=>'', 'highlight'=>false],
  'moyenne' => ['label'=>'Moyenne Box', 'price'=>'22.900', 'badge'=>' Populaire', 'highlight'=>true],
  'grande'  => ['label'=>'Grande Box',  'price'=>'34.900', 'badge'=>' Meilleure valeur', 'highlight'=>false],
];

$boxImages = [
  'sans-gluten'  => ['petite'=>'box glutten petit.jpg',    'moyenne'=>'box glutten moyen.jpg',    'grande'=>'box glutten grande.jpg'],
  'sans-lactose' => ['petite'=>'box sans lactose petite.jpg', 'moyenne'=>'box sanslactose moyenne.jpg','grande'=>'box sans lactose grande.jpg'],
  'sans-sucre'   => ['petite'=>'box sans sucre petite.png',   'moyenne'=>'box sans sucre moyenne.png',  'grande'=>'box sans sucre grande.png'],
  'vegan'        => ['petite'=>'box vegan petit.jpg',        'moyenne'=>'box vegan moyen.jpg',        'grande'=>'box vegan grande.jpg'],
  'bio'          => ['petite'=>'box bio petite.jpg',         'moyenne'=>'box bio moyen.jpg',        'grande'=>'box bio grande.jpg'],
  'protein-rich' => ['petite'=>'box sport petite.jpg',       'moyenne'=>'box sport moyenne.jpg',      'grande'=>'box sport grande.jpg'],
  'low-calorie'  => ['petite'=>'box lowcalorie petite.jpg',  'moyenne'=>'box lowcalorie moyenne.jpg', 'grande'=>'box lowcalorie grande.jpg'],
];
$boxImg = $boxImages[$boxType] ?? 'box_glutten.jpg';

?>

<style>
.box-tiers {
  display: flex;
  flex-wrap: wrap;
  gap: 28px;
  
  justify-content: center;
  padding: 48px 24px;
  max-width: 1100px;
  margin: 0 auto;
}
.tier-img {
  width: 100%;
  height: 240px;
  object-fit: cover;
  border-radius: 12px;
  display: block;
}

.box-tier-card {
  background: #fff;
  border: 2px solid #e0e0e0;
  border-radius: 20px;
  padding: 32px 28px 28px;
  width: 300px;
  display: flex;
  flex-direction: column;
  gap: 7px;
  box-shadow: 0 4px 18px rgba(0,0,0,.06);
  transition: transform .2s, box-shadow .2s;
  position: relative;
}
.box-tier-card:hover { transform: translateY(-5px); box-shadow: 0 12px 32px rgba(0,0,0,.12); }
.box-tier-card.highlighted { border-color: #2f5d3a; box-shadow: 0 6px 28px rgba(47,93,58,.18); }
.tier-badge {
  position: absolute;
  top: -14px;
  left: 50%;
  transform: translateX(-50%);
  background: #2f5d3a;
  color: #fff;
  font-size: .78rem;
  font-weight: 700;
  padding: 5px 16px;
  border-radius: 20px;
  white-space: nowrap;
}
.tier-title { font-size: 1.25rem; font-weight: 700; color: #1a2e1a; text-align: center; margin-top: 6px; }
.tier-count { text-align: center; color: #888; font-size: .88rem; }
.tier-price { font-size: 1.7rem; font-weight: 800; color: #2f5d3a; text-align: center; }
.tier-items {
  background: #f8faf5;
  border-radius: 12px;
  padding: 14px 16px;
  font-size: .78rem;  color: #444;
  line-height: 1.7;
  flex: 1;
}
.tier-items ul { margin: 0; padding: 0 0 0 16px; }
.tier-items li { margin-bottom: 4px; }
.tier-btn {
  display: block;
  width: 100%;
  padding: 12px;
  border-radius: 30px;
  font-weight: 700;
  font-size: .95rem;
  text-align: center;
  border: 2px solid #2f5d3a;
  background: transparent;
  color: #2f5d3a;
  text-decoration: none;
  transition: .2s;
  cursor: pointer;
}
.box-tier-card.highlighted .tier-btn { background: #2f5d3a; color: #fff; }
.tier-btn:hover { background: #2f5d3a; color: #fff; }
</style>

<div class="box-hero">
  <h1><?= htmlspecialchars($info['label'] ?? '') ?></h1>
  <p><?= htmlspecialchars($info['desc'] ?? '') ?></p>
  <a href="<?= BASE ?>/view/client/produits.php" 
     style="display:inline-block;margin-top:16px;padding:10px 24px;background:rgba(255,255,255,0.15);color:#fff;border:2px solid rgba(255,255,255,0.5);border-radius:30px;text-decoration:none;font-weight:600;transition:.2s;"
     onmouseover="this.style.background='rgba(255,255,255,0.25)'"
     onmouseout="this.style.background='rgba(255,255,255,0.15)'">
    ← Retour à la boutique
  </a>
</div>

<div class="box-tiers">
  <?php foreach ($sizeConfig as $sizeKey => $cfg):
    if (empty($sizes[$sizeKey])) continue;
    $data = $sizes[$sizeKey];
    $items = array_map('trim', explode(',', $data['items']));
    $isHighlighted = $cfg['highlight'];
  ?>
  
  <div class="box-tier-card <?= $isHighlighted ? 'highlighted' : '' ?>">
    <?php if ($cfg['badge']): ?>
      <div class="tier-badge"><?= $cfg['badge'] ?></div>
    <?php endif; ?>
    <img class="tier-img"
     src="<?= BASE ?>/public/uploads/produits/pics/bg_final/<?= htmlspecialchars($boxImages[$boxType][$sizeKey]) ?>"
     alt="<?= htmlspecialchars($cfg['label']) ?>"
     onerror="this.src='https://placehold.co/600x400/e8f0e3/2c5e2e?text=Benna'">
     
     <div class="tier-title"><?= $cfg['label'] ?></div>
    <div class="tier-count"><?= $data['count'] ?> produits inclus</div>
    <div class="tier-price"><?= $cfg['price'] ?> TND</div>
    <div class="tier-items" style;="  font-size: .78rem;">
        <strong style="font-size: .88rem;">produits :</strong>
      <ul>
        <?php foreach ($items as $item): ?>
          <li style="font-size: .78rem;"> <?= htmlspecialchars($item) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <a href="<?= BASE ?>/controller/panier_controller.php?action=add_prebuilt&type=<?= urlencode($boxType) ?>&size=<?= $sizeKey ?>"
       class="tier-btn">
       Ajouter au panier
    </a>
  </div>
  <?php endforeach; ?>
</div>



<?php else: ?>
<!-- ── NORMAL BUILD YOUR BOX ── -->
<div class="box-hero">
  <h1> Composez votre box</h1>
<p>Choisissez exactement 6 snacks pour créer votre box personnalisée <br><strong> un cadeau surprise sera ajouté !</strong></p>
<a href="<?= BASE ?>/view/client/produits.php" 
     style="display:inline-block;margin-top:16px;padding:10px 24px;background:rgba(255,255,255,0.15);color:#fff;border:2px solid rgba(255,255,255,0.5);border-radius:30px;text-decoration:none;font-weight:600;transition:.2s;"
     onmouseover="this.style.background='rgba(255,255,255,0.25)'"
     onmouseout="this.style.background='rgba(255,255,255,0.15)'">
    ← Retour à la boutique
  </a>
</div>

<!-- STICKY COUNTER BAR -->
<div class="box-counter-bar">
  <div>
    <strong id="selectedCount">0</strong> / 6 sélectionnés
    <span id="boxMsg" style="margin-left:12px;color:#888;font-size:.85rem;">Choisissez 6 produits pour valider.</span>
  </div>
  <div class="slots" id="slotsRow">
    <?php for ($i=0;$i<6;$i++): ?><div class="slot" id="slot<?=$i?>"></div><?php endfor; ?>
  </div>
  <button class="box-validate-btn" id="validateBox">Ajouter la box au panier </button>
</div>

<div class="box-grid" id="boxGrid">
  <?php foreach ($produits as $p): ?>
  <div class="box-card" data-id="<?= (int)$p['id'] ?>">
    <img class="box-card-img"
         src="<?= BASE ?>/public/uploads/produits/pics/bg_final/<?= (int)$p['id'] ?>.jpg"
         alt="<?= htmlspecialchars($p['nom']) ?>"
         onerror="this.onerror=null;this.src='<?= BASE ?>/view/pics/default.jpg'">
    <div class="box-card-body">
      <div class="box-card-name"><?= htmlspecialchars($p['nom']) ?></div>
      <div class="box-card-price"><?= number_format($p['prix'],3) ?> TND</div>
      <button class="box-card-btn select-btn">Sélectionner</button>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- PAGINATION -->
<?php if ($totalPages > 1): ?>
<div class="pagination" style="text-align:center;padding:20px 0 40px;">
  <?php if ($page > 1): ?><a href="?page=<?=$page-1?>" class="pagination a">←</a><?php endif; ?>
  <?php for ($i=1;$i<=$totalPages;$i++): ?>
    <a href="?page=<?=$i?>" style="display:inline-block;padding:8px 14px;margin:3px;border:1px solid #ccc;border-radius:8px;text-decoration:none;color:#333;<?=$i==$page?'background:#2e7d32;color:#fff;':''?>"><?=$i?></a>
  <?php endfor; ?>
  <?php if ($page < $totalPages): ?><a href="?page=<?=$page+1?>">→</a><?php endif; ?>
</div>
<?php endif; ?>

<?php endif; // end normal box ?>
<?php endif; // end non-mystery ?>

<script>
document.addEventListener("DOMContentLoaded", () => {
  const cards       = document.querySelectorAll(".box-card[data-id]");
  const countEl     = document.getElementById("selectedCount");
  const validateBtn = document.getElementById("validateBox");
  const boxMsg      = document.getElementById("boxMsg");

  if (!cards.length || !countEl) return;

  let selected = JSON.parse(localStorage.getItem("boxSelection") || "[]");

  // Restore UI on page load
  cards.forEach(card => {
    if (selected.includes(card.dataset.id)) {
      card.classList.add("selected");
      card.querySelector(".select-btn").textContent = "Retirer ✕";
    }
    card.querySelector(".select-btn").addEventListener("click", () => toggle(card));
  });

  updateUI();

  function toggle(card) {
    const id = card.dataset.id;
    if (selected.includes(id)) {
      selected = selected.filter(i => i !== id);
      card.classList.remove("selected");
      card.querySelector(".select-btn").textContent = "Sélectionner";
    } else {
      if (selected.length >= 6) { alert("Maximum 6 produits !"); return; }
      selected.push(id);
      card.classList.add("selected");
      card.querySelector(".select-btn").textContent = "Retirer ✕";
    }
    localStorage.setItem("boxSelection", JSON.stringify(selected));
    updateUI();
  }

  function updateUI() {
    if (!countEl) return;
    countEl.textContent = selected.length;
    // Update slots
    for (let i = 0; i < 6; i++) {
      const slot = document.getElementById("slot" + i);
      if (slot) slot.classList.toggle("filled", i < selected.length);
    }
    if (selected.length === 6) {
      if (validateBtn) { validateBtn.classList.add("ready"); }
      if (boxMsg) boxMsg.textContent = "🎉 Parfait ! Votre box est prête.";
    } else {
      if (validateBtn) { validateBtn.classList.remove("ready"); }
      if (boxMsg) boxMsg.textContent = `Choisissez encore ${6 - selected.length} produit(s).`;
    }
  }

  if (validateBtn) {
    validateBtn.addEventListener("click", () => {
      if (selected.length !== 6) return;
      localStorage.removeItem("boxSelection");
      window.location.href = "<?= BASE ?>/controller/panier_controller.php?action=add_box&ids=" + selected.join(",");
    });
  }
});
</script>

<?php include "partials/footer.php"; ?>




<!-- EXAMPLE BOX CONTENTS (for marketing purposes, not actual code) 
Gamme Sans Gluten
Petite Box (3 Produits) : Biscuits au chocolat, barre énergétique, et farine d'amande.

Moyenne Box (6 Produits) : Biscuits sans sucre au chocolat, barre énergétique, farine d'amande, préparation pour pancakes, semoule sans gluten, et pain sans gluten.

Grande Box (10 Produits) : Inclut les produits précédents avec l'ajout de préparation pour mloukhia, graines de chia, et des variétés supplémentaires de biscuits.




Gamme Sans Lactose
Petite Box (3 Produits) : Lait de coco artisanal, crackers olive et romarin, et lait sans lactose.

Moyenne Box (6 Produits) : Lait de coco, crackers olive et romarin, yaourt végétal, pain aux dattes et amandes, et deux boissons lactées supplémentaires.

Grande Box (10 Produits) : Lait de coco (plusieurs formats), crackers, pain aux dattes et amandes, smoothies verts, et jus de fruits naturels.




Gamme Sans Sucre
Petite Box (3 Produits) : Pain aux dattes et amandes, baklawa healthy, et graines de chia.

Moyenne Box (6 Produits) : Muffin sans sucre, biscuits, barre énergétique aux dattes, avoine sans sucre, graines de chia, et smoothie vert.

Grande Box (10 Produits) : Plusieurs types de biscuits, boules d'énergie, pain complet, quinoa, baklawa healthy, et une sélection de smoothies (détox et fruits rouges).



Gamme Vegan
Petite Box (3 Produits) : Lait d'avoine, granola au zaatar, et graines de lin.

Moyenne Box (6 Produits) : Lait d'avoine, granola au zaatar, biscuits aux dattes, et smoothie détox vert.

Grande Box (10 Produits) : Lait d'avoine (plusieurs unités), biscuits aux dattes, granola au zaatar, beurre de noisette, smoothie, et graines de lin.





Gamme Bio
Petite Box (3 Produits) : Crackers olive et romarin, pain aux dattes et amandes, et smoothie vert bio.

Moyenne Box (6 Produits) : Crackers, lait de coco, smoothie vert, granola au zaatar, préparation pour couscous bio, et beurre de noisette.

Grande Box (10 Produits) : Crackers, pain complet, lait de coco, smoothie vert, beurre de pistache, beurre de noisette, graines de chia, et diverses graines bio.




Gamme Sport (Performance & Récupération)
Petite Box (3 Produits) : Barre protéinée amande et miel, shake protéiné, et barre énergétique aux dattes.

Moyenne Box (6 Produits) : Barre protéinée, jus de carotte naturel, shake protéiné, mélange de noix grillées, granola fitness, et une barre énergétique supplémentaire.

Grande Box (10 Produits) : Barre protéinée, shake vanille, chips de patate douce, mélange de noix, smoothie banane, bol énergétique complet (quinoa/poulet/légumes), et granola fitness.




Gamme Low Calorie (Légèreté)
Petite Box (3 Produits) : Jus de concombre et menthe, shake protéiné léger, et galettes de riz nature.

Moyenne Box (6 Produits) : Boules d'énergie légères, jus de concombre et menthe, mini salade de quinoa, gaspacho de légumes, galettes de riz, et shake.

Grande Box (10 Produits) : Grande bouteille de jus concombre/menthe, boules d'énergie, mini lentilles, gaspacho, soufflés légers (Low-cal puffs), pudding aux graines de chia, et une grande salade méditerranéenne.


-->