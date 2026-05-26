<?php
session_start();

if (!defined('BASE')) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    define('BASE', rtrim($scriptDir === '/' ? '' : $scriptDir, '/'));
}

require_once __DIR__ . "/config/database.php";
require_once __DIR__ . "/config/app.php";

// Utilisateur connecté
$userConnected = null;
$userFirstName = '';
if (!empty($_SESSION['user_id']) && $cnx) {
    $stmtUser = $cnx->prepare("SELECT nom, email FROM users WHERE id = ? AND actif = 1");
    $stmtUser->execute([$_SESSION['user_id']]);
    $userConnected = $stmtUser->fetch();
    if ($userConnected) {
        $userFirstName = explode(' ', $userConnected['nom'])[0];
    }
}

// Produits
$products = [];
if ($cnx) {
    $stmt = $cnx->query("
        SELECT p.id, p.nom, p.description, p.prix, p.image,
               p.est_bestseller, p.est_nouveau, p.stock,
               p.calories, p.proteines, p.glucides, p.lipides,
               COALESCE(AVG(a.note), 0) AS note_moyenne
        FROM   produits p
        LEFT JOIN avis a ON a.produit_id = p.id AND a.valide = 1
        WHERE  p.est_actif = 1 AND p.est_valide = 1
        GROUP  BY p.id
        ORDER  BY p.est_bestseller DESC, p.est_nouveau DESC, p.created_at DESC
        LIMIT  4
    ");
    $products = $stmt->fetchAll();
}

// If no products in database, use static array with correct image paths
$staticProducts = [
    [
        'id' => 1,
        'nom' => 'Jus orange naturel',
        'description' => '100% frais, sans sucre ajouté, pressé à froid. Riche en vitamine C.',
        'prix' => 3.800,
        'image' => 'public/uploads/produits/pics/bg_final/17.jpg',
        'est_bestseller' => true,
        'est_nouveau' => false,
        'stock' => 50,
        'note_moyenne' => 4.8
    ],
    [
        'id' => 2,
        'nom' => 'Cookies sans gluten',
        'description' => 'Healthy cookies aux pépites de chocolat, farine de riz et amandes.',
        'prix' => 6.900,
        'image' => 'public/uploads/produits/pics/bg_final/22.jpg',
        'est_bestseller' => true,
        'est_nouveau' => false,
        'stock' => 35,
        'note_moyenne' => 4.9
    ],
    [
        'id' => 3,
        'nom' => 'Barre énergie dattes',
        'description' => 'Snack naturel aux dattes de Tunisie, noix et graines de chia.',
        'prix' => 3.200,
        'image' => 'public/uploads/produits/pics/bg_final/5.jpg',
        'est_bestseller' => true,
        'est_nouveau' => true,
        'stock' => 100,
        'note_moyenne' => 4.7
    ],
    [
        'id' => 4,
        'nom' => 'Salade quinoa bio',
        'description' => 'Repas équilibré au quinoa, légumes frais et huile dolive extra vierge.',
        'prix' => 8.500,
        'image' => 'public/uploads/produits/pics/bg_final/45.jpg',
        'est_bestseller' => true,
        'est_nouveau' => false,
        'stock' => 25,
        'note_moyenne' => 4.6
    ]
];

// Use database products if available, otherwise use static
$displayProducts = $staticProducts; // temporary — forces static products

// Avis
$reviews = [];
if ($cnx) {
    $stmt = $cnx->query("
        SELECT a.commentaire, a.note, u.nom AS auteur
        FROM   avis a
        JOIN   users u ON u.id = a.user_id
        WHERE  a.valide = 1
        ORDER  BY a.created_at DESC
        LIMIT  3
    ");
    $reviews = $stmt->fetchAll();
}

// Conseils
$conseils    = [];
$topConseils = [];
if ($cnx) {
    $stmt = $cnx->query("
        SELECT c.id, c.titre, c.type,
               LEFT(c.contenu, 115) AS contenu_preview,
               COALESCE(u.nom, 'Dr. Sana Ben Ali') AS nutri_nom
        FROM   conseils c
        LEFT   JOIN users u ON u.id = c.nutritionniste_id
        WHERE  c.public = 1
        ORDER  BY c.created_at DESC
        LIMIT  4
    ");
    $conseils    = $stmt->fetchAll();
    $topConseils = array_slice($conseils, 0, 3);
}

// Récupérer la liste de tous les allergènes
$allergenes = [];
if ($cnx) {
    $stmt = $cnx->query("SELECT id, nom, icone FROM allergenes ORDER BY nom");
    $allergenes = $stmt->fetchAll();
}

$cartCount = 0;
if (!empty($_SESSION['user_id']) && $cnx) {
    $stmt = $cnx->prepare("SELECT COALESCE(SUM(quantite),0) FROM panier WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $cartCount = (int) $stmt->fetchColumn();
}

$formSuccess = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_submit'])) {
    $formSuccess = true;
}

function getBadgeHtml(array $p): string {
    if (!empty($p['est_bestseller'])) return '<span class="product-badge badge-gold">Bestseller</span>';
    if (!empty($p['est_nouveau']))    return '<span class="product-badge badge-terra">Nouveau</span>';
    return '';
}

function conseilIcon(?string $type): string {
    return match ($type) {
        'recette'          => '🍽️',
        'plan_alimentaire' => '📋',
        'recommandation'   => '⭐',
        default            => '🥗',
    };
}

function getProductImageUrl($product) {
    if (!empty($product['image'])) {
        if (filter_var($product['image'], FILTER_VALIDATE_URL)) {
            return $product['image'];
        }
        $imagePath = ltrim($product['image'], '/');
        return BASE . '/' . $imagePath;
    }

    // Fallback: try /public/uploads/{id}.jpg
    if (!empty($product['id'])) {
        return BASE . '/public/uploads/produits/pics/bg_final/' . (int)$product['id'] . '.jpg';
    }

    return 'https://placehold.co/400x400/e8f4ea/2a5c35?text=Benna';
}

$isLoggedIn = !empty($_SESSION['user_id']);
$userRole   = $_SESSION['role'] ?? 'guest';
$pageTitle  = 'Benna — Alimentation Saine & Artisanale Tunisienne';

include __DIR__ . "/view/client/partials/header.php";
?>

<style>
/* ── Hero size fix ── */
.hero {
    min-height: 850px !important;
    height: 120vh !important;
}

/* ── Loader overlay ── */
.loader-overlay {
    position: fixed;
    inset: 0;
    background: #FFF9EF;
    z-index: 3000;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    transition: opacity 0.5s ease, visibility 0.5s;
}
.loader-overlay.hidden {
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
}
.loader-bar-container {
    width: 280px;
    height: 6px;
    background: #EBDCCB;
    border-radius: 12px;
    overflow: hidden;
    margin-top: 1rem;
}
.loader-bar {
    width: 0%;
    height: 100%;
    background: #C8684A;
    transition: width 0.12s linear;
}
.loader-label {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.2rem;
    margin-top: 1rem;
    letter-spacing: 2px;
    color: #A84C2E;
}

/* ── Hero canvas wrapper ── */
.hero-canvas-wrapper {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10;
    pointer-events: none;
}

#pasta-canvas {
    display: block;
    width: 100%;
    height: 100%;
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

.hero-stage {
    position: absolute !important;
    inset: 0 !important;
    width: 100% !important;
    height: 100% !important;
    max-width: 100% !important;
    transform: none !important;
    border-radius: 0 !important;
}

.hero-stage > img:first-of-type {
    display: none;
}

#heroCanvas {
    display: none;
}

.hero-overlay-text {
    position: fixed;
    bottom: 8%;
    left: 0;
    right: 0;
    text-align: center;
    pointer-events: none;
    z-index: 20;
    transition: opacity 0.25s ease;
}
.hero-overlay-text h2 {
    font-size: 1.8rem;
    font-weight: 500;
    font-family: 'Cormorant Garamond', serif;
    color: #2C2A29;
    text-shadow: 0 1px 2px rgba(255,255,200,0.5);
    margin: 0;
}
.hero-overlay-text p {
    font-size: 0.9rem;
    letter-spacing: 2px;
    color: #A84C2E;
    margin: 0.25rem 0 0;
}

.scroll-hint {
    position: fixed;
    bottom: 28px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(0,0,0,0.55);
    backdrop-filter: blur(8px);
    padding: 8px 18px;
    border-radius: 60px;
    color: white;
    font-size: 0.75rem;
    font-weight: 500;
    letter-spacing: 1px;
    z-index: 25;
    transition: opacity 0.4s ease, visibility 0.4s;
    pointer-events: none;
    font-family: 'Inter', sans-serif;
}
.scroll-hint.hidden {
    opacity: 0;
    visibility: hidden;
}

.allergene-badge-placeholder {
    font-size: 2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
}

.badge-cert-img-wrap .allergene-badge-placeholder {
    font-size: 2.2rem;
}

.badge-cert-img-wrap img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}
.story-img-wrap {
    width: 100%;
    height: 100%;
    aspect-ratio: 735 / 1456;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    background: #fff; /* or light beige */
}

.story-img-wrap img {
    width: 100%;
    height: 100%;
    object-fit: contain; /* change to cover if you want crop */
}

</style>

<!-- ── Loader ── -->
<div id="loader" class="loader-overlay">
    <div style="text-align:center;">
        <div style="font-size:2rem;">✨</div>
        <div class="loader-bar-container">
            <div id="loader-bar" class="loader-bar"></div>
        </div>
        <div id="loader-label" class="loader-label">Chargement des secrets de Benna...</div>
    </div>
</div>


<!-- ══════════════════════════════════════════
     HERO
══════════════════════════════════════════ -->
<section id="home" class="hero">
    <canvas id="heroCanvas" aria-hidden="true"></canvas>

    <div class="hero-orbs" aria-hidden="true">
        <div class="h-orb ho1"></div>
        <div class="h-orb ho2"></div>
        <div class="h-orb ho3"></div>
    </div>

    <div class="hero-marquee-bg" aria-hidden="true">
        <div class="mq-row" id="mqLeft">
            <span class="bena-letter">B</span><span class="bena-letter">E</span><span class="bena-letter">N</span><span class="bena-letter">N</span><span class="bena-letter">A</span>&nbsp;&nbsp;&nbsp;&nbsp;
        </div>
        <div class="mq-row" id="mqRight">
            <span class="bena-letter">B</span><span class="bena-letter">E</span><span class="bena-letter">N</span><span class="bena-letter">N</span><span class="bena-letter">A</span>&nbsp;&nbsp;&nbsp;&nbsp;
        </div>
    </div>

    <div class="hero-stage" id="heroStage">
        <div class="glow-burst" id="glowBurst"></div>
        <div class="orbit-ring" aria-hidden="true"></div>

        <div class="hero-canvas-wrapper">
            <canvas id="pasta-canvas" width="800" height="450" style="display:block;"></canvas>
        </div>

        <img src="<?= BASE ?>/pics/pic.png" alt="" style="display:none;" aria-hidden="true">

        
</section>

<!-- ══════════════════════════════════════════
     BADGES SECTION
══════════════════════════════════════════ -->
<section class="badges-section">
    <div class="container">
        <div class="badges-row">
            <!-- Static badges (always show once) -->
            <div class="badge-cert-item fade-in" style="--delay:0s">
                <div class="badge-cert-img-wrap">
                    <img src="<?= BASE ?>/pics/glutenfree.png" alt="Gluten Free"
                         onerror="this.parentElement.innerHTML='<span style=\'font-size:2.6rem\'>🌾</span>'"/>
                </div>
                <span>Gluten Free</span>
            </div>
            <div class="badge-cert-sep fade-in" style="--delay:.1s">✦</div>

            <div class="badge-cert-item fade-in" style="--delay:.2s">
                <div class="badge-cert-img-wrap">
                    <img src="<?= BASE ?>/pics/sugar free.png" alt="Sugar Free"
                         onerror="this.parentElement.innerHTML='<span style=\'font-size:2.6rem\'>🍬</span>'"/>
                </div>
                <span>Sugar Free</span>

            </div>
            <div class="badge-cert-sep fade-in" style="--delay:.3s">✦</div>

            <div class="badge-cert-item fade-in" style="--delay:.4s">
                <div class="badge-cert-img-wrap">
                    <img src="<?= BASE ?>/pics/lactose.png" alt="Sans Lactose"
                         onerror="this.parentElement.innerHTML='<span style=\'font-size:2.6rem\'>🥛</span>'"/>
                </div>
                <span>Sans Lactose</span>
            </div>
            <div class="badge-cert-sep fade-in" style="--delay:.5s">✦</div>

            <div class="badge-cert-item fade-in" style="--delay:.6s">
                <div class="badge-cert-img-wrap natural-badge"><div class="natural-icon">🌿</div></div>
                <span>100% Naturel</span>
            </div>
            <div class="badge-cert-sep fade-in" style="--delay:.7s">✦</div>

            <div class="badge-cert-item fade-in" style="--delay:.8s">
                <div class="badge-cert-img-wrap natural-badge"><div class="natural-icon">🍯</div></div>
                <span>Miel Sauvage</span>
            </div>
            <div class="badge-cert-sep fade-in" style="--delay:.7s">✦</div>
            <div class="badge-cert-item fade-in" style="--delay:.8s">
    <div class="badge-cert-img-wrap energy-badge">
        <div class="natural-icon">💪</div>
    </div>
    <span>Énergie Sportive</span>
</div>


            <!-- Dynamic allergen badges (excluding duplicates with static badges) -->
            <?php if (!empty($allergenes)): ?>
                <?php
                $excludeAllergenes = ['Gluten', 'Lactose', 'Gluten Free', 'Sans Lactose', 'Sugar Free', '100% Naturel', 'Miel Sauvage','Oeufs','Sesame','Moutarde','Soja','Arachides','Noix'];
                $uniqueAllergenes = [];
                $seenNames = [];
                foreach ($allergenes as $a) {
                    $allergeneName = trim($a['nom']);
                    if (in_array($allergeneName, $excludeAllergenes)) continue;
                    if (!in_array($allergeneName, $seenNames)) {
                        $seenNames[] = $allergeneName;
                        $uniqueAllergenes[] = $a;
                    }
                }
                $uniqueAllergenes = array_slice($uniqueAllergenes, 0, 6);
                ?>
                <?php foreach ($uniqueAllergenes as $index => $a): ?>
                    <div class="badge-cert-sep fade-in" style="--delay:<?= 0.9 + ($index * 0.1) ?>s">✦</div>
                    <div class="badge-cert-item fade-in" style="--delay:<?= 1.0 + ($index * 0.1) ?>s">
                        <div class="badge-cert-img-wrap">
                            <span class="allergene-badge-placeholder" data-allergene="<?= htmlspecialchars($a['nom']) ?>">
                                <?= htmlspecialchars($a['icone'] ?? '🔍') ?>
                            </span>
                        </div>
                        <span><?= htmlspecialchars($a['nom']) ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════
     STORY
══════════════════════════════════════════ -->
<section id="story" class="story">
    <div class="container">
        <div class="story-grid">

            <div class="story-image slide-in-left">
                <div class="story-img-wrap">
                
                <img src="/benna_final2/benna_final/benafinal/public/uploads/produits/pics/bg_final/hero.png"
     alt="Alimentation saine tunisienne"
     loading="lazy"
     style="width: 100%; height: 100%; object-fit: cover;" />
                    <div class="story-img-overlay"></div>
                </div>

                <div class="story-badge">
                    <p class="badge-label-story">Depuis</p>
                    <p class="badge-year">2026</p>
                    <p class="badge-loc">Tunisie</p>
                </div>
            </div>

            <div class="story-text slide-in-left" style="--delay:.15s">

                <p class="section-label">Notre Vision</p>

                <h2 class="section-title">
                    Mieux manger<br/>avec <em>Benna</em>
                </h2>

                <div class="section-divider"></div>

                <p class="story-intro">
    <strong style="color: #cc2222;font-weight: bold;font-size: 1.2rem;">Avec <em>Benna</em> , une nourriture bonne et saine. </strong>  <br>
    Une nouvelle façon de manger équilibré, savoureux et accessible à tous.
</p>

                <p>
                    Benna (بنة) est une plateforme tunisienne de vente en ligne dédiée aux produits alimentaires sains et adaptés à tous les besoins.
                    Nous sélectionnons des produits sans gluten, sans lactose, sans sucre et bio, afin d’aider chacun à adopter une alimentation plus équilibrée sans difficulté.
                </p>

                <p>
                    Notre objectif est de préserver le lien avec la cuisine tunisienne tout en proposant des alternatives plus saines, naturelles et contrôlées.
                    Chaque produit est choisi pour garantir qualité, transparence et plaisir.
                </p>

                <blockquote>
                    <p>
                        Manger mieux, vivre mieux.
                    </p>
                    <cite>— Vision Benna</cite>
                </blockquote>

                <a href="#shop" class="story-link">
                    Découvrir nos produits <span>→</span>
                </a>

            </div>

        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════
     SHOP / PRODUCTS
══════════════════════════════════════════ -->
<section id="shop" class="products">
    <div class="container">
        <div class="section-header fade-in">
            <p class="section-label">Notre Collection</p>
            <h2 class="section-title">Produits <em>Signature</em></h2>
            <div class="section-divider"></div>
            <p class="section-sub">Ingrédients purs, confectionnés selon les recettes ancestrales tunisiennes</p>
        </div>

        <div class="products-grid">
            <?php foreach ($displayProducts as $i => $p):
                $imgSrc = getProductImageUrl($p);
            ?>
                <div class="product-card slide-in-left" style="transition-delay:<?= $i * 0.12 ?>s">
                    <div class="product-img" style="object-fit:contain;">
                        <img src="<?= $imgSrc ?>" alt="<?= htmlspecialchars($p['nom']) ?>" loading="lazy"
                             onerror="this.src='https://placehold.co/400x400/e8f4ea/2a5c35?text=Benna'"/>
                        <div class="product-img-overlay"></div>
                        <?= getBadgeHtml($p) ?>
                    </div>
                    <div class="product-body">
                        <h3 class="product-name-shine"><?= htmlspecialchars($p['nom']) ?></h3>
                        <p><?= htmlspecialchars(mb_substr($p['description'] ?? '', 0, 88)) ?>…</p>
                        <?php if (!empty($p['note_moyenne']) && $p['note_moyenne'] > 0): ?>
                            <div class="product-stars">
                                <?= str_repeat('★', (int) round($p['note_moyenne'])) ?>
                                <span class="product-stars-num">(<?= number_format($p['note_moyenne'], 1) ?>)</span>
                            </div>
                        <?php endif; ?>
                        <div class="product-footer">
                            <span class="product-price"><?= number_format($p['prix'], 3) ?> TND</span>
                            <?php if (($p['stock'] ?? 0) <= 0): ?>
                                <span class="stock-out">Rupture</span>
                            <?php elseif ($isLoggedIn): ?>
                                <form action="<?= BASE ?>/controller/panier_controller.php?action=add" method="POST" style="display:inline;">
                                    <input type="hidden" name="produit_id" value="<?= (int)$p['id'] ?>"/>
                                    <input type="hidden" name="quantite"   value="1"/>
                                    <button class="pc-add" type="submit" title="Ajouter au panier">🛒</button>
                                </form>
                            <?php else: ?>
                                <a href="<?= BASE ?>/view/client/signup.php" class="pc-add" title="Connexion requise">🛒</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="view-all-wrapper">
            <a href="<?= BASE ?>/view/client/produits.php" class="view-all-trigger">Voir tous les produits</a>
        </div>
        <p class="products-note">✦ Livraison gratuite pour toute commande supérieure à 150 TND ✦</p>
    </div>
</section>

<!-- ══════════════════════════════════════════
     NUTRITIONIST TIPS
══════════════════════════════════════════ -->
<?php if (!empty($topConseils)): ?>
<section class="conseils-section">
    <div class="container">
        <div class="section-header fade-in">
            <p class="section-label">Expertise</p>
            <h2 class="section-title">Conseils de notre <em>Nutritionniste</em></h2>
            <div class="section-divider"></div>
        </div>
        <div class="products-grid">
            <?php foreach ($topConseils as $i => $c): ?>
                <div class="product-card fade-in" style="--delay:<?= $i * 0.15 ?>s">
                    <div class="product-body conseils-body">
                        <p class="conseil-tag"><?= conseilIcon($c['type'] ?? '') ?> Conseil nutrition</p>
                        <h3 class="product-name-shine conseil-title"><?= htmlspecialchars($c['titre']) ?></h3>
                        <p class="conseil-text"><?= htmlspecialchars($c['contenu_preview']) ?>…</p>
                        <p class="conseil-author">— <?= htmlspecialchars($c['nutri_nom']) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="view-all-wrapper" style="margin-top:30px;">
            <a href="<?= BASE ?>/view/client/conseils.php" class="view-all-trigger">Tous les conseils santé →</a>
        </div>
    </div>
</section>
<?php endif; ?>
<br><br>

<!-- ══════════════════════════════════════════
     VIP
══════════════════════════════════════════ -->
<section id="vip" class="vip-section">
    <div class="vip-bg" aria-hidden="true">
        <div class="vip-bg-orb vb1"></div>
        <div class="vip-bg-orb vb2"></div>
        <div class="vip-bg-orb vb3"></div>
    </div>
    <div class="container">
        <div class="vip-header fade-in">
            <div class="vip-crown-wrap"><div class="crown-glow"></div></div>
            <p class="section-label vip-label">Suivi Personnalisé</p>
            <h2 class="vip-title">Club <em>VIP Benna</em></h2>
            <div class="vip-divider"><span class="vd-line"></span><span class="vd-gem">◆</span><span class="vd-line"></span></div>
            <p class="vip-subtitle">Débloquez une nutrition personnalisée, des offres exclusives et un accès direct à notre nutritionniste.</p>
        </div>

        <?php
        $userLoggedIn = isset($_SESSION['user_id']);
        $userVipLevel = null;
        $userVipActive = false;

        if ($userLoggedIn) {
            if (function_exists('getAbonnementUser')) {
                $vipAbonnement = getAbonnementUser($cnx, $_SESSION['user_id']);
                if ($vipAbonnement && isset($vipAbonnement['niveau'])) {
                    $userVipActive = true;
                    $userVipLevel = $vipAbonnement['niveau'];
                }
            }
        }
        ?>

        <div class="vip-cards">
            <!-- BASIC PLAN -->
            <div class="vip-card vc-basic fade-in" style="--delay:.1s">
                <div class="vc-tier-badge">Basic</div>
                <div class="vc-price-wrap"><span class="vc-currency">TND</span><span class="vc-price">29</span><span class="vc-period">/mois</span></div>
                <ul class="vc-perks">
                    <li><span class="perk-check">✓</span> 10% de réduction sur toutes les commandes</li>
                    <li><span class="perk-check">✓</span> Accès anticipé aux nouveaux produits</li>
                    <li><span class="perk-check">✓</span> Newsletter santé mensuelle</li>
                    <li><span class="perk-check">✓</span> Livraison gratuite dès 150 TND</li>
                    <li class="perk-off"><span class="perk-x">✗</span> Consultations nutritionniste</li>
                    <li class="perk-off"><span class="perk-x">✗</span> Plans alimentaires personnalisés</li>
                </ul>
                <?php if ($userVipActive && $userVipLevel === 'basic'): ?>
                    <a href="<?= BASE ?>/view/client/vip_espace.php" class="vc-btn vc-btn-basic">✓ Mon Espace VIP</a>
                <?php elseif ($userLoggedIn): ?>
                    <a href="<?= BASE ?>/view/client/vip.php?plan=basic" class="vc-btn vc-btn-basic">Choisir Basic</a>
                <?php else: ?>
                    <a href="<?= BASE ?>/view/client/signup.php?redirect=vip" class="vc-btn vc-btn-basic">S'inscrire</a>
                <?php endif; ?>
            </div>

            <!-- PREMIUM PLAN (Featured) -->
            <div class="vip-card vc-premium featured fade-in" style="--delay:.25s">
                <div class="vc-most-popular">Meilleur Choix</div>
                <div class="vc-sparkle" aria-hidden="true">
                    <span class="sp sp1">✦</span><span class="sp sp2">✦</span>
                    <span class="sp sp3">✦</span><span class="sp sp4">✦</span>
                </div>
                <div class="vc-tier-badge premium-badge">Premium</div>
                <div class="vc-price-wrap"><span class="vc-currency">TND</span><span class="vc-price">59</span><span class="vc-period">/mois</span></div>
                <ul class="vc-perks">
                    <li><span class="perk-check">✓</span> 20% de réduction sur toutes les commandes</li>
                    <li><span class="perk-check">✓</span> Livraison prioritaire</li>
                    <li><span class="perk-check">✓</span> Coffrets saisonniers exclusifs</li>
                    <li><span class="perk-check">✓</span> 2× consultations nutritionniste/mois</li>
                    <li><span class="perk-check">✓</span> Modèle de plan alimentaire de base</li>
                    <li class="perk-off"><span class="perk-x">✗</span> Consultation vidéo individuelle</li>
                </ul>
                <?php if ($userVipActive && $userVipLevel === 'premium'): ?>
                    <a href="<?= BASE ?>/view/client/vip_espace.php" class="vc-btn vc-btn-premium">✓ Mon Espace VIP</a>
                <?php elseif ($userLoggedIn): ?>
                    <a href="<?= BASE ?>/view/client/vip.php?plan=premium" class="vc-btn vc-btn-premium">Choisir Premium →</a>
                <?php else: ?>
                    <a href="<?= BASE ?>/view/client/signup.php?redirect=vip" class="vc-btn vc-btn-premium">S'inscrire →</a>
                <?php endif; ?>
                <div class="vc-shine-bar" aria-hidden="true"></div>
            </div>

            <!-- ELITE PLAN -->
            <div class="vip-card vc-elite fade-in" style="--delay:.4s">
                <div class="vc-tier-badge elite-badge">Elite</div>
                <div class="vc-price-wrap"><span class="vc-currency">TND</span><span class="vc-price">99</span><span class="vc-period">/mois</span></div>
                <ul class="vc-perks">
                    <li><span class="perk-check gold-check">✓</span> 30% de réduction sur toutes les commandes</li>
                    <li><span class="perk-check gold-check">✓</span> Livraison gratuite, toujours</li>
                    <li><span class="perk-check gold-check">✓</span> Coffret cadeau mensuel curé</li>
                    <li><span class="perk-check gold-check">✓</span> Chat nutritionniste illimité</li>
                    <li><span class="perk-check gold-check">✓</span> Plans alimentaires personnalisés complets</li>
                    <li><span class="perk-check gold-check">✓</span> Appel vidéo individuel mensuel</li>
                </ul>
                <?php if ($userVipActive && $userVipLevel === 'elite'): ?>
                    <a href="<?= BASE ?>/view/client/vip_espace.php" class="vc-btn vc-btn-elite">✓ Mon Espace VIP</a>
                <?php elseif ($userLoggedIn): ?>
                    <a href="<?= BASE ?>/view/client/vip.php?plan=elite" class="vc-btn vc-btn-elite">Choisir Elite ♛</a>
                <?php else: ?>
                    <a href="<?= BASE ?>/view/client/signup.php?redirect=vip" class="vc-btn vc-btn-elite">S'inscrire ♛</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Display current subscription info if user has VIP -->
        <?php if ($userVipActive): ?>
        <div class="vip-current-subscription fade-in" style="text-align: center; margin-top: 30px; padding: 15px; background: linear-gradient(135deg, rgba(34,197,94,0.1), rgba(34,197,94,0.05)); border-radius: 16px; border: 1px solid rgba(34,197,94,0.2);">
            <p style="margin: 0; color: var(--green);">
                ✨ Vous êtes déjà membre <strong><?= ucfirst($userVipLevel) ?></strong> !
                <a href="<?= BASE ?>/view/client/vip_espace.php" style="color: var(--green); font-weight: 600; text-decoration: underline;">Accéder à votre espace VIP →</a>
            </p>
        </div>
        <?php endif; ?>

        <div class="vip-features fade-in">
            <div class="vf-item"><h4>Accès Nutritionniste</h4><p>Chat direct ou vidéo avec Dr. Sana Ben Ali, notre experte en nutrition certifiée.</p></div>
            <div class="vf-divider">|</div>
            <div class="vf-item"><h4>Plans Personnalisés</h4><p>Plans conçus selon vos allergies, objectifs de santé et préférences culinaires tunisiennes.</p></div>
            <div class="vf-divider">|</div>
            <div class="vf-item"><h4>Offres Exclusives</h4><p>Accès prioritaire aux produits saisonniers en édition limitée.</p></div>
            <div class="vf-divider">|</div>
            <div class="vf-item"><h4>Suivi Santé</h4><p>Alertes nutritionnelles et insights bien-être personnalisés.</p></div>
        </div>

        <div class="vip-testimonial fade-in">
            <div class="vt-quote">❝</div>
            <p class="vt-text">Le plan Elite a changé ma façon d'aborder la nourriture. Dr. Sana a créé un plan qui respecte mon intolérance au lactose et mon amour de la cuisine tunisienne. J'ai perdu 4 kg en 2 mois.</p>
            <div class="vt-author">
                <div class="vt-avatar">L</div>
                <div><strong>Leila Mansouri</strong><span>Membre Elite · Tunis</span></div>
                <div class="vt-stars">★★★★★</div>
            </div>
        </div>
    </div>
</section>

<style>
.vip-current-subscription {
    animation: fadeInUp 0.6s ease-out;
    max-width: 500px;
    margin-left: auto;
    margin-right: auto;
}
.vip-current-subscription a:hover {
    text-decoration: none !important;
    opacity: 0.8;
}
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0); }
}
.vc-btn {
    cursor: pointer;
    transition: all 0.3s ease;
}
.vc-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
}
</style>

