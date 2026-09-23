/* Category mosaic: the stage cycles through shots of the same category.
   The server picks three per tile for this visit and shuffles them, so the
   mosaic already looks different on a reload; this only turns the tile over
   while the visitor is looking at it. With a pointer that happens on hover,
   on touch on a slow staggered timer - and only for tiles actually on screen. */
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
        /* hover / keyboard focus: one step per interaction, no timer */
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
        }, INTERVAL + index * 700);   /* staggered, so five tiles never blink together */
      }

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
