/* ============================================================
   LUFLY - Preloader & Global Loading Controller (Optimized Edition)
   Timeline: brand assembly (seed dot -> glyph pieces fly in on their own
   paths -> settle + reflection) -> water fills glyphs (real progress %)
   + craftsman stage messages -> seal flash -> completion.
   Global API: window.LUFLYLoader.show(msg), update(pct, msg), hide()
   ============================================================ */

(function () {
  'use strict';

  var root = document.documentElement;
  var locale = (root.getAttribute('lang') || 'en').toLowerCase();

  var MESSAGES_BY_LOCALE = {
    en: [
      [0,   'Precision casting of high-purity brass'],
      [20,  'Robotic CNC machining to 0.01 mm'],
      [45,  'Aerospace PVD titanium surface treatment'],
      [70,  'Calibrating Kerox® ceramic cores'],
      [90,  'Hydrodynamic pressure testing and quality audit'],
      [100, 'System ready Welcome to LUFLY']
    ],
    tr: [
      [0,   'Yüksek saflıkta pirinç gövde dökümü'],
      [20,  '0.01 mm robotik CNC hassas işleme'],
      [45,  'Havacılık sınıfı PVD titanyum kaplama'],
      [70,  'Kerox® seramik göbek montajı'],
      [90,  'Hidrodinamik basınç ve kalite testi'],
      [100, 'Hazır LUFLY dünyasına hoş geldiniz']
    ],
    cs: [
      [0,   'Přesné lití vysoce čisté mosazi'],
      [20,  'Robotické CNC obrábění s přesností 0,01 mm'],
      [45,  'Aplikace titanového PVD povrchu'],
      [70,  'Kalibrace keramických kartuší Kerox®'],
      [90,  'Hydrodynamická tlaková zkouška a audit kvality'],
      [100, 'Připraveno Vítejte ve světě LUFLY']
    ]
  };

  var MESSAGES = MESSAGES_BY_LOCALE[locale] || MESSAGES_BY_LOCALE.en;

  var loader   = document.getElementById('ldLoader');
  var box      = document.getElementById('ldLogoBox');
  var water    = document.getElementById('ldWaterBody');
  var ui       = document.getElementById('ldUi');
  var track    = document.getElementById('ldFill');
  var pct      = document.getElementById('ldPct');
  var msg      = document.getElementById('ldMsg');
  var tag      = document.getElementById('ldTag');

  if (!loader) return;

  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  /* T_DRAW now covers the "building the brand" assembly (seed dot, glyph
     pieces fly in, settle) before the water-fill progress phase starts. */
  /* These used to total ~2s of mandatory animation before the page could be
     revealed, which by itself reads as a freeze. The assembly is now clipped
     to a length that still shows the brand without holding the site hostage. */
  var T_DRAW   = reduced ? 60 : 700;
  var T_HOLD   = reduced ? 60 : 90;
  var T_SEAL   = reduced ? 0 : 120;

  if (reduced) loader.classList.add('ld-reduced');

  /* ---- Bubbles ---- */
  if (!reduced && water && !water.querySelector('.bubble')) {
    for (var i = 0; i < 5; i++) {
      var b = document.createElement('span');
      var s = 3 + Math.random() * 4;
      b.className = 'bubble';
      b.style.width = s + 'px';
      b.style.height = s + 'px';
      b.style.left = (10 + Math.random() * 80) + '%';
      b.style.animationDuration = (1.1 + Math.random() * 1.4) + 's';
      b.style.animationDelay = (Math.random() * 1.5) + 's';
      water.appendChild(b);
    }
  }

  var stage = -1;
  var isExited = false;
  var customMessageActive = false;

  function setMessage(text, isFinal) {
    if (!msg) return;
    msg.classList.add('swap');
    setTimeout(function () {
      msg.textContent = text;
      if (isFinal) msg.classList.add('final');
      else msg.classList.remove('final');
      msg.classList.remove('swap');
    }, 100);
  }

  function setStage(p) {
    if (customMessageActive) return;
    var s = 0;
    for (var i = 0; i < MESSAGES.length; i++) {
      if (p >= MESSAGES[i][0]) s = i;
    }
    if (s === stage) return;
    stage = s;
    setMessage(MESSAGES[s][1], s === MESSAGES.length - 1);
  }

  function setProgress(p) {
    if (!water || !track || !pct) return;
    var val = Math.min(100, Math.max(0, p));
    water.style.height = Math.min(106, val * 1.06) + '%';
    track.style.width = val + '%';
    pct.textContent = Math.round(val) + '%';
    setStage(val);
  }

  var pageLoaded = false;
  var currentP = 0;
  var animFrameId = null;

  function runInitialLoad(onDone) {
    var t0 = performance.now();
    var duration = reduced ? 250 : 650;
    var finished = false;

    function finish() {
      if (finished) return;
      finished = true;
      if (animFrameId) cancelAnimationFrame(animFrameId);
      setProgress(100);
      if (typeof onDone === 'function') onDone();
    }

    /* Hard ceiling. Whatever happens - a stalled font promise, a blocked
       resource, an image that never fires load - the loader must come down.
       A stuck splash screen is far worse than an early one. */
    var HARD_STOP = reduced ? 800 : 2500;
    setTimeout(finish, HARD_STOP);

    function frame(now) {
      if (finished) return;

      var elapsed = (now - t0) / duration;
      if (elapsed > 1) elapsed = 1;

      var ease = 1 - Math.pow(1 - elapsed, 3);
      var target = pageLoaded ? ease * 100 : Math.min(92, ease * 100);

      currentP += (target - currentP) * 0.22;
      if (pageLoaded && elapsed >= 0.95) currentP = 100;

      setProgress(currentP);

      /* Only the pageLoaded branch can reach 100. Waiting for 99.6 while the
         target is capped at 92 was an infinite loop: currentP converges on 92
         and never crosses the threshold. Require pageLoaded explicitly. */
      if (pageLoaded && currentP >= 99.6) {
        finish();
        return;
      }

      animFrameId = requestAnimationFrame(frame);
    }

    animFrameId = requestAnimationFrame(frame);
  }

  function startInitialSequence() {
    document.body.classList.add('ld-loading');
    loader.classList.add('is-initial');

    setTimeout(function () {
      if (box) box.classList.add('filling');
      if (ui) ui.classList.add('on');
      if (tag) tag.classList.add('on');

      runInitialLoad(function () {
        setTimeout(function () {
          if (box) box.classList.add('seal');
          setTimeout(exitLoader, T_SEAL);
        }, T_HOLD);
      });
    }, T_DRAW);
  }

  function exitLoader() {
    if (isExited) return;
    isExited = true;
    loader.classList.add('exit');
    document.body.classList.remove('ld-loading');
    document.body.classList.add('ready');

    setTimeout(function () {
      loader.style.display = 'none';
    }, 450);
  }

  function showLoader(customMsg, isNavigation) {
    if (animFrameId) cancelAnimationFrame(animFrameId);
    isExited = false;
    document.body.classList.add('ld-loading');
    loader.style.display = 'flex';
    loader.classList.remove('exit');

    if (isNavigation) {
      loader.classList.add('is-nav');
      loader.classList.remove('is-initial');
    } else {
      loader.classList.remove('is-nav');
    }

    if (box) {
      box.classList.add('filling');
      box.classList.remove('seal');
    }
    if (ui) ui.classList.add('on');
    if (tag) tag.classList.add('on');

    if (customMsg) {
      customMessageActive = true;
      setMessage(customMsg, false);
    } else {
      customMessageActive = false;
    }

    setProgress(20);
    var t0 = performance.now();
    function step(now) {
      var progress = Math.min(94, 20 + (now - t0) * 0.12);
      setProgress(progress);
      if (!isExited && progress < 94) {
        animFrameId = requestAnimationFrame(step);
      }
    }
    animFrameId = requestAnimationFrame(step);
  }

  function hideLoader(finalMsg, onComplete) {
    if (animFrameId) cancelAnimationFrame(animFrameId);
    setProgress(100);

    var endMsg = finalMsg || MESSAGES[MESSAGES.length - 1][1];
    setMessage(endMsg, true);

    if (box) box.classList.add('seal');

    setTimeout(function () {
      exitLoader();
      if (typeof onComplete === 'function') onComplete();
    }, T_HOLD + T_SEAL);
  }

  window.LUFLYLoader = {
    start: startInitialSequence,
    show: showLoader,
    setProgress: setProgress,
    setMessage: function (t) { customMessageActive = true; setMessage(t, false); },
    hide: hideLoader,
    done: exitLoader
  };

  /* ---- Which navigations deserve the full-screen loader? ----
     Deny-listing product paths was the wrong way round: any route not on the
     list (categories, search, anything localized) still triggered it. The
     rule is now an explicit allow-list - only the static pages, home and
     contact, ever show the loading screen. Everything else is skeleton
     backed and navigates without it. */
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

  /* Listen for window ready.

     'load' waits for every image on the page, including the product imagery
     in database driven sections - exactly the content that is skeleton backed
     and therefore does not need to gate the loading screen. So treat the page
     as ready at DOMContentLoaded plus fonts, and let 'load' act only as a
     backstop. Static-heavy pages still get the full sequence; data sections
     fill in behind their glass placeholders. */
  function markLoaded() { pageLoaded = true; }

  /* Deliberately NOT gated on window 'load'. That event waits for every image
     on the page - the home page alone carries megabytes of photography - so
     using it meant the splash screen stayed up until the last byte arrived.
     The DOM being ready is what actually matters for revealing the layout;
     images fade in behind their skeletons afterwards.

     'load' and fonts.ready are kept purely as early signals, never as the
     only path, and a short timer guarantees the flag flips regardless. */
  window.addEventListener('load', markLoaded);

  if (document.fonts && document.fonts.ready && typeof document.fonts.ready.then === 'function') {
    document.fonts.ready.then(markLoaded).catch(markLoaded);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', markLoaded);
  } else {
    markLoaded();
  }

  /* absolute backstop */
  setTimeout(markLoaded, 1200);

  /* The same allow-list governs the first paint: landing directly on a
     catalogue, category or product URL must not show the loading screen at
     all, because those pages are skeleton backed. */
  function bootLoader() {
    /* Never let an unexpected error here leave body.ld-loading applied: that
       class sets overflow:hidden, so a throw at boot freezes the whole site. */
    try {
      bootLoaderInner();
    } catch (err) {
      releasePage();
    }
  }

  function releasePage() {
    if (loader) loader.style.display = 'none';
    document.body.classList.remove('ld-loading');
    document.body.classList.add('ready');
    isExited = true;
  }

  function bootLoaderInner() {
    if (!isLoaderPath(window.location.pathname)) {
      releasePage();
      return;
    }
    startInitialSequence();
  }

  if (document.readyState === 'complete') {
    pageLoaded = true;
    bootLoader();
  } else if (document.readyState === 'loading') {
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
    return !isLoaderPath(url.pathname);
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

    /* The full-screen loader is reserved for heavy, static-first pages.
       Product browsing (catalogue, filters, sorting, a product page) renders
       glass skeletons in place instead, so repeat navigation never feels
       gated behind an animation. */
    if (isLightNavigation(targetUrl, link)) return;

    var navMsg = locale === 'tr' ? 'Sayfa hazırlanıyor' :
                 locale === 'cs' ? 'Připravujeme stránku' : 'Loading page';
    showLoader(navMsg, true);
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
    if (!isLoaderPath(actionPath)) return;
    var submitMsg = locale === 'tr' ? 'İşleminiz gerçekleştiriliyor' :
                    locale === 'cs' ? 'Zpracováváme požadavek' : 'Processing request';
    showLoader(submitMsg, true);
  }, { passive: true });
})();
