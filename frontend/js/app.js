/* ==========================================================================
   LUFLY - Core App Bootstrap (High Performance Edition)
   Plumbing for:
     * Fast Theme switcher (instant paint + local sync)
     * Accessible modal controller
     * Smart Link & Asset Prefetcher (Instant 0ms Page Navigation)
     * BFCache (Back/Forward Cache) Instant Restoration Handler
   Sections scroll in the normal document flow - there are deliberately
   no scroll-driven animation engines here (kept the site light).
   ========================================================================== */
(function () {
  'use strict';

  var root = document.documentElement;
  var STORAGE_KEY = 'lufly-theme';

  /* ---------- 1. Theme Engine ---------- */

  function storedTheme() {
    try {
      return localStorage.getItem(STORAGE_KEY);
    } catch (error) {
      return null;
    }
  }

  function saveTheme(theme) {
    try {
      localStorage.setItem(STORAGE_KEY, theme);
    } catch (error) {
      /* storage unavailable */
    }
  }

  function preferredTheme() {
    var stored = storedTheme();
    if (stored) return stored;
    var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    return prefersDark ? 'dark' : 'light';
  }

  function applyTheme(theme) {
    root.setAttribute('data-theme', theme);
    document.dispatchEvent(new CustomEvent('lufly:theme', { detail: { theme: theme } }));
  }

  function toggleTheme() {
    var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    applyTheme(next);
    saveTheme(next);
  }

  applyTheme(preferredTheme());

  document.addEventListener('click', function (event) {
    var toggle = event.target.closest('[data-theme-toggle], #lufly-theme-toggle-btn');
    if (!toggle) return;
    event.preventDefault();
    toggleTheme();
  });

  /* ---------- 2. Modal Controller ---------- */

  function setModal(modal, open) {
    if (!modal) return;
    modal.hidden = !open;
  }

  document.addEventListener('click', function (event) {
    var opener = event.target.closest('[data-modal-open]');
    if (opener) {
      setModal(document.getElementById(opener.getAttribute('data-modal-open')), true);
      return;
    }

    var closer = event.target.closest('[data-modal-close]');
    if (closer) {
      setModal(closer.closest('[data-modal]'), false);
      return;
    }

    if (event.target.matches && event.target.matches('[data-modal]')) {
      setModal(event.target, false);
    }
  });

  document.addEventListener('keydown', function (event) {
    if (event.key !== 'Escape') return;
    document.querySelectorAll('[data-modal]:not([hidden])').forEach(function (modal) {
      setModal(modal, false);
    });
  });

  /* ---------- 3. Smart Link Prefetching Engine (0ms Navigation) ---------- */

  var prefetched = new Set();
  var prefetchTimer = null;
  var isDataSaver = navigator.connection && (navigator.connection.saveData || /2g/.test(navigator.connection.effectiveType));

  function prefetchUrl(url) {
    if (!url || isDataSaver || prefetched.has(url)) return;
    prefetched.add(url);

    try {
      var link = document.createElement('link');
      link.rel = 'prefetch';
      link.href = url;
      link.as = 'document';
      document.head.appendChild(link);
    } catch (e) {
      /* prefetch fallback */
    }
  }

  function getTargetUrl(link) {
    if (!link) return null;
    var href = link.getAttribute('href');
    if (!href || href.startsWith('#') || href.startsWith('javascript:') ||
        href.startsWith('mailto:') || href.startsWith('tel:') ||
        href.startsWith('https://wa.me') || link.target === '_blank' ||
        link.hasAttribute('download')) {
      return null;
    }

    try {
      var parsed = new URL(link.href, window.location.href);
      if (parsed.origin !== window.location.origin) return null;
      if (parsed.pathname === window.location.pathname && parsed.search === window.location.search) return null;
      if (parsed.pathname.startsWith('/admin') || parsed.pathname.includes('/logout')) return null;
      return parsed.href;
    } catch (err) {
      return null;
    }
  }

  // Hover intent (60ms threshold to prevent wasteful prefetches on quick mouse movement)
  document.addEventListener('mouseover', function (event) {
    var link = event.target.closest('a');
    var url = getTargetUrl(link);
    if (!url) return;

    clearTimeout(prefetchTimer);
    prefetchTimer = setTimeout(function () {
      prefetchUrl(url);
    }, 60);
  }, { passive: true });

  document.addEventListener('mouseout', function () {
    clearTimeout(prefetchTimer);
  }, { passive: true });

  // Touch start prefetch (instant 0ms trigger on mobile touch)
  document.addEventListener('touchstart', function (event) {
    var link = event.target.closest('a');
    var url = getTargetUrl(link);
    if (url) prefetchUrl(url);
  }, { passive: true });

  /* ---------- 4. BFCache Restoration (Instant Back/Forward Response) ---------- */

  window.addEventListener('pageshow', function (event) {
    if (event.persisted) {
      // Page was restored from back-forward cache: instantly dismiss any loader overlay
      if (window.LUFLYLoader && typeof window.LUFLYLoader.done === 'function') {
        window.LUFLYLoader.done();
      }
      document.body.classList.remove('ld-loading');
      document.body.classList.add('ready');
    }
  });
})();