<script>
document.querySelectorAll('.vc-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        const href = this.getAttribute('href');
        if (href && href.includes('signup.php?redirect=vip')) {
            sessionStorage.setItem('redirectAfterSignup', 'vip');
        }
        if (href && href.includes('vip.php?plan=')) {
            console.log('Plan selected:', href.split('plan=')[1]);
        }
    });
});
</script>

<!-- ══════════════════════════════════════════
     GALLERY
══════════════════════════════════════════ -->
<section id="gallery" class="gallery">
    <div class="container">
        
        <div class="section-header fade-in">
            <p class="section-label">Voyage Visuel

            <h2 class="section-title">Moments de<br/><em>Pure Saveur</em></h2>
            <div class="section-divider"></div>
        </div>

        <div class="carousel-scene" id="carouselScene">

            <div class="carousel-stage" id="carouselStage">

    <div class="gc-card">
        <img src="/benna_final2/benna_final/benafinal/public/uploads/produits/pics/bg_final/tabouna.jpg" alt="Tabouna">
    </div>

    <div class="gc-card">
        <img src="/benna_final2/benna_final/benafinal/public/uploads/produits/pics/bg_final/miel.jpg" alt="Miel">
    </div>

    <div class="gc-card">
        <img src="/benna_final2/benna_final/benafinal/public/uploads/produits/pics/bg_final/ble.png" alt="Sésame">
    </div>

