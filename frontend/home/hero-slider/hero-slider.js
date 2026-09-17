/* Hero slider: autoplay, dots, arrows, RTL aware. */
(function () {
  'use strict';

  var INTERVAL = 6000;

  function init() {
    var viewport = document.getElementById('hero-slider-viewport');
    var track = document.getElementById('hero-slider-track');
    var slides = document.querySelectorAll('.deante-hero-slide');
    var dots = document.querySelectorAll('.deante-hero-dot');
    var prevBtn = document.getElementById('hero-prev-btn');
    var nextBtn = document.getElementById('hero-next-btn');

    if (!track || !slides.length) {
      return;
    }

    var current = 0;
    var total = slides.length;
    var timer = null;
    var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function isRTL() {
      return document.documentElement.getAttribute('dir') === 'rtl';
    }

    function goTo(index) {
      if (index < 0) {
        index = total - 1;
      }
      if (index >= total) {
        index = 0;
      }
      current = index;

      var step = isRTL() ? 100 : -100;
      track.style.transform = 'translateX(' + (current * step) + '%)';

      dots.forEach(function (dot, i) {
        dot.classList.toggle('is-active', i === current);
      });
    }

    function stop() {
      if (timer) {
        clearInterval(timer);
        timer = null;
      }
    }

    function start() {
      if (reduced) {
        return;
      }
      stop();
      timer = setInterval(function () {
        goTo(current + 1);
      }, INTERVAL);
    }

    if (nextBtn) {
      nextBtn.addEventListener('click', function () { goTo(current + 1); start(); });
    }

    if (prevBtn) {
      prevBtn.addEventListener('click', function () { goTo(current - 1); start(); });
    }

    dots.forEach(function (dot) {
      dot.addEventListener('click', function () {
        goTo(parseInt(dot.dataset.slide, 10) || 0);
        start();
      });
    });

    if (viewport) {
      viewport.addEventListener('mouseenter', stop);
      viewport.addEventListener('mouseleave', start);
    }

    goTo(0);
    start();
  }

  document.addEventListener('DOMContentLoaded', init);
})();
