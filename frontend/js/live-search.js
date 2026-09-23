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

      function setStatus(text, withShare) {
        statusEl.innerHTML = text + (withShare ? shareButton() : '');
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
        if (sort && sort !== 'newest') params.sort = sort;
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
        return sortSelect ? sortSelect.value : 'newest';
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

        var fallback = img(null);
        var arrow = '<svg viewBox="0 0 20 20" fill="currentColor" width="13" height="13" aria-hidden="true"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>';

        var cards = list.map(function (product, index) {
          var name = esc(product.name || product.sku || 'LUFLY fixture');
          var sku = esc(product.sku || product.model_code || '');
          var shortDesc = esc(String(product.short_description || '').trim());
          var slug = encodeURIComponent(product.slug || product.id || '');
          var href = base + '/' + encodeURIComponent(locale) + '/products/' + slug;
          /* hover cycles through every image of the product: the photos
             first (main shot on top), then the technical drawings, then the
             installed shots. Each slide keeps the section it came from so
             the card can name it. */
          var slides = [];
          (product.card_slides || []).forEach(function (slide) {
            if (!slide || !slide.path) return;
            var src = img(slide.path);
            var dup = slides.some(function (s) { return s.src === src; });
            if (!dup) slides.push({ src: src, kind: slide.kind || 'photo' });
          });
          if (slides.length === 0) {
            /* payload without the slide list: fall back to the plain groups */
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

          var no = ('00' + (index + 1)).slice(-3);
          var delay = (0.05 + 0.05 * (index % 6)).toFixed(2);

          return '<article class="pcard" style="--pcard-delay:' + delay + 's">' +
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
            }).join('') +
            (slides.length > 1
              ? '<span class="pcard-dots" aria-hidden="true">' + slides.map(function (u, n) {
                  return '<i class="pcard-dot' + (n === 0 ? ' is-on' : '') + '" data-pcard-dot="' + n + '"></i>';
                }).join('') + '</span>' +
                '<span class="pcard-kind" data-pcard-kind-label aria-hidden="true" hidden></span>'
              : '') +
            '<span class="pcard-hint" aria-hidden="true">' + arrow + esc(g.details) + '</span>' +
            '</a>' +
            '<div class="pcard-body">' +
            '<div class="pcard-top"><span class="pcard-code">' + (sku || 'LUFLY') + '</span><span class="pcard-no">' + no + '</span></div>' +
            '<h3 class="pcard-title"><a href="' + href + '">' + name + '</a></h3>' +
            (shortDesc ? '<p class="pcard-desc">' + shortDesc + '</p>' : '') +
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
        var cacheKey = locale + ':' + scope + ':' + query.toLowerCase();
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

      input.addEventListener('input', function () {
        var query = input.value.trim();
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
          search(query, true);
        }, 600);
      });

      input.addEventListener('focus', function () {
        form.classList.add('is-searching');
        var query = input.value.trim();
        if (query.length >= 2) {
          var cacheKey = locale + ':' + scope + ':' + query.toLowerCase();
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
        var query = input.value.trim();
        if (query.length < 2) {
          setStatus(esc(g.min));
          return;
        }
        search(query, true);
      });

      /* sort re-renders the live results client-side (catalog.js dispatches this) */
      form.addEventListener('livesearch:rerender', function () {
        if (isLive && lastQuery) {
          var cacheKey = locale + ':' + scope + ':' + lastQuery.toLowerCase();
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
