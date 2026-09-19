/* ============================================================
   Catalog: the sort select re-renders live search results
   instantly; without an active live search it submits the form.
   ============================================================ */

(function () {
  'use strict';

  function init() {
    var select = document.querySelector('[data-catalog-sort]');
    if (!select || !select.form) return;

    select.addEventListener('change', function () {
      if (select.form.classList.contains('is-live')) {
        /* live search owns the grid: re-sort in place, no reload */
        select.form.dispatchEvent(new CustomEvent('livesearch:rerender'));
        return;
      }
      select.form.submit();
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

/* ============================================================
   Product card: cycle through the product photos on hover.
   Delegated from the grid so cards injected by live search work too.
   ============================================================ */

(function () {
  'use strict';

  /* hovering advances to the next photo straight away, then each
     following photo is held for HOLD ms before moving on */
  var HOLD = 3000;
  var timers = new WeakMap();

  function slides(media) {
    return media.querySelectorAll('[data-pcard-slide]');
  }

  function show(media, index) {
    var imgs = slides(media);
    var dots = media.querySelectorAll('[data-pcard-dot]');
    if (imgs.length === 0) return;

    index = (index + imgs.length) % imgs.length;
    imgs.forEach(function (img, n) { img.classList.toggle('is-on', n === index); });
    dots.forEach(function (dot, n) { dot.classList.toggle('is-on', n === index); });
    media.setAttribute('data-pcard-index', String(index));
  }

  function advance(media) {
    show(media, parseInt(media.getAttribute('data-pcard-index') || '0', 10) + 1);
  }

  function start(media) {
    if (timers.has(media)) return;
    if (slides(media).length < 2) return;
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    /* immediate first step, so the hover feels responsive */
    advance(media);

    var timer = setInterval(function () { advance(media); }, HOLD);
    timers.set(media, timer);
  }

  function stop(media) {
    var timer = timers.get(media);
    if (timer) {
      clearInterval(timer);
      timers.delete(media);
    }
    show(media, 0);
  }

  function init() {
    document.addEventListener('pointerenter', function (e) {
      var media = e.target.closest ? e.target.closest('[data-pcard-cycle]') : null;
      if (media) start(media);
    }, true);

    document.addEventListener('pointerleave', function (e) {
      var media = e.target.closest ? e.target.closest('[data-pcard-cycle]') : null;
      if (media) stop(media);
    }, true);

    /* keyboard users get the same preview when the card link is focused */
    document.addEventListener('focusin', function (e) {
      var media = e.target.closest ? e.target.closest('[data-pcard-cycle]') : null;
      if (media) start(media);
    });

    document.addEventListener('focusout', function (e) {
      var media = e.target.closest ? e.target.closest('[data-pcard-cycle]') : null;
      if (media) stop(media);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
