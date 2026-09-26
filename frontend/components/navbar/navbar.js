/* ==========================================================================
   LUFLY - Component: navbar controller
   --------------------------------------------------------------------------
   One small controller for the machined-glass header:
     * scroll state + the progress line that reads down the side spine,
       painted inside requestAnimationFrame
     * index drawer with hover intent (desktop), click, Escape and focus restore
     * bloom sheet on phones: scroll lock, focus trap, swipe-down to close
     * search overlay for small screens
     * instant product search (debounced, aborts stale requests)
     * language menu + keyboard shortcuts (/ and Cmd/Ctrl+K)
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
  var mobileQuery = window.matchMedia ? window.matchMedia('(max-width: 1023px)') : null;
  var hoverQuery = window.matchMedia ? window.matchMedia('(hover: hover) and (pointer: fine)') : null;
  var OPEN_DELAY = 90;
  var CLOSE_DELAY = 220;

  /* ---------- 1. scroll state + progress ---------- */

  var progress = header.querySelector('[data-nav-progress]');
  var frameQueued = false;

  function paintScroll() {
    frameQueued = false;

    var y = window.scrollY || window.pageYOffset || 0;
    header.classList.toggle('is-scrolled', y > 8);

    // Close index dropdown automatically on scroll
    if (header.classList.contains('is-index-open')) {
      closeIndex();
    }
    // Also close search and collapse it back on scroll
    hideResults();
    var dock = header.querySelector('.mnav-dock');
    if (dock) {
      dock.classList.toggle('is-visible', y > 120);
    }

    if (!progress) {
      return;
    }

    var max = root.scrollHeight - window.innerHeight;
    var ratio = max > 0 ? Math.min(1, Math.max(0, y / max)) : 0;
    progress.style.transform = 'scaleY(' + ratio.toFixed(4) + ')';
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
  paintScroll();

  /* ---------- 2. panels that can only be open one at a time ---------- */

  var marks = header.querySelectorAll('[data-drawer-toggle]');
  var mark = marks.length > 0 ? marks[0] : null;
  var drawer = header.querySelector('[data-drawer]');
  var sheet = header.querySelector('[data-nav-sheet]');
  var sheetTrigger = header.querySelector('[data-nav-open]');
  var searchWrap = header.querySelector('[data-search]');
  var indexToggle = header.querySelector('[data-index-toggle]');
  var searchToggle = header.querySelector('[data-search-toggle]');
  var searchInput = searchWrap ? searchWrap.querySelector('[data-search-input]') : null;
  var resultsBox = searchWrap ? searchWrap.querySelector('[data-search-results]') : null;
  var langMenu = header.querySelector('[data-lang-menu]');
  var langToggle = header.querySelector('[data-lang-toggle]');

  function setDrawer(open) {
    header.classList.toggle('is-drawer-open', open);

    marks.forEach(function (btn) {
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });

    if (drawer) {
      drawer.setAttribute('aria-hidden', open ? 'false' : 'true');
    }

    if (open) {
      closeSearch();
      closeLang();
    }
  }

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

  function setSearch(open) {
    header.classList.toggle('is-search-open', open);

    if (searchToggle) {
      searchToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    if (open) {
      closeLang();
      focusElement(searchInput);
    } else {
      hideResults();
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

  
  function setIndex(open) {
    header.classList.toggle('is-index-open', open);
    if (indexToggle) {
      indexToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    }
    if (open) {
      closeSearch();
      closeLang();
    }
  }

  function closeIndex() {
    setIndex(false);
    closeMore();
  }

  if (indexToggle) {
    indexToggle.addEventListener('click', function (e) {
      e.stopPropagation();
      setIndex(!header.classList.contains('is-index-open'));
    });
  }

  /* ---------- 2b. the "More" fold in the index bar ---------- */

  var moreMenu = header.querySelector('[data-more-menu]');
  var moreToggle = header.querySelector('[data-more-toggle]');

  function setMore(open) {
    if (!moreMenu || !moreToggle) {
      return;
    }

    moreMenu.classList.toggle('is-open', open);
    moreToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
  }

  function closeMore() {
    setMore(false);
  }

  if (moreToggle) {
    moreToggle.addEventListener('click', function (event) {
      event.stopPropagation();
      setMore(!moreMenu.classList.contains('is-open'));
    });
  }

  if (moreMenu) {
    /* Following a link must close the fold, whichever way the loader
       decides to move to the page. */
    moreMenu.addEventListener('click', function (event) {
      if (event.target.closest('a')) {
        closeMore();
      }
    });
  }

  /* ---------- 3. index drawer (hover intent on desktop) ---------- */

  var openTimer = null;
  var closeTimer = null;

  function clearTimers() {
    window.clearTimeout(openTimer);
    window.clearTimeout(closeTimer);
  }

  function scheduleDrawerOpen() {
    clearTimers();
    openTimer = window.setTimeout(function () {
      setDrawer(true);
    }, OPEN_DELAY);
  }

  function scheduleDrawerClose() {
    clearTimers();
    closeTimer = window.setTimeout(function () {
      if (isPointing(mark) || isPointing(drawer)) {
        return;
      }

      if (drawer && drawer.contains(document.activeElement)) {
        return;
      }

      setDrawer(false);
    }, CLOSE_DELAY);
  }

  function isPointing(element) {
    return !!element && element.matches(':hover');
  }

  if (drawer) {
    var rail = header.querySelector('.mnav-rail');
    if (rail) {
      rail.addEventListener('mouseenter', scheduleDrawerOpen);
      rail.addEventListener('mouseleave', scheduleDrawerClose);
    }
    marks.forEach(function (btn) {
      btn.addEventListener('click', function (event) {
        event.preventDefault();
        clearTimers();
        setDrawer(!header.classList.contains('is-drawer-open'));
      });
    });

    if (drawer) {
      drawer.addEventListener('mouseenter', clearTimers);
    }

    drawer.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') {
        setDrawer(false);
        focusElement(mark);
      }
    });
  }

  var drawerCloses = header.querySelectorAll('[data-drawer-close]');
    drawerCloses.forEach(function (btn) { btn.addEventListener('click', function () { setDrawer(false); }); });
    var drawerClose = null;

  if (drawerClose) {
    drawerClose.addEventListener('click', function () {
      setDrawer(false);
      focusElement(mark);
    });
  }

  /* ---------- 4. bloom sheet (phones) ---------- */

  if (sheetTrigger) {
    sheetTrigger.addEventListener('click', function (event) {
      event.preventDefault();
      event.stopPropagation();
      if (typeof closeIndex === 'function') closeIndex();
      if (typeof closeSearch === 'function') closeSearch();
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
      /* only the grab area (handle / petals) starts a pull-down */
      if (event.clientY - sheet.getBoundingClientRect().top > 110) {
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
      });
    });
  }

  /* ---------- 5. search overlay + instant search ---------- */

  if (searchToggle) {
    searchToggle.addEventListener('click', function () {
      setSearch(!header.classList.contains('is-search-open'));
    });
  }

  var searchClose = header.querySelector('[data-search-close]');

  if (searchClose) {
    searchClose.addEventListener('click', function () {
      setSearch(false);
    });
  }

  function hideResults() {
    if (resultsBox) {
      resultsBox.classList.remove('is-open');
      resultsBox.innerHTML = '';
    }
    if (searchWrap) {
      searchWrap.classList.remove('is-expanded');
    }
    if (header) {
      header.classList.remove('is-search-open');
    }
    if (searchInput && document.activeElement === searchInput) {
      searchInput.blur();
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
      '<img class="search-result-thumb" src="' + productImage(product.image || product.main_image_url) + '" alt="" loading="lazy" decoding="async" width="64" height="64">' +
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

  function showStatus(text, isError) {
    if (!resultsBox) return;
    resultsBox.innerHTML = '<div class="search-status-banner">' +
      '<span class="search-status-icon"><svg class="icon icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg></span>' +
      '<span class="search-status-text">' + text + '</span>' +
      '</div>';
    resultsBox.classList.add('is-open');
  }

  /* skeleton result rows so the user sees cards are on their way */
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
      if (searchWrap) searchWrap.classList.add('is-expanded');

      if (query.length === 1) {
        showStatus(getGuideText('minChars', query), false);
      } else if (query.length === 0) {
        showStatus(getGuideText('hint', query), false);
      }
    });

    searchInput.addEventListener('blur', function (event) {
      setTimeout(function () {
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
        showStatus(getGuideText('hint', query), false);
        return;
      }

      if (query.length === 1) {
        showStatus(getGuideText('minChars', query), false);
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
        searchInput.blur();
      }
    });
  }

  /* ---------- 6. language menu ---------- */

  if (langToggle) {
    langToggle.addEventListener('click', function (event) {
      event.preventDefault();
      event.stopPropagation();
      var currentlyOpen = langMenu && langMenu.classList.contains('is-open');
      if (typeof closeIndex === 'function') closeIndex();
      if (typeof closeSearch === 'function') closeSearch();
      if (header.classList.contains('is-sheet-open')) setSheet(false);
      setLang(!currentlyOpen);
    });
  }

  /* ---------- 7. global dismissal + shortcuts ---------- */

  document.addEventListener('click', function (event) {
    if (langMenu && !langMenu.contains(event.target)) {
      closeLang();
    }

    if (moreMenu && !moreMenu.contains(event.target)) {
      closeMore();
    }
    var dropdownBar = header.querySelector('#mnav-dropdown-bar');
    if (dropdownBar && !dropdownBar.contains(event.target) && !(indexToggle && indexToggle.contains(event.target))) {
      closeIndex();
    }

    if (searchWrap && !searchWrap.contains(event.target) &&
      !(searchToggle && searchToggle.contains(event.target))) {
      hideResults();
    }

    var isClickOnToggle = false;
    marks.forEach(function (btn) {
      if (btn.contains(event.target)) isClickOnToggle = true;
    });
    if (drawer && !drawer.contains(event.target) && !isClickOnToggle &&
      !(hoverQuery && hoverQuery.matches)) {
      setDrawer(false);
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
      } else if (header.classList.contains('is-drawer-open')) {
        setDrawer(false);
      }

      closeLang();
      closeMore();
      return;
    }

    if (typing) {
      return;
    }

    var isSlash = event.key === '/' && !event.metaKey && !event.ctrlKey && !event.altKey;
    var isCommand = (event.metaKey || event.ctrlKey) && String(event.key).toLowerCase() === 'k';

    if ((isSlash || isCommand) && searchInput) {
      event.preventDefault();

      if (mobileQuery && mobileQuery.matches) {
        setSearch(true);
      } else {
        focusElement(searchInput);
      }
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

    closeIndex();
    setDrawer(false);
    hideResults();
  });

  /* desktop <-> mobile switches must not leave an orphan panel open */
  if (mobileQuery && typeof mobileQuery.addEventListener === 'function') {
    mobileQuery.addEventListener('change', function (event) {
      if (!event.matches) {
        setSheet(false);
        setSearch(false);
      } else {
        setDrawer(false);
      }
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

    if (focusable.length) {
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
