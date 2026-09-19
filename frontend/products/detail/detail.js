/* ============================================================
   Product detail: essential-view switcher (product / drawing / situ).
   Plain tabs - no scroll logic, no animation engines.
   ============================================================ */

(function () {
  'use strict';

  function init() {
    var stage = document.querySelector('.pdp-stage');
    if (!stage) return;

    var views = stage.querySelectorAll('[data-pdp-view]');
    var tabs = stage.querySelectorAll('[data-pdp-tab]');
    if (views.length === 0 || tabs.length === 0) return;

    function activate(name) {
      views.forEach(function (view) {
        view.classList.toggle('is-on', view.getAttribute('data-pdp-view') === name);
      });
      tabs.forEach(function (tab) {
        var on = tab.getAttribute('data-pdp-tab') === name;
        tab.classList.toggle('is-on', on);
        tab.setAttribute('aria-selected', on ? 'true' : 'false');
      });
    }

    tabs.forEach(function (tab) {
      tab.addEventListener('click', function () {
        activate(tab.getAttribute('data-pdp-tab') || 'main');
      });
    });

    stage.addEventListener('keydown', function (e) {
      if (e.key !== 'ArrowRight' && e.key !== 'ArrowLeft') return;
      var current = 0;
      tabs.forEach(function (tab, i) {
        if (tab.classList.contains('is-on')) current = i;
      });
      var dir = e.key === 'ArrowRight' ? 1 : -1;
      var next = (current + dir + tabs.length) % tabs.length;
      activate(tabs[next].getAttribute('data-pdp-tab') || 'main');
      tabs[next].focus();
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
