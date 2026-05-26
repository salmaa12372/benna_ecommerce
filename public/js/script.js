const $ = (sel, root = document) => root.querySelector(sel);
const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

if ('scrollRestoration' in history) history.scrollRestoration = 'manual';
window.addEventListener('load', () => window.scrollTo(0, 0));

(function () {
  const canvas = document.getElementById('heroCanvas');
  if (!canvas) return;
  const ctx = canvas.getContext('2d');
  let W, H, pts = [];

  function resize() {
    W = canvas.width  = canvas.offsetWidth;
    H = canvas.height = canvas.offsetHeight;
  }
  resize();
  window.addEventListener('resize', resize);

  class Pt {
    constructor() { this.init(true); }
    init(rand = false) {
      this.x  = Math.random() * W;
      this.y  = rand ? Math.random() * H : H + 8;
      this.r  = Math.random() * 2.4 + 0.5;
      this.vy = -(Math.random() * 0.6 + 0.15);
      this.vx = (Math.random() - 0.5) * 0.2;
      this.a  = Math.random() * 0.8 + 0.1;
      this.da = Math.random() * 0.003 + 0.001;
      this.hue = Math.random() > 0.45 ? 42 : 130;
    }
    step() {
      this.x += this.vx; this.y += this.vy; this.a -= this.da;
      if (this.a <= 0 || this.y < -8) this.init();
    }
    draw() {
      ctx.beginPath();
      ctx.arc(this.x, this.y, this.r, 0, Math.PI * 2);
      ctx.fillStyle = `hsla(${this.hue},60%,70%,${this.a * 0.5})`;
      ctx.fill();
    }
  }

  for (let i = 0; i < 100; i++) pts.push(new Pt());

  (function loop() {
    ctx.clearRect(0, 0, W, H);
    pts.forEach(p => { p.step(); p.draw(); });
    requestAnimationFrame(loop);
  })();
})();


/* ══════════════════════════════════════════════════════════════
   DARK MODE — syncs across all pages, respects system preference
   Uses 'bena-theme' key (main.js) as the canonical key.
══════════════════════════════════════════════════════════════ */
(function () {
  const THEME_KEY = 'bena-theme';
  const toggle    = document.getElementById('holo-toggle');

  function setTheme(theme) {
    document.body.classList.toggle('dark', theme === 'dark');
    if (toggle) toggle.checked = (theme === 'dark');
  }

  const saved   = localStorage.getItem(THEME_KEY);
  const sysDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
  setTheme(saved || (sysDark ? 'dark' : 'light'));

  toggle?.addEventListener('change', function () {
    const t = this.checked ? 'dark' : 'light';
    setTheme(t);
    localStorage.setItem(THEME_KEY, t);
  });

  document.addEventListener('visibilitychange', () => {
    if (!document.hidden) setTheme(localStorage.getItem(THEME_KEY) || 'light');
  });
})();


/* ══════════════════════════════════════════════════════════════
   NAVBAR SCROLL
══════════════════════════════════════════════════════════════ */
const navbar = document.getElementById('navbar');
if (navbar) {
  function updateNavbar() {
    navbar.classList.toggle('scrolled', window.scrollY > 60);
  }
  window.addEventListener('scroll', updateNavbar, { passive: true });
  updateNavbar();
}


/* ══════════════════════════════════════════════════════════════
   HAMBURGER MENU
   (from main.js — absent in script.js)
══════════════════════════════════════════════════════════════ */
const hamburger = document.getElementById('hamburger');
const navLinks  = document.getElementById('navLinks');
const navLinks2 = document.getElementById('navLinks2');

hamburger?.addEventListener('click', (e) => {
  e.stopPropagation();
  navLinks?.classList.toggle('active');
  navLinks2?.classList.toggle('active');
});
document.addEventListener('click', e => {
  if (!hamburger?.contains(e.target) && !navLinks?.contains(e.target)) {
    navLinks?.classList.remove('active');
    navLinks2?.classList.remove('active');
  }
});


/* ══════════════════════════════════════════════════════════════
   SCROLL REVEAL — IntersectionObserver
   Merges both files' selector lists.
══════════════════════════════════════════════════════════════ */
const revealIO = new IntersectionObserver(
  entries => entries.forEach(e => {
    if (e.isIntersecting) { e.target.classList.add('visible'); revealIO.unobserve(e.target); }
  }),
  { threshold: 0.12, rootMargin: '0px 0px -40px 0px' }
);
$$(
  '.fade-in, .slide-in-left, .slide-in-right, .scale-in, .badge-cert-item, .badge-cert-sep'
).forEach(el => revealIO.observe(el));


