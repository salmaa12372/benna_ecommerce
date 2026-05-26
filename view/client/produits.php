<?php
$extraCss  = 'stylePro.css';
$pageTitle = 'Boutique';
require_once __DIR__ . '/../../config/database.php';
include "partials/header.php";


/* =========================
   FILTER LOGIC
========================= */

$where = ["est_actif = 1", "(description != 'box' OR description IS NULL)"];  // ← start here, never add a second WHERE
$params  = [];

/* SEARCH */
if (!empty($_GET['search'])) {
    $where[] = "(nom LIKE :search OR description LIKE :search2)";
    $params[':search']  = '%' . $_GET['search'] . '%';
    $params[':search2'] = '%' . $_GET['search'] . '%';
}

/* DIET FILTER */
if (!empty($_GET['filters'])) {
    $parts = [];
    foreach ($_GET['filters'] as $i => $f) {
        $key = ":filter$i";
        $parts[]       = "FIND_IN_SET($key, REPLACE(regime,' ',''))";
        // simpler: LIKE
        $key2          = ":filterL$i";
        $parts[$i]     = "regime LIKE $key2";
        $params[$key2] = "%" . trim($f) . "%";
    }
    if ($parts) {
        $where[] = '(' . implode(' OR ', $parts) . ')';
    }
}

/* STOCK FILTER */
if (!empty($_GET['in_stock'])) {
    $where[] = "stock > 0";
}

/* PRICE FILTER */
if (isset($_GET['min_price']) && $_GET['min_price'] !== '') {
    $where[]              = "prix >= :min_price";
    $params[':min_price'] = (float)$_GET['min_price'];
}
if (isset($_GET['max_price']) && $_GET['max_price'] !== '') {
    $where[]              = "prix <= :max_price";
    $params[':max_price'] = (float)$_GET['max_price'];
}

/* FINAL QUERY — single WHERE */
$sql = "SELECT * FROM produits WHERE " . implode(" AND ", $where);

/* PAGINATION */
$limit  = 12;
$page   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

/* COUNT */
$countSql  = "SELECT COUNT(*) as total FROM produits WHERE " . implode(" AND ", $where);
$countStmt = $cnx->prepare($countSql);
$countStmt->execute($params);
$total = (int)$countStmt->fetch(PDO::FETCH_OBJ)->total;
$pages = $total > 0 ? ceil($total / $limit) : 1;

/* PAGINATED DATA */
$sql  .= " LIMIT :limit OFFSET :offset";
$stmt  = $cnx->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_OBJ);

$cartCount = isset($_SESSION['user_id']) ? countPanier($cnx, $_SESSION['user_id']) : 0;

/* Keep GET params for pagination links (strip page) */
$queryParams = $_GET;
unset($queryParams['page']);
?>
<!-- ═══════════════════════════════════════════
     SEARCH BAR  (above hero, or inside hero)
═══════════════════════════════════════════ -->

<style>
.hero-shop {
    padding: 100px 0 60px !important;
    text-align: center !important;
    background: #1e3a20 !important;
}
html{
    font-family: var(--font-body);

}
.hero-shop h1 { color: #fff !important; font-size: 2.4rem !important; }
.hero-shop p  { color: rgba(255,255,255,0.7) !important; }


#searchForm {
    display: flex !important;
    gap: 8px !important;
    max-width: 480px !important;
    margin: 1.5rem auto 0 !important;
}
#searchForm input[type="text"] {
    flex: 1 !important;
    padding: 12px 20px !important;
    border-radius: 999px !important;
    border: 1.5px solid rgba(255,255,255,0.25) !important;
    background: rgba(255,255,255,0.15) !important;
    color: #fff !important;
    font-size: 0.95rem !important;
    outline: none !important;
}
#searchForm input[type="text"]::placeholder { color: rgba(255,255,255,0.5) !important; }
#searchForm button {
    padding: 12px 22px !important;
    border-radius: 999px !important;
    border: none !important;
    background: #b8860e !important;
    color: #fff !important;
    font-weight: 700 !important;
    cursor: pointer !important;
    white-space: nowrap !important;
}


/* Shop layout — sidebar + products grid */
.shop-layout {
    display: grid !important;
    grid-template-columns: 268px 1fr !important;
    gap: 32px !important;
    max-width: 1280px !important;
    margin: 40px auto 0 !important;
    padding: 0 28px !important;
    align-items: start !important;
}


