(function () {
  'use strict';

  var loader = document.getElementById('ldLoader');
  if (!loader) return;

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

  var T_SHOW = reduced ? 1100 : 2500;
  var T_CURTAIN = 900;
  var HARD_STOP = 6000;
  var NAV_HOLD = reduced ? 200 : 520;
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

  function releasePage() {
    loader.style.display = 'none';
    document.body.classList.remove('ld-loading');
    document.body.classList.add('ready');
    isExited = true;
  }

  function startInitialSequence() {
    document.body.classList.add('ld-loading');
    setTimeout(exitLoader, T_SHOW);
    setTimeout(exitLoader, HARD_STOP);
  }

  function showLoader() {
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
      exitLoader();
      if (typeof cb === 'function') cb();
    }, hold);
  }

  var LOADER_PATHS = [
    'contact',   'kontakt',   'iletisim'   ];

  function isHomePath(pathname) {
    var parts = pathname.split('/').filter(function (x) { return x !== ''; });
    if (parts.length === 0) return true;
    if (parts.length === 1 && parts[0].length <= 5) return true; return false;
  }

  function isLoaderPath(pathname) {
    if (isHomePath(pathname)) return true;
    var parts = pathname.split('/').filter(function (x) { return x !== ''; });
    var last = parts[parts.length - 1] || '';
    return LOADER_PATHS.indexOf(last.toLowerCase()) !== -1;
  }

  function bootLoader() {
    try {
      bootLoaderInner();
    } catch (err) {
      releasePage();
    }
  }

  function bootLoaderInner() {
    if (!isLoaderPath(stripBase(window.location.pathname))) {
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

    if (url.pathname === window.location.pathname) return true;

    return !isLoaderPath(stripBase(url.pathname));
  }

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

    if (isLightNavigation(targetUrl, link)) return;

    showLoader();
  }, { passive: true });

  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (!form || form.target === '_blank' || e.defaultPrevented) return;
    if (form.hasAttribute('data-loader-skip')) return;

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

  window.LUFLYLoader = {
    start: startInitialSequence,
    show: showLoader,
    setProgress: function () {},
    setMessage: function () {},
    hide: hideLoader,
    done: exitLoader
  };
})();
