const $ = (sel, root = document) => root.querySelector(sel);
const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));
if ('scrollRestoration' in history) {
  history.scrollRestoration = 'manual';
}

window.onload = () => {
  window.scrollTo(0, 0);
};

const navbar = $("#navbar");
function updateNavbar() {
  if (!navbar) return;
  navbar.classList.toggle("scrolled", window.scrollY > 60);
}
window.addEventListener("scroll", updateNavbar, { passive: true });
updateNavbar();

const menuToggle = $("#menuToggle");
const navLinks = $("#navLinks");

function closeMenu() {
  navLinks?.classList.remove("open");
  menuToggle?.setAttribute("aria-expanded", "false");
}
function toggleMenu() {
  if (!navLinks || !menuToggle) return;
  const open = navLinks.classList.toggle("open");
  menuToggle.setAttribute("aria-expanded", String(open));
}

menuToggle?.addEventListener("click", e => { e.stopPropagation(); toggleMenu(); });
$$("a", navLinks).forEach(a => a.addEventListener("click", closeMenu));
document.addEventListener("click", e => {
  if (!navLinks?.contains(e.target) && !menuToggle?.contains(e.target)) closeMenu();
});
document.addEventListener("keydown", e => { if (e.key === "Escape") closeMenu(); });


// Updated toggle ID to match simplified HTML
const toggle = document.getElementById("holo-toggle");
const THEME_KEY = "bena-theme";

function setTheme(theme) {
  document.body.classList.toggle("dark", theme === "dark");
  if (toggle) toggle.checked = theme === "dark";
}

