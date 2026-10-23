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
    var roots = Array.prototype.slice.call(explorer.querySelectorAll('[data-category-root]'));
    var status = explorer.querySelector('[data-category-status]');
    var motion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)');

    function panelFor(slug) {
      for (var i = 0; i < panels.length; i += 1) {
        if (panels[i].getAttribute('data-category-panel') === slug) {
          return panels[i];
        }
      }
      return null;
    }

    function animatePanel(panel) {
      if ((motion && motion.matches) || document.hidden || typeof panel.animate !== 'function') {
        return;
      }

      panel.animate([
        { opacity: 0, transform: 'translateY(10px)' },
        { opacity: 1, transform: 'translateY(0)' }
      ], { duration: 360, easing: 'cubic-bezier(0.16, 1, 0.3, 1)' });

      var cards = panel.querySelectorAll('.category-child');
      each(cards, function (card, index) {
        card.animate([
          { opacity: 0, transform: 'translateY(12px)' },
          { opacity: 1, transform: 'translateY(0)' }
        ], {
          duration: 420,
          delay: Math.min(index * 32, 160),
          easing: 'cubic-bezier(0.16, 1, 0.3, 1)',
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
      each(panels, function (panel) {
        panel.hidden = panel !== target;
      });
      explorer.setAttribute('data-selected', slug);

      var rootSlug = target.getAttribute('data-root');
      each(roots, function (rootLink) {
        var active = rootLink.getAttribute('data-category-root') === rootSlug;
        rootLink.classList.toggle('is-active', active);
        if (active) {
          rootLink.setAttribute('aria-current', 'location');
        } else {
          rootLink.removeAttribute('aria-current');
        }
      });

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
        animatePanel(target);
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
