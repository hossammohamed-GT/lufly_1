/* ==========================================================================
   LUFLY — Component: navbar controller
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
  }

  if (indexToggle) {
    indexToggle.addEventListener('click', function (e) {
      e.stopPropagation();
      setIndex(!header.classList.contains('is-index-open'));
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

  function resultRow(product) {
    var name = escapeHtml(product.name || product.sku || 'LUFLY fixture');
    var sku = escapeHtml(product.sku || '');
    var slug = product.slug || product.id || '';
    var href = base + '/' + encodeURIComponent(locale()) + '/products/' + encodeURIComponent(slug);

    return '<a class="search-result" href="' + href + '">' +
      '<img class="search-result-thumb" src="' + productImage(product.image || product.main_image_url) + '" alt="" loading="lazy" decoding="async" width="52" height="52">' +
      '<span class="search-result-body">' +
      '<span class="search-result-name">' + name + '</span>' +
      '<span class="search-result-sku">' + (sku ? 'SKU ' + sku : '') + '</span>' +
      '</span></a>';
  }

  function locale() {
    return (searchInput && searchInput.getAttribute('data-locale')) || 'en';
  }

  function renderResults(matches, query) {
    if (!resultsBox) {
      return;
    }

    if (!matches.length) {
      resultsBox.innerHTML = '<div class="search-results-empty">' + escapeHtml(query) + '</div>';
    } else {
      resultsBox.innerHTML = matches.map(resultRow).join('');
    }

    resultsBox.classList.add('is-open');
  }

  if (searchInput && resultsBox) {
    var debounce = null;
    var inFlight = null;

    searchInput.addEventListener('input', function () {
      window.clearTimeout(debounce);

      var query = searchInput.value.trim();

      if (query.length < 2) {
        hideResults();
        return;
      }

      debounce = window.setTimeout(function () {
        if (inFlight && typeof inFlight.abort === 'function') {
          inFlight.abort();
        }

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
            renderResults(json && Array.isArray(json.data) ? json.data : [], query);
          })
          .catch(function () {
            /* request aborted or offline */
          });
      }, 180);
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
