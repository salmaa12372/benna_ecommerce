<!-- ═══════════════════════════════════════════════════════════
     SNIPPET_produits.php
     À coller dans produits.php juste avant </body> (ou dans le <head>)
     et remplacer le <form> de recherche existant par celui ci-dessous
════════════════════════════════════════════════════════════ -->

<!-- 1. Charger le moteur JS (généré par indexer.py) -->
<script src="<?= BASE ?>/search/output/benna_search.js"></script>

<!-- 2. Remplacer votre <form id="searchForm"> par celui-ci -->
<form id="searchForm" method="GET"
      style="display:flex;gap:.5rem;max-width:500px;margin:1.4rem auto 0;">
  <?php foreach ($_GET as $k => $v):
    if ($k === 'search' || $k === 'page') continue;
    if (is_array($v)): foreach ($v as $item): ?>
      <input type="hidden" name="<?= htmlspecialchars($k) ?>[]" value="<?= htmlspecialchars($item) ?>">
    <?php endforeach; else: ?>
      <input type="hidden" name="<?= htmlspecialchars($k) ?>" value="<?= htmlspecialchars($v) ?>">
  <?php endif; endforeach; ?>

  <input type="text" id="searchInput" name="search"
         value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
         placeholder=" Rechercher un produit…"
         autocomplete="off"
         style="flex:1;padding:.75rem 1.1rem;border-radius:30px;border:none;font-size:1rem;outline:none;box-shadow:0 2px 12px rgba(0,0,0,.12);">
  <button type="submit"
          style="padding:.75rem 1.4rem;border-radius:30px;border:none;background:var(--green-dark);color:#fff;font-weight:700;cursor:pointer;white-space:nowrap;">
    Rechercher
  </button>
</form>

<!-- 3. Suggestions live (dropdown) -->
<div id="search-suggestions"
     style="position:absolute;z-index:9999;background:#fff;border:1px solid #c5e0a8;
            border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,.12);
            max-width:500px;width:100%;left:50%;transform:translateX(-50%);
            margin-top:4px;overflow:hidden;display:none;">
</div>

<!-- 4. Script d'intégration -->
<script>
(function () {
  const BASE_URL = <?= json_encode(BASE) ?>;

  /* Charger l'index dès que la page est prête */
  BennaSearch.load(BASE_URL);

  const input       = document.getElementById('searchInput');
  const suggestions = document.getElementById('search-suggestions');
  const form        = document.getElementById('searchForm');
  let   debounceTimer;

  if (!input || !suggestions) return;

  /* ── Suggestions live en temps réel ── */
  input.addEventListener('input', function () {
    clearTimeout(debounceTimer);
    const q = this.value.trim();

    if (q.length < 2) { suggestions.style.display = 'none'; return; }

    debounceTimer = setTimeout(() => {
      BennaSearch.onReady(() => {
        const results = BennaSearch.search(q, { limit: 6, minScore: 0.01 });
        renderSuggestions(results, q);
      });
    }, 200);
  });

  function renderSuggestions(results, query) {
    if (!results.length) { suggestions.style.display = 'none'; return; }

    suggestions.innerHTML = results.map(p => `
      <a href="${BASE_URL}/view/client/produit_detail.php?id=${p.id}"
         style="display:flex;align-items:center;gap:.75rem;padding:.65rem 1rem;
                text-decoration:none;color:inherit;border-bottom:1px solid #f0f7ec;
                transition:background .15s;"
         onmouseover="this.style.background='#f0f7ec'"
         onmouseout="this.style.background=''">
        <img src="${BASE_URL}/public/uploads/produits/pics/bg_final/${p.id}.jpg"
             style="width:38px;height:38px;border-radius:8px;object-fit:cover;flex-shrink:0;"
             onerror="this.src='https://placehold.co/38x38/e8f0e3/2c5e2e?text=B'"/>
        <div style="flex:1;min-width:0;">
          <div style="font-weight:600;font-size:.9rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
            ${highlightMatch(p.nom, query)}
          </div>
          <div style="font-size:.78rem;color:#6a8a5a;">${p.prix.toFixed ? p.prix.toFixed(3) : p.prix} TND
            ${p.stock > 0 ? '<span style="color:#16a34a;margin-left:.4rem;">En stock</span>' : '<span style="color:#dc2626;margin-left:.4rem;">Rupture</span>'}
          </div>
        </div>
        <div style="font-size:.7rem;color:#9ab08a;font-weight:700;">
          ${Math.round(p._score * 100)}%
        </div>
      </a>
    `).join('') +
    `<a href="?search=${encodeURIComponent(query)}"
        style="display:block;text-align:center;padding:.6rem;font-size:.82rem;
               color:#2c5e2e;font-weight:700;text-decoration:none;background:#f7fbf4;"
        onmouseover="this.style.background='#ecf5e8'"
        onmouseout="this.style.background='#f7fbf4'">
      Voir tous les résultats pour « ${esc(query)} » →
    </a>`;
    suggestions.style.display = 'block';
  }

  /* ── Highlight du terme recherché ── */
  function highlightMatch(text, query) {
    const escaped = esc(text);
    const regex   = new RegExp('(' + esc(query).replace(/[-[\]{}()*+?.,\\^$|#\s]/g,'\\$&') + ')', 'gi');
    return escaped.replace(regex, '<mark style="background:#d1fae5;color:#065f46;border-radius:3px;">$1</mark>');
  }

  function esc(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  /* ── Fermer suggestions au clic extérieur ── */
  document.addEventListener('click', e => {
    if (!form.contains(e.target) && !suggestions.contains(e.target)) {
      suggestions.style.display = 'none';
    }
  });

  /* ── Fermer au submit ── */
  form.addEventListener('submit', () => { suggestions.style.display = 'none'; });
})();
</script>
