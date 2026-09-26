/* ============================================================
   LUFLY - Preloader "Building the brand" & Global Loading Controller
   Seed dot -> glyph pieces fly in -> settle + glass reflection ->
   seal glow -> curtain exit. All choreography lives in loader.css
   keyframes; this controller only schedules seal/exit and classifies
   which pages get the loader at all.
   ============================================================ */

(function () {
  'use strict';

  var loader = document.getElementById('ldLoader');
  if (!loader) return;

  /* The app may be deployed under a sub-directory (XAMPP: /lufly_1/). Strip
     that base before classifying URLs - otherwise home (/lufly_1/en) never
     matches the home rule and the loader is skipped (shows a lone seed dot
     and vanishes). */
  var basePath = (loader.getAttribute('data-base') || '').replace(/\/+$/, '');
  function stripBase(pathname) {
    if (basePath && pathname.toLowerCase().indexOf(basePath.toLowerCase()) === 0) {
      var rest = pathname.slice(basePath.length);
      return rest === '' ? '/' : rest;
    }
    return pathname;
  }

  var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (reduced) loader.classList.add('ld-reduced');

  /* --- assembly choreography timing (must match loader.css keyframes) ---
     These used to be 2500 + 450 = 2950 ms of blocked, unscrollable page on
     every single home visit. The brand moment is kept for a first-time
     visitor, but at a fifth of the length; everyone coming back (and every
     in-site navigation) goes straight to the page. See
     docs/Home-Performance-Audit.md. */
  var T_SHOW = reduced ? 300 : 800;     /* seed -> pieces assemble -> settle + reflection in */
  var T_SEAL = reduced ? 120 : 300;     /* mark glow pulse before the curtain */
  var T_CURTAIN = 900;                  /* slide-out transition (css: .85s + .01) */
  var HARD_STOP = 2500;                 /* never trap the page behind the splash */
  var NAV_HOLD = reduced ? 120 : 300;   /* how long the nav flash holds */

  /* --- first visit only ---------------------------------------------------
     Stored per browser, not per tab, so a repeat visit is instant. Bump
     SEEN_VERSION whenever the animation itself changes and deserves another
     airing. Storage can throw (private mode, blocked cookies) - in that case
     the visitor simply sees the splash, which is the safe direction. */
  var SEEN_KEY = 'lufly-loader-seen';
  var SEEN_VERSION = '1';

  function hasSeenSplash() {
    try {
      return window.localStorage.getItem(SEEN_KEY) === SEEN_VERSION;
    } catch (err) {
      return false;
    }
  }

  function rememberSplash() {
    try {
      window.localStorage.setItem(SEEN_KEY, SEEN_VERSION);
    } catch (err) { /* nothing to remember into */ }
  }

  var isExited = false;

  function exitLoader() {
    if (isExited) return;
    isExited = true;
    loader.classList.add('exit');
    document.body.classList.remove('ld-loading');
    document.body.classList.add('ready');
    setTimeout(function () {
      if (isExited) loader.style.display = 'none';
    }, T_CURTAIN);
  }

  /* Boot skip path: pages that never get the loader must not flash it. */
  function releasePage() {
    loader.style.display = 'none';
    document.body.classList.remove('ld-loading');
    document.body.classList.add('ready');
    isExited = true;
  }

  /* Initial visit: the assembly owns the full moment, then seal and exit.
     No progress gating - the animation IS the wait, by design. */
  function startInitialSequence() {
    rememberSplash();
    document.body.classList.add('ld-loading');
    setTimeout(function () { loader.classList.add('seal'); }, T_SHOW);
    setTimeout(exitLoader, T_SHOW + T_SEAL);
    /* absolute backstop; harmless once exitLoader has run */
    setTimeout(exitLoader, HARD_STOP);
  }

  /* Navigation flash (home/contact links + contact form submits): the
     finished mark without the choreography, shorter hold. */
  function showLoader() {
    /* Returning visitors get the page immediately, splash or not. */
    if (hasSeenSplash()) return;

    isExited = false;
    loader.classList.remove('exit', 'seal');
    loader.classList.add('is-nav');
    loader.style.display = '';
    document.body.classList.add('ld-loading');
    setTimeout(exitLoader, HARD_STOP);
  }

  function hideLoader(after, onComplete) {
    var hold = typeof after === 'number' ? after : NAV_HOLD;
    var cb = typeof after === 'function' ? after : onComplete;
    setTimeout(function () {
      loader.classList.add('seal');
      setTimeout(function () {
        exitLoader();
        if (typeof cb === 'function') cb();
      }, T_SEAL);
    }, hold);
  }

  /* ---- Which navigations deserve the full-screen loader? ----
     Explicit allow-list: home and contact only. Everything else
     (catalogue, product, search) is skeleton backed and navigates
     without it. */
  var LOADER_PATHS = [
    'contact',   /* en */
    'kontakt',   /* cs */
    'iletisim'   /* tr */
  ];

  function isHomePath(pathname) {
    /* home is '' after the locale segment: '/', '/en', '/en/' */
    var parts = pathname.split('/').filter(function (x) { return x !== ''; });
    if (parts.length === 0) return true;
    if (parts.length === 1 && parts[0].length <= 5) return true; /* locale only */
    return false;
  }

  function isLoaderPath(pathname) {
    if (isHomePath(pathname)) return true;
    var parts = pathname.split('/').filter(function (x) { return x !== ''; });
    var last = parts[parts.length - 1] || '';
    return LOADER_PATHS.indexOf(last.toLowerCase()) !== -1;
  }

  function bootLoader() {
    /* Never let an unexpected error here leave body.ld-loading applied: that
       class sets overflow:hidden, so a throw at boot freezes the whole site. */
    try {
      bootLoaderInner();
    } catch (err) {
      releasePage();
    }
  }

  function bootLoaderInner() {
    /* Landing directly on a catalogue, category or product URL must not show
       the loading screen at all, because those pages are skeleton backed. */
    if (!isLoaderPath(stripBase(window.location.pathname))) {
      releasePage();
      return;
    }

    /* The splash is a once-per-browser brand moment. After that the assets are
       cached and holding the page back only makes the site feel slow. */
    if (hasSeenSplash()) {
      releasePage();
      return;
    }

    startInitialSequence();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootLoader);
  } else {
    bootLoader();
  }

  function isLightNavigation(url, link) {
    if (link && link.hasAttribute('data-loader-skip')) return true;
    if (link && link.hasAttribute('data-loader-force')) return false;

    /* pagination, filtering and sorting on the current page */
    if (url.pathname === window.location.pathname) return true;

    /* anything that is not home or contact skips the loader */
    return !isLoaderPath(stripBase(url.pathname));
  }

  /* ---- Auto-wire: link clicks ---- */
  document.addEventListener('click', function (e) {
    var link = e.target.closest('a');
    if (!link) return;
    var href = link.getAttribute('href');
    if (!href) return;

    if (href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('mailto:') ||
        href.startsWith('tel:') || href.startsWith('https://wa.me') || link.target === '_blank' ||
        link.hasAttribute('download') || e.defaultPrevented || e.metaKey || e.ctrlKey || e.shiftKey) {
      return;
    }

    var targetUrl;
    try {
      targetUrl = new URL(link.href, window.location.href);
    } catch (err) {
      return;
    }

    if (targetUrl.origin !== window.location.origin) return;
    if (targetUrl.pathname === window.location.pathname && targetUrl.search === window.location.search) return;

    /* The full-screen loader is reserved for home and contact. Product
       browsing renders glass skeletons in place instead. */
    if (isLightNavigation(targetUrl, link)) return;

    showLoader();
  }, { passive: true });

  /* ---- Auto-wire: form submissions ---- */
  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (!form || form.target === '_blank' || e.defaultPrevented) return;
    if (form.hasAttribute('data-loader-skip')) return;

    /* only forms that post to a loader page (i.e. contact) show it */
    var action = form.getAttribute('action') || window.location.pathname;
    var actionPath;
    try {
      actionPath = new URL(action, window.location.href).pathname;
    } catch (err) {
      actionPath = window.location.pathname;
    }
    if (!isLoaderPath(stripBase(actionPath))) return;
    showLoader();
  }, { passive: true });

  /* Public API. setProgress/setMessage are accepted no-ops: the assembly
     choreography carries the moment now, but older callers stay safe.
     .done() is used by app.js to dismiss the loader on bfcache restore. */
  window.LUFLYLoader = {
    start: startInitialSequence,
    show: showLoader,
    setProgress: function () {},
    setMessage: function () {},
    hide: hideLoader,
    done: exitLoader
  };
})();