/* ══════════════════════════════════════════════════════════════
   HERO ANIMATION SEQUENCE
   Marquee rows · product pop · orbit · parallax tilt
   (from script.js — absent in main.js)
══════════════════════════════════════════════════════════════ */
(function () {
  const rowLeft    = document.getElementById('mqLeft');
  const rowRight   = document.getElementById('mqRight');
  const product    = document.getElementById('heroProduct');
  const glowBurst  = document.getElementById('glowBurst');
  const orbitTrack = document.getElementById('orbitTrack');
  const orbitRing  = document.querySelector('.orbit-ring');

  if (!rowLeft || !rowRight || !product) return;

  /* Per-badge entry delays */
  const orbitItems = $$('.orbit-item', orbitTrack);
  const entryDelays = [0.9, 1.05, 1.2, 1.35];
  orbitItems.forEach((item, i) => {
    const badge = item.querySelector('.orbit-badge');
    if (badge) badge.style.setProperty('--badge-entry-delay', `${entryDelays[i]}s`);
  });

  let scrollLoopRunning = false;
  let posLeft  = 0;
  let posRight = 0;

  /* STEP 1 — Marquee slide-in */
  rowLeft.classList.add('entering-left');
  rowRight.classList.add('entering-right');

  /* STEP 2 — Product pop + orbit reveal */
  setTimeout(() => {
    glowBurst?.classList.add('burst');
    product.classList.add('product-pop');
    orbitRing?.classList.add('visible');
    orbitTrack?.classList.add('orbit-ready');
  }, 400);

  /* STEP 3 — Switch marquee to rAF, product to idle */
  setTimeout(() => {
    rowLeft.classList.remove('entering-left');
    rowRight.classList.remove('entering-right');
    rowLeft.classList.add('scrolling');
    rowRight.classList.add('scrolling');

    rowLeft.innerHTML  += rowLeft.innerHTML;
    rowRight.innerHTML += rowRight.innerHTML;

    posLeft  = 0;
    posRight = -(rowRight.scrollWidth / 2);

    if (!scrollLoopRunning) {
      scrollLoopRunning = true;
      scrollTick();
    }

    product.classList.remove('product-pop');
    product.classList.add('product-idle');
  }, 1700);

  /* Continuous marquee rAF */
  let lastScrollY = window.scrollY;
  let velExtra    = 0;

  window.addEventListener('scroll', () => {
    const delta = window.scrollY - lastScrollY;
    lastScrollY = window.scrollY;
    velExtra += delta * 0.4;
  }, { passive: true });

  function scrollTick() {
    const BASE = 0.45;
    posLeft  -= BASE + velExtra;
    posRight += BASE + velExtra;
    velExtra *= 0.88;

    const halfLeft  = rowLeft.scrollWidth  / 2;
    const halfRight = rowRight.scrollWidth / 2;
    if (posLeft  < -halfLeft)  posLeft  += halfLeft;
    if (posRight >  0)         posRight -= halfRight;

    rowLeft.style.transform  = `translateX(${posLeft.toFixed(2)}px)`;
    rowRight.style.transform = `translateX(${posRight.toFixed(2)}px)`;
    requestAnimationFrame(scrollTick);
  }

  /* Mouse parallax tilt */
  const stage = document.getElementById('heroStage');
  let tRX = 0, tRY = 0, cRX = 0, cRY = 0;
  document.addEventListener('mousemove', e => {
    if (!stage) return;
    const r  = stage.getBoundingClientRect();
    const cx = r.left + r.width  / 2;
    const cy = r.top  + r.height / 2;
    tRX = ((e.clientY - cy) / (window.innerHeight / 2)) * -8;
    tRY = ((e.clientX - cx) / (window.innerWidth  / 2)) *  10;
  }, { passive: true });

  (function tiltLoop() {
    cRX += (tRX - cRX) * 0.07;
    cRY += (tRY - cRY) * 0.07;
    if (stage) stage.style.transform = `perspective(900px) rotateX(${cRX.toFixed(2)}deg) rotateY(${cRY.toFixed(2)}deg)`;
    requestAnimationFrame(tiltLoop);
  })();
})();


/* ══════════════════════════════════════════════════════════════
   ORBIT — pause on badge hover
   (from script.js — absent in main.js)
══════════════════════════════════════════════════════════════ */
(function () {
  const track = document.getElementById('orbitTrack');
  if (!track) return;
  $$('.orbit-badge').forEach(badge => {
    badge.addEventListener('mouseenter', () => {
      track.style.animationPlayState = 'paused';
      $$('.orbit-badge').forEach(b => b.style.animationPlayState = 'paused');
    });
    badge.addEventListener('mouseleave', () => {
      track.style.animationPlayState = 'running';
      $$('.orbit-badge').forEach(b => b.style.animationPlayState = 'running');
    });
  });
})();