</div>
        <div class="carousel-controls">
            <button class="carousel-btn" id="carouselPrev" type="button" aria-label="Précédent">←</button>
            <div class="carousel-dots" id="carouselDots"></div>
            <button class="carousel-btn" id="carouselNext" type="button" aria-label="Suivant">→</button>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════
     REVIEWS
══════════════════════════════════════════ -->
<section id="reviews" class="reviews">
    <div class="container">
        <div class="section-header fade-in">
            <p class="section-label">Communauté</p>
            <h2 class="section-title">Aimé par le<br/><em>Benna Circle</em></h2>
            <div class="section-divider"></div>
        </div>
        <div class="reviews-grid">
            <?php if (!empty($reviews)): ?>
                <?php foreach ($reviews as $i => $r): ?>
                    <div class="review-card fade-in" style="--delay:<?= $i * 0.15 ?>s">
                        <div class="review-inner">
                            <span class="review-quote">"</span>
                            <p><?= htmlspecialchars($r['commentaire']) ?></p>
                            <div class="review-footer">
                                <?php $note = max(0, min(5, (int)$r['note'])); ?>
                                <span class="review-stars"><?= str_repeat('★', $note) ?><?= str_repeat('☆', 5 - $note) ?></span>
                                <span>— <?= htmlspecialchars($r['auteur']) ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="review-card fade-in" style="--delay:0s">
                    <div class="review-inner">
                        <span class="review-quote">"</span>
                        <p>Un snack sain au goût extraordinaire. Le jus orange est mon rituel quotidien.</p>
                        <div class="review-footer">
                            <span class="review-stars">★★★★★</span>
                            <span>— Leila B., Tunis</span>
                        </div>
                    </div>
                </div>
                <div class="review-card fade-in" style="--delay:.15s">
                    <div class="review-inner">
                        <span class="review-quote">"</span>
                        <p>Les cookies sans gluten sont parfaits ! Texture incroyable et zéro culpabilité.</p>
                        <div class="review-footer">
                            <span class="review-stars">★★★★★</span>
                            <span>— Marcus L., Sousse</span>
                        </div>
                    </div>
                </div>
                <div class="review-card fade-in" style="--delay:.3s">
                    <div class="review-inner">
                        <span class="review-quote">"</span>
                        <p>Ingrédients purs, emballage magnifique. Les barres énergie dattes sont délicieuses.</p>
                        <div class="review-footer">
                            <span class="review-stars">★★★★★</span>
                            <span>— Ahlem M., Gabes</span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ── Floating cart ── -->
