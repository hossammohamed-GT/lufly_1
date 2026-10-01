/* ==========================================================================
   LUFLY - Component: navbar controller ("Overlay Glass")
   --------------------------------------------------------------------------
     * scroll state - on the home page the glass opacity tracks how far the
       hero has scrolled away (--nav-glass, painted inside one rAF batch);
       everywhere else the bar is glass from the start
     * the expanding search: opens with a width+fade animation, runs the
       instant product search (debounced, aborts stale requests, caches)
       and types its placeholder moods while idle
     * the hero field (home, desktop): while the bar is still part of the
       hero photograph the search field sits open beside its icon - no
       focus stolen, moods typing away - and folds back into the icon in
       the same frame the glass + centre links take over
     * language menu, bloom sheet (scroll lock, focus trap,
       swipe-down to close), keyboard shortcuts (/ and Cmd/Ctrl+K)
   No dependencies, no layout reads on every frame, listeners are passive.
   ========================================================================== */

(function () {
  'use strict';

  var header = document.querySelector('[data-navbar]');

  if (!header) {
    return;
  }

  var root = document.documentElement;
  var base = root.getAttribute('data-base') || '';
  var overlay = header.hasAttribute('data-nav-hero');
  var hero = overlay ? document.querySelector('[data-hero]') : null;
  var mobileQuery = window.matchMedia ? window.matchMedia('(max-width: 1023px)') : null;

  /* ---------- 1. scroll state + the progressive glass ---------- */

  var frameQueued = false;
  var heroEdge = 0;              /* scroll position at which the hero is gone */

  function measureHero() {
    if (!hero) {
      return;
    }

    var rect = hero.getBoundingClientRect();
    var top = Math.max(0, rect.top + (window.pageYOffset || 0));
    var navH = header.offsetHeight || 64;
    heroEdge = Math.max(1, top + rect.height - navH);
  }

  function paintScroll() {
    frameQueued = false;

    var y = window.scrollY || window.pageYOffset || 0;

    if (overlay && hero) {
      /* how much of the hero is still under the bar (0..1) */
      var t = Math.min(1, Math.max(0, y / heroEdge));
      var settled = t >= 0.42;
      header.style.setProperty('--nav-glass', Math.pow(t, 0.6).toFixed(3));
      header.classList.toggle('is-scrolled', settled);
      /* the glass is nearly solid here: safe for the ink + layer to join
         the theme (see the two-stage rules in navbar.css) */
      header.classList.toggle('is-solid', t >= 0.8);
      /* the bar is still a piece of the photograph => the hero field shows */
      overHero = !settled;
      syncHeroField();
    } else {
      header.classList.toggle('is-scrolled', y > 8);
    }
  }

  function queueScrollPaint() {
    if (frameQueued) {
      return;
    }

    frameQueued = true;
    window.requestAnimationFrame(paintScroll);
  }

  window.addEventListener('scroll', queueScrollPaint, { passive: true });
  window.addEventListener('resize', queueScrollPaint, { passive: true });

  if (hero && typeof ResizeObserver === 'function') {
    new ResizeObserver(measureHero).observe(hero);
  }

  /* ---------- 2. panels that can only be open one at a time ---------- */

  var sheet = header.querySelector('[data-nav-sheet]');
  var sheetTrigger = header.querySelector('[data-nav-open]');
  var searchWrap = header.querySelector('[data-search]');
  var searchToggles = Array.prototype.slice.call(header.querySelectorAll('[data-search-toggle]'));
  var searchToggle = searchToggles[0] || null;
  var searchInput = searchWrap ? searchWrap.querySelector('[data-search-input]') : null;
  var resultsBox = searchWrap ? searchWrap.querySelector('[data-search-results]') : null;
  var langMenu = header.querySelector('[data-lang-menu]');
  var langToggle = header.querySelector('[data-lang-toggle]');

  function setSheet(open) {
    header.classList.toggle('is-sheet-open', open);

    if (sheetTrigger) {
      sheetTrigger.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    if (sheet) {
      sheet.setAttribute('aria-hidden', open ? 'false' : 'true');
    }

    lockScroll(open);

    if (open) {
      closeSearch();
      closeLang();
      window.requestAnimationFrame(function () {
        focusFirst(sheet);
      });
    } else if (sheet && sheet.contains(document.activeElement)) {
      focusElement(sheetTrigger);
    }
  }

  function setSearch(open, opts) {
    header.classList.toggle('is-search-open', open);

    searchToggles.forEach(function (toggle) {
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });

    if (open) {
      setSheet(false);
      closeLang();
      /* the hero field opens silently: stealing focus on load would be rude
         (and would pop the hint panel + keyboard before the visitor asked) */
      if (!opts || opts.focus !== false) {
        focusElement(searchInput);
      }
    } else {
      hideResults();
    }
  }

  /* ---------- 2b. the hero field: open with the hero, fold into the icon -- */

  /* On the storefront home page the search field is part of the hero
     presentation: it sits open beside its icon while the bar is still a
     piece of the photograph (its placeholder keeps typing the moods), then
     folds back into the icon with the same width+fade handover the manual
     toggle uses, in the same frame the centre links fade in. Only the
     hero<->glass TRANSITIONS act, so a field the visitor is typing into is
     never yanked away (it simply stays open until he closes it), and phones
     are left out entirely: there the search is a drop panel under the bar,
     not a bar field, and it stays closed until asked for. */
  var overHero = true;            /* last hero state painted by paintScroll */
  var heroFieldWanted = null;     /* what the hero state last asked of it */

  function searchInUse() {
    return !!(searchInput &&
      (searchInput.value !== '' || document.activeElement === searchInput));
  }

  function syncHeroField() {
    if (!overlay || !hero) {
      return;
    }

    var wanted = overHero && !(mobileQuery && mobileQuery.matches);

    if (heroFieldWanted === wanted) {
      return;
    }

    heroFieldWanted = wanted;

    if (wanted) {
      if (!header.classList.contains('is-search-open')) {
        setSearch(true, { focus: false });
      }
    } else if (header.classList.contains('is-search-open') && !searchInUse()) {
      setSearch(false);
    }
  }

  function closeSearch() {
    if (header.classList.contains('is-search-open')) {
      setSearch(false);
    }
  }

  function setLang(open) {
    if (!langMenu || !langToggle) {
      return;
    }

    langMenu.classList.toggle('is-open', open);
    langToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
  }

  function closeLang() {
    setLang(false);
  }

  /* first paint - done here, after every panel helper above exists */
  measureHero();
  paintScroll();

  /* ---------- 3. the expanding search + instant results ---------- */

  searchToggles.forEach(function (toggle) {
    toggle.addEventListener('click', function () {
      setSearch(!header.classList.contains('is-search-open'));
    });
  });

  var searchClose = header.querySelector('[data-search-close]');

  if (searchClose) {
    searchClose.addEventListener('click', function () {
      setSearch(false);
      focusElement(searchToggle);
    });
  }

  function hideResults() {
    if (resultsBox) {
      resultsBox.classList.remove('is-open');
      resultsBox.innerHTML = '';
    }
  }

  function escapeHtml(value) {
    return String(value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function productImage(raw) {
    if (!raw) {
      return base + '/images/favicon.png';
    }

    if (/^(?:https?:)?\/\//.test(raw)) {
      return raw;
    }

    return base + '/' + String(raw).replace(/^\/+/, '');
  }

  function resultCard(product) {
    var name = escapeHtml(product.name || product.sku || 'LUFLY fixture');
    var sku = escapeHtml(product.sku || '');
    var slug = product.slug || product.id || '';
    var href = base + '/' + encodeURIComponent(locale()) + '/products/' + encodeURIComponent(slug);

    return '<a class="search-result-card" href="' + href + '">' +
      '<img class="search-result-thumb" src="' + productImage(product.image || product.main_image_url) + '" alt="" loading="lazy" decoding="async" width="56" height="56">' +
      '<span class="search-result-body">' +
      '<span class="search-result-name">' + name + '</span>' +
      '<span class="search-result-sku">' + (sku ? 'SKU ' + sku : '') + '</span>' +
      '<span class="search-result-badge">LUFLY Architecture</span>' +
      '</span></a>';
  }

  function locale() {
    return (searchInput && searchInput.getAttribute('data-locale')) || 'en';
  }

  function getGuideText(type, query) {
    var loc = locale();
    var guides = {
      en: {
        minChars: 'Please type at least 2 characters to start searching...',
        busy: 'Searching for ":q"...',
        empty: 'No architectural fixtures found matching "' + escapeHtml(query) + '"',
        hint: 'Search by product name, SKU code, or category',
        viewAll: 'View all results'
      },
      tr: {
        minChars: 'Aramaya başlamak için lütfen en az 2 karakter girin...',
        busy: '":q" için aranıyor...',
        empty: '"' + escapeHtml(query) + '" ile eşleşen ürün bulunamadı',
        hint: 'Ürün adı, stok kodu veya kategori ile arayabilirsiniz',
        viewAll: 'Tüm sonuçları gör'
      },
      cs: {
        minChars: 'Pro zahájení vyhledávání zadejte alespoň 2 znaky...',
        busy: 'Vyhledává se ":q"...',
        empty: 'Nebyly nalezeny žádné produkty odpovídající "' + escapeHtml(query) + '"',
        hint: 'Hledejte podle názvu produktu, kódu SKU nebo kategorie',
        viewAll: 'Zobrazit všechny výsledky'
      }
    };
    var d = guides[loc] || guides.en;
    return d[type] || '';
  }

  function showStatus(text) {
    if (!resultsBox) return;
    resultsBox.innerHTML = '<div class="search-status-banner">' +
      '<span class="search-status-icon"><svg class="icon icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg></span>' +
      '<span class="search-status-text">' + text + '</span>' +
      '</div>';
    resultsBox.classList.add('is-open');
  }

  function showSearchBusy(query) {
    if (!resultsBox) return;
    var one = '<div class="livesearch-skel livesearch-skel-row" aria-hidden="true">' +
      '<span class="livesearch-skel-thumb"></span>' +
      '<span class="livesearch-skel-body">' +
      '<span class="livesearch-skel-line is-w35"></span>' +
      '<span class="livesearch-skel-line is-w80"></span>' +
      '</span></div>';
    var skels = '';
    for (var i = 0; i < 4; i++) skels += one;

    resultsBox.innerHTML = '<div class="search-status-banner">' +
      '<span class="search-status-icon"><svg class="icon icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg></span>' +
      '<span class="search-status-text">' + escapeHtml(getGuideText('busy', query)) + '</span>' +
      '</div>' +
      '<div class="search-results-grid">' + skels + '</div>';
    resultsBox.classList.add('is-open');
  }

  function viewAllLink(query) {
    var href = base + '/' + encodeURIComponent(locale()) +
      '/products?q=' + encodeURIComponent(query);
    return '<a class="livesearch-all" href="' + href + '">' +
      '<svg viewBox="0 0 20 20" fill="currentColor" width="13" height="13" aria-hidden="true"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>' +
      escapeHtml(getGuideText('viewAll', query)) +
      '</a>';
  }

  function renderResults(matches, query) {
    if (!resultsBox) return;

    if (!matches.length) {
      resultsBox.innerHTML = '<div class="search-results-empty">' +
        '<svg class="search-results-empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>' +
        '<strong>' + getGuideText('empty', query) + '</strong>' +
        '<span>' + getGuideText('hint', query) + '</span>' +
        '</div>';
    } else {
      var headerMsg = locale() === 'tr' ? '"' + escapeHtml(query) + '" için ' + matches.length + ' ürün bulundu' :
                      locale() === 'cs' ? 'Nalezeno ' + matches.length + ' produktů pro "' + escapeHtml(query) + '"' :
                      'Found ' + matches.length + ' fixtures matching "' + escapeHtml(query) + '"';

      resultsBox.innerHTML = '<div class="search-status-banner">' +
        '<span class="search-status-icon"><svg class="icon icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg></span>' +
        '<span class="search-status-text">' + headerMsg + '</span>' +
        '</div>' +
        '<div class="search-results-grid">' + matches.map(resultCard).join('') + '</div>' +
        viewAllLink(query);
    }

    resultsBox.classList.add('is-open');
  }

  var searchCache = {};

  if (searchInput && resultsBox) {
    var debounce = null;
    var inFlight = null;

    searchInput.addEventListener('focus', function () {
      var query = searchInput.value.trim();

      if (query.length === 1) {
        showStatus(getGuideText('minChars', query));
      } else if (query.length === 0) {
        showStatus(getGuideText('hint', query));
      }
    });

    searchInput.addEventListener('blur', function () {
      window.setTimeout(function () {
        var active = document.activeElement;
        if (searchWrap && !searchWrap.contains(active)) {
          hideResults();
        }
      }, 180);
    });

    searchInput.addEventListener('input', function () {
      window.clearTimeout(debounce);
      var query = searchInput.value.trim();

      if (query.length === 0) {
        showStatus(getGuideText('hint', query));
        return;
      }

      if (query.length === 1) {
        showStatus(getGuideText('minChars', query));
        return;
      }

      var cacheKey = locale() + ':' + query.toLowerCase();
      if (searchCache[cacheKey]) {
        renderResults(searchCache[cacheKey], query);
        return;
      }

      debounce = window.setTimeout(function () {
        if (inFlight && typeof inFlight.abort === 'function') {
          inFlight.abort();
        }

        showSearchBusy(query);

        var url = base + '/api/products/search?q=' + encodeURIComponent(query) +
          '&limit=8&locale=' + encodeURIComponent(locale());

        if (typeof AbortController === 'function') {
          inFlight = new AbortController();
        }

        fetch(url, {
          headers: { Accept: 'application/json' },
          signal: inFlight ? inFlight.signal : undefined
        })
          .then(function (response) {
            return response.ok ? response.json() : null;
          })
          .then(function (json) {
            var data = json && Array.isArray(json.data) ? json.data : [];
            searchCache[cacheKey] = data;
            renderResults(data, query);
          })
          .catch(function () {
            /* request aborted or offline */
          });
      }, 120);
    });

    searchInput.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') {
        searchInput.value = '';
        hideResults();
        setSearch(false);
      }
    });
  }

  /* ---------- 4. the placeholder that types itself (while open + idle) ---------- */

  (function () {
    var field = header.querySelector('[data-search-moods]');

    if (!field) return;

    var moods = [];

    try {
      moods = JSON.parse(field.getAttribute('data-search-moods')) || [];
    } catch (error) {
      moods = [];
    }

    moods = moods.filter(function (mood) {
      return typeof mood === 'string' && mood.trim() !== '';
    });

    var calm = window.matchMedia
      && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (moods.length === 0 || calm) return;

    var index = 0;
    var pos = 0;
    var dir = 1;
    var timer = null;

    function idle() {
      if (document.hidden) return false;
      if (!header.classList.contains('is-search-open')) return false;
      if (field.value !== '') return false;
      if (document.activeElement === field) return false;
      return true;
    }

    function tick() {
      if (!idle()) {
        timer = window.setTimeout(tick, 900);
        return;
      }

      var mood = moods[index];

      pos += dir;
      field.setAttribute('placeholder', mood.slice(0, pos));

      var delay = dir > 0 ? 58 : 26;

      if (pos >= mood.length) {
        dir = -1;
        delay = 1900;
      } else if (pos <= 0) {
        dir = 1;
        index = (index + 1) % moods.length;
        delay = 460;
      }

      timer = window.setTimeout(tick, delay);
    }

    timer = window.setTimeout(function () {
      pos = 0;
      dir = 1;
      tick();
    }, 2400);
  })();

  /* ---------- 5. language menu ---------- */

  if (langToggle) {
    langToggle.addEventListener('click', function (event) {
      event.preventDefault();
      event.stopPropagation();
      var currentlyOpen = langMenu && langMenu.classList.contains('is-open');
      closeSearch();
      setLang(!currentlyOpen);
    });
  }

  /* ---------- 6. bloom sheet (phones) ---------- */

  if (sheetTrigger) {
    sheetTrigger.addEventListener('click', function (event) {
      event.preventDefault();
      event.stopPropagation();
      closeSearch();
      closeLang();
      setSheet(!header.classList.contains('is-sheet-open'));
    });
  }

  Array.prototype.forEach.call(header.querySelectorAll('[data-sheet-close]'), function (trigger) {
    trigger.addEventListener('click', function () {
      setSheet(false);
    });
  });

  if (sheet && typeof PointerEvent === 'function') {
    var dragStart = null;
    var dragDelta = 0;

    sheet.addEventListener('pointerdown', function (event) {
      /* only the grab area (handle) starts a pull-down */
      if (event.clientY - sheet.getBoundingClientRect().top > 64) {
        return;
      }

      dragStart = event.clientY;
      dragDelta = 0;
    });

    sheet.addEventListener('pointermove', function (event) {
      if (dragStart === null) {
        return;
      }

      dragDelta = Math.max(0, event.clientY - dragStart);
      sheet.style.transform = 'translate3d(0,' + dragDelta + 'px,0)';
    });

    ['pointerup', 'pointercancel'].forEach(function (type) {
      sheet.addEventListener(type, function () {
        if (dragStart === null) {
          return;
        }

        dragStart = null;
        sheet.style.transform = '';

        if (dragDelta > 90) {
          setSheet(false);
        }

        dragDelta = 0;
      });
    });
  }

  /* ---------- 7. global dismissal + shortcuts ---------- */

  document.addEventListener('click', function (event) {
    if (langMenu && !langMenu.contains(event.target)) {
      closeLang();
    }

    if (searchWrap && !searchWrap.contains(event.target) &&
      !(searchToggle && searchToggle.contains(event.target))) {
      hideResults();
    }
  });

  document.addEventListener('keydown', function (event) {
    var target = event.target;
    var typing = target && (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA' ||
      target.tagName === 'SELECT' || target.isContentEditable);

    if (event.key === 'Escape') {
      if (header.classList.contains('is-sheet-open')) {
        setSheet(false);
      } else if (header.classList.contains('is-search-open')) {
        setSearch(false);
      }

      closeLang();
      return;
    }

    if (typing) {
      return;
    }

    var isSlash = event.key === '/' && !event.metaKey && !event.ctrlKey && !event.altKey;
    var isCommand = (event.metaKey || event.ctrlKey) && String(event.key).toLowerCase() === 'k';

    if ((isSlash || isCommand) && searchInput) {
      event.preventDefault();
      setSearch(true);
    }
  });

  /* Tab is trapped inside the sheet while it is open */
  document.addEventListener('keydown', function (event) {
    if (event.key !== 'Tab' || !header.classList.contains('is-sheet-open') || !sheet) {
      return;
    }

    var focusable = focusableIn(sheet);

    if (!focusable.length) {
      return;
    }

    var first = focusable[0];
    var last = focusable[focusable.length - 1];

    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault();
      last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault();
      first.focus();
    }
  });

  /* navigating anywhere closes the panels */
  header.addEventListener('click', function (event) {
    var link = event.target.closest ? event.target.closest('a') : null;

    if (!link) {
      return;
    }

    if (header.classList.contains('is-sheet-open')) {
      setSheet(false);
    }

    hideResults();
  });

  /* desktop <-> mobile switches must not leave an orphan panel open */
  if (mobileQuery && typeof mobileQuery.addEventListener === 'function') {
    mobileQuery.addEventListener('change', function (event) {
      if (!event.matches) {
        setSheet(false);
        setSearch(false);
      } else if (header.classList.contains('is-search-open') && !searchInUse()) {
        /* the auto-opened hero field must not survive into the phone layout
           as a drop panel; one the visitor is typing into may */
        setSearch(false);
      }

      syncHeroField();
    });
  }

  /* ---------- 8. helpers ---------- */

  function focusElement(element) {
    if (element && typeof element.focus === 'function') {
      element.focus({ preventScroll: true });
    }
  }

  function focusableIn(scope) {
    if (!scope) {
      return [];
    }

    return Array.prototype.filter.call(
      scope.querySelectorAll('a[href], button:not([disabled]), input, select, textarea, [tabindex]:not([tabindex="-1"])'),
      function (element) {
        return element.offsetParent !== null;
      }
    );
  }

  function focusFirst(scope) {
    var focusable = focusableIn(scope);

    if (focusable) {
      focusElement(focusable[0]);
    }
  }

  var lockedPadding = '';

  function lockScroll(lock) {
    if (lock) {
      var gap = window.innerWidth - root.clientWidth;
      lockedPadding = document.body.style.paddingRight;
      document.body.style.overflow = 'hidden';

      if (gap > 0) {
        document.body.style.paddingRight = gap + 'px';
      }
    } else {
      document.body.style.overflow = '';
      document.body.style.paddingRight = lockedPadding;
    }
  }
})();
