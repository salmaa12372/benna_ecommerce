/**
 * scriptP.js — Boutique benna/Benna
 * 
 * IMPORTANT: productsData, isLoggedIn, BASE_URL sont injectés
 * dynamiquement par produits.php via PHP (json_encode depuis la BDD).
 * Ce fichier ne contient AUCUNE donnée codée en dur.
 */

/* ── Utilitaires ─────────────────────────────── */
const $ = (s, p = document) => p.querySelector(s);
const $$ = (s, p = document) => [...p.querySelectorAll(s)];

if ('scrollRestoration' in history) {
    history.scrollRestoration = 'manual';
}
window.onload = () => { window.scrollTo(0, 0); };

/* ── Navbar scroll ───────────────────────────── */
const navbar = $("#navbar");
function updateNavbar() {
    if (!navbar) return;
    navbar.classList.toggle("scrolled", window.scrollY > 60);
}
window.addEventListener("scroll", updateNavbar, { passive: true });
updateNavbar();

/* ── Dark mode ───────────────────────────────── */
const toggle = document.getElementById("holo-toggle");
const THEME_KEY = "bena-theme";
function setTheme(theme) {
    document.body.classList.toggle("dark", theme === "dark");
    if (toggle) toggle.checked = theme === "dark";
}
const savedTheme = localStorage.getItem(THEME_KEY);
setTheme(savedTheme || (window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light"));
toggle?.addEventListener("change", () => {
    const t = toggle.checked ? "dark" : "light";
    setTheme(t);
    localStorage.setItem(THEME_KEY, t);
});

/* ── Hamburger menu ──────────────────────────── */
const hamburger = document.getElementById("hamburger");
const navLinks  = document.getElementById("navLinks");
const navLinks2 = document.getElementById("navLinks2");
hamburger?.addEventListener("click", () => {
    navLinks?.classList.toggle("active");
    navLinks2?.classList.toggle("active");
});

/* ── Intersection Observer (animations) ─────── */
const io = new IntersectionObserver(
    entries => entries.forEach(e => {
        if (e.isIntersecting) { e.target.classList.add("visible"); io.unobserve(e.target); }
    }),
    { threshold: 0.12, rootMargin: "0px 0px -40px 0px" }
);
$$(".fade-in, .slide-in-left, .badge-cert-item, .badge-cert-sep").forEach(el => io.observe(el));

/* ── État global des filtres ─────────────────── */
let currentFilters = {
    glutenFree:   false,
    lactoseFree:  false,
    sugarFree:    false,
    bio:          false,
    vegan:        false,
    minPrice:     0,
    maxPrice:     100,
    category:     "all",
    searchTerm:   ""
};
let currentSort = "default";
let currentPage = 1;
const itemsPerPage = 6;
let wishlist = JSON.parse(localStorage.getItem("wishlist") || "[]");

/* ── Filtrage ────────────────────────────────── */
function filterProducts() {
    // productsData est injecté par produits.php depuis la BDD
    if (typeof productsData === 'undefined' || !productsData.length) return [];

    return productsData.filter(p => {
        if (currentFilters.glutenFree  && !p.glutenFree)  return false;
        if (currentFilters.lactoseFree && !p.lactoseFree) return false;
        if (currentFilters.sugarFree   && !p.sugarFree)   return false;
        if (currentFilters.bio         && !p.bio)         return false;
        if (currentFilters.vegan       && !p.vegan)       return false;
        if (p.price < currentFilters.minPrice || p.price > currentFilters.maxPrice) return false;
        if (currentFilters.category !== "all" && p.category !== currentFilters.category) return false;
        if (currentFilters.searchTerm &&
            !p.name.toLowerCase().includes(currentFilters.searchTerm.toLowerCase())) return false;
        return true;
    });
}

/* ── Tri ─────────────────────────────────────── */
function sortProducts(products) {
    let sorted = [...products];
    if (currentSort === "price_asc")   sorted.sort((a, b) => a.price - b.price);
    else if (currentSort === "price_desc")  sorted.sort((a, b) => b.price - a.price);
    else if (currentSort === "popularite")  sorted.sort((a, b) => b.rating - a.rating);
    else if (currentSort === "nouveaute")   sorted.sort((a, b) => new Date(b.date) - new Date(a.date));
    return sorted;
}

/* ── Rendu des produits ──────────────────────── */
function renderProducts() {
    const grid = document.getElementById("productsGrid");
    const countEl = document.getElementById("resultCount");
    if (!grid) return;

    const filtered  = filterProducts();
    const sorted    = sortProducts(filtered);
    const totalPages = Math.ceil(sorted.length / itemsPerPage);
    const start     = (currentPage - 1) * itemsPerPage;
    const paginated = sorted.slice(start, start + itemsPerPage);

    if (countEl) countEl.textContent = filtered.length;

    if (filtered.length === 0) {
        grid.innerHTML = `
            <div style="grid-column:1/-1;text-align:center;padding:3rem;color:var(--muted);">
                <div style="font-size:3rem;margin-bottom:.8rem;">🔍</div>
                <p>Aucun produit ne correspond à vos filtres.</p>
                <button onclick="resetAllFilters()" style="margin-top:.8rem;padding:.5rem 1.2rem;border:2px solid var(--green);background:none;border-radius:10px;cursor:pointer;color:var(--green);font-family:var(--font-body);">
                    Réinitialiser les filtres
                </button>
            </div>`;
        renderPagination(0);
        return;
    }

    const detailBase = (typeof BASE_URL !== 'undefined' ? BASE_URL : '') + '/view/client/produit_detail.php?id=';

    grid.innerHTML = paginated.map(p => {
        const isFav     = wishlist.includes(p.id);
        const inStock   = p.stock;
        const stockHtml = inStock
            ? '<span class="stock-ok"> En stock</span>'
            : '<span class="stock-ko"> Rupture</span>';
        const stars = '★'.repeat(Math.floor(p.rating)) + (p.rating % 1 >= 0.5 ? '½' : '');

        return `
        <div class="product-card" data-id="${p.id}">
            <div class="product-img">
                <img src="${p.img}" alt="${p.name}" loading="lazy"
                     onerror="this.src='https://placehold.co/400x400/e8f0e3/2c5e2e?text=${encodeURIComponent(p.name)}'"/>
                <div class="fav-icon ${isFav ? 'active' : ''}" data-id="${p.id}" title="Ajouter aux favoris">
                    <i class="fas fa-heart"></i>
                </div>
                <div class="product-badges">
                    ${p.glutenFree  ? '<span class="badge gluten"> Sans gluten</span>' : ''}
                    ${p.bio         ? '<span class="badge bio"> Bio</span>'            : ''}
                    ${p.vegan       ? '<span class="badge vegan"> Vegan</span>'        : ''}
                    ${p.sugarFree   ? '<span class="badge"> Sans sucre</span>'         : ''}
                    ${p.lactoseFree ? '<span class="badge"> Sans lactose</span>'       : ''}
                </div>
            </div>
            <div class="product-info">
                <a href="${detailBase}${p.db_id || p.id}" style="text-decoration:none;color:inherit;">
                    <div class="product-title">${p.name}</div>
                </a>
                <div class="rating" style="color:var(--gold,#b7893a);">${stars} <span style="font-size:.82rem;color:var(--muted);">(${p.rating.toFixed(1)})</span></div>
                <div class="price">${p.price.toFixed(3)} TND</div>
                <div class="stock-status">${stockHtml}</div>
                <div class="card-actions">
                    <button class="add-to-cart" data-id="${p.id}" ${!inStock ? 'disabled style="opacity:.5;cursor:not-allowed;"' : ''}>
                        <i class="fas fa-cart-plus"></i> ${inStock ? 'Ajouter' : 'Indisponible'}
                    </button>
                </div>
            </div>
        </div>`;
    }).join("");

    // Attach events
    grid.querySelectorAll(".fav-icon").forEach(icon => {
        icon.addEventListener("click", e => {
            e.stopPropagation();
            toggleWishlist(parseInt(icon.dataset.id));
            renderProducts();
        });
    });
    grid.querySelectorAll(".add-to-cart:not([disabled])").forEach(btn => {
        btn.addEventListener("click", e => {
            e.stopPropagation();
            addToCart(parseInt(btn.dataset.id));
        });
    });

    renderPagination(totalPages);
}

/* ── Pagination ──────────────────────────────── */
function renderPagination(totalPages) {
    const pagDiv = document.getElementById("pagination");
    if (!pagDiv) return;
    if (totalPages <= 1) { pagDiv.innerHTML = ""; return; }
    let html = "";
    for (let i = 1; i <= totalPages; i++) {
        html += `<button class="page-btn ${i === currentPage ? 'active' : ''}" data-page="${i}">${i}</button>`;
    }
    pagDiv.innerHTML = html;
    pagDiv.querySelectorAll(".page-btn").forEach(btn => {
        btn.addEventListener("click", () => {
            currentPage = parseInt(btn.dataset.page);
            renderProducts();
            const area = document.querySelector('.products-area');
            if (area) window.scrollTo({ top: area.offsetTop - 80, behavior: 'smooth' });
        });
    });
}

/* ── Wishlist ─────────────────────────────────── */
function toggleWishlist(id) {
    if (wishlist.includes(id)) wishlist = wishlist.filter(i => i !== id);
    else wishlist.push(id);
    localStorage.setItem("wishlist", JSON.stringify(wishlist));
}

/* ── Ajouter au panier (via PHP) ─────────────── */
function addToCart(id) {
    const BASE = (typeof BASE_URL !== 'undefined' ? BASE_URL : '');
    const loggedIn = (typeof isLoggedIn !== 'undefined' ? isLoggedIn : false);

    if (!loggedIn) {
        window.location.href = BASE + '/view/client/signup.php';
        return;
    }

    const product = (typeof productsData !== 'undefined')
        ? productsData.find(p => p.id === id)
        : null;

    if (!product) return;

    // Feedback visuel sur le bouton
    const btn = document.querySelector(`.add-to-cart[data-id="${id}"]`);
    if (btn) {
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i> Ajouté !';
        btn.style.background = 'var(--green,#2d6a4f)';
        btn.style.color = 'white';
        btn.disabled = true;
    }

    // Soumettre vers le contrôleur PHP
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = BASE + '/controller/panier_controller.php?action=add';
    form.innerHTML = `
        <input name="produit_id" value="${product.db_id || id}">
        <input name="quantite" value="1">`;
    document.body.appendChild(form);

    // Petit délai pour montrer le feedback avant redirect
    setTimeout(() => form.submit(), 400);
}

/* ── Appliquer filtres & re-rendre ───────────── */
function applyFiltersAndRender() {
    currentPage = 1;
    renderProducts();
}

function resetAllFilters() {
    currentFilters = { glutenFree:false, lactoseFree:false, sugarFree:false, bio:false, vegan:false, minPrice:0, maxPrice:100, category:"all", searchTerm:"" };
    $$(".diet-filter").forEach(cb => cb.checked = false);
    const minP = document.getElementById("minPrice");
    const maxP = document.getElementById("maxPrice");
    const catF = document.getElementById("categoryFilter");
    const srcI = document.getElementById("searchInput");
    const srtS = document.getElementById("sortSelect");
    if (minP) minP.value = 0;
    if (maxP) maxP.value = 100;
    if (catF) catF.value = "all";
    if (srcI) srcI.value = "";
    if (srtS) srtS.value = "default";
    currentSort = "default";
    applyFiltersAndRender();
}

/* ── Event listeners filtres ─────────────────── */
$$(".diet-filter").forEach(cb => {
    cb.addEventListener("change", e => {
        currentFilters[e.target.dataset.filter] = e.target.checked;
        applyFiltersAndRender();
    });
});

document.getElementById("minPrice")?.addEventListener("input", e => {
    currentFilters.minPrice = parseFloat(e.target.value) || 0;
    applyFiltersAndRender();
});
document.getElementById("maxPrice")?.addEventListener("input", e => {
    currentFilters.maxPrice = parseFloat(e.target.value) || 100;
    applyFiltersAndRender();
});
document.getElementById("categoryFilter")?.addEventListener("change", e => {
    currentFilters.category = e.target.value;
    applyFiltersAndRender();
});
document.getElementById("sortSelect")?.addEventListener("change", e => {
    currentSort = e.target.value;
    applyFiltersAndRender();
});
document.getElementById("searchInput")?.addEventListener("input", e => {
    currentFilters.searchTerm = e.target.value;
    applyFiltersAndRender();
});
document.getElementById("resetFilters")?.addEventListener("click", resetAllFilters);

/* ── Floating cart overlay ───────────────────── */
const floatingCartBtn = document.getElementById("floatingCartBtn");
const cartOverlay     = document.getElementById("cartOverlay");
const cartClose       = document.getElementById("cartClose");

if (floatingCartBtn && cartOverlay) {
    floatingCartBtn.addEventListener("click", e => {
        e.preventDefault();
        cartOverlay.classList.add("active");
    });
    cartClose?.addEventListener("click", () => cartOverlay.classList.remove("active"));
    cartOverlay.addEventListener("click", e => {
        if (e.target === cartOverlay) cartOverlay.classList.remove("active");
    });
}

/* ── Init ────────────────────────────────────── */
// Vérifier que productsData est bien défini par PHP avant de rendre
if (typeof productsData !== 'undefined' && Array.isArray(productsData)) {
    renderProducts();
} else {
    // productsData non injecté (chargement sur page non-PHP)
    const grid = document.getElementById("productsGrid");
    if (grid) {
        grid.innerHTML = `
            <div style="grid-column:1/-1;text-align:center;padding:3rem;color:var(--muted);">
                <div style="font-size:2.5rem;margin-bottom:.8rem;">⚠️</div>
                <p>Impossible de charger les produits. Veuillez recharger la page.</p>
            </div>`;
    }
}
