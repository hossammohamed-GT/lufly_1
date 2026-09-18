/* ============================================================
   LUFLY — Preloader & Global Loading Controller
   Timeline: SVG stroke draw → water fills glyphs (real progress %)
   + bubbly waves + factory stage messages → seal flash → completion
   message (with apology / patience note) → curtain exit.
   Global API: window.LUFLYLoader.show(msg), update(pct, msg), hide()
   Automatically integrates with full page load, links, forms and fetch/XHR.
   ============================================================ */

(function () {
  'use strict';

  var root = document.documentElement;
  var locale = (root.getAttribute('lang') || 'en').toLowerCase();

  var MESSAGES_BY_LOCALE = {
    ar: [
      [0,   'صب أجسام النحاس عالي النقاء'],
      [20,  'التشكيل الرقمي بدقة 0.01 مم'],
      [45,  'صقل ومعالجة طلاء PVD الفاخر'],
      [70,  'إحكام قلب السيراميك الدقيق'],
      [90,  'فحص الجودة النهائي واختبار الضغط'],
      [100, 'جاهز للبدء · نعتذر على الانتظار وشكراً لصبركم']
    ],
    en: [
      [0,   'Casting the brass bodies'],
      [20,  'CNC-machining to 0.01 mm'],
      [45,  'Polishing the PVD finish'],
      [70,  'Sealing ceramic cartridges'],
      [90,  'Final quality inspection'],
      [100, 'Ready · Thank you for your patience, sorry for the wait']
    ],
    tr: [
      [0,   'Pirinç gövdelerin dökümü'],
      [20,  '0.01 mm hassasiyetle CNC işleme'],
      [45,  'PVD kaplama parlatma işlemi'],
      [70,  'Seramik kartuşların sızdırmazlığı'],
      [90,  'Son kalite ve basınç denetimi'],
      [100, 'Hazır · Beklettiğimiz için özür diler, sabrınız için teşekkür ederiz']
    ],
    cs: [
      [0,   'Odlévání mosazných těles'],
      [20,  'CNC obrábění s přesností 0,01 mm'],
      [45,  'Leštění povrchové úpravy PVD'],
      [70,  'Těsnění keramických kartuší'],
      [90,  'Závěrečná kontrola kvality'],
      [100, 'Připraveno · Děkujeme za trpělivost a omlouváme se za čekání']
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

  if (!loader) {
    return;
  }

  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var T_DRAW   = reduced ? 150 : 1200;
  var T_HOLD   = reduced ? 250 : 650;
  var T_SEAL   = reduced ? 0   : 480;

  if (reduced) {
    loader.classList.add('ld-reduced');
  }

  /* ---- Bubbles ---- */
  if (!reduced && water && !water.querySelector('.bubble')) {
    for (var i = 0; i < 7; i++) {
      var b = document.createElement('span');
      var s = 3 + Math.random() * 5;
      b.className = 'bubble';
      b.style.width = s + 'px';
      b.style.height = s + 'px';
      b.style.left = (8 + Math.random() * 84) + '%';
      b.style.animationDuration = (1.2 + Math.random() * 1.8) + 's';
      b.style.animationDelay = (Math.random() * 2.4) + 's';
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
      if (isFinal) {
        msg.classList.add('final');
      } else {
        msg.classList.remove('final');
      }
      msg.classList.remove('swap');
    }, 200);
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

  /* ---- Progress simulation tracking real window load ---- */
  var pageLoaded = false;
  var currentP = 0;
  var animFrameId = null;

  function runInitialLoad(onDone) {
    var t0 = performance.now();
    var duration = reduced ? 600 : 2000;
    var lastProgress = 0;

    function frame(now) {
      var elapsed = (now - t0) / duration;
      if (elapsed > 1) elapsed = 1;

      // Cubic ease-out for ultra smooth mechanical glide
      var ease = 1 - Math.pow(1 - elapsed, 3);
      var target = pageLoaded ? ease * 100 : Math.min(88, ease * 100);

      // Interpolate towards target smoothly
      currentP += (target - currentP) * 0.18;
      if (pageLoaded && elapsed >= 0.98) {
        currentP = 100;
      }

      // Only update DOM if change is meaningful for maximum performance
      if (Math.abs(currentP - lastProgress) > 0.25 || currentP >= 100) {
        lastProgress = currentP;
        setProgress(currentP);
      }

      if (currentP >= 99.8) {
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
    }, 1000);
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

    setProgress(15);
    var t0 = performance.now();
    function step(now) {
      var progress = Math.min(92, 15 + (now - t0) * 0.05);
      setProgress(progress);
      if (!isExited && progress < 92) {
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

  /* ---- Auto-wire: link clicks that navigate to real internal pages ---- */
  document.addEventListener('click', function (e) {
    var link = e.target.closest('a');
    if (!link) return;
    var href = link.getAttribute('href');
    if (!href) return;

    // Ignore anchors, JS triggers, mailto, tel, whatsapp, download, external
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

    if (targetUrl.origin !== window.location.origin) {
      return;
    }

    if (targetUrl.pathname === window.location.pathname && targetUrl.search === window.location.search) {
      return;
    }

    // Show transparent preloader for the next page transition
    var navMsg = locale === 'ar' ? 'جاري تجهيز الصفحة المطلوبة' :
                 locale === 'tr' ? 'Sayfa hazırlanıyor' :
                 locale === 'cs' ? 'Připravujeme stránku' : 'Preparing the page';
    showLoader(navMsg, true);
  });

  /* ---- Auto-wire: form submissions ---- */
  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (!form || form.target === '_blank' || e.defaultPrevented) return;
    var submitMsg = locale === 'ar' ? 'جاري معالجة طلبكم والتحقق' :
                    locale === 'tr' ? 'İşleminiz gerçekleştiriliyor' :
                    locale === 'cs' ? 'Zpracováváme váš požadavek' : 'Processing your request';
    showLoader(submitMsg, true);
  });
})();
