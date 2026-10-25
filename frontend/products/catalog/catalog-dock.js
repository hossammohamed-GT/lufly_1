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
    var navbar = document.querySelector('[data-navbar]');
    var navbarSlot = navbar ? navbar.querySelector('[data-catalog-navbar-slot]') : null;
    var navToggle = navbar ? navbar.querySelector('[data-catalog-nav-toggle]') : null;
    if (!catalog || !slot || !input || !hiddenCategory || !clear || !picker || !navbar || !navbarSlot || !navToggle) return;

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
    var navRegions = Array.prototype.slice.call(navbar.querySelectorAll('.mnav-links, .mnav-search, .mnav-actions'));

    form.__catalogDock = true;
    navbar.classList.add('is-catalog-ready');
    var frame = null;
    var resizeFrame = null;
    var resizeAnchor = null;
    var lastGridTop = null;
    var pendingGridTop = null;
    var currentCategory = hiddenCategory.value || '';
    var navigationPreview = false;
    var navHideTimer = null;
    var orbitTimer = null;
    var motion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)');

    function event(name, detail) {
      return new CustomEvent(name, { bubbles: true, detail: detail || {} });
    }

    function syncClear() {
      clear.hidden = input.value === '';
    }

    function metadataFor(slug) {
      if (!slug) return null;
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

    function initialViewIdFor(slug) {
      if (!slug) return 'roots';
      if (viewFor(slug)) return slug;
      var link = metadataFor(slug);
      var parentView = link && link.closest ? link.closest('[data-category-view]') : null;
      return parentView ? (parentView.getAttribute('data-category-view') || 'roots') : 'roots';
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
      return navbar.getBoundingClientRect().height || (window.innerWidth <= 1023 ? 52 : 56);
    }

    function syncToggle() {
      var visible = navigationPreview || form.classList.contains('is-docked');
      var label = navigationPreview ? navToggle.getAttribute('data-label-filters') : navToggle.getAttribute('data-label-navigation');
      navToggle.hidden = !visible;
      navToggle.setAttribute('aria-expanded', navigationPreview ? 'true' : 'false');
      navToggle.setAttribute('aria-label', label || '');
      navToggle.title = label || '';
    }

    function aimNavbarRegions() {
      var brand = navbar.querySelector('.mnav-brand');
      if (!brand) return;
      var brandRect = brand.getBoundingClientRect();
      var rtl = window.getComputedStyle(navbar).direction === 'rtl';
      navRegions.forEach(function (region) {
        var rect = region.getBoundingClientRect();
        /* Keep auto margins and flex layout intact while the visible region
           travels to the brand. A signed distance works in both directions. */
        var distance = rtl ? brandRect.right - rect.right : brandRect.left - rect.left;
        region.style.setProperty('--mnav-catalog-pull', distance.toFixed(2) + 'px');
      });
    }

    function animateNavbar() {
      window.clearTimeout(orbitTimer);
      if (motion && motion.matches) {
        navbar.classList.remove('is-catalog-switching');
        return;
      }
      navbar.classList.remove('is-catalog-switching');
      void navbar.offsetWidth;
      navbar.classList.add('is-catalog-switching');
      /* Keep the edge runner alive through the 1.5s withdrawal and the
         catalog entrance. Restoring navigation uses the shorter return. */
      var duration = form.classList.contains('is-docked') && !navigationPreview ? 2300 : 1600;
      orbitTimer = window.setTimeout(function () { navbar.classList.remove('is-catalog-switching'); }, duration);
    }

    function setDocked(docked) {
      if (form.classList.contains('is-docked') === docked) return;
      var focused = form.contains(document.activeElement) ? document.activeElement : null;
      var navHadFocus = navRegions.some(function (region) { return region.contains(document.activeElement); });
      window.clearTimeout(navHideTimer);

      if (docked) {
        navigationPreview = false;
        slot.style.height = 'var(--catalog-placeholder-height)';
        navToggle.hidden = false;
        if (navHadFocus) focusWithoutScroll(navToggle);
        aimNavbarRegions();
        navRegions.forEach(function (region) {
          region.hidden = false;
          region.setAttribute('inert', '');
        });
        form.classList.add('is-docked');
        navbarSlot.appendChild(form);
        /* Establish the collapsed slot before it takes over the row. */
        void navbarSlot.offsetWidth;
        navbar.classList.remove('is-catalog-preview');
        navbar.classList.add('is-catalog-docked', 'is-catalog-preparing');
        form.classList.remove('is-entering');
        navHideTimer = window.setTimeout(function () {
          if (!form.classList.contains('is-docked')) return;
          navRegions.forEach(function (region) { region.hidden = true; });
          navbar.classList.remove('is-catalog-preparing');
          /* The search enters only after the navigation has finished its
             visible 1.5s withdrawal, never on top of the links. */
          if (motion && motion.matches) form.classList.remove('is-entering');
          else {
            void form.offsetWidth;
            form.classList.add('is-entering');
          }
        }, motion && motion.matches ? 0 : 1500);
      } else {
        navRegions.forEach(function (region) { region.hidden = false; });
        /* If they were fully hidden, restart from the collapsed style before
           the normal navigation expands back out of the brand. */
        void navbar.offsetWidth;
        form.classList.remove('is-docked', 'is-entering');
        slot.appendChild(form);
        slot.style.height = '';
        navbar.classList.remove('is-catalog-docked', 'is-catalog-preparing');
        navRegions.forEach(function (region) { region.removeAttribute('inert'); });
      }
      if (focused) focusWithoutScroll(focused);
      syncToggle();
      animateNavbar();
      form.dispatchEvent(event('catalog:dockstate', { docked: docked }));
      navbar.dispatchEvent(event('catalog:navbardock', { docked: docked }));
    }

    function setNavigationPreview(preview) {
      if (navigationPreview === preview) return;
      navigationPreview = preview;
      navbar.classList.toggle('is-catalog-preview', preview);
      setDocked(!preview);
      syncToggle();
    }

    function updateDock() {
      if (resizeFrame !== null) return;
      var dockTop = navbarHeight() + 8;
      var slotRect = slot.getBoundingClientRect();
      var catalogRect = catalog.getBoundingClientRect();
      var docked = form.classList.contains('is-docked');
      var threshold = dockTop + (docked ? 4 : 0);
      var withinCatalog = slotRect.top <= threshold && catalogRect.bottom > dockTop + 128;
      if (navigationPreview && (slotRect.top > dockTop + 24 || catalogRect.bottom <= dockTop + 128)) {
        navigationPreview = false;
        navbar.classList.remove('is-catalog-preview');
      }
      var shouldDock = withinCatalog && !navigationPreview;
      setDocked(shouldDock);
      syncToggle();
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
        resizeAnchor = null;
        /* The placeholder uses the same responsive custom property as the
           original form. Compensate layout shifts before testing the dock
           threshold, but never measure the compact navbar form as its height. */
        if (anchor !== null && form.classList.contains('is-docked')) alignGridTo(anchor);
        updateDock();
        if (form.classList.contains('is-docked') && anchor !== null) {
          lastGridTop = gridHost.getBoundingClientRect().top;
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

    function selectCategory(slug, keepOpen) {
      slug = String(slug || '');
      if (slug === currentCategory) {
        if (!keepOpen) closePicker(true);
        return;
      }
      var keepGridPosition = form.classList.contains('is-docked');
      var previousGridTop = keepGridPosition ? gridHost.getBoundingClientRect().top : 0;
      pendingGridTop = keepGridPosition ? previousGridTop : null;
      syncCategory(slug);
      if (!keepOpen) {
        closePicker(true);
      }
      if (explorer && explorer.getAttribute('data-atlas-ready') === 'true') {
        explorer.dispatchEvent(event('category:select', { slug: slug }));
      } else {
        navigateFallback(slug);
      }
      if (keepGridPosition) alignGridTo(previousGridTop);
      scheduleDock();
    }

    picker.addEventListener('toggle', function () {
      if (picker.open) {
        showView(initialViewIdFor(currentCategory), false, false);
      } else {
        showView('roots', false, false);
      }
    });

    picker.addEventListener('click', function (clickEvent) {
      var back = clickEvent.target.closest('[data-category-back]');
      if (back && picker.contains(back)) {
        clickEvent.preventDefault();
        var currentView = back.closest('[data-category-view]');
        var currentId = currentView ? currentView.getAttribute('data-category-view') : '';
        var parentId = back.getAttribute('data-category-back') || 'roots';
        selectCategory(parentId === 'roots' ? '' : parentId, true);
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
        var drillSlug = drill.getAttribute('data-category-drill') || '';
        selectCategory(drillSlug, true);
        showView(drillSlug, true, true);
        return;
      }
      var choice = clickEvent.target.closest('[data-category-choice]');
      if (choice && picker.contains(choice)) {
        clickEvent.preventDefault();
        selectCategory(choice.getAttribute('data-category-slug') || '', true);
      }
    });

    picker.addEventListener('keydown', function (keyEvent) {
      if (keyEvent.key !== 'Escape' || !picker.open) return;
      keyEvent.preventDefault();
      closePicker(true);
    });

    navToggle.addEventListener('click', function () {
      if (navigationPreview) setNavigationPreview(false);
      else if (form.classList.contains('is-docked')) setNavigationPreview(true);
    });
    navbar.addEventListener('catalog:navigation-request', function () {
      if (form.classList.contains('is-docked')) setNavigationPreview(true);
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
    syncToggle();
    scheduleDock();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
