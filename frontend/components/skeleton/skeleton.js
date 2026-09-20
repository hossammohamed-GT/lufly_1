/* ============================================================
   Skeleton controller.

   Any element carrying [data-sk-host] holds two children: a glass
   [data-sk-skeleton] placeholder and the real [data-sk-real] content. The
   real content is already in the HTML - the skeleton exists so the section
   has a settled shape while its images decode, which is what actually makes
   a data driven block feel slow.

   Reveal happens on whichever comes first:
     - the images inside the real content have loaded, or
     - a short ceiling, so a broken image can never strand a skeleton.
   ============================================================ */

(function () {
  'use strict';

  var CEILING = 2200;

  function revealHost(host) {
    if (!host) return;
    host.classList.remove('sk-armed');
    host.classList.add('is-ready');
  }

  function initHost(host) {
    var real = host.querySelector('[data-sk-real]');

    /* Arm only now: from this point the script is demonstrably running, so
       hiding the real content is safe because we can always reveal it. */
    host.classList.add('sk-armed');
    if (!real) {
      revealHost(host);
      return;
    }

    var imgs = Array.prototype.slice.call(real.querySelectorAll('img'));

    /* nothing to wait for */
    if (imgs.length === 0) {
      revealHost(host);
      return;
    }

    var pending = 0;
    var settled = false;

    function done() {
      pending -= 1;
      if (pending <= 0 && !settled) {
        settled = true;
        revealHost(host);
      }
    }

    imgs.forEach(function (img) {
      if (img.complete) return;
      pending += 1;
      img.addEventListener('load', done, { once: true });
      img.addEventListener('error', done, { once: true });
    });

    if (pending === 0) {
      settled = true;
      revealHost(host);
      return;
    }

    /* never let a stalled request hold the section hostage */
    setTimeout(function () {
      if (!settled) {
        settled = true;
        revealHost(host);
      }
    }, CEILING);
  }

  function init() {
    var hosts = document.querySelectorAll('[data-sk-host]');
    Array.prototype.forEach.call(hosts, initHost);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  /* expose so live search and other dynamic renderers can reuse it */
  window.LUFLYSkeleton = { reveal: revealHost, scan: init };
})();
