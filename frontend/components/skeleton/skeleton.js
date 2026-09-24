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

    host.classList.add('sk-armed');
    if (!real) {
      revealHost(host);
      return;
    }

    var imgs = Array.prototype.slice.call(real.querySelectorAll('img'));

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

  window.LUFLYSkeleton = { reveal: revealHost, scan: init };
})();
