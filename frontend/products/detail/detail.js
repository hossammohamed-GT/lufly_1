/* ============================================================
   Product detail media stage.

   Two levels, both fully data driven:
   1. View tabs (product / drawing / installed). A tab only exists when the
      product actually has images of that type, so nothing is hardcoded here.
   2. Inside a view, any number of images: prev/next arrows, dots, swipe and
      arrow keys. Views holding a single image simply have no controls.
   ============================================================ */

(function () {
  'use strict';

  function initSlider(view) {
    var slides = view.querySelectorAll('[data-pdp-slide]');
    if (slides.length < 2) return null;

    var dots = view.querySelectorAll('[data-pdp-dot]');
    var counter = view.querySelector('[data-pdp-counter]');
    var prev = view.querySelector('[data-pdp-prev]');
    var next = view.querySelector('[data-pdp-next]');
    var index = 0;

    function show(i) {
      index = (i + slides.length) % slides.length;
      slides.forEach(function (slide, n) {
        slide.classList.toggle('is-on', n === index);
      });
      dots.forEach(function (dot, n) {
        dot.classList.toggle('is-on', n === index);
      });
      if (counter) counter.textContent = index + 1 + '/' + slides.length;
    }

    if (prev) prev.addEventListener('click', function () { show(index - 1); });
    if (next) next.addEventListener('click', function () { show(index + 1); });

    dots.forEach(function (dot, n) {
      dot.addEventListener('click', function () { show(n); });
    });

    /* touch swipe */
    var startX = null;
    view.addEventListener('touchstart', function (e) {
      startX = e.touches[0].clientX;
    }, { passive: true });

    view.addEventListener('touchend', function (e) {
      if (startX === null) return;
      var dx = e.changedTouches[0].clientX - startX;
      if (Math.abs(dx) > 40) show(dx < 0 ? index + 1 : index - 1);
      startX = null;
    }, { passive: true });

    return { step: function (d) { show(index + d); } };
  }

  function init() {
    var stage = document.querySelector('.pdp-stage');
    if (!stage) return;

    var views = stage.querySelectorAll('[data-pdp-view]');
    if (views.length === 0) return;

    var sliders = {};
    views.forEach(function (view) {
      sliders[view.getAttribute('data-pdp-view')] = initSlider(view);
    });

    var tabs = stage.querySelectorAll('[data-pdp-tab]');

    function current() {
      var name = null;
      views.forEach(function (view) {
        if (view.classList.contains('is-on')) name = view.getAttribute('data-pdp-view');
      });
      return name;
    }

    if (tabs.length > 1) {
      var activate = function (name) {
        views.forEach(function (view) {
          view.classList.toggle('is-on', view.getAttribute('data-pdp-view') === name);
        });
        tabs.forEach(function (tab) {
          var on = tab.getAttribute('data-pdp-tab') === name;
          tab.classList.toggle('is-on', on);
          tab.setAttribute('aria-selected', on ? 'true' : 'false');
        });
      };

      tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
          activate(tab.getAttribute('data-pdp-tab'));
        });
      });
    }

    /* arrow keys move through the images of the view on screen */
    stage.addEventListener('keydown', function (e) {
      if (e.key !== 'ArrowRight' && e.key !== 'ArrowLeft') return;
      var slider = sliders[current()];
      if (!slider) return;
      e.preventDefault();
      slider.step(e.key === 'ArrowRight' ? 1 : -1);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
