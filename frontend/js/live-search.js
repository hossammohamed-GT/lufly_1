/* ============================================================
   Live product search (shared)
   Type 2+ characters, pause, full product cards appear below the
   field (same editorial cards as the catalog grid), with skeleton
   cards while loading and a "view all results" jump. Reuses the
   /api/products/search endpoint.
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
          hint: 'Tip: try a model code like 1620 or a name like "wall-hung"',
          details: 'View details',
          viewAll: 'View all results'
        },
        tr: {
          idle: 'Kataloğda aramak için yazmaya başlayın...',
          min: 'Lütfen en az 2 karakter girin...',
          busy: '":q" için aranıyor...',
          found: '":q" için :n ürün bulundu',
          empty: '":q" ile eşleşen ürün bulunamadı',
          hint: 'İpucu: 1620 gibi bir model kodu ya da "asma klozet" deneyin',
          details: 'Detayları gör',
          viewAll: 'Tüm sonuçları gör'
        },
        cs: {
          idle: 'Začněte psát pro vyhledávání v katalogu...',
          min: 'Zadejte prosím alespoň 2 znaky...',
          busy: 'Vyhledává se ":q"...',
          found: 'Nalezeno :n produktů pro ":q"',
          empty: 'Nebyly nalezeny žádné produkty odpovídající ":q"',
          hint: 'Tip: zkuste kód modelu jako 1620 nebo název "závěsné WC"',
          details: 'Zobrazit detail',
          viewAll: 'Zobrazit všechny výsledky'
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
        return '<div class="livesearch-cards" aria-hidden="true">' + out + '</div>';
      }

      function showBusy(query) {
        box.innerHTML =
          '<div class="livesearch-status">' + esc(g.busy.replace(':q', query)) + '</div>' +
          skeletonCards(4);
        box.classList.add('is-open', 'is-busy');
        form.classList.add('is-searching');
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

        var fallback = img(null);
        var arrow = '<svg viewBox="0 0 20 20" fill="currentColor" width="13" height="13" aria-hidden="true"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>';

        var cards = matches.map(function (product, index) {
          var name = esc(product.name || product.sku || 'LUFLY fixture');
          var sku = esc(product.sku || product.model_code || '');
          var shortDesc = esc(String(product.short_description || '').trim());
          var slug = encodeURIComponent(product.slug || product.id || '');
          var href = base + '/' + encodeURIComponent(locale) + '/products/' + slug;
          var situ = product.situ_image ? img(product.situ_image) : '';
          var no = ('00' + (index + 1)).slice(-3);
          var delay = (0.05 + 0.05 * index).toFixed(2);

          return '<article class="pcard" style="--pcard-delay:' + delay + 's">' +
            '<a class="pcard-media" href="' + href + '">' +
            '<img class="pcard-img" src="' + esc(img(product.image)) + '" alt="' + name + '" loading="lazy" decoding="async" width="420" height="320"' +
            ' onerror="this.onerror=null;this.src=\'' + fallback + '\'">' +
            (situ ? '<img class="pcard-img pcard-img-situ" src="' + esc(situ) + '" alt="" loading="lazy" decoding="async" width="420" height="320"' +
              ' onerror="this.onerror=null;this.remove();">' : '') +
            '<span class="pcard-hint" aria-hidden="true">' + arrow + esc(g.details) + '</span>' +
            '</a>' +
            '<div class="pcard-body">' +
            '<div class="pcard-top"><span class="pcard-code">' + (sku || 'LUFLY') + '</span><span class="pcard-no">' + no + '</span></div>' +
            '<h3 class="pcard-title"><a href="' + href + '">' + name + '</a></h3>' +
            (shortDesc ? '<p class="pcard-desc">' + shortDesc + '</p>' : '') +
            '</div></article>';
        }).join('');

        box.innerHTML =
          '<div class="livesearch-status is-ok">' + esc(g.found.replace(':n', matches.length).replace(':q', query)) + '</div>' +
          '<div class="livesearch-cards">' + cards + '</div>' +
          '<a class="livesearch-all" href="' + base + '/' + encodeURIComponent(locale) +
          '/products?q=' + encodeURIComponent(query) + '">' + arrow + esc(g.viewAll) + '</a>';
        box.classList.add('is-open');
      }

      function search(query) {
        var cacheKey = locale + ':' + query.toLowerCase();
        if (cache[cacheKey]) {
          render(cache[cacheKey], query);
          return;
        }

        showBusy(query);
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
          } else {
            search(query);
          }
        } else if (query.length === 1) {
          fill(esc(g.min));
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
