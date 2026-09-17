/* App bootstrap: theme, modal, header, search, language. */
(function () {
  'use strict';

  var root = document.documentElement;
  var STORAGE_KEY = 'lufly-theme';

  function base() {
    return root.getAttribute('data-base') || '';
  }

  function storedTheme() {
    try {
      return localStorage.getItem(STORAGE_KEY);
    } catch (error) {
      return null;
    }
  }

  function saveTheme(theme) {
    try {
      localStorage.setItem(STORAGE_KEY, theme);
    } catch (error) {
      /* storage unavailable */
    }
  }

  /* ---------- Theme ---------- */

  function initTheme() {
    var theme = storedTheme();
    if (!theme) {
      var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
      theme = prefersDark ? 'dark' : 'light';
    }
    root.setAttribute('data-theme', theme);
  }

  function toggleTheme() {
    var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    root.setAttribute('data-theme', next);
    saveTheme(next);
  }

  document.addEventListener('click', function (event) {
    var toggle = event.target.closest('[data-theme-toggle], #lufly-theme-toggle-btn');
    if (!toggle) {
      return;
    }
    event.preventDefault();
    toggleTheme();
  });

  /* ---------- Modal ---------- */

  function setModal(modal, open) {
    if (!modal) {
      return;
    }
    modal.hidden = !open;
  }

  document.addEventListener('click', function (event) {
    var opener = event.target.closest('[data-modal-open]');
    if (opener) {
      setModal(document.getElementById(opener.getAttribute('data-modal-open')), true);
      return;
    }

    var closer = event.target.closest('[data-modal-close]');
    if (closer) {
      setModal(closer.closest('[data-modal]'), false);
      return;
    }

    if (event.target.matches && event.target.matches('[data-modal]')) {
      setModal(event.target, false);
    }
  });

  document.addEventListener('keydown', function (event) {
    if (event.key !== 'Escape') {
      return;
    }
    document.querySelectorAll('[data-modal]:not([hidden])').forEach(function (modal) {
      setModal(modal, false);
    });
  });

  /* ---------- Sticky header ---------- */

  function initHeader() {
    var header = document.querySelector('.deante-header');
    if (!header) {
      return;
    }

    window.addEventListener('scroll', function () {
      header.classList.toggle('is-scrolled', window.scrollY > 20);
    }, { passive: true });
  }

  /* ---------- Language dropdown ---------- */

  function initLanguageDropdown() {
    var menu = document.getElementById('lufly-lang-menu');
    var toggle = document.getElementById('lufly-lang-toggle');
    if (!menu || !toggle) {
      return;
    }

    toggle.addEventListener('click', function (event) {
      event.stopPropagation();
      var isOpen = menu.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });

    document.addEventListener('click', function (event) {
      if (!menu.contains(event.target)) {
        menu.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
      }
    });
  }

  /* ---------- Instant product search ---------- */

  function escapeHtml(value) {
    return String(value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function productImage(raw) {
    if (!raw) {
      return base() + '/images/favicon.png';
    }
    if (/^(?:https?:)?\/\//.test(raw)) {
      return raw;
    }
    return base() + '/' + raw.replace(/^\/+/, '');
  }

  function initSearch() {
    var wrap = document.querySelector('[data-search]');
    var input = wrap ? wrap.querySelector('.deante-search-pill') : null;
    var dropdown = wrap ? wrap.querySelector('.deante-search-results') : null;
    if (!wrap || !input || !dropdown) {
      return;
    }

    var locale = input.getAttribute('data-locale') || 'en';
    var timer = null;

    function hide() {
      dropdown.classList.remove('is-open');
      dropdown.innerHTML = '';
    }

    function row(product) {
      var name = escapeHtml(product.name || product.sku || 'LUFLY fixture');
      var sku = escapeHtml(product.sku || '');
      var slug = product.slug || product.id || '';
      var href = base() + '/' + encodeURIComponent(locale) + '/products/' + encodeURIComponent(slug);

      return '<a class="search-result" href="' + href + '">' +
        '<img class="search-result-thumb" src="' + productImage(product.image || product.main_image_url) + '" alt="' + name + '" loading="lazy">' +
        '<span class="search-result-body">' +
        '<span class="search-result-name">' + name + '</span>' +
        '<span class="search-result-sku">' + (sku ? 'SKU: ' + sku : '') + '</span>' +
        '</span></a>';
    }

    function render(matches, query) {
      if (!matches.length) {
        dropdown.innerHTML = '<div class="search-results-empty">No fixtures found matching "' + escapeHtml(query) + '"</div>';
        dropdown.classList.add('is-open');
        return;
      }
      dropdown.innerHTML = matches.map(row).join('');
      dropdown.classList.add('is-open');
    }

    function fetchProducts(query) {
      var url = base() + '/api/products/search?q=' + encodeURIComponent(query) +
        '&limit=8&locale=' + encodeURIComponent(locale);

      return fetch(url, { headers: { Accept: 'application/json' } })
        .then(function (response) { return response.ok ? response.json() : null; })
        .then(function (json) { return json && Array.isArray(json.data) ? json.data : []; })
        .catch(function () { return []; });
    }

    input.addEventListener('input', function () {
      clearTimeout(timer);
      var query = input.value.trim();

      if (query.length < 2) {
        hide();
        return;
      }

      timer = setTimeout(function () {
        fetchProducts(query).then(function (matches) { render(matches, query); });
      }, 200);
    });

    document.addEventListener('click', function (event) {
      if (!wrap.contains(event.target)) {
        hide();
      }
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    initTheme();
    initHeader();
    initLanguageDropdown();
    initSearch();
  });
})();
