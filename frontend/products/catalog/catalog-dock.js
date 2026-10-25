/* Scrolled catalog dock: a compact adapter over the existing atlas and search. */
(function () {
  'use strict';

  function init() {
    var dock = document.querySelector('[data-catalog-dock]');
    if (!dock || dock.__catalogDock) return;

    var catalog = dock.closest('.catalog');
    var select = dock.querySelector('[data-catalog-dock-category]');
    var form = dock.querySelector('[data-catalog-dock-search]');
    var input = dock.querySelector('[data-catalog-dock-input]');
    var clear = dock.querySelector('[data-catalog-dock-clear]');
    if (!catalog || !select || !form || !input || !clear) return;

    var explorer = catalog.querySelector('[data-category-explorer]');
    var searchHost = catalog.querySelector('[data-livesearch]');
    var searchForm = searchHost ? searchHost.querySelector('.catalog-searchform') : null;
    var searchInput = searchForm ? searchForm.querySelector('[data-livesearch-input]') : null;
    var gridHost = catalog.querySelector('[data-sk-host]');
    if (!searchHost || !searchForm || !searchInput || !gridHost) return;

    dock.__catalogDock = true;
    var frame = null;
    var resizeFrame = null;
    var resizeAnchor = null;
    var lastGridTop = null;
    var pendingGridTop = null;

    function event(name, detail, cancelable) {
      return new CustomEvent(name, {
        bubbles: true,
        cancelable: Boolean(cancelable),
        detail: detail || {}
      });
    }

    function syncClear() {
      clear.hidden = input.value === '';
    }

    function setQuery(value) {
      input.value = String(value || '');
      syncClear();
    }

    function syncCategory(slug) {
      select.value = String(slug || '');
      var option = select.selectedOptions && select.selectedOptions[0];
      select.title = option ? (option.getAttribute('aria-label') || option.textContent.trim()) : '';
    }

    function liveSearchReady() {
      return searchHost.__livesearch === true;
    }

    function forwardInput() {
      searchInput.value = input.value;
      searchInput.dispatchEvent(event('input', { source: 'catalog-dock' }));
    }

    function forwardSubmit() {
      searchInput.value = input.value;
      searchForm.dispatchEvent(event('submit', { source: 'catalog-dock' }, true));
    }

    /* Asset failure fallback only. In normal operation both category and
       search stay on their original controllers and no second requester runs. */
    function navigateFallback(slug, query) {
      var url;
      try {
        url = new URL(window.location.href);
      } catch (error) {
        return;
      }
      if (slug) url.searchParams.set('category', slug);
      else url.searchParams.delete('category');
      if (query) url.searchParams.set('q', query);
      else url.searchParams.delete('q');
      url.searchParams.delete('page');
      catalog.dispatchEvent(event('catalog:navigate', { url: url.href, reveal: false }));
    }

    function navbarHeight() {
      var navbar = document.querySelector('[data-navbar]');
      if (navbar) return navbar.getBoundingClientRect().height;
      return window.innerWidth <= 1023 ? 52 : 56;
    }

    function focusResults() {
      var results = catalog.querySelector('#catalog-results');
      if (!results) return;
      try {
        results.focus({ preventScroll: true });
      } catch (error) {
        results.focus();
      }
    }

    function setVisible(visible) {
      if (dock.classList.contains('is-visible') === visible) return;
      if (!visible && dock.contains(document.activeElement)) focusResults();
      dock.classList.toggle('is-visible', visible);
      dock.setAttribute('aria-hidden', visible ? 'false' : 'true');
      if (visible) dock.removeAttribute('inert');
      else dock.setAttribute('inert', '');
    }

    function updateVisibility() {
      if (resizeFrame !== null) return;
      var dockTop = navbarHeight() + 8;
      var gridRect = gridHost.getBoundingClientRect();
      var catalogRect = catalog.getBoundingClientRect();
      var alreadyVisible = dock.classList.contains('is-visible');
      /* Small hysteresis prevents a one-pixel scroll or font swap from
         repeatedly opening and closing the dock at its entry threshold. */
      var threshold = dockTop + (alreadyVisible ? 32 : 24);
      var reachedProducts = gridRect.top <= threshold;
      var insideCatalog = catalogRect.bottom > dockTop + 128;
      var visible = reachedProducts && insideCatalog;
      if (visible) lastGridTop = gridRect.top;
      setVisible(visible);
    }

    function scheduleVisibility() {
      if (frame !== null) return;
      frame = window.requestAnimationFrame(function () {
        frame = null;
        updateVisibility();
      });
    }

    function alignGridTo(viewportTop) {
      var shift = gridHost.getBoundingClientRect().top - viewportTop;
      if (Math.abs(shift) <= 0.5) return;
      try {
        window.scrollBy({ top: shift, left: 0, behavior: 'instant' });
      } catch (error) {
        window.scrollBy(0, shift);
      }
    }

    function handleScroll() {
      if (pendingGridTop !== null) {
        if (dock.classList.contains('is-visible')) pendingGridTop = gridHost.getBoundingClientRect().top;
        else pendingGridTop = null;
      }
      scheduleVisibility();
    }

    function handleResize() {
      if (resizeFrame === null) resizeAnchor = dock.classList.contains('is-visible') ? lastGridTop : null;
      if (resizeFrame !== null) window.cancelAnimationFrame(resizeFrame);
      resizeFrame = window.requestAnimationFrame(function () {
        resizeFrame = null;
        var anchor = resizeAnchor;
        resizeAnchor = null;
        /* Responsive atlas/card rows can reflow above the reader. Preserve the
           same product position through orientation and viewport changes. */
        if (anchor !== null) alignGridTo(anchor);
        updateVisibility();
      });
    }

    select.addEventListener('change', function () {
      var slug = select.value;
      var keepGridPosition = dock.classList.contains('is-visible');
      var previousGridTop = keepGridPosition ? gridHost.getBoundingClientRect().top : 0;
      pendingGridTop = keepGridPosition ? previousGridTop : null;
      syncCategory(slug);
      if (explorer && explorer.getAttribute('data-atlas-ready') === 'true') {
        explorer.dispatchEvent(event('category:select', { slug: slug }));
      } else {
        navigateFallback(slug, '');
      }
      /* Atlas branches above the viewport can have different heights. Their
         activation must not push the products away or dismiss the dock while
         the visitor is using it. Compensate only that synchronous layout shift;
         this is not an animated scroll and the select keeps its focus. */
      if (keepGridPosition) alignGridTo(previousGridTop);
      scheduleVisibility();
    });

    input.addEventListener('input', function () {
      syncClear();
      if (liveSearchReady()) forwardInput();
    });

    form.addEventListener('submit', function (submitEvent) {
      submitEvent.preventDefault();
      if (liveSearchReady()) forwardSubmit();
      else navigateFallback(select.value, input.value.trim());
    });

    clear.addEventListener('click', function () {
      setQuery('');
      if (liveSearchReady()) forwardInput();
      else navigateFallback(select.value, '');
      try {
        input.focus({ preventScroll: true });
      } catch (error) {
        input.focus();
      }
    });

    input.addEventListener('keydown', function (keyEvent) {
      if (keyEvent.key !== 'Escape') return;
      if (input.value !== '') {
        keyEvent.preventDefault();
        setQuery('');
        if (liveSearchReady()) forwardInput();
        else navigateFallback(select.value, '');
      } else {
        input.blur();
      }
    });

    /* Keep both visible inputs identical without attaching live-search to the
       dock. Programmatic events from the dock simply converge to the same value. */
    searchInput.addEventListener('input', function () {
      setQuery(searchInput.value);
    });

    searchForm.addEventListener('livesearch:reset', function (resetEvent) {
      var detail = resetEvent.detail || {};
      setQuery(detail.query || '');
    });

    catalog.addEventListener('catalog:updated', function (updatedEvent) {
      var detail = updatedEvent.detail || {};
      syncCategory(detail.category || '');
      setQuery(searchInput.value);
      if (pendingGridTop !== null) {
        if (dock.classList.contains('is-visible')) alignGridTo(pendingGridTop);
        pendingGridTop = null;
      }
      scheduleVisibility();
    });

    window.addEventListener('scroll', handleScroll, { passive: true });
    window.addEventListener('resize', handleResize);
    window.addEventListener('pageshow', scheduleVisibility);
    window.addEventListener('load', scheduleVisibility);

    syncCategory(select.value);
    syncClear();
    scheduleVisibility();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
