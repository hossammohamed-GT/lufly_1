/* Progressive enhancement for the LUFLY category atlas. */
(function () {
  'use strict';

  function each(list, callback) {
    Array.prototype.forEach.call(list, callback);
  }

  function initExplorer(explorer) {
    if (explorer.getAttribute('data-atlas-ready') === 'true') {
      return;
    }
    explorer.setAttribute('data-atlas-ready', 'true');

    var panels = Array.prototype.slice.call(explorer.querySelectorAll('[data-category-panel]'));
    var panelGroups = Array.prototype.slice.call(explorer.querySelectorAll('[data-category-panels-root]'));
    var roots = Array.prototype.slice.call(explorer.querySelectorAll('[data-category-root]'));
    var rootList = explorer.querySelector('.category-root-list');
    var initialNodes = rootList ? Array.prototype.slice.call(rootList.children) : [];
    var status = explorer.querySelector('[data-category-status]');
    var isCatalogMode = explorer.classList.contains('category-explorer--catalog');
    var motion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)');
    var easing = 'cubic-bezier(0.22, 1, 0.36, 1)';
    var exitClones = [];

    function syncLeafCards(activeLeafSlug) {
      var cards = explorer.querySelectorAll('.category-child');
      each(cards, function (card) {
        var cardSlug = card.getAttribute('data-category-select') || '';
        var isLeaf = card.getAttribute('data-category-leaf') === 'true';
        var active = Boolean(isCatalogMode && isLeaf && activeLeafSlug && cardSlug === activeLeafSlug);
        card.classList.toggle('is-active', active);
        if (active) {
          card.setAttribute('aria-current', 'location');
        } else if (isLeaf) {
          card.removeAttribute('aria-current');
        }
      });
    }

    function parentSlugForPanel(panel) {
      if (!panel) {
        return '';
      }
      var crumbs = panel.querySelectorAll('.category-breadcrumb [data-category-select]');
      if (crumbs.length > 0) {
        return crumbs[crumbs.length - 1].getAttribute('data-category-select') || '';
      }
      return panel.getAttribute('data-root') || '';
    }

    function panelFor(slug) {
      for (var i = 0; i < panels.length; i += 1) {
        if (panels[i].getAttribute('data-category-panel') === slug) {
          return panels[i];
        }
      }
      return null;
    }

    function canAnimate(element) {
      return Boolean(element) && !(motion && motion.matches) && !document.hidden && typeof element.animate === 'function';
    }

    function releaseHeight(element) {
      element.style.overflow = '';
    }

    function animateHeight(element, fromHeight, duration) {
      if (!canAnimate(element) || fromHeight === null || typeof fromHeight === 'undefined') {
        return;
      }
      var targetHeight = element.scrollHeight;
      if (Math.abs(targetHeight - fromHeight) < 2) {
        return;
      }
      element.style.overflow = 'hidden';
      var animation = element.animate([
        { height: fromHeight + 'px' },
        { height: targetHeight + 'px' }
      ], { duration: duration, easing: easing });
      animation.onfinish = function () { releaseHeight(element); };
      animation.oncancel = function () { releaseHeight(element); };
    }

    function cancelRootLayoutAnimations() {
      each(roots, function (rootLink) {
        if (typeof rootLink.getAnimations !== 'function') {
          return;
        }
        each(rootLink.getAnimations(), function (animation) {
          if (animation.id === 'category-root-layout') {
            animation.cancel();
          }
        });
      });
    }

    function captureRootLayout() {
      return roots.map(function (rootLink) {
        var rect = rootLink.getBoundingClientRect();
        return { root: rootLink, left: rect.left, top: rect.top, width: rect.width, height: rect.height };
      });
    }

    function animateRootLayout(frames, suppressAnimation) {
      if (suppressAnimation) {
        return;
      }
      each(frames, function (frame) {
        var rootLink = frame.root;
        if (!canAnimate(rootLink)) {
          return;
        }
        var rect = rootLink.getBoundingClientRect();
        var deltaX = frame.left - rect.left;
        var deltaY = frame.top - rect.top;
        var scaleX = rect.width > 0 ? frame.width / rect.width : 1;
        var scaleY = rect.height > 0 ? frame.height / rect.height : 1;
        if (Math.abs(deltaX) < 0.5 && Math.abs(deltaY) < 0.5 && Math.abs(scaleX - 1) < 0.01 && Math.abs(scaleY - 1) < 0.01) {
          return;
        }
        var animation = rootLink.animate([
          { transformOrigin: '0 0', transform: 'translate(' + deltaX + 'px,' + deltaY + 'px) scale(' + scaleX + ',' + scaleY + ')' },
          { transformOrigin: '0 0', transform: 'translate(0,0) scale(1,1)' }
        ], { duration: 560, easing: easing });
        animation.id = 'category-root-layout';
      });
    }

    function placePanelGroup(rootLink) {
      if (!rootLink || !rootList) {
        return;
      }
      var rootSlug = rootLink.getAttribute('data-category-root');
      var group = null;
      for (var i = 0; i < panelGroups.length; i += 1) {
        if (panelGroups[i].getAttribute('data-category-panels-root') === rootSlug) {
          group = panelGroups[i];
          break;
        }
      }
      if (!group) {
        return;
      }

      /* Move the lead in the DOM as well as visually, so Tab order follows the
         mosaic. Desktop panels sit below the two-row mosaic; compact mobile
         panels remain directly below the selected card's first row. */
      var restoreRootFocus = rootLink === document.activeElement || rootLink.contains(document.activeElement);
      if (rootList.firstElementChild !== rootLink) {
        rootList.insertBefore(rootLink, rootList.firstElementChild);
      }
      var orderedRoots = Array.prototype.slice.call(rootList.children).filter(function (item) {
        return item.classList && item.classList.contains('category-root');
      });
      var template = window.getComputedStyle(rootList).gridTemplateColumns;
      var columns = template && template !== 'none'
        ? template.split(/\s+/).filter(Boolean).length
        : orderedRoots.length;
      columns = Math.max(1, Math.min(columns, orderedRoots.length));
      var anchor = columns <= 3
        ? orderedRoots[Math.min(columns, orderedRoots.length) - 1]
        : orderedRoots[orderedRoots.length - 1];
      var nextNode = anchor ? anchor.nextSibling : null;
      if (nextNode !== group) {
        rootList.insertBefore(group, nextNode);
      }
      if (restoreRootFocus && document.activeElement !== rootLink) {
        try {
          rootLink.focus({ preventScroll: true });
        } catch (error) {
          rootLink.focus();
        }
      }
    }

    function updateSelection(slug, target, moveFocus) {
      explorer.setAttribute('data-selected', slug);
      var heading = target.querySelector('[data-category-heading]');
      if (status && heading) {
        status.textContent = heading.textContent;
      }
      if (moveFocus && heading) {
        try {
          heading.focus({ preventScroll: true });
        } catch (error) {
          heading.focus();
        }
      }
    }

    function animateCards(panel, delayStart) {
      var cards = panel.querySelectorAll('.category-child:not([hidden])');
      each(cards, function (card, index) {
        if (!canAnimate(card)) {
          return;
        }
        card.animate([
          { opacity: 0, transform: 'translateY(20px) scale(0.98)' },
          { opacity: 1, transform: 'translateY(0) scale(1)' }
        ], {
          duration: 480,
          delay: delayStart + Math.min(index * 40, 220),
          easing: easing,
          fill: 'backwards'
        });
      });
    }

    function animatePanel(panel, expand, rootLink, previousHeight) {
      if (!canAnimate(panel)) {
        return;
      }
      var group = panel.closest('[data-category-panels-root]');
      if (rootLink) {
        var rootRect = rootLink.getBoundingClientRect();
        var panelRect = panel.getBoundingClientRect();
        panel.style.setProperty('--category-origin-x', (rootRect.left + (rootRect.width / 2) - panelRect.left) + 'px');
      }
      if (group) {
        animateHeight(group, expand ? 0 : previousHeight, 560);
      }
      panel.animate([
        { opacity: 0, transform: 'translateY(-16px) scale(0.98)', filter: 'blur(3px)' },
        { opacity: 1, transform: 'translateY(0) scale(1)', filter: 'blur(0)' }
      ], { duration: 520, easing: easing });
      animateCards(panel, 60);
    }

    function restoreNestedPanels() {
      clearExitClones();
      var nested = Array.prototype.slice.call(explorer.querySelectorAll('.category-panel.is-nested'));
      each(nested, function (panel) {
        if (typeof panel.getAnimations === 'function') {
          each(panel.getAnimations(), function (animation) { animation.cancel(); });
        }
        releaseHeight(panel);
        var rootSlug = panel.getAttribute('data-root');
        for (var i = 0; i < panelGroups.length; i += 1) {
          if (panelGroups[i].getAttribute('data-category-panels-root') === rootSlug) {
            panelGroups[i].appendChild(panel);
            break;
          }
        }
        panel.classList.remove('is-nested');
        panel.hidden = true;
      });
      each(panels, function (panel) { panel.classList.remove('has-nested-panel'); });
      var cards = explorer.querySelectorAll('.category-child');
      each(cards, function (card) {
        if (typeof card.getAnimations === 'function') {
          each(card.getAnimations(), function (animation) { animation.cancel(); });
        }
        card.hidden = false;
        card.classList.remove('is-expanded', 'is-active');
        if (card.getAttribute('data-category-leaf') === 'true') {
          card.removeAttribute('aria-current');
        }
        if (card.hasAttribute('aria-expanded')) {
          card.setAttribute('aria-expanded', 'false');
        }
      });
    }

    /* Base activation handles roots and direct leaf URLs. A level-two branch
       uses the nested activation below instead of replacing the whole panel. */
    function activateBase(slug, moveFocus, suppressAnimation, revealChildren) {
      var target = panelFor(slug);
      if (!target) {
        return null;
      }
      var rootFrames = captureRootLayout();
      cancelRootLayoutAnimations();
      var current = panelFor(explorer.getAttribute('data-selected') || '');
      var previousRoot = current ? current.getAttribute('data-root') || '' : '';
      var currentGroup = current ? current.closest('[data-category-panels-root]') : null;
      var previousHeight = currentGroup && !currentGroup.hidden ? currentGroup.getBoundingClientRect().height : 0;
      var changed = explorer.getAttribute('data-selected') !== slug;
      var rootSlug = target.getAttribute('data-root') || '';
      var rootChanged = previousRoot !== rootSlug;

      restoreNestedPanels();
      each(panelGroups, function (group) {
        if (typeof group.getAnimations === 'function') {
          each(group.getAnimations(), function (animation) { animation.cancel(); });
        }
        releaseHeight(group);
        group.hidden = group.getAttribute('data-category-panels-root') !== rootSlug;
      });
      each(panels, function (panel) { panel.hidden = panel !== target; });

      var activeRoot = null;
      each(roots, function (rootLink) {
        var active = rootLink.getAttribute('data-category-root') === rootSlug;
        rootLink.classList.toggle('is-active', active);
        rootLink.classList.toggle('is-lead', active);
        rootLink.setAttribute('aria-expanded', active ? 'true' : 'false');
        if (active) {
          activeRoot = rootLink;
          rootLink.setAttribute('aria-current', 'location');
        } else {
          rootLink.removeAttribute('aria-current');
        }
      });
      placePanelGroup(activeRoot);
      updateSelection(slug, target, moveFocus);
      animateRootLayout(rootFrames, suppressAnimation);
      /* Scroll before height/overflow animations begin, so scrollIntoView
         always moves the page rather than a temporarily clipped panel. */
      if (revealChildren) {
        revealChildCards(target);
      }
      if (changed && !suppressAnimation) {
        animatePanel(target, rootChanged, activeRoot, previousHeight);
      }
      return { target: target, activeRoot: activeRoot };
    }

    function childTriggerFor(rootPanel, slug) {
      var triggers = rootPanel.querySelectorAll('.category-child[data-category-select]');
      for (var i = 0; i < triggers.length; i += 1) {
        if (triggers[i].getAttribute('data-category-select') === slug) {
          return triggers[i];
        }
      }
      return null;
    }

    function animateNestedPanel(panel, originRect) {
      if (!canAnimate(panel)) {
        return;
      }
      var panelRect = panel.getBoundingClientRect();
      panel.style.setProperty('--category-origin-x', (originRect.left + (originRect.width / 2) - panelRect.left) + 'px');
      animateHeight(panel, 0, 540);
      panel.animate([
        { opacity: 0, transform: 'translateY(-14px) scale(0.985)', filter: 'blur(3px)' },
        { opacity: 1, transform: 'translateY(0) scale(1)', filter: 'blur(0)' }
      ], { duration: 500, easing: easing });
      animateCards(panel, 80);
    }

    function discardExitClone(clone) {
      var index = exitClones.indexOf(clone);
      if (index !== -1) {
        exitClones.splice(index, 1);
      }
      if (clone.parentNode) {
        clone.parentNode.removeChild(clone);
      }
    }

    function clearExitClones() {
      var clones = exitClones.slice();
      exitClones.length = 0;
      each(clones, function (clone) {
        if (typeof clone.getAnimations === 'function') {
          each(clone.getAnimations(), function (animation) { animation.cancel(); });
        }
        if (clone.parentNode) {
          clone.parentNode.removeChild(clone);
        }
      });
    }

    function animateSiblingExit(sibling, suppressAnimation) {
      var rect = sibling.getBoundingClientRect();
      if (suppressAnimation || !canAnimate(sibling)) {
        sibling.hidden = true;
        return;
      }

      /* A fixed, inert clone carries the exit motion while the real grid
         immediately reaches its interactive nested state. No timer controls
         navigation, focus, visibility, or which category is selected. */
      var opacity = window.getComputedStyle(sibling).opacity;
      var clone = sibling.cloneNode(true);
      clone.classList.add('category-exit-clone');
      clone.setAttribute('aria-hidden', 'true');
      clone.setAttribute('tabindex', '-1');
      clone.removeAttribute('id');
      clone.removeAttribute('href');
      clone.removeAttribute('data-category-select');
      each(clone.querySelectorAll('[id]'), function (node) { node.removeAttribute('id'); });
      clone.style.position = 'fixed';
      clone.style.left = rect.left + 'px';
      clone.style.top = rect.top + 'px';
      clone.style.width = rect.width + 'px';
      clone.style.height = rect.height + 'px';
      clone.style.margin = '0';
      clone.style.zIndex = '15';
      clone.style.pointerEvents = 'none';
      clone.style.transformOrigin = 'center center';

      if (typeof sibling.getAnimations === 'function') {
        each(sibling.getAnimations(), function (animation) { animation.cancel(); });
      }
      sibling.hidden = true;
      document.body.appendChild(clone);
      exitClones.push(clone);

      var animation = clone.animate([
        { opacity: opacity, transform: 'translateY(0) scale(1)' },
        { opacity: 0, transform: 'translateY(-9px) scale(0.98)' }
      ], { duration: 180, easing: 'ease-in', fill: 'forwards' });
      animation.onfinish = function () { discardExitClone(clone); };
      animation.oncancel = function () { discardExitClone(clone); };
      window.setTimeout(function () { discardExitClone(clone); }, 500);
    }

    function activateNested(target, suppliedTrigger, moveFocus, suppressAnimation, revealChildren) {
      var slug = target.getAttribute('data-category-panel');
      var rootSlug = target.getAttribute('data-root') || '';
      var base = activateBase(rootSlug, false, true);
      if (!base) {
        return;
      }
      var rootPanel = base.target;
      var trigger = suppliedTrigger && rootPanel.contains(suppliedTrigger)
        ? suppliedTrigger
        : childTriggerFor(rootPanel, slug);
      if (!trigger) {
        activateBase(slug, moveFocus, suppressAnimation);
        return;
      }
      var grid = trigger.closest('.category-child-grid');
      var oldRect = trigger.getBoundingClientRect();
      var siblings = Array.prototype.slice.call(grid.children).filter(function (item) {
        return item.classList && item.classList.contains('category-child') && item !== trigger && !item.hidden;
      });

      each(siblings, function (sibling) { animateSiblingExit(sibling, suppressAnimation); });
      trigger.classList.add('is-expanded');
      trigger.setAttribute('aria-expanded', 'true');

      /* The expanded hero is the single level-two card. The compact trigger
         returns only when this branch collapses, so the two never duplicate
         the same category or compete for vertical space. */
      trigger.hidden = true;
      target.classList.add('is-nested');
      target.hidden = false;
      rootPanel.classList.add('has-nested-panel');
      grid.insertBefore(target, trigger.nextSibling);
      updateSelection(slug, target, moveFocus);
      if (revealChildren) {
        revealChildCards(target);
      }

      if (!suppressAnimation) {
        animateNestedPanel(target, oldRect);
      }
    }

    /* Root and branch cards reveal their next level in place. Bring that
       level's heading and first cards into view so the large selected hero
       does not force the visitor to hunt for the newly revealed choices.
       Product CTAs, breadcrumbs and collapse controls keep their own behavior. */
    function revealChildCards(target) {
      if (!target) {
        return;
      }
      var heading = target.querySelector('.category-children-head');
      if (!heading || typeof heading.scrollIntoView !== 'function') {
        return;
      }
      try {
        heading.scrollIntoView({
          behavior: motion && motion.matches ? 'auto' : 'smooth',
          block: 'start',
          inline: 'nearest'
        });
      } catch (error) {
        heading.scrollIntoView();
      }
    }

    function notifySelection(slug, trigger) {
      var target = panelFor(slug);
      var heading = target ? target.querySelector('[data-category-heading]') : null;
      explorer.dispatchEvent(new CustomEvent('category:activate', {
        bubbles: true,
        detail: {
          slug: slug,
          name: heading ? heading.textContent.trim() : '',
          reveal: Boolean(trigger && trigger.getAttribute('data-category-reveal') === 'true')
        }
      }));
    }

    function clearSelection(suppressAnimation, moveFocus) {
      var rootFrames = captureRootLayout();
      cancelRootLayoutAnimations();
      restoreNestedPanels();
      each(panelGroups, function (group) {
        if (typeof group.getAnimations === 'function') {
          each(group.getAnimations(), function (animation) { animation.cancel(); });
        }
        releaseHeight(group);
        group.hidden = true;
      });
      each(panels, function (panel) { panel.hidden = true; });
      each(roots, function (rootLink, index) {
        rootLink.classList.remove('is-active');
        rootLink.classList.toggle('is-lead', index === 0);
        rootLink.setAttribute('aria-expanded', 'false');
        rootLink.removeAttribute('aria-current');
      });
      if (rootList) {
        each(initialNodes, function (node) { rootList.appendChild(node); });
      }
      explorer.setAttribute('data-selected', '');
      if (status) {
        status.textContent = '';
      }
      if (moveFocus && roots[0]) {
        try {
          roots[0].focus({ preventScroll: true });
        } catch (error) {
          roots[0].focus();
        }
      }
      animateRootLayout(rootFrames, suppressAnimation);
    }

    function activate(slug, moveFocus, suppressAnimation, trigger, allowToggle) {
      allowToggle = allowToggle !== false;
      var target = panelFor(slug);
      if (!target) {
        return false;
      }
      var depth = parseInt(target.getAttribute('data-depth') || '1', 10);
      var hasChildren = Boolean(target.querySelector('.category-child'));
      var revealChildren = Boolean(trigger && (
        trigger.classList.contains('category-root') || trigger.classList.contains('category-child')
      ));

      /* In catalog mode, selecting a non-root leaf keeps its parent subcategory
         grid open so sibling choices remain immediately clickable while the
         products grid updates below. */
      if (isCatalogMode && depth >= 2 && !hasChildren) {
        var parentSlug = parentSlugForPanel(target);
        if (allowToggle && explorer.getAttribute('data-selected') === slug && parentSlug !== '') {
          return activate(parentSlug, false, suppressAnimation, null, false);
        }
        var parentPanel = panelFor(parentSlug);
        if (parentPanel) {
          var parentDepth = parseInt(parentPanel.getAttribute('data-depth') || '1', 10);
          if (parentDepth === 2) {
            if (!parentPanel.classList.contains('is-nested') || parentPanel.hidden) {
              activateNested(parentPanel, null, false, suppressAnimation, false);
            }
          } else if (parentPanel.hidden || parentPanel.classList.contains('has-nested-panel')) {
            activateBase(parentSlug, false, suppressAnimation, false);
          }
        }
        explorer.setAttribute('data-selected', slug);
        syncLeafCards(slug);
        var leafHeading = target.querySelector('[data-category-heading]');
        if (status && leafHeading) {
          status.textContent = leafHeading.textContent.trim();
        }
        return true;
      }

      if (depth === 2) {
        /* Clicking an open branch's collapse control returns to its parent root. */
        if (allowToggle && explorer.getAttribute('data-selected') === slug && target.classList.contains('is-nested')) {
          syncLeafCards('');
          activateBase(target.getAttribute('data-root') || '', moveFocus, suppressAnimation);
          return true;
        }
        if (target.classList.contains('is-nested') && !target.hidden) {
          syncLeafCards('');
          updateSelection(slug, target, moveFocus);
          return true;
        }
        syncLeafCards('');
        activateNested(target, trigger, moveFocus, suppressAnimation, revealChildren);
        return true;
      }

      if (allowToggle && depth === 1 && explorer.getAttribute('data-selected') === slug && !target.hidden && !target.classList.contains('has-nested-panel')) {
        clearSelection(suppressAnimation, false);
        return true;
      }

      syncLeafCards('');
      activateBase(slug, moveFocus, suppressAnimation, revealChildren);
      return true;
    }

    explorer.addEventListener('click', function (event) {
      if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
        return;
      }
      var clearTrigger = event.target.closest('[data-category-clear]');
      if (clearTrigger && explorer.contains(clearTrigger)) {
        event.preventDefault();
        clearSelection(false, Boolean(clearTrigger.closest('.category-panel')));
        notifySelection('');
        return;
      }
      var trigger = event.target.closest('[data-category-select]');
      if (!trigger || !explorer.contains(trigger)) {
        return;
      }
      var slug = trigger.getAttribute('data-category-select');
      if (!panelFor(slug)) {
        return; // Home leaf categories keep their real filtered-products link.
      }
      event.preventDefault();
      if (activate(slug, Boolean(trigger.closest('.category-panel')), false, trigger, true)) {
        notifySelection(explorer.getAttribute('data-selected') || '', trigger);
      }
    });

    /* External compact controls (the scrolled catalog dock) reuse the very
       same activation path as an atlas click. Suppress off-screen motion and
       never reveal the atlas: the visitor is already reading the products. */
    explorer.addEventListener('category:select', function (event) {
      var detail = event.detail || {};
      var slug = String(detail.slug || '');
      if (slug === '') {
        clearSelection(true, false);
        notifySelection('');
        return;
      }
      if (activate(slug, false, true, null, false)) {
        notifySelection(explorer.getAttribute('data-selected') || slug, null);
      }
    });

    explorer.addEventListener('category:clear', function () {
      clearSelection(false, false);
    });

    var resizeTimer = null;
    window.addEventListener('resize', function () {
      window.clearTimeout(resizeTimer);
      resizeTimer = window.setTimeout(function () {
        var current = panelFor(explorer.getAttribute('data-selected') || '');
        var currentRoot = current ? current.getAttribute('data-root') : '';
        for (var i = 0; i < roots.length; i += 1) {
          if (roots[i].getAttribute('data-category-root') === currentRoot) {
            placePanelGroup(roots[i]);
            break;
          }
        }
      }, 100);
    });

    /* Direct level-two and leaf URLs receive the same open layout, without an
       entrance animation on initial render. An empty selection stays empty. */
    activate(explorer.getAttribute('data-selected') || '', false, true, null, false);
  }

  function init() {
    each(document.querySelectorAll('[data-category-explorer]'), initExplorer);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