/* ══════════════════════════════════════════════════════════════
   CART
   Unified count variable; navCartBadge support from main.js.
══════════════════════════════════════════════════════════════ */
let cartCount = parseInt(document.getElementById('cartCount')?.textContent || '0', 10);

$$('.pc-add').forEach(btn => {
  btn.addEventListener('click', function () {
    const orig = this.innerHTML;
    this.innerHTML = '✓';
    this.style.cssText = 'background:var(--green);color:white;transform:scale(1.18);border-color:var(--green)';
    setTimeout(() => { this.innerHTML = orig; this.style.cssText = ''; }, 1500);

    cartCount++;
    const countEl    = document.getElementById('cartCount');
    const modalCount = document.getElementById('cartModalCount');
    const navBadge   = document.getElementById('navCartBadge');

    if (countEl) {
      countEl.textContent = cartCount;
      countEl.style.transform = 'scale(1.5)';
      setTimeout(() => countEl.style.transform = 'scale(1)', 250);
    }
    if (modalCount) modalCount.textContent = cartCount;
    if (navBadge)   navBadge.textContent   = cartCount;
  });
});

/* Floating cart modal */
const floatingCartBtn = document.getElementById('floatingCartBtn');
const cartOverlay     = document.getElementById('cartOverlay');
const cartClose       = document.getElementById('cartClose');

if (floatingCartBtn && cartOverlay) {
  floatingCartBtn.addEventListener('click', e => {
    e.preventDefault();
    cartOverlay.classList.add('active');
    document.body.style.overflow = 'hidden';
  });

  function closeCartModal() {
    cartOverlay.classList.remove('active');
    document.body.style.overflow = '';
  }
  cartClose?.addEventListener('click', closeCartModal);
  cartOverlay.addEventListener('click', e => { if (e.target === cartOverlay) closeCartModal(); });
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeCartModal(); });
}


/* ══════════════════════════════════════════════════════════════
   CONTACT FORM
   Success label from script.js ("Welcome to the Circle!").
══════════════════════════════════════════════════════════════ */
$('#subscribeForm')?.addEventListener('submit', function (e) {
  e.preventDefault();
  const btn = $('#submitBtn');
  if (!btn) return;
  const orig = btn.textContent;
  btn.textContent  = '✓ Welcome to the Circle!';
  btn.style.cssText = 'background:var(--gold);color:white';
  setTimeout(() => { btn.textContent = orig; btn.style.cssText = ''; }, 2600);
  this.reset();
});


/* ══════════════════════════════════════════════════════════════
   GALLERY CAROUSEL
   Uses script.js's English labels (canonical) while preserving
   the onerror fallback image from main.js.
══════════════════════════════════════════════════════════════ */
const galleryData = [
  { 
    title: 'Stone Oven Baking',
    desc: 'Traditional Tunisian tabouna oven — signature crunch and golden hue.',
    img: '/benna_final2/benna_final/benafinal/public/uploads/produits/pics/bg_final/tabouna.jpg',
    tags: ['Stone-baked', 'Heritage']
  },
  { 
    title: 'Harvesting Honey',
    desc: 'Wild Jebel honey from northern Tunisia — pure, unfiltered, alive.',
    img: '/benna_final2/benna_final/benafinal/public/uploads/produits/pics/bg_final/miel.jpg',
    tags: ['Wild Honey', 'Organic']
  },
  { 
    title: 'Sfax Sesame Fields',
    desc: 'Toasted sesame from Sfax, rich in flavour and tradition.',
    img: '/benna_final2/benna_final/benafinal/public/uploads/produits/pics/bg_final/ble.png',
    tags: ['Local', 'Premium']
  },
  { 
    title: 'Handcrafting Process',
    desc: 'Each bite shaped by hand — small batches, unhurried care.',
    img: '/benna_final2/benna_final/benafinal/public/uploads/produits/pics/bg_final/handcrafting.jpg',
    tags: ['Artisan', 'Small batch']
  }
];



let activeIndex  = 0;
let autoInterval = null;
const carouselStage = document.getElementById('carouselStage');
const carouselDots  = document.getElementById('carouselDots');
const carouselScene = document.getElementById('carouselScene');

