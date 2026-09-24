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

  function initLightbox(stage) {
    var overlay = document.createElement('div');
    overlay.className = 'pdp-lightbox';
    overlay.setAttribute('aria-hidden', 'true');
    overlay.innerHTML = '<div class="pdp-lightbox-bar"><button type="button" data-lightbox-close aria-label="Close">×</button><button type="button" data-lightbox-zoom-out aria-label="Zoom out">−</button><button type="button" data-lightbox-reset aria-label="Reset zoom">100%</button><button type="button" data-lightbox-zoom-in aria-label="Zoom in">+</button></div><div class="pdp-lightbox-canvas"><img alt="" data-lightbox-image></div>';
    document.body.appendChild(overlay);
    var image = overlay.querySelector('[data-lightbox-image]');
    var scale = 1;

    function render() { image.style.transform = 'scale(' + scale + ')'; }
    function close() { overlay.classList.remove('is-open'); overlay.setAttribute('aria-hidden', 'true'); document.body.classList.remove('pdp-lightbox-open'); }
    function open(source) { image.src = source.currentSrc || source.src; image.alt = source.alt || ''; scale = 1; render(); overlay.classList.add('is-open'); overlay.setAttribute('aria-hidden', 'false'); document.body.classList.add('pdp-lightbox-open'); }
    function zoom(delta) { scale = Math.min(4, Math.max(1, scale + delta)); render(); }

    stage.addEventListener('click', function (e) {
      var source = e.target.closest ? e.target.closest('[data-pdp-lightbox]') : null;
      if (!source) return;
      e.preventDefault(); open(source);
    });
    stage.addEventListener('keydown', function (e) {
      if ((e.key === 'Enter' || e.key === ' ') && e.target.matches('[data-pdp-lightbox]')) { e.preventDefault(); open(e.target); }
    });
    overlay.addEventListener('click', function (e) { if (e.target === overlay || e.target.classList.contains('pdp-lightbox-canvas')) close(); });
    overlay.querySelector('[data-lightbox-close]').addEventListener('click', close);
    overlay.querySelector('[data-lightbox-zoom-in]').addEventListener('click', function () { zoom(.25); });
    overlay.querySelector('[data-lightbox-zoom-out]').addEventListener('click', function () { zoom(-.25); });
    overlay.querySelector('[data-lightbox-reset]').addEventListener('click', function () { scale = 1; render(); });
    overlay.addEventListener('wheel', function (e) { if (!overlay.classList.contains('is-open')) return; e.preventDefault(); zoom(e.deltaY < 0 ? .2 : -.2); }, { passive: false });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && overlay.classList.contains('is-open')) close(); });
  }

  function init() {
    var stage = document.querySelector('.pdp-stage');
    if (!stage) return;

    var views = stage.querySelectorAll('[data-pdp-view]');
    if (views.length === 0) return;
    initLightbox(stage);

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
