/* ============================================================
   Catalog navigation stays on this page. Sorting, category filters,
   pagination and the results cue use the same live renderer; real
   links remain intact as the no-JavaScript fallback.
   ============================================================ */

(function () {
  'use strict';

  function init() {
    var select = document.querySelector('[data-catalog-sort]');
    if (!select || !select.form) return;

    select.addEventListener('change', function () {
      if (select.form.classList.contains('is-live')) {
        select.form.dispatchEvent(new CustomEvent('livesearch:rerender'));
        return;
      }
      var url = new URL(select.form.getAttribute('action') || window.location.href, window.location.href);
      var params = new URLSearchParams(new FormData(select.form));
      ['category', 'q', 'sort'].forEach(function (key) {
        if (!params.get(key)) params.delete(key);
      });
      if (params.get('sort') === 'curated') params.delete('sort');
      params.delete('page');
      url.search = params.toString();
      url.hash = '';
      document.dispatchEvent(new CustomEvent('catalog:navigate', {
        detail: { url: url.href, reveal: false }
      }));
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

/* ============================================================
   Live catalog filtering. The server still renders complete product
   cards, favorite/box states and pagination. Only those regions are
   swapped, so the atlas, focus and scroll position remain intact.
   ============================================================ */

(function () {
  'use strict';

  function init() {
    var catalog = document.querySelector('.catalog');
    if (!catalog) return;
    var grid = catalog.querySelector('[data-catalog-grid]');
    var countEl = catalog.querySelector('.catalog-count');
    var pagination = catalog.querySelector('[data-catalog-pagination]');
    var activeFilter = catalog.querySelector('[data-catalog-active-filter]');
    var form = catalog.querySelector('.catalog-searchform');
    var explorer = catalog.querySelector('[data-category-explorer]');
    var cue = catalog.querySelector('[data-category-results-cue]');
    var resultsHead = catalog.querySelector('#catalog-results');
    if (!grid || !form) return;

    var input = form.querySelector('[data-livesearch-input]');
    var hiddenCategory = form.querySelector('input[name="category"]');
    var sortSelect = form.querySelector('[data-catalog-sort]');
    var allLabel = activeFilter && activeFilter.querySelector('[data-category-name]')
      ? activeFilter.querySelector('[data-category-name]').getAttribute('data-category-name')
      : '';
    var inFlight = null;
    var serial = 0;
    var lastRequest = null;

    function reducedMotion() {
      return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    }

    function revealResults() {
      if (!resultsHead || typeof resultsHead.scrollIntoView !== 'function') return;
      try {
        resultsHead.scrollIntoView({ behavior: reducedMotion() ? 'auto' : 'smooth', block: 'start' });
      } catch (error) {
        resultsHead.scrollIntoView();
      }
    }

    function cueLabel(value) {
      if (!cue) return;
      var label = cue.querySelector('[data-category-results-label]');
      if (label) label.textContent = value;
    }

    function showCue(name) {
      if (!cue) return;
      cue.hidden = false;
      cue.classList.remove('is-error', 'is-ready');
      cue.classList.add('is-loading');
      var nameEl = cue.querySelector('[data-category-results-name]');
      var count = cue.querySelector('[data-category-results-count]');
      if (nameEl) nameEl.textContent = name || allLabel;
      if (count) count.textContent = '…';
      cueLabel(cue.getAttribute('data-label-loading') || 'Updating products…');
    }

    function finishCue() {
      if (!cue) return;
      cue.hidden = false;
      cue.classList.remove('is-loading', 'is-error');
      cue.classList.add('is-ready');
      var count = cue.querySelector('[data-category-results-count]');
      if (count) count.textContent = countEl ? countEl.textContent.trim() : '';
      cueLabel(cue.getAttribute('data-label-ready') || 'Products are below');
    }

    function failCue() {
      if (!cue) return;
      cue.hidden = false;
      cue.classList.remove('is-loading', 'is-ready');
      cue.classList.add('is-error');
      var count = cue.querySelector('[data-category-results-count]');
      if (count) count.textContent = '';
      cueLabel(cue.getAttribute('data-label-error') || 'Unable to update products');
    }

    function setBusy(busy) {
      grid.classList.toggle('is-filtering', busy);
      grid.setAttribute('aria-busy', busy ? 'true' : 'false');
      catalog.classList.toggle('is-catalog-updating', busy);
    }

    function cleanUrl(raw, clearSearch, resetPage) {
      var url = new URL(raw, window.location.href);
      url.hash = '';
      if (clearSearch) url.searchParams.delete('q');
      if (resetPage) url.searchParams.delete('page');
      return url;
    }

    function categoryUrl(slug) {
      var url = cleanUrl(window.location.href, true, true);
      if (slug) url.searchParams.set('category', slug);
      else url.searchParams.delete('category');
      return url;
    }

    function sameAddress(url) {
      return url.pathname + url.search === window.location.pathname + window.location.search;
    }

    function syncAddress(url) {
      if (!window.history || !window.history.replaceState) return;
      try {
        window.history.replaceState({ luflyCatalog: true }, '', url.pathname + url.search);
      } catch (error) { /* old/file browser: filtering still works */ }
    }

    function filterName(trigger) {
      var explicit = trigger.getAttribute('data-category-name');
      if (explicit) return explicit;
      var panel = trigger.closest('[data-category-panel]');
      var heading = panel ? panel.querySelector('[data-category-heading]') : null;
      if (heading) return heading.textContent.trim();
      var rootContext = trigger.closest('.category-root-context');
      var rootName = rootContext ? rootContext.querySelector('.category-root-context-title strong') : null;
      return rootName ? rootName.textContent.trim() : allLabel;
    }

    function applyDocument(doc, url, options) {
      var nextGrid = doc.querySelector('[data-catalog-grid]');
      if (!nextGrid) throw new Error('Catalog results missing');

      grid.innerHTML = nextGrid.innerHTML;
      grid.className = nextGrid.className;
      var nextCount = doc.querySelector('.catalog-count');
      if (countEl && nextCount) countEl.textContent = nextCount.textContent;

      var nextPagination = doc.querySelector('[data-catalog-pagination]');
      if (pagination) {
        pagination.innerHTML = nextPagination ? nextPagination.innerHTML : '';
        pagination.hidden = !nextPagination || nextPagination.hidden;
      }
      var nextFilter = doc.querySelector('[data-catalog-active-filter]');
      if (activeFilter && nextFilter) {
        activeFilter.innerHTML = nextFilter.innerHTML;
        activeFilter.hidden = nextFilter.hidden;
      }

      var category = url.searchParams.get('category') || '';
      if (hiddenCategory) hiddenCategory.value = category;
      var nextForm = doc.querySelector('.catalog-searchform');
      var nextInput = nextForm ? nextForm.querySelector('[data-livesearch-input]') : null;
      var nextSort = nextForm ? nextForm.querySelector('[data-catalog-sort]') : null;
      if (input) input.value = options.clearSearch ? '' : (nextInput ? nextInput.value : input.value);
      if (sortSelect && nextSort) sortSelect.value = nextSort.value;

      Array.prototype.forEach.call(form.querySelectorAll('[data-scope]'), function (button) {
        var isCategory = button.getAttribute('data-scope') === 'category';
        if (isCategory) button.hidden = category === '';
        button.classList.toggle('is-active', isCategory ? category !== '' : category === '');
      });

      syncAddress(url);
      form.dispatchEvent(new CustomEvent('livesearch:reset', {
        detail: { category: category, query: input ? input.value : '' }
      }));
      setBusy(false);
      if (window.LUFLYSkeleton && typeof window.LUFLYSkeleton.reveal === 'function') {
        window.LUFLYSkeleton.reveal(grid.closest('[data-sk-host]'));
      }
      if (options.name !== undefined) finishCue();
      catalog.dispatchEvent(new CustomEvent('catalog:updated', {
        bubbles: true,
        detail: { url: url.href, category: category }
      }));
      if (options.reveal) {
        if (window.requestAnimationFrame) window.requestAnimationFrame(revealResults);
        else revealResults();
      }
    }

    function load(rawUrl, options) {
      options = options || {};
      var url = cleanUrl(rawUrl, Boolean(options.clearSearch), Boolean(options.resetPage));
      if (options.name !== undefined) showCue(options.name);
      lastRequest = { url: url.href, options: options };

      if (sameAddress(url) && !options.force && !catalog.classList.contains('is-catalog-updating')) {
        if (options.name !== undefined) finishCue();
        if (options.reveal) revealResults();
        return;
      }

      if (inFlight && typeof inFlight.abort === 'function') inFlight.abort();
      var requestId = ++serial;
      inFlight = typeof AbortController === 'function' ? new AbortController() : null;
      setBusy(true);

      fetch(url.href, {
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        signal: inFlight ? inFlight.signal : undefined
      }).then(function (response) {
        if (!response.ok) throw new Error('Catalog request failed');
        return response.text();
      }).then(function (html) {
        if (requestId !== serial) return;
        var doc = new DOMParser().parseFromString(html, 'text/html');
        applyDocument(doc, url, options);
      }).catch(function (error) {
        if (requestId !== serial || (error && error.name === 'AbortError')) return;
        setBusy(false);
        failCue();
      });
    }

    if (explorer) {
      explorer.addEventListener('category:activate', function (event) {
        var detail = event.detail || {};
        var slug = String(detail.slug || '');
        load(categoryUrl(slug), {
          clearSearch: true,
          resetPage: true,
          reveal: Boolean(detail.reveal),
          name: detail.name || (slug === '' ? allLabel : '')
        });
      });
    }

    document.addEventListener('catalog:navigate', function (event) {
      var detail = event.detail || {};
      if (detail.url) load(detail.url, { reveal: Boolean(detail.reveal) });
    });

    document.addEventListener('click', function (event) {
      if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
      var target = event.target.closest ? event.target : null;
      if (!target) return;

      var resultsCue = target.closest('[data-category-results-cue]');
      if (resultsCue && catalog.contains(resultsCue)) {
        event.preventDefault();
        if (resultsCue.classList.contains('is-error') && lastRequest) {
          var retryOptions = Object.assign({}, lastRequest.options, { force: true });
          load(lastRequest.url, retryOptions);
        } else {
          revealResults();
        }
        return;
      }

      var filter = target.closest('[data-category-filter]');
      if (filter && catalog.contains(filter)) {
        event.preventDefault();
        var slug = filter.getAttribute('data-category-filter') || '';
        if (slug === '' && explorer) explorer.dispatchEvent(new CustomEvent('category:clear'));
        load(cleanUrl(filter.href, true, true), {
          clearSearch: true,
          resetPage: true,
          reveal: filter.getAttribute('data-category-reveal') === 'true',
          name: filterName(filter)
        });
        return;
      }

      var pageLink = target.closest('[data-catalog-pagination] a[href]');
      if (pageLink && catalog.contains(pageLink)) {
        event.preventDefault();
        load(pageLink.href, { reveal: true });
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

/* ============================================================
   Product card: cycle through every image of the product on
   hover - photos first, then the technical drawings, then the
   installed shots. Each slide names its section (see
   data-pcard-kind on the slide and the label map on the card),
   so a drawing that rotates in is never mistaken for a photo.
   Delegated from the grid so cards injected by live search work too.
   ============================================================ */

(function () {
  'use strict';

  /* hovering advances to the next photo straight away, then each
     following photo is held for HOLD ms before moving on */
  var HOLD = 3000;
  var timers = new WeakMap();

  function slides(media) {
    return media.querySelectorAll('[data-pcard-slide]');
  }

  function show(media, index) {
    var imgs = slides(media);
    /* Dots and the media-kind label live below the clean product image in
       the new glass layout. Fall back to the media itself for old markup. */
    var stage = media.closest ? media.closest('.pcard-stage') : null;
    var scope = stage || media;
    var dots = scope.querySelectorAll('[data-pcard-dot]');
    if (imgs.length === 0) return;

    index = (index + imgs.length) % imgs.length;
    imgs.forEach(function (img, n) { img.classList.toggle('is-on', n === index); });
    dots.forEach(function (dot, n) { dot.classList.toggle('is-on', n === index); });
    media.setAttribute('data-pcard-index', String(index));

    /* the badge only speaks up for drawings / installed shots: the label
       map lives on the card ("data-pcard-kind-drawing" and friends) */
    var badge = scope.querySelector('[data-pcard-kind-label]');
    if (badge) {
      var kind = imgs[index].getAttribute('data-pcard-kind') || '';
      var label = kind && kind !== 'photo' ? (media.getAttribute('data-pcard-kind-' + kind) || '') : '';
      badge.textContent = label;
      badge.hidden = label === '';
    }
  }

  function advance(media) {
    show(media, parseInt(media.getAttribute('data-pcard-index') || '0', 10) + 1);
  }

  function start(media) {
    if (timers.has(media)) return;
    if (slides(media).length < 2) return;
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    /* immediate first step, so the hover feels responsive */
    advance(media);

    var timer = setInterval(function () { advance(media); }, HOLD);
    timers.set(media, timer);
  }

  function stop(media) {
    var timer = timers.get(media);
    if (timer) {
      clearInterval(timer);
      timers.delete(media);
    }
    show(media, 0);
  }

  function init() {
    document.addEventListener('pointerenter', function (e) {
      var media = e.target.closest ? e.target.closest('[data-pcard-cycle]') : null;
      if (media) start(media);
    }, true);

    document.addEventListener('pointerleave', function (e) {
      var media = e.target.closest ? e.target.closest('[data-pcard-cycle]') : null;
      if (media) stop(media);
    }, true);

    /* keyboard users get the same preview when the card link is focused */
    document.addEventListener('focusin', function (e) {
      var media = e.target.closest ? e.target.closest('[data-pcard-cycle]') : null;
      if (media) start(media);
    });

    document.addEventListener('focusout', function (e) {
      var media = e.target.closest ? e.target.closest('[data-pcard-cycle]') : null;
      if (media) stop(media);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
