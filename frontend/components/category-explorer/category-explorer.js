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
    var status = explorer.querySelector('[data-category-status]');
    var motion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)');
    var easing = 'cubic-bezier(0.22, 1, 0.36, 1)';

    function panelFor(slug) {
      for (var i = 0; i < panels.length; i += 1) {
        if (panels[i].getAttribute('data-category-panel') === slug) {
          return panels[i];
        }
      }
      return null;
    }

    function canAnimate(element) {
      return !(motion && motion.matches) && !document.hidden && typeof element.animate === 'function';
    }

    function releaseGroup(group) {
      group.style.overflow = '';
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

      /* Put the opening row after the last card visibly sharing this row.
         Besides making the accordion position exact, this keeps Tab order
         aligned with the visual order at every responsive grid size. */
      var template = window.getComputedStyle(rootList).gridTemplateColumns;
      var columns = template && template !== 'none'
        ? template.split(/\s+/).filter(Boolean).length
        : roots.length;
      columns = Math.max(1, Math.min(columns, roots.length));
      var rootIndex = roots.indexOf(rootLink);
      var rowEnd = Math.min((Math.floor(rootIndex / columns) * columns) + columns, roots.length) - 1;
      var nextNode = roots[rowEnd].nextSibling;
      if (nextNode !== group) {
        rootList.insertBefore(group, nextNode);
      }
    }

    function animatePanel(panel, expand, rootLink) {
      if (!canAnimate(panel)) {
        return;
      }

      var group = panel.closest('[data-category-panels-root]');
      if (rootLink) {
        var rootRect = rootLink.getBoundingClientRect();
        var panelRect = panel.getBoundingClientRect();
        var originX = rootRect.left + (rootRect.width / 2) - panelRect.left;
        panel.style.setProperty('--category-origin-x', originX + 'px');
      }

      /* The grid row itself opens from zero height, so the panel feels as if
         it unfolds out of its category rather than simply fading in later. */
      if (expand && group && canAnimate(group)) {
        var targetHeight = group.scrollHeight;
        group.style.overflow = 'hidden';
        var opening = group.animate([
          { height: '0px' },
          { height: targetHeight + 'px' }
        ], { duration: 620, easing: easing });
        opening.onfinish = function () { releaseGroup(group); };
        opening.oncancel = function () { releaseGroup(group); };
      }

      panel.animate([
        { opacity: 0, transform: 'translateY(-18px) scale(0.975)', filter: 'blur(4px)' },
        { opacity: 1, transform: 'translateY(0) scale(1)', filter: 'blur(0)' }
      ], { duration: 560, easing: easing });

      var cards = panel.querySelectorAll('.category-child');
      each(cards, function (card, index) {
        if (!canAnimate(card)) {
          return;
        }
        card.animate([
          { opacity: 0, transform: 'translateY(22px) scale(0.98)' },
          { opacity: 1, transform: 'translateY(0) scale(1)' }
        ], {
          duration: 500,
          delay: 70 + Math.min(index * 42, 250),
          easing: easing,
          fill: 'backwards'
        });
      });
    }

    function activate(slug, moveFocus) {
      var target = panelFor(slug);
      if (!target) {
        return;
      }

      var changed = explorer.getAttribute('data-selected') !== slug;
      var previousRoot = '';
      for (var i = 0; i < panels.length; i += 1) {
        if (!panels[i].hidden) {
          previousRoot = panels[i].getAttribute('data-root') || '';
          break;
        }
      }
      var rootSlug = target.getAttribute('data-root') || '';
      var rootChanged = previousRoot !== rootSlug;

      /* Stop an interrupted opening before moving its group in the grid. */
      each(panelGroups, function (group) {
        if (typeof group.getAnimations === 'function') {
          each(group.getAnimations(), function (animation) { animation.cancel(); });
        }
        releaseGroup(group);
        group.hidden = group.getAttribute('data-category-panels-root') !== rootSlug;
      });
      each(panels, function (panel) {
        panel.hidden = panel !== target;
      });
      explorer.setAttribute('data-selected', slug);

      var activeRoot = null;
      each(roots, function (rootLink) {
        var active = rootLink.getAttribute('data-category-root') === rootSlug;
        rootLink.classList.toggle('is-active', active);
        rootLink.setAttribute('aria-expanded', active ? 'true' : 'false');
        if (active) {
          activeRoot = rootLink;
          rootLink.setAttribute('aria-current', 'location');
        } else {
          rootLink.removeAttribute('aria-current');
        }
      });
      placePanelGroup(activeRoot);

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
      if (changed) {
        animatePanel(target, rootChanged, activeRoot);
      }
    }

    explorer.addEventListener('click', function (event) {
      if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
        return;
      }
      var trigger = event.target.closest('[data-category-select]');
      if (!trigger || !explorer.contains(trigger)) {
        return;
      }
      var slug = trigger.getAttribute('data-category-select');
      if (!panelFor(slug)) {
        return; // A real catalog link remains the fallback.
      }

      event.preventDefault();
      activate(slug, Boolean(trigger.closest('.category-panel')));
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

    /* Normalize the server-rendered group position without playing an
       entrance animation on the initial page load. */
    activate(explorer.getAttribute('data-selected') || '', false);
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
