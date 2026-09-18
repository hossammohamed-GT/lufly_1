/* ============================================================
   LUFLY — Preloader & Global Loading Controller (Optimized Edition)
   Timeline: SVG stroke draw → water fills glyphs (real progress %)
   + snappy fluid motion + craftsman stage messages → seal flash → completion.
   Global API: window.LUFLYLoader.show(msg), update(pct, msg), hide()
   ============================================================ */

(function () {
  'use strict';

  var root = document.documentElement;
  var locale = (root.getAttribute('lang') || 'en').toLowerCase();

  var MESSAGES_BY_LOCALE = {
    ar: [
      [0,   'سباكة أجسام النحاس المعماري عالي النقاء'],
      [20,  'التشكيل الرقمي بالروبوت بدقة 0.01 مم'],
      [45,  'صقل ومعالجة طلاء PVD التيتانيوم الفاخر'],
      [70,  'إحكام قلب السيراميك الدقيق Kerox®'],
      [90,  'الفحص المخبري للجودة واختبار الضغط'],
      [100, 'تم التجهيز بنجاح مرحباً بكم في لوفلي']
    ],
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
  var T_DRAW   = reduced ? 80 : 350;
  var T_HOLD   = reduced ? 100 : 200;
  var T_SEAL   = reduced ? 0 : 180;

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
    }, 120);
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
    var duration = reduced ? 300 : 750;
    var lastProgress = 0;

    function frame(now) {
      var elapsed = (now - t0) / duration;
      if (elapsed > 1) elapsed = 1;

      var ease = 1 - Math.pow(1 - elapsed, 3);
      var target = pageLoaded ? ease * 100 : Math.min(90, ease * 100);

      currentP += (target - currentP) * 0.25;
      if (pageLoaded && elapsed >= 0.95) currentP = 100;

      if (Math.abs(currentP - lastProgress) > 0.4 || currentP >= 100) {
        lastProgress = currentP;
        setProgress(currentP);
      }

      if (currentP >= 99.5) {
        setProgress(100);
        if (typeof onDone === 'function') onDone();
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

  /* Listen for window ready */
  window.addEventListener('load', function () {
    pageLoaded = true;
  });

  if (document.readyState === 'complete') {
    pageLoaded = true;
    startInitialSequence();
  } else if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', startInitialSequence);
  } else {
    startInitialSequence();
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

    var navMsg = locale === 'ar' ? 'جاري تجهيز الصفحة' :
                 locale === 'tr' ? 'Sayfa hazırlanıyor' :
                 locale === 'cs' ? 'Připravujeme stránku' : 'Loading page';
    showLoader(navMsg, true);
  }, { passive: true });

  /* ---- Auto-wire: form submissions ---- */
  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (!form || form.target === '_blank' || e.defaultPrevented) return;
    var submitMsg = locale === 'ar' ? 'جاري التحقق والارسال' :
                    locale === 'tr' ? 'İşleminiz gerçekleştiriliyor' :
                    locale === 'cs' ? 'Zpracováváme požadavek' : 'Processing request';
    showLoader(submitMsg, true);
  }, { passive: true });
})();
