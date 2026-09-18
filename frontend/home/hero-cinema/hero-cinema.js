/* ==========================================================================
   LUFLY — HERO CINEMA engine
   Vanilla JS, no dependencies. Markup-driven (panels/scenes rendered by
   PHP). Handles: responsive image swap, crossfade + Ken-Burns scheduling,
   progress-fill ticks, arrows, touch swipe, pointer parallax, the Font Awesome
   hand-over for the icon glyphs, and pauses when off-screen / hidden /
   reduced-motion.
   ========================================================================== */
(function () {
  'use strict';

  var root = document.getElementById('lfc');
  if (!root) return;

  var AUTOPLAY_MS = 6500;
  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var isMobile = window.matchMedia('(max-width: 760px)').matches;
  var finePointer = window.matchMedia('(pointer: fine)').matches;

  root.style.setProperty('--lfc-dur', AUTOPLAY_MS + 'ms');
  if (reduceMotion) root.classList.add('lfc-reduced');

  var sceneLayer = root.querySelector('.lfc-scenes');
  var stage = root.querySelector('.lfc-stage');
  var scenes = Array.prototype.slice.call(root.querySelectorAll('.lfc-scene'));
  var panels = Array.prototype.slice.call(root.querySelectorAll('.lfc-panel'));
  var ticks = Array.prototype.slice.call(root.querySelectorAll('.lfc-tick'));
  var prevBtn = document.getElementById('lfc-prev');
  var nextBtn = document.getElementById('lfc-next');
  var N = scenes.length;
  if (N === 0) return;

  /* ---- Font Awesome hand-over ------------------------------------------
     The icon stylesheet is loaded without blocking the first paint, so the
     arrow glyphs may arrive a moment late (hero-cinema.css paints CSS chevrons
     until then). .fa-ready is only flipped when the icon stylesheet has really
     applied AND its webfont is usable, and it keeps listening, so a slow CDN
     still ends up with the glyphs — while an unreachable one simply leaves the
     CSS chevrons in place. The arrows are therefore never blank. */
  (function handOverToIconFont() {
    var probe = null;
    var settled = false;
    var retries = 0;
    var waits = [250, 700, 1500, 3000, 6000];

    function stylesheetApplied() {
      if (!probe) {
        probe = document.createElement('i');
        probe.className = 'fa-solid fa-chevron-left';
        probe.setAttribute('aria-hidden', 'true');
        probe.style.cssText = 'position:absolute;left:-9999px;top:0;font-size:16px;line-height:1;';
        document.body.appendChild(probe);
      }

      var family = window.getComputedStyle(probe, '::before').fontFamily || '';

      return family.indexOf('Font Awesome') !== -1;
    }

    function fontUsable() {
      if (!document.fonts || typeof document.fonts.check !== 'function') {
        return true; /* no font loading API: trust the stylesheet */
      }

      return document.fonts.check('900 16px "Font Awesome 6 Free"');
    }

    function cleanup() {
      if (probe && typeof probe.remove === 'function') {
        probe.remove();
      }

      probe = null;
    }

    function finish() {
      if (settled) {
        return;
      }

      settled = true;
      document.documentElement.classList.add('fa-ready');
      cleanup();
    }

    function attempt() {
      if (settled) {
        return;
      }

      if (stylesheetApplied() && fontUsable()) {
        finish();
        return;
      }

      if (retries < waits.length) {
        window.setTimeout(attempt, waits[retries]);
        retries += 1;
      } else {
        cleanup();
      }
    }

    /* late arrivals still get handed over, however slow the network is */
    if (document.fonts && typeof document.fonts.addEventListener === 'function') {
      document.fonts.addEventListener('loadingdone', attempt);

      if (document.fonts.ready && typeof document.fonts.ready.then === 'function') {
        document.fonts.ready.then(attempt);
      }
    }

    attempt();
  })();

  /* ---- fit exactly the first screen: viewport minus whatever sits
          above the hero (the site header is in normal flow) ---- */
  function fitHeight() {
    var top = root.getBoundingClientRect().top + (window.scrollY || 0);
    var h = window.innerHeight - top;
    root.style.height = Math.max(480, Math.round(h)) + 'px';
  }
  fitHeight();
  window.addEventListener('resize', fitHeight);
  window.addEventListener('load', fitHeight);

  /* ---- responsive backgrounds (mobile gets the light _m variant) ---- */
  scenes.forEach(function (sc) {
    var url = isMobile ? (sc.getAttribute('data-img-m') || sc.getAttribute('data-img'))
                       : sc.getAttribute('data-img');
    if (url) sc.style.backgroundImage = 'url("' + url + '")';
  });

  /* ---- idle preload of every scene (desktop variant too, one pass) ---- */
  window.addEventListener('load', function () {
    scenes.forEach(function (sc) {
      ['data-img', 'data-img-m'].forEach(function (k) {
        var u = sc.getAttribute(k);
        if (u) { var im = new Image(); im.src = u; }
      });
    });
  });

  var cur = -1;
  var timer = null;
  var running = true;

  function armTick(i) {
    ticks.forEach(function (t, j) {
      t.classList.toggle('act', j === i);
      if (j === i) {
        var bar = t.querySelector('.lfc-bar');
        if (bar) { var f = document.createElement('i'); bar.replaceChildren(f); }
      }
    });
  }

  function schedule() {
    clearTimeout(timer);
    if (running && !reduceMotion) {
      timer = setTimeout(function () { go(cur + 1); }, AUTOPLAY_MS);
    }
  }

  function go(i) {
    i = ((i % N) + N) % N;
    if (i === cur) return;
    var prev = scenes[cur];
    var next = scenes[i];

    next.style.zIndex = 2;
    if (prev) prev.style.zIndex = 1;
    next.classList.remove('kb1', 'kb2', 'kb3', 'kb4');
    void next.offsetWidth; /* restart the Ken-Burns animation */
    next.classList.add('is-on', 'kb' + ((i % 4) + 1));
    if (prev) {
      (function (p) {
        setTimeout(function () { p.classList.remove('is-on'); p.style.zIndex = 0; }, 1500);
      })(prev);
    }

    panels.forEach(function (p, j) { p.classList.toggle('is-on', j === i); });
    armTick(i);
    cur = i;
    schedule();
  }

  /* ---- controls ---- */
  ticks.forEach(function (t, j) { t.addEventListener('click', function () { go(j); }); });
  if (prevBtn) prevBtn.addEventListener('click', function () { go(cur - 1); });
  if (nextBtn) nextBtn.addEventListener('click', function () { go(cur + 1); });

  /* ---- touch swipe ---- */
  var sx = null;
  root.addEventListener('pointerdown', function (e) {
    if (e.pointerType === 'touch') sx = e.clientX;
  }, { passive: true });
  root.addEventListener('pointerup', function (e) {
    if (sx === null) return;
    var dx = e.clientX - sx;
    sx = null;
    if (Math.abs(dx) > 48) go(cur + (dx < 0 ? 1 : -1));
  }, { passive: true });

  /* ---- pause when hidden / scrolled away ---- */
  document.addEventListener('visibilitychange', function () {
    running = !document.hidden;
    if (running) schedule(); else clearTimeout(timer);
  });
  if ('IntersectionObserver' in window) {
    new IntersectionObserver(function (en) {
      running = en[0].isIntersecting && !document.hidden;
      if (running) schedule(); else clearTimeout(timer);
    }, { threshold: 0.12 }).observe(root);
  }

  /* ---- pointer parallax (desktop, fine pointers only) ---- */
  if (finePointer && !reduceMotion && !isMobile) {
    var tx = 0, ty = 0, px = 0, py = 0, raf = null;
    root.addEventListener('mousemove', function (e) {
      var r = root.getBoundingClientRect();
      tx = (e.clientX - r.left) / r.width - 0.5;
      ty = (e.clientY - r.top) / r.height - 0.5;
      if (!raf) raf = requestAnimationFrame(tickPar);
    });
    function tickPar() {
      px += (tx - px) * 0.055;
      py += (ty - py) * 0.055;
      sceneLayer.style.transform = 'translate3d(' + (px * -18) + 'px,' + (py * -12) + 'px,0)';
      stage.style.transform = 'translate3d(' + (px * 9) + 'px,' + (py * 6) + 'px,0)';
      if (Math.abs(tx - px) > 0.001 || Math.abs(ty - py) > 0.001) {
        raf = requestAnimationFrame(tickPar);
      } else { raf = null; }
    }
  }

  go(0);
})();
