(function () {
  'use strict';

  var root = document.getElementById('lfc');
  if (!root) return;

  var AUTOPLAY_MS = 6500;
  var HERO_PHONE_QUERY = '(max-width: 760px)';
  var PORTRAIT_FRAME_MAX = 900; var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var finePointer = window.matchMedia && window.matchMedia('(pointer: fine)').matches;
  var phoneQuery = window.matchMedia ? window.matchMedia(HERO_PHONE_QUERY) : null;
  var portraitQuery = window.matchMedia ? window.matchMedia('(orientation: portrait)') : null;
  var isMobile = phoneQuery ? phoneQuery.matches : false;

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

  var probe = document.createElement('div');
  probe.setAttribute('aria-hidden', 'true');
  probe.style.cssText = 'position:absolute;top:0;left:-9999px;width:0;height:100svh;visibility:hidden;pointer-events:none;';
  (document.body || document.documentElement).appendChild(probe);

  var smallestViewport = Infinity;

  function svhHeight() {
    var small = probe.offsetHeight || probe.getBoundingClientRect().height || 0;
    return small > 0 ? small : 0;
  }

  function viewportHeight() {
    var small = svhHeight();
    var current = window.innerHeight || 0;

    if (current > 0 && current < smallestViewport) smallestViewport = current;

    if (small > 0) {
      return Math.max(current, Math.min(small, smallestViewport));
    }

    return Math.min(smallestViewport, current);
  }

  function heroTop() {
    var rect = root.getBoundingClientRect();
    var scroll = window.pageYOffset || document.documentElement.scrollTop || 0;
    return Math.max(0, rect.top + scroll);
  }

  function contentHeight() {
    var needed = 0;

    for (var i = 0; i < panels.length; i++) {
      var inner = panels[i].querySelector('.lfc-inner');
      if (!inner) continue;
      var cs = window.getComputedStyle(panels[i]);
      var pad = (parseFloat(cs.paddingTop) || 0) + (parseFloat(cs.paddingBottom) || 0);
      needed = Math.max(needed, inner.offsetHeight + pad);
    }

    return Math.round(needed);
  }

  var fittedHeight = 0;
  var appliedVariant = null;

  function fitToScreen() {
    isMobile = phoneNow();

    var scrollY = window.pageYOffset || document.documentElement.scrollTop || 0;
    var viewport = viewportHeight();
    if (scrollY > 4) {
      var small = svhHeight();
      if (small > 0) viewport = Math.min(viewport, small);
    }

    var space = Math.round(viewport - heroTop());
    if (space < 200) return; var needed = contentHeight();

    var target = space;

    if (needed > space) {
      target = Math.min(needed, Math.round(space * 1.2));
    }

    target = Math.max(200, Math.round(target));

    var key = variantKey();
    if (key !== appliedVariant) {
      appliedVariant = key;
      applyBackgrounds(false);
    }

    if (Math.abs(target - fittedHeight) < 1) return; fittedHeight = target;
    root.style.height = target + 'px';
  }

  var fitFrame = null;
  function scheduleFit() {
    if (fitFrame !== null) return;
    var run = function () {
      fitFrame = null;
      fitToScreen();
    };
    fitFrame = window.requestAnimationFrame ? window.requestAnimationFrame(run) : setTimeout(run, 16);
  }

  applyBackgrounds(true);
  appliedVariant = variantKey();

  fitToScreen();

  window.addEventListener('resize', scheduleFit, { passive: true });
  window.addEventListener('orientationchange', function () {
    scheduleFit();
    setTimeout(scheduleFit, 260); }, { passive: true });
  window.addEventListener('load', scheduleFit, { passive: true });

  if (window.visualViewport) {
    window.visualViewport.addEventListener('resize', scheduleFit, { passive: true });
  }
  if (phoneQuery && phoneQuery.addEventListener) {
    phoneQuery.addEventListener('change', scheduleFit);
  }
  if (portraitQuery && portraitQuery.addEventListener) {
    portraitQuery.addEventListener('change', scheduleFit);
  }
  if (document.fonts && document.fonts.ready && typeof document.fonts.ready.then === 'function') {
    document.fonts.ready.then(scheduleFit);
  }

  if (window.ResizeObserver) {
    var chromeObserver = new ResizeObserver(scheduleFit);
    ['.lufly-announcements', '.mnav'].forEach(function (selector) {
      var node = document.querySelector(selector);
      if (node) chromeObserver.observe(node);
    });
  }

  function themeIsLight() {
    return document.documentElement.getAttribute('data-theme') === 'light';
  }

  function phoneNow() {
    return phoneQuery ? phoneQuery.matches : false;
  }

  function isPortrait() {
    return !portraitQuery || portraitQuery.matches;
  }

  function portraitFrame() {
    if (!isPortrait()) return false;
    var width = window.innerWidth || 0;
    return phoneNow() || (width > 0 && width <= PORTRAIT_FRAME_MAX);
  }

  function portraitPhone() {
    return phoneNow() && isPortrait();
  }

  function sceneUrl(sc) {
    var light = themeIsLight();
    var suffix = portraitFrame() ? '-p' : (phoneNow() ? '-m' : '');
    return sc.getAttribute('data-img' + (light ? '-light' : '') + suffix)
        || sc.getAttribute(light ? 'data-img-light' : 'data-img');
  }

  function variantKey() {
    return (themeIsLight() ? 'light' : 'dark') + (
      portraitFrame() ? '-p' : (phoneNow() ? '-m' : '-d')
    );
  }
  function applyBackgrounds(lazyOthers) {
    scenes.forEach(function (sc, idx) {
      var url = sceneUrl(sc);
      if (!url) return;
      if (idx === 0 && lazyOthers) {
        sc.style.backgroundImage = 'url("' + url + '")';
      } else if (lazyOthers) {
        sc._bgUrl = url;
      } else {
        sc.style.backgroundImage = 'url("' + url + '")';
        sc._bgUrl = url;
      }
    });
  }

  if (window.MutationObserver) {
    new MutationObserver(function (muts) {
      for (var i = 0; i < muts.length; i++) {
        var attr = muts[i].attributeName;
        if (attr === 'data-theme') {
          applyBackgrounds(false);
        } else if (attr === 'class') {
          scheduleFit();
        }
      }
    }).observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme', 'class'] });
  }

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

  ticks.forEach(function (t, j) {
    t.addEventListener('click', function () { go(j); }, { passive: true });
  });
  if (prevBtn) prevBtn.addEventListener('click', function () { go(cur - 1); }, { passive: true });
  if (nextBtn) nextBtn.addEventListener('click', function () { go(cur + 1); }, { passive: true });

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

  window.LUFLYHero = {
    refit: function () {
      fittedHeight = 0;
      fitToScreen();
      return fittedHeight;
    },
    height: function () { return Math.round(root.getBoundingClientRect().height); },
    top: heroTop,
    viewport: viewportHeight,
    needed: contentHeight,
    index: function () { return cur; }
  };

  go(0);
})();
