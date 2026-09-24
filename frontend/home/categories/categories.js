(function () {
  'use strict';

  var INTERVAL = 5200;

  function init() {
    var cards = document.querySelectorAll('.category-card-monolith');
    if (!cards.length) {
      return;
    }

    var pointer = window.matchMedia && window.matchMedia('(hover: hover)').matches;
    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    Array.prototype.forEach.call(cards, function (card, index) {
      var shots = Array.prototype.slice.call(card.querySelectorAll('.category-card-shot'));
      if (shots.length < 2) {
        return;
      }

      var current = 0;
      var timer = null;

      function advance() {
        shots[current].classList.remove('is-on');
        current = (current + 1) % shots.length;
        shots[current].classList.add('is-on');
      }

      if (pointer || reduce) {
        card.addEventListener('mouseenter', advance);
        card.addEventListener('focus', advance, true);
        return;
      }

      function start() {
        if (timer) {
          return;
        }
        timer = window.setInterval(function () {
          if (!document.hidden) {
            advance();
          }
        }, INTERVAL + index * 700);   }

      function stop() {
        if (timer) {
          window.clearInterval(timer);
          timer = null;
        }
      }

      if ('IntersectionObserver' in window) {
        new IntersectionObserver(function (entries) {
          entries.forEach(function (entry) {
            if (entry.isIntersecting) {
              start();
            } else {
              stop();
            }
          });
        }, { rootMargin: '140px' }).observe(card);
      } else {
        start();
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
