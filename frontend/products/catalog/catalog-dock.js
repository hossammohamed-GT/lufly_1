/* Sticky catalog search bar and progressive category drill-down. */
(function () {
  'use strict';

  function init() {
    var form = document.querySelector('[data-catalog-dock]');
    if (!form || form.__catalogDock) return;

    var catalog = form.closest('.catalog');
    var slot = form.closest('[data-catalog-dock-slot]');
    var input = form.querySelector('[data-livesearch-input]');
    var hiddenCategory = form.querySelector('input[name="category"]');
    var clear = form.querySelector('[data-catalog-search-clear]');
    var picker = form.querySelector('[data-catalog-category-picker]');
    if (!catalog || !slot || !input || !hiddenCategory || !clear || !picker) return;

    var summary = picker.querySelector('[data-catalog-category-summary]');
    var currentName = picker.querySelector('[data-catalog-category-current-name]');
    var currentImage = picker.querySelector('[data-catalog-category-current-image]');
    var currentFallback = picker.querySelector('[data-catalog-category-current-fallback]');
    var status = picker.querySelector('[data-catalog-category-status]');
    var views = Array.prototype.slice.call(picker.querySelectorAll('[data-category-view]'));
    var categoryLinks = Array.prototype.slice.call(picker.querySelectorAll('[data-category-slug]'));
    var explorer = catalog.querySelector('[data-category-explorer]');
    var gridHost = catalog.querySelector('[data-sk-host]');
    if (!summary || !currentName || !currentImage || !currentFallback || !gridHost) return;

    form.__catalogDock = true;
    var frame = null;
    var resizeFrame = null;
    var resizeAnchor = null;
    var lastGridTop = null;
    var pendingGridTop = null;
    var currentCategory = hiddenCategory.value || '';
    var motion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)');

    function event(name, detail) {
      return new CustomEvent(name, { bubbles: true, detail: detail || {} });
    }

    function syncClear() {
      clear.hidden = input.value === '';
    }

    function metadataFor(slug) {
      for (var i = 0; i < categoryLinks.length; i += 1) {
        if (categoryLinks[i].getAttribute('data-category-slug') === slug) return categoryLinks[i];
      }
      return null;
    }

    function syncCategory(slug) {
      currentCategory = String(slug || '');
      var metadata = metadataFor(currentCategory);
      var name = metadata ? metadata.getAttribute('data-category-name') : picker.getAttribute('data-category-all-name') || '';
      var path = metadata ? metadata.getAttribute('data-category-path') : name;
      var image = metadata ? metadata.getAttribute('data-category-image') : '';
      currentName.textContent = name;
      summary.title = path || name;
      if (image) {
        if (currentImage.getAttribute('src') !== image) currentImage.setAttribute('src', image);
        currentImage.hidden = false;
        currentFallback.hidden = true;
      } else {
        currentImage.hidden = true;
        currentImage.removeAttribute('src');
        currentFallback.hidden = false;
      }
      categoryLinks.forEach(function (link) {
        var linkSlug = link.getAttribute('data-category-slug') || '';
        var selected = linkSlug === currentCategory;
        link.classList.toggle('is-active', selected);
        if (link.hasAttribute('data-category-choice') && selected) link.setAttribute('aria-current', 'true');
        else link.removeAttribute('aria-current');
      });
    }

    function viewFor(id) {
      for (var i = 0; i < views.length; i += 1) {
        if (views[i].getAttribute('data-category-view') === id) return views[i];
      }
      return null;
    }

    function focusWithoutScroll(element) {
      if (!element) return;
      try {
        element.focus({ preventScroll: true });
      } catch (error) {
        element.focus();
      }
    }

    function showView(id, animate, moveFocus) {
      var target = viewFor(id);
      if (!target) return;
      views.forEach(function (view) {
        view.classList.remove('is-entering');
        view.hidden = view !== target;
      });
      target.hidden = false;
      if (animate && !(motion && motion.matches)) target.classList.add('is-entering');
      if (status) status.textContent = target.getAttribute('data-category-view-label') || '';
      if (moveFocus) {
        var nextFocus = target.querySelector('[data-category-back]') || target.querySelector('a');
        focusWithoutScroll(nextFocus);
      }
    }

    function closePicker(restoreFocus) {
      if (picker.open) picker.open = false;
      showView('roots', false, false);
      if (restoreFocus) focusWithoutScroll(summary);
    }

    function navbarHeight() {
      var navbar = document.querySelector('[data-navbar]');
      if (navbar) return navbar.getBoundingClientRect().height;
      return window.innerWidth <= 1023 ? 52 : 56;
    }

    function positionDock() {
      if (!form.classList.contains('is-docked')) return;
      var rect = slot.getBoundingClientRect();
      form.style.left = rect.left + 'px';
      form.style.width = rect.width + 'px';
    }

    function setDocked(docked) {
      if (form.classList.contains('is-docked') === docked) return;
      if (docked) {
        var rect = slot.getBoundingClientRect();
        slot.style.height = form.getBoundingClientRect().height + 'px';
        form.style.left = rect.left + 'px';
        form.style.width = rect.width + 'px';
        form.classList.add('is-docked');
        form.classList.remove('is-entering');
        /* One short entrance, only when the existing bar becomes fixed. */
        void form.offsetWidth;
        form.classList.add('is-entering');
      } else {
        form.classList.remove('is-docked', 'is-entering');
        form.style.left = '';
        form.style.width = '';
        slot.style.height = '';
      }
      form.dispatchEvent(event('catalog:dockstate', { docked: docked }));
    }

    function updateDock() {
      if (resizeFrame !== null) return;
      var dockTop = navbarHeight() + 8;
      var slotRect = slot.getBoundingClientRect();
      var catalogRect = catalog.getBoundingClientRect();
      var docked = form.classList.contains('is-docked');
      var threshold = dockTop + (docked ? 4 : 0);
      var shouldDock = slotRect.top <= threshold && catalogRect.bottom > dockTop + 128;
      setDocked(shouldDock);
      if (shouldDock) lastGridTop = gridHost.getBoundingClientRect().top;
    }

    function scheduleDock() {
      if (frame !== null) return;
      frame = window.requestAnimationFrame(function () {
        frame = null;
        updateDock();
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
        if (form.classList.contains('is-docked')) pendingGridTop = gridHost.getBoundingClientRect().top;
        else pendingGridTop = null;
      }
      scheduleDock();
    }

    function handleResize() {
      if (resizeFrame === null) resizeAnchor = form.classList.contains('is-docked') ? lastGridTop : null;
      if (resizeFrame !== null) window.cancelAnimationFrame(resizeFrame);
      resizeFrame = window.requestAnimationFrame(function () {
        resizeFrame = null;
        var anchor = resizeAnchor;
        var wasDocked = form.classList.contains('is-docked');
        resizeAnchor = null;
        if (wasDocked) {
          /* Responsive changes above the toolbar can move its document
             position. Preserve the reading anchor before evaluating the
             sticky threshold, and include the new placeholder height. */
          slot.style.height = form.getBoundingClientRect().height + 'px';
          positionDock();
          if (anchor !== null) alignGridTo(anchor);
        }
        updateDock();
        if (form.classList.contains('is-docked')) {
          slot.style.height = form.getBoundingClientRect().height + 'px';
          positionDock();
          if (anchor !== null) lastGridTop = gridHost.getBoundingClientRect().top;
        }
      });
    }

    function navigateFallback(slug) {
      var url;
      try {
        url = new URL(window.location.href);
      } catch (error) {
        return;
      }
      if (slug) url.searchParams.set('category', slug);
      else url.searchParams.delete('category');
      url.searchParams.delete('q');
      url.searchParams.delete('page');
      catalog.dispatchEvent(event('catalog:navigate', { url: url.href, reveal: false }));
    }

    function selectCategory(slug) {
      if (slug === currentCategory) {
        closePicker(true);
        return;
      }
      var keepGridPosition = form.classList.contains('is-docked');
      var previousGridTop = keepGridPosition ? gridHost.getBoundingClientRect().top : 0;
      pendingGridTop = keepGridPosition ? previousGridTop : null;
      syncCategory(slug);
      closePicker(true);
      if (explorer && explorer.getAttribute('data-atlas-ready') === 'true') {
        explorer.dispatchEvent(event('category:select', { slug: slug }));
      } else {
        navigateFallback(slug);
      }
      if (keepGridPosition) alignGridTo(previousGridTop);
      scheduleDock();
    }

    picker.addEventListener('toggle', function () {
      /* Every new opening starts with the nine roots, never a stale branch. */
      showView('roots', false, false);
    });

    picker.addEventListener('click', function (clickEvent) {
      var back = clickEvent.target.closest('[data-category-back]');
      if (back && picker.contains(back)) {
        clickEvent.preventDefault();
        var currentView = back.closest('[data-category-view]');
        var currentId = currentView ? currentView.getAttribute('data-category-view') : '';
        var parentId = back.getAttribute('data-category-back');
        showView(parentId, true, false);
        var parentView = viewFor(parentId);
        var origin = null;
        if (parentView) {
          origin = Array.prototype.find.call(parentView.querySelectorAll('[data-category-drill]'), function (link) {
            return link.getAttribute('data-category-drill') === currentId;
          }) || null;
        }
        focusWithoutScroll(origin || (parentView && parentView.querySelector('a')) || summary);
        return;
      }
      var drill = clickEvent.target.closest('[data-category-drill]');
      if (drill && picker.contains(drill)) {
        clickEvent.preventDefault();
        showView(drill.getAttribute('data-category-drill'), true, true);
        return;
      }
      var choice = clickEvent.target.closest('[data-category-choice]');
      if (choice && picker.contains(choice)) {
        clickEvent.preventDefault();
        selectCategory(choice.getAttribute('data-category-slug') || '');
      }
    });

    picker.addEventListener('keydown', function (keyEvent) {
      if (keyEvent.key !== 'Escape' || !picker.open) return;
      keyEvent.preventDefault();
      closePicker(true);
    });

    /* The existing All-categories filter remains the reset control. While
       docked, route it through the same anchored selection path as the
       picker instead of letting a second controller move the results. */
    catalog.addEventListener('click', function (clickEvent) {
      if (clickEvent.defaultPrevented || clickEvent.button !== 0 || clickEvent.metaKey || clickEvent.ctrlKey || clickEvent.shiftKey || clickEvent.altKey) return;
      if (!form.classList.contains('is-docked')) return;
      var filter = clickEvent.target.closest ? clickEvent.target.closest('[data-category-filter]') : null;
      if (!filter || !catalog.contains(filter) || (filter.getAttribute('data-category-filter') || '') !== '') return;
      clickEvent.preventDefault();
      clickEvent.stopPropagation();
      selectCategory('');
    }, true);

    document.addEventListener('click', function (clickEvent) {
      if (picker.open && !picker.contains(clickEvent.target)) closePicker(false);
    });

    input.addEventListener('focus', function () {
      picker.setAttribute('inert', '');
      closePicker(false);
    });
    input.addEventListener('blur', function () { picker.removeAttribute('inert'); });
    input.addEventListener('keydown', function (keyEvent) {
      if (keyEvent.key !== 'Tab' || !keyEvent.shiftKey) return;
      /* The collapsed picker is inert; make Shift+Tab an explicit, visible
         route back to categories instead of skipping out of the toolbar. */
      keyEvent.preventDefault();
      input.blur();
      focusWithoutScroll(summary);
    });
    input.addEventListener('input', syncClear);
    clear.addEventListener('pointerdown', function (pointerEvent) {
      /* Keep the search focused while its clear button is used by pointer. */
      pointerEvent.preventDefault();
    });
    clear.addEventListener('click', function () {
      input.value = '';
      input.dispatchEvent(new Event('input', { bubbles: true }));
      focusWithoutScroll(input);
    });

    form.addEventListener('submit', function () { closePicker(false); });
    form.addEventListener('animationend', function (animationEvent) {
      if (animationEvent.animationName === 'catalogDockArrive') form.classList.remove('is-entering');
    });
    form.addEventListener('livesearch:reset', function (resetEvent) {
      var detail = resetEvent.detail || {};
      syncCategory(detail.category || '');
      syncClear();
    });

    catalog.addEventListener('catalog:updated', function (updatedEvent) {
      var detail = updatedEvent.detail || {};
      syncCategory(detail.category || '');
      syncClear();
      if (pendingGridTop !== null) {
        if (form.classList.contains('is-docked')) alignGridTo(pendingGridTop);
        pendingGridTop = null;
      }
      scheduleDock();
    });

    window.addEventListener('scroll', handleScroll, { passive: true });
    window.addEventListener('resize', handleResize);
    window.addEventListener('pageshow', scheduleDock);
    window.addEventListener('load', scheduleDock);

    syncCategory(currentCategory);
    syncClear();
    scheduleDock();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
