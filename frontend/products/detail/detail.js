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

  function initLightbox(stage) {
    var lightbox = document.querySelector('[data-pdp-lightbox]');
    if (!lightbox) return;

    var dialog = lightbox.querySelector('.pdp-lightbox-dialog');
    var image = lightbox.querySelector('[data-pdp-lightbox-image]');
    var caption = lightbox.querySelector('[data-pdp-lightbox-caption]');
    var closeButton = lightbox.querySelector('[data-pdp-lightbox-close]');
    var activeTrigger = null;
    var isOpen = false;

    if (!dialog || !image || !caption || !closeButton) return;

    function open(trigger) {
      var source = trigger.currentSrc || trigger.getAttribute('src');
      if (!source) return;

      activeTrigger = trigger;
      image.src = source;
      image.alt = trigger.getAttribute('alt') || '';
      caption.textContent = trigger.getAttribute('data-pdp-zoom-caption') || '';
      lightbox.hidden = false;
      lightbox.setAttribute('aria-hidden', 'false');
      document.documentElement.classList.add('pdp-lightbox-open');
      document.body.classList.add('pdp-lightbox-open');
      isOpen = true;

      window.requestAnimationFrame(function () {
        lightbox.classList.add('is-open');
        closeButton.focus();
      });
    }

    function close() {
      if (!isOpen) return;

      isOpen = false;
      lightbox.classList.remove('is-open');
      lightbox.hidden = true;
      lightbox.setAttribute('aria-hidden', 'true');
      document.documentElement.classList.remove('pdp-lightbox-open');
      document.body.classList.remove('pdp-lightbox-open');
      image.removeAttribute('src');

      if (activeTrigger && document.contains(activeTrigger)) {
        activeTrigger.focus();
      }
      activeTrigger = null;
    }

    stage.querySelectorAll('[data-pdp-zoom]').forEach(function (trigger) {
      trigger.addEventListener('click', function () { open(trigger); });
      trigger.addEventListener('keydown', function (event) {
        if (event.key !== 'Enter' && event.key !== ' ') return;
        event.preventDefault();
        open(trigger);
      });
    });

    closeButton.addEventListener('click', close);
    lightbox.addEventListener('click', function (event) {
      if (event.target === lightbox) close();
    });
    dialog.addEventListener('click', function (event) {
      /* The empty area around the image behaves like the backdrop. */
      if (event.target === dialog) close();
    });
    lightbox.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') {
        event.preventDefault();
        close();
      } else if (event.key === 'Tab') {
        /* The close control is the only focusable element in the preview. */
        event.preventDefault();
        closeButton.focus();
      }
    });
    document.addEventListener('keydown', function (event) {
      if (isOpen && event.key === 'Escape') {
        event.preventDefault();
        close();
      }
    });
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
