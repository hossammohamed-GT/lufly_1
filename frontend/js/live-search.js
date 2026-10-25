/* ============================================================
   Live product search (shared)
   THE search system of the site. There is no separate results
   page: typing filters the product grid in place, with scope
   (current category / all products), skeleton cards while
   loading, and a shareable URL (replaceState, no navigation).
   ------------------------------------------------------------
   Contract for host markup:
   <div data-livesearch                                     (required)
        data-livesearch-grid="[data-catalog-grid]"          (optional: results grid)
        data-livesearch-pagination="[data-catalog-pagination]" (optional: hidden while live)
        data-livesearch-count=".catalog-count">             (optional: live count badge)
     <form>
       <input data-livesearch-input data-locale="en"
              data-livesearch-limit="36" ...>
     </form>
     <div data-livesearch-results>                (status panel; scope row lives here)
       <div data-livesearch-scope>                (optional)
         <button type="button" data-scope="category">..</button>
         <button type="button" data-scope="all">..</button>
       </div>
     </div>
   ============================================================ */

(function () {
  'use strict';

  function boot() {
    var hosts = document.querySelectorAll('[data-livesearch]');
    if (hosts.length === 0) return;

    hosts.forEach(function (host) {
      if (host.__livesearch) return;
      host.__livesearch = true;

      // the host may be the form itself or a wrapper around it
      var form = host.matches('form') ? host : host.querySelector('form');
      if (!form) return;

      var input = form.querySelector('[data-livesearch-input]');
      if (!input) return;

      var box = host.querySelector('[data-livesearch-results]');
      if (!box) {
        box = document.createElement('div');
        box.setAttribute('data-livesearch-results', '');
        box.setAttribute('aria-live', 'polite');
        form.appendChild(box);
      }

      var gridSel = host.getAttribute('data-livesearch-grid') || '';
      var grid = gridSel ? document.querySelector(gridSel) : null;
      var pagSel = host.getAttribute('data-livesearch-pagination') || '';
      var pagination = pagSel ? document.querySelector(pagSel) : null;
      var countSel = host.getAttribute('data-livesearch-count') || '';
      var countEl = countSel ? document.querySelector(countSel) : null;
      var limit = parseInt(input.getAttribute('data-livesearch-limit') || '8', 10) || 8;
      var sortSelect = form.querySelector('[data-catalog-sort]');
      var categorySlug = '';
      var hiddenCategory = form.querySelector('input[name="category"]');
      if (hiddenCategory) categorySlug = hiddenCategory.value || '';

      var base = document.documentElement.getAttribute('data-base') || '';
      var favoritesEndpoint = host.getAttribute('data-favorites-endpoint') || '';
      var boxEndpoint = host.getAttribute('data-box-endpoint') || '';
      var locale = input.getAttribute('data-locale') ||
        document.documentElement.getAttribute('lang') || 'en';

      var scopeWrap = host.querySelector('[data-livesearch-scope]');
      var scope = 'all';
      if (scopeWrap) {
        var activeBtn = scopeWrap.querySelector('[data-scope].is-active');
        scope = activeBtn ? activeBtn.getAttribute('data-scope') : 'all';
      }

      var cache = {};
      var debounce = null;
      var inFlight = null;
      var lastQuery = '';
      var isLive = false;
      var compactStatus = false;
      var savedGrid = '';
      var savedCount = '';
      var paginationHidden = false;

      var guides = {
        en: {
          idle: 'Start typing to search the catalog...',
          min: 'Type at least 2 characters...',
          busy: 'Searching for ":q"...',
          found: 'Found :n fixtures matching ":q"',
          empty: 'No architectural fixtures found matching ":q"',
          hint: 'Tip: try a model code like 1620 or a name like "wall-hung"',
          details: 'View details',
          namePending: 'Name not provided',
          price: 'Price',
          pricePending: 'Price pending',
          addToCart: 'Add to cart',
          cartPending: 'Available once a price is added',
          availableFinishes: 'Available finishes',
          colorOptions: 'Color options',
          productFinish: 'Piece finish',
          availableColors: 'Available colours',
          save: 'Save product',
          saved: 'Saved',
          favoriteAdded: ':name saved to your list',
          favoriteRemoved: ':name removed from your list',
          boxAdded: 'In the box',
          boxError: 'The cart did not answer — try again.',
          kindDrawing: 'Drawing',
          kindSitu: 'Installed',
          share: 'Copy link',
          copied: 'Link copied',
          clear: 'Clear search',
          countOne: '1 item',
          countMany: ':n items'
        },
        tr: {
          idle: 'Kataloğda aramak için yazmaya başlayın...',
          min: 'Lütfen en az 2 karakter girin...',
          busy: '":q" için aranıyor...',
          found: '":q" için :n ürün bulundu',
          empty: '":q" ile eşleşen ürün bulunamadı',
          hint: 'İpucu: 1620 gibi bir model kodu ya da "asma klozet" deneyin',
          details: 'Detayları gör',
          namePending: 'Ürün adı belirtilmemiş',
          price: 'Fiyat',
          pricePending: 'Fiyat bekleniyor',
          addToCart: 'Sepete ekle',
          cartPending: 'Fiyat eklendiğinde kullanılabilir',
          availableFinishes: 'Mevcut kaplamalar',
          colorOptions: 'Renk seçenekleri',
          productFinish: 'Ürün kaplaması',
          availableColors: 'Mevcut renkler',
          save: 'Ürünü kaydet',
          saved: 'Kaydedildi',
          favoriteAdded: ':name listenize kaydedildi',
          favoriteRemoved: ':name listenizden çıkarıldı',
          boxAdded: 'Kutuda',
          boxError: 'Sepet yanıt vermedi — tekrar deneyin.',
          kindDrawing: 'Teknik çizim',
          kindSitu: 'Montajlı',
          share: 'Bağlantıyı kopyala',
          copied: 'Bağlantı kopyalandı',
          clear: 'Aramayı temizle',
          countOne: '1 ürün',
          countMany: ':n ürün'
        },
        cs: {
          idle: 'Začněte psát pro vyhledávání v katalogu...',
          min: 'Zadejte prosím alespoň 2 znaky...',
          busy: 'Vyhledává se ":q"...',
          found: 'Nalezeno :n produktů pro ":q"',
          empty: 'Nebyly nalezeny žádné produkty odpovídající ":q"',
          hint: 'Tip: zkuste kód modelu jako 1620 nebo název "závěsné WC"',
          details: 'Zobrazit detail',
          namePending: 'Název produktu neuveden',
          price: 'Cena',
          pricePending: 'Cena bude doplněna',
          addToCart: 'Přidat do košíku',
          cartPending: 'Dostupné po doplnění ceny',
          availableFinishes: 'Dostupné povrchy',
          colorOptions: 'Možnosti barev',
          productFinish: 'Povrch výrobku',
          availableColors: 'Dostupné barvy',
          save: 'Uložit produkt',
          saved: 'Uloženo',
          favoriteAdded: ':name bylo uloženo do seznamu',
          favoriteRemoved: ':name bylo odebráno ze seznamu',
          boxAdded: 'V krabici',
          boxError: 'Košík neodpověděl — zkuste to znovu.',
          kindDrawing: 'Výkres',
          kindSitu: 'Instalace',
          share: 'Kopírovat odkaz',
          copied: 'Odkaz zkopírován',
          clear: 'Vymazat hledání',
          countOne: '1 položka',
          countMany: ':n položek'
        }
      };
      var g = guides[locale] || guides.en;

      function esc(value) {
        return String(value).replace(/[&<>"']/g, function (ch) {
          return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch];
        });
      }

      function img(raw) {
        if (!raw) return base + '/images/favicon.png';
        if (/^(?:https?:)?\/\//.test(raw)) return raw;
        return base + '/' + String(raw).replace(/^\/+/, '');
      }

      /* ---------- status panel (scope row stays in the DOM, listeners intact) ---------- */

      var statusEl = box.querySelector('.livesearch-status');
      if (!statusEl) {
        statusEl = document.createElement('div');
        statusEl.className = 'livesearch-status';
        box.appendChild(statusEl);
      }

      function syncStatusMode() {
        box.classList.toggle('is-compact', compactStatus);
        if (compactStatus) box.classList.add('is-open');
      }

      function setStatus(text, withShare) {
        statusEl.innerHTML = text + (withShare ? shareButton() : '');
        syncStatusMode();
        box.classList.add('is-open');
        form.classList.add('is-searching');
        if (withShare) bindShare();
      }

      function showIdle() {
        setStatus(esc(g.idle) + ' <span class="livesearch-hint">' + esc(g.hint) + '</span>');
      }

      function shareButton() {
        return '<button type="button" class="livesearch-share" data-livesearch-share title="' + esc(g.share) + '">' +
          '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" width="13" height="13" aria-hidden="true">' +
          '<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>' +
          '<path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>' +
          '</svg><span>' + esc(g.share) + '</span></button>';
      }

      function bindShare() {
        var btn = statusEl.querySelector('[data-livesearch-share]');
        if (!btn || btn.__shareBound) return;
        btn.__shareBound = true;
        btn.addEventListener('click', function () {
          copyLink().then(function () {
            btn.classList.add('is-copied');
            var label = btn.querySelector('span');
            if (label) label.textContent = g.copied;
            window.setTimeout(function () {
              btn.classList.remove('is-copied');
              if (label) label.textContent = g.share;
            }, 1600);
          });
        });
      }

      function copyLink() {
        var url = window.location.href;
        if (navigator.clipboard && navigator.clipboard.writeText) {
          return navigator.clipboard.writeText(url);
        }
        return new Promise(function (resolve) {
          try {
            var ta = document.createElement('textarea');
            ta.value = url;
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
          } catch (e) { /* clipboard unavailable */ }
          resolve();
        });
      }

      /* ---------- grid takeover ---------- */

      /* the grid lives inside a skeleton host; while live search owns it the
         real content must stay visible rather than flipping back to glass */
      function ensureGridVisible() {
        if (!grid) return;
        var host = grid.closest ? grid.closest('[data-sk-host]') : null;
        if (host) host.classList.add('is-ready');
      }

      function rememberGrid() {
        if (isLive || !grid) return;
        ensureGridVisible();
        savedGrid = grid.innerHTML;
        savedCount = countEl ? countEl.textContent : '';
        isLive = true;
        form.classList.add('is-live');
        if (pagination && !paginationHidden) {
          pagination.style.display = 'none';
          paginationHidden = true;
        }
      }

      function restoreGrid() {
        if (!isLive) return;
        if (grid) grid.innerHTML = savedGrid;
        if (countEl) countEl.textContent = savedCount;
        if (pagination && paginationHidden) {
          pagination.style.display = '';
          paginationHidden = false;
        }
        isLive = false;
        form.classList.remove('is-live');
        lastQuery = '';
        syncUrl('');
      }

      function skeletonCards(count) {
        var one = '<div class="livesearch-skel" aria-hidden="true">' +
          '<div class="livesearch-skel-media"></div>' +
          '<div class="livesearch-skel-body">' +
          '<span class="livesearch-skel-line is-w35"></span>' +
          '<span class="livesearch-skel-line is-w80"></span>' +
          '<span class="livesearch-skel-line is-w55"></span>' +
          '</div></div>';
        var out = '';
        for (var i = 0; i < count; i++) out += one;
        return out;
      }

      /* make sure the user sees the skeletons / results appear */
      function revealGrid() {
        if (!grid || typeof grid.scrollIntoView !== 'function') return;
        var rect = grid.getBoundingClientRect();
        if (rect.top > window.innerHeight * 0.6 || rect.top < 0) {
          var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
          try {
            grid.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'start' });
          } catch (e) { /* older browsers */ }
        }
      }

      /* ---------- url + sort ---------- */

      function syncUrl(query) {
        if (!window.history || !window.history.replaceState) return;
        var params = {};
        if (scope === 'category' && categorySlug) params.category = categorySlug;
        if (query) params.q = query;
        var sort = sortSelect ? sortSelect.value : '';
        if (sort && sort !== 'curated') params.sort = sort;
        var keys = Object.keys(params);
        var qs = keys.map(function (k) {
          return encodeURIComponent(k) + '=' + encodeURIComponent(params[k]);
        }).join('&');
        window.history.replaceState(null, '', window.location.pathname + (qs ? '?' + qs : ''));
      }

      function sortedMatches(matches) {
        var list = matches.slice();
        if (currentSort() === 'name') {
          list.sort(function (a, b) {
            return String(a.name || '').localeCompare(String(b.name || ''), locale);
          });
        } else if (currentSort() === 'model') {
          list.sort(function (a, b) {
            return String(a.model_code || a.sku || '').localeCompare(String(b.model_code || b.sku || ''), locale, { numeric: true });
          });
        }
        return list;
      }

      function currentSort() {
        return sortSelect ? sortSelect.value : 'curated';
      }

      /* ---------- premium card fragments ---------- */

      function cardIcon(name) {
        var paths = {
          installation: '<path d="M5 4h14v16H5z"/><path d="M9 8h6M9 12h6M9 16h3"/>',
          rimless: '<ellipse cx="12" cy="9" rx="8" ry="4.5"/><path d="M4 9v4c0 3.2 3.2 5.5 8 5.5s8-2.3 8-5.5V9M8 21h8"/>',
          'soft-close': '<path d="M4 7h16M4 12h16M4 17h16"/><path d="m8 4 8 16"/>',
          'water-saving': '<path d="M12 3S6.5 9.2 6.5 13.4a5.5 5.5 0 0 0 11 0C17.5 9.2 12 3 12 3Z"/><path d="m9.5 13.5 1.6 1.6 3.4-3.6"/>',
          'easy-clean': '<path d="m12 3 1.5 5.5L19 10l-5.5 1.5L12 17l-1.5-5.5L5 10l5.5-1.5L12 3Z"/><path d="m18 16 .7 2.3L21 19l-2.3.7L18 22l-.7-2.3L15 19l2.3-.7L18 16Z"/>',
          'scratch-resistant': '<path d="m12 3 1.5 5.5L19 10l-5.5 1.5L12 17l-1.5-5.5L5 10l5.5-1.5L12 3Z"/><path d="m18 16 .7 2.3L21 19l-2.3.7L18 22l-.7-2.3L15 19l2.3-.7L18 16Z"/>',
          cartridge: '<path d="M6 20V9a6 6 0 0 1 12 0v3"/><path d="M12 12v8M4 20h16M15 6h5"/>',
          'single-lever': '<path d="M6 20V9a6 6 0 0 1 12 0v3"/><path d="M12 12v8M4 20h16M15 6h5"/>',
          thermostatic: '<path d="M6 20V9a6 6 0 0 1 12 0v3"/><path d="M12 12v8M4 20h16M15 6h5"/>',
          shower: '<path d="M12 3v5M6 12a6 6 0 0 1 12 0M5 12h14"/><path d="M8 16v1M12 16v1M16 16v1M8 20v1M12 20v1M16 20v1"/>',
          'high-flow': '<path d="M4 8h12M4 12h16M4 16h12"/><path d="m14 5 3 3-3 3M14 13l3 3-3 3"/>',
          'anti-odor': '<path d="M12 3 20 6v5c0 4.8-3.3 8.3-8 10-4.7-1.7-8-5.2-8-10V6l8-3Z"/><path d="m8.5 12 2.2 2.2 4.8-5"/>',
          material: '<path d="m12 3 8 4.5v9L12 21l-8-4.5v-9L12 3Z"/><path d="m4 7.5 8 4.5 8-4.5M12 12v9"/>',
          dimensions: '<path d="M4 5h16v14H4z"/><path d="M8 5v4M12 5v3M16 5v4M8 19v-4M12 19v-3M16 19v-4"/>',
          spec: '<circle cx="12" cy="12" r="8.5"/><path d="m8.5 12 2.2 2.2 4.8-5"/>'
        };
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.45" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' +
          (paths[name] || paths.spec) + '</svg>';
      }

      function favoriteButton(product, card, rawName) {
        if (!favoritesEndpoint || !product.id) return '';
        var on = !!product.is_saved;
        var save = card.save_label || g.save;
        var saved = card.saved_label || g.saved;
        var label = on ? saved : save;
        var added = card.favorite_added || g.favoriteAdded.replace(':name', rawName);
        var removed = card.favorite_removed || g.favoriteRemoved.replace(':name', rawName);
        return '<button type="button" class="favbtn favbtn-card' + (on ? ' is-on' : '') + '"' +
          ' data-fav-toggle data-fav-product="' + esc(product.id) + '" data-fav-endpoint="' + esc(favoritesEndpoint) + '"' +
          ' data-fav-added="' + esc(added) + '" data-fav-removed="' + esc(removed) + '"' +
          ' data-fav-label-on="' + esc(saved) + '" data-fav-label-off="' + esc(save) + '"' +
          ' aria-pressed="' + (on ? 'true' : 'false') + '" aria-label="' + esc(label + ' — ' + rawName) + '" title="' + esc(label) + '">' +
          '<span class="favbtn-icon" aria-hidden="true">' +
          '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20.6 4.2 12.8a5.1 5.1 0 0 1 0-7.2 5.1 5.1 0 0 1 7.2 0l.6.6.6-.6a5.1 5.1 0 0 1 7.2 0 5.1 5.1 0 0 1 0 7.2Z"/></svg>' +
          '<svg class="favbtn-burst" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/></svg>' +
          '</span></button>';
      }

      function cartButton(product, card, rawName) {
        var enabled = !!card.cart_enabled && !!boxEndpoint;
        var on = enabled && !!product.in_box;
        var label = enabled ? (on ? (card.box_label_on || g.boxAdded) : (card.box_label_off || g.addToCart)) : (card.cart_label || g.cartPending);
        if (!product.id) return '';
        return '<button type="button" class="boxbtn boxbtn-cart' + (on ? ' is-on' : '') + '" data-box-product="' + esc(product.id) + '"' +
          (enabled
            ? ' data-box-add data-box-endpoint="' + esc(boxEndpoint) + '" data-box-label-on="' + esc(card.box_label_on || g.boxAdded) + '"' +
              ' data-box-label-off="' + esc(card.box_label_off || g.addToCart) + '" data-box-error="' + esc(card.box_error || g.boxError) + '"'
            : ' disabled aria-disabled="true"') +
          ' aria-pressed="' + (on ? 'true' : 'false') + '" aria-label="' + esc(label + ' — ' + rawName) + '" title="' + esc(label) + '">' +
          '<span class="boxbtn-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">' +
          '<circle cx="9" cy="20" r="1.3"/><circle cx="18" cy="20" r="1.3"/><path d="M3 4h2l2.2 10.2a2 2 0 0 0 2 1.6h8.9a2 2 0 0 0 2-1.6L21.5 8H6"/>' +
          '</svg></span></button>';
      }

      function featureList(card) {
        if (!Array.isArray(card.features) || card.features.length === 0) return '';
        return '<span class="pcard-features" role="list">' + card.features.slice(0, 3).map(function (feature) {
          return '<span class="pcard-feature" role="listitem">' + cardIcon(feature.icon) + '<span>' + esc(feature.label || '') + '</span></span>';
        }).join('') + '</span>';
      }

      function jsonForScript(value) {
        return JSON.stringify(value).replace(/</g, '\\u003C').replace(/>/g, '\\u003E').replace(/&/g, '\\u0026');
      }

      function finishKey(finish) {
        return String(finish && finish.key || 'neutral').toLowerCase().replace(/[^a-z-]/g, '') || 'neutral';
      }

      function toneParts(finishes) {
        return finishes.slice(0, 4).map(function (finish) {
          return '<i class="pcard-tone pcard-swatch--' + finishKey(finish) + '" aria-hidden="true"></i>';
        }).join('');
      }

      function pieceFinish(card, product) {
        var finishes = Array.isArray(card.finishes) ? card.finishes.slice(0, 4) : [];
        if (!finishes.length && product.finish && product.finish.label) finishes = [product.finish];
        if (!finishes.length) return '';
        var label = String(product.finish && product.finish.label || finishes.map(function (finish) { return finish.label || ''; }).filter(Boolean).join(' / '));
        return '<div class="pcard-finish-piece" data-pcard-piece>' +
          '<span class="pcard-finish-caption">' + esc(g.productFinish) + '</span>' +
          '<span class="pcard-finish-value"><span class="pcard-tone-swatch" data-pcard-piece-tones role="img" aria-label="' + esc(label) + '" title="' + esc(label) + '">' + toneParts(finishes) + '</span>' +
          '<span class="pcard-finish-count pcard-finish-current pcard-finish-name" data-pcard-finish-label aria-live="polite" title="' + esc(label) + '">' + esc(label) + '</span></span></div>';
      }

      function finishList(card, href, product) {
        var variants = Array.isArray(product.color_variants) ? product.color_variants : [];
        var piece = pieceFinish(card, product);
        if (variants.length > 1) {
          var links = variants.map(function (variant) {
            var finish = variant.finish || {};
            var optionFinishes = variant.card && Array.isArray(variant.card.finishes) && variant.card.finishes.length
              ? variant.card.finishes : (finish.label ? [finish] : []);
            var label = String(finish.label || optionFinishes.map(function (item) { return item.label || ''; }).filter(Boolean).join(' / '));
            var code = String(variant.sku || variant.model_code || '');
            var selected = parseInt(variant.id, 10) === parseInt(product.id, 10);
            var aria = label + (code ? ' — ' + code : '');
            return '<a class="pcard-swatch pcard-option' + (selected ? ' is-active' : '') + '"' +
              ' role="listitem" href="' + esc(variant.url || href) + '" data-pcard-variant="' + esc(variant.id) + '"' +
              ' title="' + esc(aria) + '" aria-label="' + esc(aria) + '"' + (selected ? ' aria-current="true"' : '') + '>' +
              '<span class="pcard-option-tones" aria-hidden="true">' + toneParts(optionFinishes) + '</span></a>';
          }).join('');
          return '<div class="pcard-finishes pcard-finish-box" data-pcard-colors data-product-finish-label="' + esc(g.productFinish) + '" data-color-options-label="' + esc(g.availableColors) + '">' +
            piece + '<div class="pcard-color-variants pcard-finish-choices"><span class="pcard-finish-caption">' + esc(g.availableColors) + '</span>' +
            '<span class="pcard-swatches" role="list" aria-label="' + esc(g.availableColors) + '">' + links + '</span></div></div>' +
            '<script type="application/json" data-pcard-variant-data>' + jsonForScript(variants) + '<\/script>';
        }
        if (!piece) return '';
        return '<div class="pcard-finishes pcard-finish-box" data-pcard-colors data-product-finish-label="' + esc(g.productFinish) + '" data-color-options-label="' + esc(g.availableColors) + '">' + piece + '</div>';
      }

      function specList(card) {
        if (!Array.isArray(card.specs) || card.specs.length === 0) return '';
        return '<div class="pcard-specs" role="list">' + card.specs.slice(0, 2).map(function (spec) {
          return '<div class="pcard-spec' + (spec.icon === 'dimensions' ? ' pcard-spec--dimensions' : '') + '" role="listitem">' + cardIcon(spec.icon) + '<div><span class="pcard-spec-label">' + esc(spec.label || '') + '</span><span class="pcard-spec-value">' + esc(spec.value || '') + '</span></div></div>';
        }).join('') + '</div>';
      }

      /* ---------- rendering ---------- */

      function render(matches, query) {
        var list = sortedMatches(matches);

        if (!list.length) {
          setStatus(esc(g.empty.replace(':q', query)));
          if (grid) {
            rememberGrid();
            grid.innerHTML =
              '<div class="catalog-empty catalog-empty-live">' +
              '<span class="catalog-empty-code">0</span>' +
              '<p>' + esc(g.empty.replace(':q', query)) + '</p>' +
              '<button type="button" class="catalog-empty-btn" data-livesearch-clear>' + esc(g.clear) + '</button>' +
              '</div>';
            var clearBtn = grid.querySelector('[data-livesearch-clear]');
            if (clearBtn) clearBtn.addEventListener('click', clearSearch);
          }
          syncUrl(query);
          return;
        }

        var arrow = '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M4 10h12m-5-5 5 5-5 5"/></svg>';
        var cards = list.map(function (product, index) {
          var rawName = String(product.name || g.namePending);
          var name = esc(rawName);
          var sku = esc(product.sku || product.model_code || '');
          var shortDescText = String(product.short_description || product.description || '').trim();
          var verifiedBlock = shortDescText.indexOf('Verified drawing dimensions:');
          if (verifiedBlock !== -1) shortDescText = shortDescText.slice(0, verifiedBlock).trim();
          var shortDesc = esc(shortDescText);
          var slug = encodeURIComponent(product.slug || product.id || '');
          var href = base + '/' + encodeURIComponent(locale) + '/products/' + slug;
          var card = product.card && typeof product.card === 'object' ? product.card : {};
          var hasPrice = card.has_price !== undefined ? !!card.has_price : parseFloat(product.price || 0) > 0;
          if (card.cart_enabled === undefined) card.cart_enabled = hasPrice;
          var priceLabel = card.price_label || (hasPrice ? String(product.price) : g.pricePending);
          var priceCaption = card.price_caption || g.price;

          /* hover cycles through every image of the product: photos first,
             then technical drawings, then installed shots. */
          var slides = [];
          (product.card_slides || []).forEach(function (slide) {
            if (!slide || !slide.path) return;
            var src = img(slide.path);
            var dup = slides.some(function (s) { return s.src === src; });
            if (!dup) slides.push({ src: src, kind: slide.kind || 'photo' });
          });
          if (slides.length === 0) {
            [['gallery', 'photo'], ['drawings', 'drawing'], ['situ_images', 'situ']].forEach(function (group) {
              (product[group[0]] || []).forEach(function (p) {
                if (!p) return;
                var src = img(p);
                var dup = slides.some(function (s) { return s.src === src; });
                if (!dup) slides.push({ src: src, kind: group[1] });
              });
            });
          }
          if (slides.length === 0) slides = [{ src: img(product.image), kind: 'photo' }];
          var kindLabel = function (kind) {
            return kind === 'drawing' ? g.kindDrawing : kind === 'situ' ? g.kindSitu : '';
          };
          var delay = (0.04 * ((index % 6) + 1)).toFixed(2);
          var features = featureList(card);
          var hasFeaturePanel = !!card.badge || features !== '';
          var featurePanel = hasFeaturePanel
            ? '<div class="pcard-feature-panel">' + (card.badge ? '<span class="pcard-badge">' + esc(card.badge) + '</span>' : '') + features + '</div>'
            : '';

          return '<article class="pcard ' + (hasPrice ? 'has-price' : 'is-price-pending') + '" data-product-card data-product-id="' + esc(product.id || '') + '" style="--pcard-delay:' + delay + 's">' +
            '<div class="pcard-stage">' +
            '<a class="pcard-media" href="' + href + '"' + (slides.length > 1
              ? ' data-pcard-cycle data-pcard-kind-drawing="' + esc(g.kindDrawing) + '" data-pcard-kind-situ="' + esc(g.kindSitu) + '"'
              : '') + '>' +
            slides.map(function (slide, n) {
              var label = kindLabel(slide.kind);
              var alt = n === 0 ? name : (label ? label + ' — ' + name : '');
              return '<img class="pcard-img' + (n === 0 ? ' is-on' : '') + '" data-pcard-slide="' + n + '"' +
                ' data-pcard-kind="' + esc(slide.kind) + '"' +
                ' src="' + esc(slide.src) + '" alt="' + alt + '" loading="lazy" decoding="async" width="420" height="320"' +
                ' onerror="this.onerror=null;this.remove();">';
            }).join('') + '</a>' +
            (slides.length > 1
              ? '<div class="pcard-media-meta"><span class="pcard-dots" aria-hidden="true">' + slides.map(function (u, n) {
                  return '<i class="pcard-dot' + (n === 0 ? ' is-on' : '') + '" data-pcard-dot="' + n + '"></i>';
                }).join('') + '</span>' +
                '<span class="pcard-kind" data-pcard-kind-label aria-hidden="true" hidden></span></div>'
              : '') +
            '</div>' +
            '<div class="pcard-body">' +
            '<div class="pcard-top pcard-heading"><h3 class="pcard-title"><a href="' + href + '">' + name + '</a></h3>' + favoriteButton(product, card, rawName) + '</div>' +
            (sku ? '<span class="pcard-code">' + sku + '</span>' : '') +
            (shortDesc ? '<p class="pcard-desc">' + shortDesc + '</p>' : '') +
            featurePanel +
            finishList(card, href, product) +
            specList(card) +
            '<div class="pcard-actions"><div class="pcard-action-copy">' +
            '<span class="pcard-price"><small>' + esc(priceCaption) + '</small><strong' + (hasPrice ? '' : ' class="is-pending"') + '>' + esc(priceLabel) + '</strong></span>' +
            '<a class="pcard-details" href="' + href + '">' + esc(g.details) + arrow + '</a>' +
            '</div>' + cartButton(product, card, rawName) + '</div>' +
            '</div></article>';
        }).join('');

        setStatus(esc(g.found.replace(':n', list.length).replace(':q', query)), true);

        if (grid) {
          rememberGrid();
          grid.innerHTML = cards;
        } else {
          var cardsEl = box.querySelector('.livesearch-cards');
          if (!cardsEl) {
            cardsEl = document.createElement('div');
            cardsEl.className = 'livesearch-cards';
            box.appendChild(cardsEl);
          }
          cardsEl.innerHTML = cards;
        }

        if (countEl) {
          countEl.textContent = list.length === 1
            ? g.countOne
            : g.countMany.replace(':n', String(list.length));
        }
        syncUrl(query);
      }

      /* ---------- search ---------- */

      function search(query, reveal) {
        lastQuery = query;
        var cacheKey = locale + ':' + scope + ':' + categorySlug + ':' + query.toLowerCase();
        if (cache[cacheKey]) {
          render(cache[cacheKey], query);
          if (reveal) revealGrid();
          return;
        }

        setStatus(esc(g.busy.replace(':q', query)));
        box.classList.add('is-busy');
        if (grid) {
          rememberGrid();
          grid.innerHTML = skeletonCards(6);
          if (reveal) revealGrid();
        }

        if (inFlight && typeof inFlight.abort === 'function') {
          inFlight.abort();
        }
        if (typeof AbortController === 'function') {
          inFlight = new AbortController();
        }

        var url = base + '/api/products/search?q=' + encodeURIComponent(query) +
          '&limit=' + limit + '&locale=' + encodeURIComponent(locale);
        if (scope === 'category' && categorySlug) {
          url += '&category=' + encodeURIComponent(categorySlug);
        }

        fetch(url, {
          headers: { Accept: 'application/json' },
          signal: inFlight ? inFlight.signal : undefined
        })
          .then(function (response) { return response.ok ? response.json() : null; })
          .then(function (json) {
            box.classList.remove('is-busy');
            var data = json && Array.isArray(json.data) ? json.data : [];
            cache[cacheKey] = data;
            if (lastQuery === query) render(data, query);
          })
          .catch(function () { /* aborted or offline */ });
      }

      function clearSearch() {
        input.value = '';
        compactStatus = form.classList.contains('is-docked');
        syncStatusMode();
        restoreGrid();
        box.classList.remove('is-busy');
        showIdle();
        input.focus();
      }

      /* ---------- scope toggle ---------- */

      if (scopeWrap) {
        scopeWrap.addEventListener('click', function (e) {
          var btn = e.target.closest('[data-scope]');
          if (!btn || btn.getAttribute('data-scope') === scope) return;
          scope = btn.getAttribute('data-scope');
          compactStatus = false;
          syncStatusMode();
          scopeWrap.querySelectorAll('[data-scope]').forEach(function (b) {
            b.classList.toggle('is-active', b === btn);
          });
          var pending = lastQuery || input.value.trim();
          if (pending && pending.length >= 2) {
            search(pending, true);
          }
        });
      }

      /* ---------- input wiring ---------- */

      /* Once this same form is fixed beside the grid, searching must not pull
         the visitor to a new scroll position. The event-source check keeps
         compatibility with previously forwarded dock events. */
      function shouldRevealGrid(event) {
        var forwardedDockEvent = event && event.detail && event.detail.source === 'catalog-dock';
        return !forwardedDockEvent && !form.classList.contains('is-docked');
      }

      input.addEventListener('input', function (event) {
        var query = input.value.trim();
        var reveal = shouldRevealGrid(event);
        compactStatus = !reveal;
        syncStatusMode();
        window.clearTimeout(debounce);

        if (query.length === 0) {
          restoreGrid();
          showIdle();
          return;
        }
        if (query.length === 1) {
          restoreGrid();
          setStatus(esc(g.min));
          return;
        }

        debounce = window.setTimeout(function () {
          search(query, reveal);
        }, 600);
      });

      input.addEventListener('focus', function () {
        compactStatus = form.classList.contains('is-docked');
        syncStatusMode();
        form.classList.add('is-searching');
        var query = input.value.trim();
        if (query.length >= 2) {
          var cacheKey = locale + ':' + scope + ':' + categorySlug + ':' + query.toLowerCase();
          if (cache[cacheKey]) {
            render(cache[cacheKey], query);
          } else if (!isLive) {
            search(query);
          }
        } else if (query.length === 1) {
          setStatus(esc(g.min));
        } else {
          showIdle();
        }
      });

      /* Enter never navigates: the live grid already shows everything */
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        compactStatus = !shouldRevealGrid(e);
        syncStatusMode();
        window.clearTimeout(debounce);
        var query = input.value.trim();
        if (query.length < 2) {
          setStatus(esc(g.min));
          return;
        }
        search(query, shouldRevealGrid(e));
      });

      /* The original bar can become fixed after it already has focus. Keep
         its status compact from that point, and restore the normal panel when
         it returns to its document position. */
      form.addEventListener('catalog:dockstate', function (event) {
        var detail = event.detail || {};
        compactStatus = Boolean(detail.docked) && form.classList.contains('is-searching');
        syncStatusMode();
      });

      /* A server-rendered category/sort/page update becomes the new baseline.
         Do not restore a previous category's saved grid on the next search. */
      form.addEventListener('livesearch:reset', function (event) {
        var detail = event.detail || {};
        window.clearTimeout(debounce);
        if (inFlight && typeof inFlight.abort === 'function') inFlight.abort();
        categorySlug = String(detail.category || '');
        scope = categorySlug ? 'category' : 'all';
        cache = {};
        lastQuery = '';
        isLive = false;
        compactStatus = false;
        input.value = String(detail.query || '');
        form.classList.remove('is-live', 'is-searching');
        box.classList.remove('is-open', 'is-busy', 'is-compact');
        savedGrid = grid ? grid.innerHTML : '';
        savedCount = countEl ? countEl.textContent : '';
        if (pagination) pagination.style.display = '';
        paginationHidden = false;
        ensureGridVisible();
      });

      /* sort re-renders the live results client-side (catalog.js dispatches this) */
      form.addEventListener('livesearch:rerender', function () {
        if (isLive && lastQuery) {
          var cacheKey = locale + ':' + scope + ':' + categorySlug + ':' + lastQuery.toLowerCase();
          if (cache[cacheKey]) render(cache[cacheKey], lastQuery);
        }
      });

      /* close the panel on outside click (grid results stay) */
      document.addEventListener('click', function (e) {
        if (!host.contains(e.target)) {
          box.classList.remove('is-open');
          form.classList.remove('is-searching');
        }
      });

      input.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
          clearSearch();
          input.blur();
        }
      });

      /* ---------- deep link: /products?q=... opens in live search ---------- */
      var urlQuery = '';
      try {
        urlQuery = new URLSearchParams(window.location.search).get('q') || '';
      } catch (e2) { /* older browsers */ }
      if (urlQuery.trim().length >= 2) {
        input.value = urlQuery.trim();
        search(urlQuery.trim());
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
