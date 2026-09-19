/* ============================================================
   Live product search (shared)
   Same experience as the navbar search: type 2+ characters, pause,
   results appear below the field. No submit button needed.
   Reuses the /api/products/search endpoint.
   ------------------------------------------------------------
   Contract for host markup:
   <div data-livesearch>                    (wrapper, or the form itself)
     <form>
       <input data-livesearch-input data-locale="en" ...>
     </form>
     <div data-livesearch-results></div>    (optional; created if missing)
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
        box.className = 'livesearch-results';
        box.setAttribute('data-livesearch-results', '');
        box.setAttribute('aria-live', 'polite');
        form.appendChild(box);
      }
      var base = document.documentElement.getAttribute('data-base') || '';
      var locale = input.getAttribute('data-locale') ||
        document.documentElement.getAttribute('lang') || 'en';

      var cache = {};
      var debounce = null;
      var inFlight = null;
      var typing = null;

      var guides = {
        en: {
          idle: 'Start typing to search the catalog...',
          min: 'Type at least 2 characters...',
          busy: 'Searching for ":q"...',
          found: 'Found :n fixtures matching ":q"',
          empty: 'No architectural fixtures found matching ":q"',
          hint: 'Tip: try a model code like 1620 or a name like "wall-hung"'
        },
        tr: {
          idle: 'Kataloğda aramak için yazmaya başlayın...',
          min: 'Lütfen en az 2 karakter girin...',
          busy: '":q" için aranıyor...',
          found: '":q" için :n ürün bulundu',
          empty: '":q" ile eşleşen ürün bulunamadı',
          hint: 'İpucu: 1620 gibi bir model kodu ya da "asma klozet" deneyin'
        },
        cs: {
          idle: 'Začněte psát pro vyhledávání v katalogu...',
          min: 'Zadejte prosím alespoň 2 znaky...',
          busy: 'Vyhledává se ":q"...',
          found: 'Nalezeno :n produktů pro ":q"',
          empty: 'Nebyly nalezeny žádné produkty odpovídající ":q"',
          hint: 'Tip: zkuste kód modelu jako 1620 nebo název "závěsné WC"'
        }
      };
      var g = guides[locale] || guides.en;

      function esc(value) {
        return String(value).replace(/[&<>"']/g, function (ch) {
          return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch];
        });
      }

      function fill(text) {
        box.innerHTML = '<div class="livesearch-status">' + text + '</div>';
        box.classList.add('is-open');
        form.classList.add('is-searching');
      }

      function collapse() {
        box.classList.remove('is-open');
        form.classList.remove('is-searching');
      }

      function showIdle() {
        fill(esc(g.idle) + ' <span class="livesearch-hint">' + esc(g.hint) + '</span>');
      }

      function img(raw) {
        if (!raw) return base + '/images/favicon.png';
        if (/^(?:https?:)?\/\//.test(raw)) return raw;
        return base + '/' + String(raw).replace(/^\/+/, '');
      }

      function render(matches, query) {
        if (!matches.length) {
          fill(esc(g.empty.replace(':q', query)));
          return;
        }

        var cards = matches.map(function (product) {
          var name = esc(product.name || product.sku || 'LUFLY fixture');
          var sku = esc(product.sku || product.model_code || '');
          var slug = encodeURIComponent(product.slug || product.id || '');
          var href = base + '/' + encodeURIComponent(locale) + '/products/' + slug;

          return '<a class="livesearch-card" href="' + href + '">' +
            '<img class="livesearch-thumb" src="' + img(product.image) + '" alt="" loading="lazy" decoding="async" width="56" height="56">' +
            '<span class="livesearch-body">' +
            '<span class="livesearch-name">' + name + '</span>' +
            (sku ? '<span class="livesearch-sku">' + esc(g.skuLabel || 'SKU') + ' ' + sku + '</span>' : '') +
            '</span>' +
            '<svg class="livesearch-arrow" viewBox="0 0 20 20" fill="currentColor" width="14" height="14" aria-hidden="true"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>' +
            '</a>';
        }).join('');

        box.innerHTML =
          '<div class="livesearch-status is-ok">' + esc(g.found.replace(':n', matches.length).replace(':q', query)) + '</div>' +
          '<div class="livesearch-grid">' + cards + '</div>';
        box.classList.add('is-open');
      }

      function search(query) {
        var cacheKey = locale + ':' + query.toLowerCase();
        if (cache[cacheKey]) {
          render(cache[cacheKey], query);
          return;
        }

        fill(esc(g.busy.replace(':q', query)).replace(':q', esc(query)));
        box.classList.add('is-busy');

        if (inFlight && typeof inFlight.abort === 'function') {
          inFlight.abort();
        }
        if (typeof AbortController === 'function') {
          inFlight = new AbortController();
        }

        fetch(base + '/api/products/search?q=' + encodeURIComponent(query) +
          '&limit=8&locale=' + encodeURIComponent(locale), {
          headers: { Accept: 'application/json' },
          signal: inFlight ? inFlight.signal : undefined
        })
          .then(function (response) { return response.ok ? response.json() : null; })
          .then(function (json) {
            box.classList.remove('is-busy');
            var data = json && Array.isArray(json.data) ? json.data : [];
            cache[cacheKey] = data;
            render(data, query);
          })
          .catch(function () { /* aborted or offline */ });
      }

      input.addEventListener('input', function () {
        var query = input.value.trim();
        window.clearTimeout(debounce);

        if (query.length === 0) {
          showIdle();
          return;
        }
        if (query.length === 1) {
          fill(esc(g.min));
          return;
        }

        debounce = window.setTimeout(function () {
          search(query);
        }, 600);
      });

      input.addEventListener('focus', function () {
        form.classList.add('is-searching');
        var query = input.value.trim();
        if (query.length >= 2) {
          var cacheKey = locale + ':' + query.toLowerCase();
          if (cache[cacheKey]) {
            render(cache[cacheKey], query);
          }
        } else {
          showIdle();
        }
      });

      /* Enter still navigates to the full results page */
      form.addEventListener('submit', function (e) {
        var query = input.value.trim();
        if (query.length < 2) {
          e.preventDefault();
          fill(esc(g.min));
          return;
        }
        e.preventDefault();
        window.location.href = base + '/' + encodeURIComponent(locale) +
          '/products?q=' + encodeURIComponent(query);
      });

      /* close on outside click */
      document.addEventListener('click', function (e) {
        if (!host.contains(e.target)) {
          collapse();
        }
      });

      input.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
          collapse();
        }
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
