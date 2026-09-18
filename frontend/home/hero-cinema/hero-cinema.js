/* ==========================================================================
   LUFLY - HERO CINEMA engine (High Performance Edition)
   Vanilla JS, zero dependencies, zero icon font polling.
   Handles: responsive background swap, lightweight Ken-Burns scheduling,
   progress-fill ticks, arrows, touch swipe, pointer parallax,
   and instant pause when off-screen or tab is hidden.
   ========================================================================== */
(function () {
  'use strict';

  var root = document.getElementById('lfc');
  if (!root) return;

  var AUTOPLAY_MS = 6500;
  var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var isMobile = window.matchMedia && window.matchMedia('(max-width: 760px)').matches;
  var finePointer = window.matchMedia && window.matchMedia('(pointer: fine)').matches;

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

  /* ---- Viewport fit ---- */
  function fitHeight() {
    var top = root.getBoundingClientRect().top + (window.scrollY || 0);
    var h = window.innerHeight - top;
    root.style.height = Math.max(480, Math.round(h)) + 'px';
  }
  fitHeight();
  window.addEventListener('resize', fitHeight, { passive: true });

  /* ---- Responsive backgrounds ---- */
  scenes.forEach(function (sc, idx) {
    var url = isMobile ? (sc.getAttribute('data-img-m') || sc.getAttribute('data-img'))
                       : sc.getAttribute('data-img');
    if (idx === 0 && url) {
      sc.style.backgroundImage = 'url("' + url + '")';
    } else if (url) {
      sc._bgUrl = url;
    }
  });

  /* Lazy-load subsequent backgrounds on idle */
  if ('requestIdleCallback' in window) {
    window.requestIdleCallback(function () {
      scenes.forEach(function (sc) {
        if (sc._bgUrl) sc.style.backgroundImage = 'url("' + sc._bgUrl + '")';
      });
    });
  } else {
    setTimeout(function () {
      scenes.forEach(function (sc) {
        if (sc._bgUrl) sc.style.backgroundImage = 'url("' + sc._bgUrl + '")';
      });
    }, 400);
  }

  var cur = -1;
  var timer = null;
  var running = true;

  function armTick(i) {
    ticks.forEach(function (t, j) {
      t.classList.toggle('act', j === i);
      if (j === i) {
        var bar = t.querySelector('.lfc-bar');
        if (bar) {
          var f = document.createElement('i');
          bar.replaceChildren ? bar.replaceChildren(f) : (bar.innerHTML = '<i></i>');
        }
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

    if (next._bgUrl && !next.style.backgroundImage) {
      next.style.backgroundImage = 'url("' + next._bgUrl + '")';
    }

    next.style.zIndex = 2;
    if (prev) prev.style.zIndex = 1;
    next.classList.remove('kb1', 'kb2', 'kb3', 'kb4');
    void next.offsetWidth;
    next.classList.add('is-on', 'kb' + ((i % 4) + 1));
    if (prev) {
      setTimeout(function () {
        prev.classList.remove('is-on');
        prev.style.zIndex = 0;
      }, 1200);
    }

    panels.forEach(function (p, j) { p.classList.toggle('is-on', j === i); });
    armTick(i);
    cur = i;
    schedule();
  }

  /* ---- Controls ---- */
  ticks.forEach(function (t, j) {
    t.addEventListener('click', function () { go(j); }, { passive: true });
  });
  if (prevBtn) prevBtn.addEventListener('click', function () { go(cur - 1); }, { passive: true });
  if (nextBtn) nextBtn.addEventListener('click', function () { go(cur + 1); }, { passive: true });

  /* ---- Touch swipe ---- */
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

  /* ---- Pause when hidden or scrolled away ---- */
  function setRunning(active) {
    running = active;
    if (running) schedule();
    else clearTimeout(timer);
  }

  document.addEventListener('visibilitychange', function () {
    setRunning(!document.hidden);
  });
  if ('IntersectionObserver' in window) {
    new IntersectionObserver(function (entries) {
      setRunning(entries[0].isIntersecting && !document.hidden);
    }, { threshold: 0.05 }).observe(root);
  }

  /* ---- Pointer parallax (Desktop fine pointers only) ---- */
  if (finePointer && !reduceMotion && !isMobile) {
    var tx = 0, ty = 0, px = 0, py = 0, raf = null;
    root.addEventListener('mousemove', function (e) {
      var r = root.getBoundingClientRect();
      tx = (e.clientX - r.left) / r.width - 0.5;
      ty = (e.clientY - r.top) / r.height - 0.5;
      if (!raf) raf = requestAnimationFrame(tickPar);
    }, { passive: true });

    function tickPar() {
      px += (tx - px) * 0.06;
      py += (ty - py) * 0.06;
      if (sceneLayer) sceneLayer.style.transform = 'translate3d(' + (px * -16).toFixed(2) + 'px,' + (py * -10).toFixed(2) + 'px,0)';
      if (stage) stage.style.transform = 'translate3d(' + (px * 8).toFixed(2) + 'px,' + (py * 5).toFixed(2) + 'px,0)';
      if (Math.abs(tx - px) > 0.002 || Math.abs(ty - py) > 0.002) {
        raf = requestAnimationFrame(tickPar);
      } else {
        raf = null;
      }
    }
  }

  go(0);
})();