const saved = localStorage.getItem(THEME_KEY);
setTheme(saved || (window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light"));

toggle?.addEventListener("change", () => {
  const t = toggle.checked ? "dark" : "light";
  setTheme(t);
  localStorage.setItem(THEME_KEY, t);
});


const io = new IntersectionObserver(
  entries => entries.forEach(e => {
    if (e.isIntersecting) { e.target.classList.add("visible"); io.unobserve(e.target); }
  }),
  { threshold: 0.12, rootMargin: "0px 0px -40px 0px" }
);
$$(".fade-in, .slide-in-left, .badge-cert-item, .badge-cert-sep").forEach(el => io.observe(el));


let currentCartCount = 0;
$$(".pc-add").forEach(btn => {
  btn.addEventListener("click", function () {
    const orig = this.innerHTML;
    this.innerHTML = "✓";
    this.style.cssText = "background:var(--green);color:white;transform:scale(1.12)";
    setTimeout(() => { this.innerHTML = orig; this.style.cssText = ""; }, 1400);

    currentCartCount++;
    const cartCountEl = document.getElementById("cartCount");
    if (cartCountEl) {
      cartCountEl.textContent = currentCartCount;
      cartCountEl.style.transform = "scale(1.3)";
      setTimeout(() => cartCountEl.style.transform = "scale(1)", 200);
    }

    const cartModalCountEl = document.getElementById("cartModalCount");
    if (cartModalCountEl) {
      cartModalCountEl.textContent = currentCartCount;
    }
  });
});


$("#subscribeForm")?.addEventListener("submit", function (e) {
  e.preventDefault();
  const btn = $("#submitBtn");
  if (!btn) return;
  const orig = btn.textContent;
  btn.textContent = "✓ Welcome to the Circle!";
  btn.style.cssText = "background:var(--gold);color:white";
  setTimeout(() => { btn.textContent = orig; btn.style.cssText = ""; }, 2400);
  this.reset();
});


const gallery = [
  { title: "Stone Oven Baking", desc: "Traditional Tunisian tabouna oven, signature crunch and golden hue.", img: "pics/gallery-1.jpg", tags: ["Stone-baked", "Heritage"] },
  { title: "Harvesting Honey", desc: "Wild Jebel honey from northern Tunisia — pure, unfiltered, alive.", img: "pics/gallery-2.jpg", tags: ["Wild Honey", "Organic"] },
  { title: "Sfax Sesame Fields", desc: "Toasted sesame from Sfax, rich in flavour and tradition.", img: "pics/gallery-3.jpg", tags: ["Local", "Premium"] },
  { title: "Handcrafting Process", desc: "Each bite shaped by hand — small batches, unhurried care.", img: "pics/gallery-4.jpg", tags: ["Artisan", "Small batch"] },
];

let activeIndex = 0, autoInterval = null;
const stage = $("#carouselStage");
const dotsWrap = $("#carouselDots");
const scene = $("#carouselScene");

function buildCarousel() {
  if (!stage || !dotsWrap) return;
  stage.innerHTML = dotsWrap.innerHTML = "";
  gallery.forEach((item, idx) => {
    const card = document.createElement("div");
    card.className = "gc-card";
    card.innerHTML = `
      <img src="${item.img}" alt="${item.title}" loading="lazy">
      <div class="gc-body">
        <div class="gc-title">${item.title}</div>
        <p class="gc-desc">${item.desc}</p>
        <div class="gc-tags">${item.tags.map(t => `<span class="gc-tag">${t}</span>`).join("")}</div>
      </div>`;
    card.addEventListener("click", () => goTo(idx));
    stage.appendChild(card);
    const dot = document.createElement("div");
    dot.className = "c-dot" + (idx === 0 ? " active" : "");
    dot.addEventListener("click", () => goTo(idx));
    dotsWrap.appendChild(dot);
  });
  positionSlides(false);
}

function positionSlides(animate) {
  const cards = $$(".gc-card"), dots = $$(".c-dot"), total = gallery.length;
  cards.forEach((card, i) => {
    let off = (i - activeIndex + total) % total;
    if (off > total / 2) off -= total;
    let transform, opacity, zIndex, pointer;
    if (off === 0) { transform = "translateX(0) rotateY(0deg) scale(1)"; opacity = 1; zIndex = 10; pointer = "auto"; }
    else if (off === 1) { transform = "translateX(280px) rotateY(-25deg) scale(0.82)"; opacity = 0.62; zIndex = 6; pointer = "auto"; }
    else if (off === -1) { transform = "translateX(-280px) rotateY(25deg) scale(0.82)"; opacity = 0.62; zIndex = 6; pointer = "auto"; }
    else { transform = `translateX(${off * 520}px) scale(0.55)`; opacity = 0; zIndex = 1; pointer = "none"; }
    card.style.transition = animate ? "transform .7s cubic-bezier(0.23,1,0.32,1),opacity .45s ease" : "none";
    card.style.transform = transform;
    card.style.opacity = opacity;
    card.style.zIndex = zIndex;
    card.style.pointerEvents = pointer;
  });
  dots.forEach((d, i) => d.classList.toggle("active", i === activeIndex));
}

function goTo(idx) { activeIndex = (idx + gallery.length) % gallery.length; positionSlides(true); resetAuto(); }
function next() { goTo(activeIndex + 1); }
function prev() { goTo(activeIndex - 1); }
function startAuto() { stopAuto(); autoInterval = setInterval(next, 5000); }
function stopAuto() { if (autoInterval) clearInterval(autoInterval); autoInterval = null; }
function resetAuto() { startAuto(); }

$("#carouselPrev")?.addEventListener("click", () => { prev(); resetAuto(); });
$("#carouselNext")?.addEventListener("click", () => { next(); resetAuto(); });

let touchStartX = null;
scene?.addEventListener("touchstart", e => { touchStartX = e.touches[0].clientX; }, { passive: true });
scene?.addEventListener("touchend", e => {
  if (touchStartX == null) return;
  const diff = e.changedTouches[0].clientX - touchStartX;
  if (Math.abs(diff) > 40) diff < 0 ? next() : prev();
  resetAuto(); touchStartX = null;
});
scene?.addEventListener("mouseenter", stopAuto);
scene?.addEventListener("mouseleave", startAuto);

buildCarousel();
startAuto();