function buildCarousel() {
  if (!carouselStage || !carouselDots) return;
  carouselStage.innerHTML = carouselDots.innerHTML = '';

  galleryData.forEach((item, idx) => {
    const card = document.createElement('div');
    card.className = 'gc-card';
    card.innerHTML = `
      <img src="${item.img}" alt="${item.title}" loading="lazy"
           onerror="this.src='https://placehold.co/380x230/d4edda/2a5c35?text=Benna'">
      <div class="gc-body">
        <div class="gc-title">${item.title}</div>
        <p class="gc-desc">${item.desc}</p>
        <div class="gc-tags">${item.tags.map(t => `<span class="gc-tag">${t}</span>`).join('')}</div>
      </div>`;
    card.addEventListener('click', () => goTo(idx));
    carouselStage.appendChild(card);

    const dot = document.createElement('div');
    dot.className = 'c-dot' + (idx === 0 ? ' active' : '');
    dot.addEventListener('click', () => goTo(idx));
    carouselDots.appendChild(dot);
  });

  positionSlides(false);
}

function positionSlides(animate) {
  const cards = $$('.gc-card');
  const dots  = $$('.c-dot');
  const total = galleryData.length;

  cards.forEach((card, i) => {
    let off = (i - activeIndex + total) % total;
    if (off > total / 2) off -= total;

    let transform, opacity, zIndex, pointer;
    if      (off ===  0) { transform = 'translateX(0) rotateY(0deg) scale(1)';          opacity = 1;    zIndex = 10; pointer = 'auto'; }
    else if (off ===  1) { transform = 'translateX(280px) rotateY(-25deg) scale(0.82)'; opacity = 0.62; zIndex = 6;  pointer = 'auto'; }
    else if (off === -1) { transform = 'translateX(-280px) rotateY(25deg) scale(0.82)'; opacity = 0.62; zIndex = 6;  pointer = 'auto'; }
    else                 { transform = `translateX(${off * 520}px) scale(0.55)`;         opacity = 0;    zIndex = 1;  pointer = 'none'; }

    card.style.transition    = animate ? 'transform .7s cubic-bezier(0.23,1,0.32,1), opacity .45s ease' : 'none';
    card.style.transform     = transform;
    card.style.opacity       = String(opacity);
    card.style.zIndex        = String(zIndex);
    card.style.pointerEvents = pointer;
  });

  dots.forEach((d, i) => d.classList.toggle('active', i === activeIndex));
}

function goTo(idx)   { activeIndex = (idx + galleryData.length) % galleryData.length; positionSlides(true); resetAuto(); }
function nextSlide() { goTo(activeIndex + 1); }
function prevSlide() { goTo(activeIndex - 1); }
function startAuto() { stopAuto(); autoInterval = setInterval(nextSlide, 5000); }
function stopAuto()  { if (autoInterval) { clearInterval(autoInterval); autoInterval = null; } }
function resetAuto() { startAuto(); }

$('#carouselPrev')?.addEventListener('click', () => { prevSlide(); resetAuto(); });
$('#carouselNext')?.addEventListener('click', () => { nextSlide(); resetAuto(); });

let touchStartX = null;
carouselScene?.addEventListener('touchstart', e => { touchStartX = e.touches[0].clientX; }, { passive: true });
carouselScene?.addEventListener('touchend', e => {
  if (touchStartX == null) return;

  const diff = e.changedTouches[0].clientX - touchStartX;

  if (Math.abs(diff) > 40) {
    if (diff < 0) {
      nextSlide();
    } else {
      prevSlide();
    }
  }

  resetAuto();
  touchStartX = null;
});
carouselScene?.addEventListener('mouseenter', stopAuto);
carouselScene?.addEventListener('mouseleave', startAuto);

buildCarousel();
startAuto();


/* ══════════════════════════════════════════════════════════════
   PRODUCT CARD TILT
   (from main.js — absent in script.js)
══════════════════════════════════════════════════════════════ */
$$('.product-card').forEach(card => {
  card.addEventListener('mousemove', e => {
    const r = card.getBoundingClientRect();
    const x = (e.clientX - r.left) / r.width  - 0.5;
    const y = (e.clientY - r.top)  / r.height - 0.5;
    card.style.transition = 'transform .12s ease';
    card.style.transform  = `translateY(-10px) rotateX(${-y * 4}deg) rotateY(${x * 4}deg)`;
  });
  card.addEventListener('mouseleave', () => {
    card.style.transition = 'transform .5s var(--ease-smooth)';
    card.style.transform  = '';
  });
});


/* ══════════════════════════════════════════════════════════════
   SMOOTH ANCHOR SCROLL
   (from main.js — absent in script.js)
══════════════════════════════════════════════════════════════ */
$$('a[href*="#"]').forEach(a => {
  a.addEventListener('click', e => {
    const hash   = a.getAttribute('href')?.split('#')[1];
    if (!hash) return;
    const target = document.getElementById(hash);
    if (!target) return;
    e.preventDefault();
    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
  });
});