<a href="<?= BASE ?>/view/client/panier.php" class="floating-cart" id="floatingCartBtn" aria-label="Panier">
    <span class="floating-cart-icon">🛒</span>
    <span class="floating-cart-count" id="cartCount"><?= $cartCount ?></span>
</a>
<div class="cart-overlay" id="cartOverlay">
    <div class="cart-modal">
        <div class="cart-modal-header">
            <h3>🛒 Mon Panier</h3>
            <button class="cart-close" id="cartClose" aria-label="Fermer">&times;</button>
        </div>
        <div class="cart-modal-body">
            <p>Vous avez <strong id="cartModalCount"><?= $cartCount ?></strong> article(s).</p>
            <?php if ($cartCount === 0): ?>
                <p style="color:var(--muted);font-size:.86rem;">Votre panier est vide.</p>
            <?php endif; ?>
        </div>
        <div class="cart-modal-footer">
            <a href="<?= BASE ?>/view/client/panier.php" class="nav-join view-cart-btn">Voir le panier →</a>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════
     FRAME ANIMATION
══════════════════════════════════════════ -->
<script>
(function () {
    'use strict';

    var TOTAL_FRAMES = 41;
    var LERP_SPEED   = 0.22;
    var ANIM_VH      = 0.9;

    var FRAMES_BASE = '<?= BASE ?>/hd pics/';

    function framePath(i) {
        return FRAMES_BASE + (i + 1) + '.png';
    }

    var canvas      = document.getElementById('pasta-canvas');
    var ctx         = canvas.getContext('2d');
    var loaderEl    = document.getElementById('loader');
    var loaderBar   = document.getElementById('loader-bar');
    var loaderLabel = document.getElementById('loader-label');
    var scrollHint  = document.getElementById('scroll-hint');
    var heroOverlay = document.getElementById('hero-overlay');

    var NW = 1672, NH = 941;
    var lastDrawn = -1;

    function sizeCanvas() {
        var dpr = Math.min(window.devicePixelRatio || 1, 2);
        canvas.width  = NW * dpr;
        canvas.height = NH * dpr;
        ctx.setTransform(1, 0, 0, 1, 0, 0);
        ctx.scale(dpr, dpr);
        lastDrawn = -1;
    }
    sizeCanvas();
    window.addEventListener('resize', function() { lastDrawn = -1; });

    var frames      = new Array(TOTAL_FRAMES);
    var loadedCount = 0;

    function onOneLoaded(e) {
        loadedCount++;
        if (loadedCount === 1 && e && e.target && e.target.naturalWidth) {
            NW = e.target.naturalWidth;
            NH = e.target.naturalHeight;
            sizeCanvas();
        }
        var pct = Math.round((loadedCount / TOTAL_FRAMES) * 100);
        if (loaderBar)   loaderBar.style.width   = pct + '%';
        if (loaderLabel) loaderLabel.textContent = 'Chargement — ' + pct + '%';
        if (loadedCount === TOTAL_FRAMES) onAllLoaded();
    }

    function preload() {
        for (var i = 0; i < TOTAL_FRAMES; i++) {
            var img      = new Image();
            img.decoding = 'async';
            img.onload   = onOneLoaded;
            img.onerror  = onOneLoaded;
            img.src      = framePath(i);
            frames[i]    = img;
        }
    }

    var currentFrame = 0;
    var targetFrame  = 0;
    var rafId        = null;

    function drawFrame(floatIdx) {
        var i = Math.max(0, Math.min(TOTAL_FRAMES - 1, Math.round(floatIdx)));
        if (i === lastDrawn) return;
        lastDrawn = i;
        var img = frames[i];
        if (!img || !img.complete || !img.naturalWidth) return;
        ctx.clearRect(0, 0, img.naturalWidth, img.naturalHeight);
        ctx.drawImage(img, 0, 0, img.naturalWidth, img.naturalHeight);
    }

    function renderLoop() {
        currentFrame += (targetFrame - currentFrame) * LERP_SPEED;
        drawFrame(currentFrame);
        rafId = requestAnimationFrame(renderLoop);
    }

    function onScroll() {
        var scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        var animZone  = window.innerHeight * ANIM_VH;
        var progress  = Math.max(0, Math.min(1, scrollTop / animZone));

        targetFrame = progress * (TOTAL_FRAMES - 1);

        if (scrollHint && progress > 0.015) {
            scrollHint.classList.add('hidden');
        }

        if (heroOverlay) {
            var alpha = progress > 0.72
                ? Math.max(0, 1 - (progress - 0.72) / 0.28)
                : 1;
            heroOverlay.style.opacity = String(alpha);
        }
    }

    function onAllLoaded() {
        drawFrame(0);
        setTimeout(function () {
            if (loaderEl) loaderEl.classList.add('hidden');
            window.addEventListener('scroll', onScroll, { passive: true });
            onScroll();
            rafId = requestAnimationFrame(renderLoop);
        }, 0);
    }

    preload();
})();
</script>

<script src="<?= BASE ?>/public/js/script.js"></script>
</body>
</html>
<?php include __DIR__ . "/view/client/partials/footer.php"; ?>