/* Sidebar */
.filters-sidebar {
    border-radius: 20px !important;
    overflow: hidden !important;
    position: sticky !important;
    font-family: var(--font-display) ;
    top: 96px !important;
    background: #d4f0c0 !important;
    border: 1px solid #c5e0a8 !important;
}
body.dark .filters-sidebar { background: #0d1f0e !important; border-color: #1e3a1e !important; }


.filters-sidebar > h2 {
    font-size: 0.85rem !important;
    font-weight: 700 !important;
    letter-spacing: 0.07em !important;
    text-transform: uppercase !important;
    font-family: var(--font-display) ;
    padding: 16px 22px !important;
    margin: 0 !important;
    background: rgba(44,94,46,0.12) !important;
    border-bottom: 1px solid #c5e0a8 !important;
}


.filter-group {
    padding: 14px 22px !important;
    border-bottom: 1px solid #4b5c38 !important;
}
.filter-group h4 {
    font-size: 0.68rem !important;
    font-weight: 700 !important;
    letter-spacing: 0.1em !important;
    text-transform: uppercase !important;
    margin-bottom: 9px !important;
    color: #4e524c !important;
}
.filter-group label {
    display: flex !important;
    color: #39493a;
    align-items: center !important;
    gap: 8px !important;
    margin-bottom: 6px !important;
    font-size: 0.9rem !important;
    cursor: pointer !important;
}

body.dark .filter-group label {
    display: flex !important;
    color: #ecf4ec !important;
    align-items: center !important;
    gap: 8px !important;
    margin-bottom: 6px !important;
    font-size: 0.9rem !important;
    cursor: pointer !important;
}
body.dark .pagination {
  color: #1d1d1d!important;;
}

.filter-group input[type="checkbox"] { accent-color: #273127 !important; }
.filter-group input[type="number"] {
    width: 72px !important;
    padding: 6px 8px !important;
    border-radius: 6px !important;
    border: 1px solid #c5e0a8 !important;
    font-size: 0.83rem !important;
    background: #eafade !important;
}
.filter-group input[type="range"] {
    width: 100% !important;
    margin-top: 8px !important;
    accent-color: #2c5e2e !important;
}
.range-labels {
    display: flex !important;
    justify-content: space-between !important;
    font-size: 0.7rem !important;
    color: #bac3b0 !important;
    margin-top: 4px !important;
}
.price-row { display: flex !important; align-items: center !important; gap: 7px !important; }
.price-dash { color: #8aaa7a !important; }


.sidebar-actions {
    padding: 14px 22px 18px !important;
    display: flex !important;
    flex-direction: column !important;
    gap: 8px !important;
    border-top: 1px solid #c5e0a8 !important;
}
.sidebar-btn-apply {
    width: 100% !important;
    padding: 10px !important;
    border: none !important;
    border-radius: 12px !important;
    background: #2c5e2e !important;
    color: #fff !important;
    font-weight: 700 !important;
    cursor: pointer !important;
    font-size: 0.87rem !important;
}
.sidebar-btn-reset {
    display: block !important;
    width: 100% !important;
    padding: 10px !important;
    border-radius: 12px !important;
    border: 1px solid #4b5c38 !important;
    text-align: center !important;
    font-size: 0.87rem !important;
    font-weight: 600 !important;
    background: transparent !important;
    cursor: pointer !important;
    text-decoration: none !important;
    color: inherit !important;
}


/* Products area */
.products-area { min-width: 0 !important; }
.top-bar {
    display: flex !important;
    justify-content: space-between !important;
    align-items: center !important;
    margin-bottom: 20px !important;
    padding-bottom: 14px !important;
    border-bottom: 1px solid #c5e0a8 !important;
}
.top-bar-count { font-size: 0.9rem !important; color: #5a7a4a !important; }
.top-bar-count strong { color: #1a3a1c !important; }


/* Products grid */
.products-grid {
    font-family: "Playfair Display";
    display: grid !important;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)) !important;
    gap: 20px !important;
    margin-bottom: 40px !important;
}
.product-card-link {
    display: flex !important;
    flex-direction: column !important;
    text-decoration: none !important;
    color: inherit !important;
    height: 100% !important;
}
.product-card {
    border-radius: 18px !important;
    overflow: hidden !important;
    border: 1px solid #c5e0a8 !important;
    background: #d4f0c0 !important;
    transition: transform 0.22s, box-shadow 0.22s !important;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06) !important;
    display: flex !important;
    flex-direction: column !important;
}
body.dark .product-card { background: #0d1f0e !important; border-color: #1e3a1e !important; }
.product-card:hover { transform: translateY(-5px) !important; box-shadow: 0 12px 32px rgba(0,0,0,0.12) !important; }




.product-img img { width: 100% !important; height: 100% !important; object-fit: contain !important; transition: transform 0.4s !important; }
.product-card:hover .product-img img { transform: scale(1.05) !important; }


.product-info {
    padding: 14px 16px 16px !important;
    display: flex !important;
    flex-direction: column !important;
    flex: 1 !important;
}
.price { font-size: 1.1rem !important; font-weight: 700 !important; color: #2c5e2e !important; margin-bottom: 4px !important; }
.stock-ok { color: #16a34a !important; font-size: 0.74rem !important; font-weight: 700 !important; }
.stock-ko { color: #dc2626 !important; font-size: 0.74rem !important; font-weight: 700 !important; }


.card-actions {
    display: flex !important;
    margin-top: auto !important;
}
.add-to-cart-form { width: 100% !important; }
.add-to-cart {
    width: 100% !important;
    padding: 9px 12px !important;
    border: none !important;
    border-radius: 12px !important;
    background: #2c5e2e !important;
    color: #fff !important;
    font-weight: 700 !important;
    font-size: 0.83rem !important;
    cursor: pointer !important;
    transition: background 0.15s !important;
}
.add-to-cart:hover:not(:disabled) { background: #1a3a1c !important; }
.add-to-cart:disabled { background: #c5e0a8 !important; color: #8aaa7a !important; cursor: not-allowed !important; }


/* Pagination */
.pagination { display: flex !important; justify-content: center !important; gap: 6px !important; flex-wrap: wrap !important; margin: 8px 0 40px !important; }
.pagination a {
    display: flex !important; align-items: center !important; justify-content: center !important;
    min-width: 36px !important; height: 36px !important; padding: 0 10px !important;
    border-radius: 9px !important; font-size: 0.86rem !important; font-weight: 500 !important;
    border: 1px solid #c5e0a8 !important; background: #d4f0c0 !important; color: inherit !important;
    transition: background 0.15s !important;
}
.pagination a.active { background: #2c5e2e !important; color: #fff !important; border-color: #2c5e2e !important; }


/* Floating cart */
.floating-cart {
    position: fixed;
    bottom: 20px;
    right: 20px;
    background: #ffb300;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
    text-decoration: none;
}

.floating-cart-count {
    position: absolute;
    top: -5px;
    right: -5px;
    background: red;
    color: white;
    padding: 5px;
    border-radius: 50%;
    font-size: 12px;
}

/* =========================
   CART MODAL
========================= */
.cart-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    display: none;
}

.cart-overlay.active {
    display: flex;
    justify-content: center;
    align-items: center;
}

.cart-modal {
    background: white;
    padding: 20px;
    border-radius: 16px;
    width: 300px;
}




/* Prebuilt section */
.prebuilt-section { max-width: 1280px !important; margin: 60px auto 0 !important; padding: 0 28px !important; text-align: center !important; }
body.dark .prebuilt-section .subtitle { font-size: 0.95rem !important; color: #ffffff !important; margin-bottom: 28px !important; }
.prebuilt-section h2 { font-size: 2rem !important; margin-bottom: 6px !important; }

.prebuilt-section .subtitle { font-size: 0.95rem !important; color: #6a8a5a !important; margin-bottom: 28px !important; }
.prebuilt-grid {
    display: grid !important;
    grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)) !important;
    gap: 14px !important;
}
.prebuilt-card {
    display: flex !important; flex-direction: column !important; align-items: center !important;
    justify-content: center !important; gap: 9px ;
     padding: 20px 10px;
    border-radius: 18px !important; background: #d4f0c063 !important; border: 1px solid #c5e0a855 !important;
    cursor: pointer !important; transition: transform 0.2s, border-color 0.2s !important;
    font-size: 0.82rem !important; font-weight: 600 !important;
}
.prebuilt-card:hover { transform: translateY(-4px) !important; border-color: #2c5e2e !important; color: #2c5e2e !important; }
.prebuilt-card.highlight { background: rgba(184,134,14,0.1) !important; border-color: #b8860e !important; }
.prebuilt-icon { font-size: 1.8rem !important; color: #2c5e2e !important; }

.body.dar .prebuilt-icon { font-size: 1.8rem !important; color: #e9f0ea !important; }

/* Custom box */
.custom-box {
    max-width: 1280px !important; margin: 60px auto !important; padding: 50px 36px !important;
    text-align: center !important; border-radius: 26px !important;
    background: rgba(189, 230, 178, 0.54) !important; border: 1px solid #33382d !important;
}
.custom-box h2 { font-size: 2rem !important; margin-bottom: 10px !important; }
.custom-box p { font-size: 1rem !important; color: #5a7a4a !important; max-width: 400px !important; margin: 0 auto !important; }
.body.dark .custom-box p { font-size: 1rem !important; color: #eaf6e3 !important; max-width: 400px !important; margin: 0 auto !important; }

.btn-box {
    display: inline-flex !important; align-items: center !important; gap: 6px !important;
    margin-top: 22px !important; padding: 12px 26px !important; border-radius: 999px !important;
    background: #b8860e !important; color: #fff !important; font-weight: 700 !important;
    font-size: 0.9rem !important; text-decoration: none !important; transition: background 0.15s !important;
}
.btn-box:hover { background: #7a5808 !important; }


/* Trust icons */
.trust-icons {
    max-width: 1280px !important; margin: 0 auto 60px !important; padding: 0 28px !important;
    display: grid !important; grid-template-columns: repeat(4, 1fr) !important; gap: 18px !important; text-align: center !important;
}
.trust-item {
    padding: 22px 14px 18px !important; border-radius: 18px !important;
    border: 1px solid #c5e0a8 !important; background: #d5e5ca !important;
    font-size: 0.86rem !important; font-weight: 700 !important;
}

.body.dark .trust-item i {
    padding: 22px 14px 18px !important; border-radius: 18px !important;
    border: 1px solid #343830 !important; background: #d5e5ca !important;
    font-size: 0.86rem !important; font-weight: 700 !important;
}
.trust-item i { font-size: 1.8rem !important; display: block !important; margin-bottom: 9px !important; color: #2c5e2e !important; }


/* Empty state */
.empty-state { text-align: center !important; padding: 5rem 1rem !important; }
.empty-state p { color: #6a8a5a !important; margin-bottom: 1rem !important; }
.empty-state a { color: #2c5e2e !important; font-weight: 700 !important; text-decoration: underline !important; }


/* Product badges */
.product-badges { position: absolute !important; bottom: 8px !important; left: 8px !important; display: flex !important; gap: 5px !important; flex-wrap: wrap !important; }
.badge { font-size: 0.63rem !important; font-weight: 700 !important; padding: 3px 7px !important; border-radius: 999px !important; background: rgba(0,0,0,0.6) !important; color: #fff !important; backdrop-filter: blur(3px) !important; }
.badge.gluten { background: #17451f !important; color: #afedb875 !important; }
.badge.bio    { background: #384a10 !important; color: #e0ffa0 !important; }
.badge.vegan  { background: #4a3008 !important; color: #ffd88a !important; }


/* Responsive fallback */
@media (max-width: 860px) {
    .shop-layout { grid-template-columns: 1fr !important; }
    .filters-sidebar { position: static !important; }
}
@media (max-width: 480px) {
    .products-grid { grid-template-columns: repeat(2, 1fr) !important; }
    .trust-icons { grid-template-columns: 1fr 1fr !important; }
}

/* Image — hauteur fixe */
.product-img {
    width: 100% !important;
    height: 200px !important;        /* ← fixe, pas aspect-ratio */
    overflow: hidden !important;
    background: #e8f5e0 !important;
    flex-shrink: 0 !important;       /* ← ne se compresse pas */
}

/* Titre — hauteur fixe sur 2 lignes */
.product-title {
    font-size: 1.0rem !important;
    font-weight: 600 !important;
    margin-bottom: 6px !important;
    line-height: 1.3 !important;
    min-height: 2.6rem !important;   /* ← exactement 2 lignes */
    display: -webkit-box !important;
    -webkit-line-clamp: 2 !important;
    -webkit-box-orient: vertical !important;
    overflow: hidden !important;
}

/* Régime — hauteur fixe 1 ligne */
.regime-status {
    font-size: 0.71rem !important;
    color: #6a8a5a !important;
    margin-bottom: 10px !important;
    min-height: 1rem !important;     /* ← espace réservé même si vide */
    white-space: nowrap !important;
    overflow: hidden !important;
    text-overflow: ellipsis !important;
}

body.dark .cart-modal h3 {
  color: black;
}
body.dark .cart-modal .cart-empty-msg {
  color: black;
}

</style>

<section class="hero-shop">
  <div class="container"><br><br>
    <h1>Découvrez nos produits sains</h1>
    <p>Adaptés à votre régime : sans gluten, sans lactose, vegan, bio</p>

    <!-- ★ BARRE DE RECHERCHE -->
    <form method="GET" id="searchForm" style="margin-top:1.4rem;display:flex;gap:.5rem;max-width:500px;margin-inline:auto;">
      <!-- preserve active filters -->
      <?php foreach ($_GET as $k => $v):
        if ($k === 'search' || $k === 'page') continue;
        if (is_array($v)):
          foreach ($v as $item): ?>
            <input type="hidden" name="<?= htmlspecialchars($k) ?>[]" value="<?= htmlspecialchars($item) ?>">
          <?php endforeach;
        else: ?>
          <input type="hidden" name="<?= htmlspecialchars($k) ?>" value="<?= htmlspecialchars($v) ?>">
      <?php endif; endforeach; ?>

      <input type="text" name="search"
             value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
             placeholder=" Rechercher un produit…"
             style="flex:1;padding:.75rem 1.1rem;border-radius:30px;border:none;font-size:1rem;outline:none;box-shadow:0 2px 12px rgba(0,0,0,.12);">
      <button type="submit"
              style="padding:.75rem 1.4rem;border-radius:30px;border:none;background:var(--green-dark);color:#fff;font-weight:700;cursor:pointer;white-space:nowrap;">
        Rechercher
      </button>
    </form>
  </div>
</section>


<div class="shop-layout">

  <!-- ═══ SIDEBAR FILTERS ═══ -->
  <form method="GET" id="filterForm">

    <!-- preserve search -->
    <?php if (!empty($_GET['search'])): ?>
      <input type="hidden" name="search" value="<?= htmlspecialchars($_GET['search']) ?>">
    <?php endif; ?>

    <aside class="filters-sidebar">
      <h2>Filtres</h2>

      <!-- BASE -->
      <div class="filter-group">
        <h4>Base</h4>
        <?php
        $dietOptions = [
          'vegan'      => 'Vegan',
          'vegetarian' => 'Vegetarian',
          'bio'        => 'Bio',
        ];
        foreach ($dietOptions as $val => $label):
          $checked = in_array($val, (array)($_GET['filters'] ?? [])) ? 'checked' : '';
        ?>
        <label>
          <input type="checkbox" name="filters[]" value="<?= $val ?>" <?= $checked ?>>
          <?= $label ?>
        </label>
        <?php endforeach; ?>
      </div>

      <!-- INTOLERANCES -->
      <div class="filter-group">
        <h4>Intolérances</h4>
        <?php
        $intolerances = [
          'sans-gluten'   => 'Gluten free',
          'sans-lactose'  => 'Lactose free',
          'sans-sucre'    => 'Sugar free',
          'sans-additifs' => 'No additives',
        ];
        foreach ($intolerances as $val => $label):
          $checked = in_array($val, (array)($_GET['filters'] ?? [])) ? 'checked' : '';
        ?>
        <label>
          <input type="checkbox" name="filters[]" value="<?= $val ?>" <?= $checked ?>>
          <?= $label ?>
        </label>
        <?php endforeach; ?>
      </div>

      <!-- HEALTHY STYLE -->
      <div class="filter-group">
        <h4>Healthy style</h4>
        <?php
        $healthy = [
          'natural'     => 'Natural',
          'homemade'    => 'Homemade',
          'low-calorie' => 'Low calorie',
          'high-fiber'  => 'High fiber',
          'protein-rich'=> 'Protein rich',
        ];
        foreach ($healthy as $val => $label):
          $checked = in_array($val, (array)($_GET['filters'] ?? [])) ? 'checked' : '';
        ?>
        <label>
          <input type="checkbox" name="filters[]" value="<?= $val ?>" <?= $checked ?>>
          <?= $label ?>
        </label>
        <?php endforeach; ?>
      </div>

      <!-- BONUS -->
      <div class="filter-group">
        <h4>Bonus</h4>
        <?php
        $bonus = ['traditional' => 'Traditional', 'gourmet' => 'Gourmet'];
        foreach ($bonus as $val => $label):
          $checked = in_array($val, (array)($_GET['filters'] ?? [])) ? 'checked' : '';
        ?>
        <label>
          <input type="checkbox" name="filters[]" value="<?= $val ?>" <?= $checked ?>>
          <?= $label ?>
        </label>
        <?php endforeach; ?>
      </div>

      <!-- STOCK -->
      <div class="filter-group">
        <h4>Disponibilité</h4>
        <label>
          <input type="checkbox" name="in_stock" value="1"
                 <?= !empty($_GET['in_stock']) ? 'checked' : '' ?>>
          En stock uniquement
        </label>
      </div>

      <!-- ★ PRICE RANGE -->
      <div class="filter-group">
        <h4>Prix (TND)</h4>
        <div style="display:flex;gap:.5rem;align-items:center;">
          <input type="number" name="min_price" placeholder="Min"
                 value="<?= htmlspecialchars($_GET['min_price'] ?? '') ?>"
                 min="0" step="0.001"
                 style="width:72px;padding:.35rem .5rem;border:1px solid var(--border);border-radius:8px;font-size:.85rem;">
          <span style="color:var(--muted);">–</span>
          <input type="number" name="max_price" placeholder="Max"
                 value="<?= htmlspecialchars($_GET['max_price'] ?? '') ?>"
                 min="0" step="0.001"
                 style="width:72px;padding:.35rem .5rem;border:1px solid var(--border);border-radius:8px;font-size:.85rem;">
        </div>
        <!-- Visual range slider (cosmetic, updates the number inputs) -->
        <div style="margin-top:.6rem;">
          <input type="range" id="priceRangeMax" min="0" max="200" step="1"
                 value="<?= htmlspecialchars($_GET['max_price'] ?? 200) ?>"
                 style="width:100%;accent-color:var(--green);">
          <div style="display:flex;justify-content:space-between;font-size:.72rem;color:var(--muted);">
            <span>0 TND</span><span id="priceRangeLabel"><?= (int)($_GET['max_price'] ?? 200) ?> TND</span>
          </div>
        </div>
      </div>

      <button type="submit" class="reset-filters" style="background:var(--green);color:#fff;margin-bottom:.5rem;">
        Appliquer
      </button>
      <a href="<?= BASE ?>/view/client/produits.php" class="reset-filters" style="display:block;text-align:center;color:var(--green);text-decoration:none;">
        Réinitialiser
      </a>

    </aside>
  </form>


  <!-- ═══ PRODUCTS AREA ═══ -->
  <div class="products-area">

    <div class="top-bar">
      <div>
        <strong><?= $total ?></strong> produit<?= $total > 1 ? 's' : '' ?>
        <?php if (!empty($_GET['search'])): ?>
          pour «&nbsp;<em><?= htmlspecialchars($_GET['search']) ?></em>&nbsp;»
        <?php endif; ?>
      </div>
    </div>

    <?php if (empty($products)): ?>
      <div style="text-align:center;padding:4rem 1rem;color:var(--muted);">
        <p>Aucun produit ne correspond à vos critères.</p>
        <a href="<?= BASE ?>/view/client/produits.php" style="color:var(--green);">Voir tous les produits</a>
      </div>
    <?php else: ?>

    <div class="products-grid">
      <?php foreach ($products as $p): ?>
      <a href="<?= BASE ?>/view/client/produit_detail.php?id=<?= $p->id ?>" class="product-card-link">
        <div class="product-card">

          <div class="product-img" >
           <img src="<?= BASE ?>/public/uploads/produits/pics/bg_final/<?= $p->id ?>.jpg"
     alt="<?= htmlspecialchars($p->nom) ?>"
           onerror="this.src='https://placehold.co/600x600/e8f0e3/2c5e2e?text=Benna'"/>
          </div>

        
          <div class="product-info">
            <div class="product-title"><?= htmlspecialchars($p->nom) ?></div>
            <div class="price"><?= number_format($p->prix, 3) ?> TND</div>
            <?php if ($p->note_moyenne > 0): ?>
                <div style="color:var(--gold);font-size:0.75rem;margin:4px 0;">
                    <?= str_repeat('★', round($p->note_moyenne)) ?>
                      <?= str_repeat('☆', 5 - round($p->note_moyenne)) ?>
                      <span style="color:var(--muted);">(<?= number_format($p->note_moyenne,1) ?>)</span>
                </div>
              <?php endif; ?>
            <div class="stock-status">
              <?= $p->stock > 0 ? 'En stock' : ' Rupture' ?>
            </div>
            <?php if ($p->regime): ?>
            <div class="regime-status" style="font-size:.75rem;color:var(--muted);margin-top:.2rem;">
              <?= htmlspecialchars($p->regime) ?>
            </div>
            <?php endif; ?>

            <div class="card-actions">
              <?php if (isset($_SESSION['user_id'])): ?>
    <?php if ($p->stock > 0): ?>
        <form method="POST"
              action="<?= BASE ?>/controller/panier_controller.php?action=add"
              class="add-to-cart-form"
              onclick="event.stopPropagation()">
          <input type="hidden" name="produit_id" value="<?= $p->id ?>">
          <input type="hidden" name="quantite" value="1">
          <button class="add-to-cart" type="submit">Ajouter au panier</button>
        </form>
    <?php else: ?>
        <button class="add-to-cart" disabled style="background:#ccc;cursor:not-allowed;">
          Rupture
        </button>
    <?php endif; ?>
<?php endif; ?>
            </div>
          </div>

        </div>
      </a>
      <?php endforeach; ?>
    </div>

    <!-- PAGINATION -->
    <?php if ($pages > 1): ?>
    <div class="pagination">
      <?php if ($page > 1): ?>
        <a href="?<?= http_build_query(array_merge($queryParams, ['page' => $page - 1])) ?>">←</a>
      <?php endif; ?>
      <?php for ($i = 1; $i <= $pages; $i++): ?>
        <a href="?<?= http_build_query(array_merge($queryParams, ['page' => $i])) ?>"
           class="<?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
      <?php endfor; ?>
      <?php if ($page < $pages): ?>
        <a href="?<?= http_build_query(array_merge($queryParams, ['page' => $page + 1])) ?>">→</a>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php endif; // end empty check ?>

  </div><!-- /products-area -->
</div><!-- /shop-layout -->


<div class="prebuilt-section">
  <h2>Choisissez votre box</h2>
  <p class="subtitle">Sélection rapide selon votre régime</p>

  <div class="prebuilt-grid">
    <div class="prebuilt-card" onclick="goBox('sans-gluten')">
      <div class="prebuilt-icon"><i class="fas fa-wheat-awn-circle-exclamation"></i></div>
      <span>Sans gluten</span>
    </div>
    <div class="prebuilt-card" onclick="goBox('sans-lactose')">
      <div class="prebuilt-icon"><i class="fas fa-glass-water"></i></div>
      <span>Sans lactose</span>
    </div>
    <div class="prebuilt-card" onclick="goBox('sans-sucre')">
      <div class="prebuilt-icon"><i class="fas fa-candy-cane"></i></div>
      <span>Sans sucre</span>
    </div>
    <div class="prebuilt-card" onclick="goBox('vegan')">
      <div class="prebuilt-icon"><i class="fas fa-seedling"></i></div>
      <span>Vegan</span>
    </div>
    <div class="prebuilt-card" onclick="goBox('bio')">
      <div class="prebuilt-icon"><i class="fas fa-leaf"></i></div>
      <span>Bio</span>
    </div>
    <div class="prebuilt-card" onclick="goBox('protein-rich')">
      <div class="prebuilt-icon"><i class="fas fa-dumbbell"></i></div>
      <span>Sport</span>
    </div>
    <div class="prebuilt-card" onclick="goBox('low-calorie')">
      <div class="prebuilt-icon"><i class="fas fa-fire"></i></div>
      <span>Low calorie</span>
    </div>
    <div class="prebuilt-card highlight" onclick="goBox('mystery')">
      <div class="prebuilt-icon"><i class="fas fa-gift"></i></div>
      <span>Boîte mystère</span>
    </div>
  </div>
</div>

<!-- BOX SECTION -->
<div class="custom-box">
  <h2>Build your custom box</h2>
  <p>Choose your 6 favorite snacks, get a unique box delivered to your home with a surprise gift added .</p>
  <a href="<?= BASE ?>/view/client/box.php" class="btn-box">Create my box →</a>
</div> <br>

<!-- CONFIANCE -->
<div class="trust-icons">
  <div class="trust-item"><i class="fas fa-home"></i><br/>100% fait maison</div>
  <div class="trust-item"><i class="fas fa-allergies"></i><br/>Allergènes contrôlés</div>
  <div class="trust-item"><i class="fas fa-truck"></i><br/>Livraison 24/48h</div>
  <div class="trust-item"><i class="fas fa-seedling"></i><br/>Ingrédients naturels</div>
</div><br>
<br>
<!-- FLOATING CART -->
<a href="<?= BASE ?>/view/client/panier.php" class="floating-cart" id="floatingCartBtn">
  <span class="floating-cart-icon">🛒</span>
  <span class="floating-cart-count" id="cartCount"><?= $cartCount ?></span>
</a>
<div class="cart-overlay" id="cartOverlay">
  <div class="cart-modal">
    <div class="cart-modal-header">
      <h3>Mon Panier</h3>
      <button class="cart-close" id="cartClose">&times;</button>
    </div>
    <div class="cart-modal-body">
      <p class="cart-empty-msg">Vous avez <span id="cartModalCount"><?= $cartCount ?></span> article(s).</p>
    </div>


    <div class="cart-modal-footer">
      <a href="<?= BASE ?>/view/client/panier.php" class="nav-join view-cart-btn">Voir le panier</a>
    </div>
  </div>
</div>

<script>
/* Price range slider → max_price input */
const slider = document.getElementById('priceRangeMax');
const label  = document.getElementById('priceRangeLabel');
const maxInput = document.querySelector('input[name="max_price"]');
if (slider) {
  slider.addEventListener('input', () => {
    label.textContent = slider.value + ' TND';
    if (maxInput) maxInput.value = slider.value;
  });
}

/* Cart modal */
const floatingBtn = document.getElementById('floatingCartBtn');
const overlay     = document.getElementById('cartOverlay');
const closeBtn    = document.getElementById('cartClose');
if (floatingBtn && overlay) {
  floatingBtn.addEventListener('click', e => { e.preventDefault(); overlay.classList.add('open'); });
  closeBtn?.addEventListener('click', () => overlay.classList.remove('open'));
  overlay.addEventListener('click', e => { if (e.target === overlay) overlay.classList.remove('open'); });
}


function goBox(type) {
  window.location.href = "<?= BASE ?>/view/client/box.php?type=" + type;
}


// AJAX add-to-cart for all forms on this page
document.querySelectorAll('form[action*="action=add"]').forEach(form => {
  form.addEventListener('submit', function(e) {
    e.preventDefault();
    e.stopPropagation();

    const btn = form.querySelector('button[type="submit"]');
    const origText = btn ? btn.textContent : '';
    if (btn) { btn.textContent = '✓ Ajouté !'; btn.disabled = true; btn.style.background='#1b5e20'; }

    const formData = new FormData(form);

fetch('<?= BASE ?>/controller/panier_controller.php?action=add', {
      method: 'POST',
      body: formData,
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        // Update all cart counters on page
        document.querySelectorAll('#cartCount, .floating-cart-count').forEach(el => {
          el.textContent = data.count;
        });
        document.querySelectorAll('#cartModalCount').forEach(el => {
          el.textContent = data.count;
        });
      }
      // Re-enable button after 1.5s
      setTimeout(() => {
        if (btn) { btn.textContent = origText; btn.disabled = false; btn.style.background=''; }
      }, 1500);
    })
    .catch(err => {
      console.error(err);
      if (btn) { btn.textContent = origText; btn.disabled = false; btn.style.background=''; }
    });
  });
});
</script>

<?php include "partials/footer.php"; ?>
