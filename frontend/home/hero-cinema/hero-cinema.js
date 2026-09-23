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
  var HERO_PHONE_QUERY = '(max-width: 760px)';
  var PORTRAIT_FRAME_MAX = 900; /* wider than this the portrait crop would be upscaled */
  var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
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

  /* ---- Exact first-screen height (pixel-precise) ----
     The CSS calc() approximation can leave a few-px strip of the next
     section visible below the hero (sub-pixel navbar/borders, scrollbar
     quirks). Measure here instead: the hero is whatever space is left in
     the viewport below its own top edge.

     Three rules keep that measurement honest - without them the hero grows
     without bound on phones, and every extra pixel of height zooms the
     `cover` background further:

       1. `top` is read in DOCUMENT space (rect.top + scrollY). Reading the
          raw client rect made the hero grow by the scroll offset every time
          the browser fired `resize` - which mobile browsers do on almost
          every scroll, when the URL bar slides away.
       2. the viewport height is the SMALL viewport (`svh`, measured through
          a probe element). `window.innerHeight` changes when the URL bar
          hides, so the hero used to grow ~100px mid-scroll for no reason.
       3. the result is clamped: never taller than the space that is really
          left, never taller than the copy needs, and on portrait phones
          never taller than ~1.35x the screen width (past that the landscape
          artwork is cropped into an unreadable close-up).
     Everything below re-measures on resize, rotation, breakpoint change and
     whenever the chrome above the hero (announcement bar, navbar) changes
     height. */

  var probe = document.createElement('div');
  probe.setAttribute('aria-hidden', 'true');
  probe.style.cssText = 'position:absolute;top:0;left:-9999px;width:0;height:100svh;visibility:hidden;pointer-events:none;';
  (document.body || document.documentElement).appendChild(probe);

  /* Two heights matter:

       - `svhHeight()` - 100svh, the viewport with the browser toolbars
         showing. It does not change while the page scrolls.
       - `viewportHeight()` - the viewport that is visible RIGHT NOW
         (window.innerHeight, i.e. the OPEN screen once the toolbars have
         slid away). The hero is sized against this one, so it always fills
         whatever the user can actually see.

     Reading `window.innerHeight` is safe here because the height is measured
     in document space (see heroTop) - the old bug was the client rect, not
     the viewport value. `svhHeight` stays as the floor for browsers that
     report a bogus innerHeight (keyboard open, page zoom) and as the
     fallback for old iOS, which has no svh at all. */
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

  /* Top of the hero in document space: immune to scrolling. */
  function heroTop() {
    var rect = root.getBoundingClientRect();
    var scroll = window.pageYOffset || document.documentElement.scrollTop || 0;
    return Math.max(0, rect.top + scroll);
  }

  /* What the tallest slide needs to render without clipping a CTA. Every
     panel is measured, not just the visible one: the height must hold for
     all of them, otherwise switching slides would make the hero jump. */
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

    /* While the page is scrolled the hero must not chase the browser toolbar:
       growing it would push down everything the user is reading (the toolbars
       retract exactly when you scroll). The growth to the OPEN screen happens
       at the top, which is where it is the whole point. */
    var scrollY = window.pageYOffset || document.documentElement.scrollTop || 0;
    var viewport = viewportHeight();
    if (scrollY > 4) {
      var small = svhHeight();
      if (small > 0) viewport = Math.min(viewport, small);
    }

    var space = Math.round(viewport - heroTop());
    if (space < 200) return; /* hidden tab, print view, ... */

    var needed = contentHeight();

    /* The hero takes the whole visible screen - the artwork fills it edge to
       edge, exactly like on the desktop: with the browser toolbars in place
       that is the space below the navbar, and when they slide away the newly
       visible strip is filled as well. */
    var target = space;

    /* ... unless the copy is taller than the screen (very small phones, or a
       very large font): let the hero run slightly over the fold rather than
       clip the buttons */
    if (needed > space) {
      target = Math.min(needed, Math.round(space * 1.2));
    }

    target = Math.max(200, Math.round(target));

    /* The artwork follows the viewport, not just the height: every path that
       re-measures (resize, rotation, visualViewport, the chrome observers)
       also re-checks which crop file this viewport should be using. */
    var key = variantKey();
    if (key !== appliedVariant) {
      appliedVariant = key;
      applyBackgrounds(false);
    }

    if (Math.abs(target - fittedHeight) < 1) return; /* nothing to do */
    fittedHeight = target;
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

  /* the artwork first, so the initial fit sees the variant already applied
     (and the lazy preload stays lazy: only the opening scene is fetched now) */
  applyBackgrounds(true);
  appliedVariant = variantKey();

  fitToScreen();

  window.addEventListener('resize', scheduleFit, { passive: true });
  window.addEventListener('orientationchange', function () {
    scheduleFit();
    setTimeout(scheduleFit, 260); /* the UA settles its chrome afterwards */
  }, { passive: true });
  window.addEventListener('load', scheduleFit, { passive: true });

  if (window.visualViewport) {
    window.visualViewport.addEventListener('resize', scheduleFit, { passive: true });
  }
  if (phoneQuery && phoneQuery.addEventListener) {
    /* scheduleFit() re-reads the breakpoint and re-applies the artwork when it
       changed - see the variant check inside fitToScreen() */
    phoneQuery.addEventListener('change', scheduleFit);
  }
  if (portraitQuery && portraitQuery.addEventListener) {
    portraitQuery.addEventListener('change', scheduleFit);
  }
  if (document.fonts && document.fonts.ready && typeof document.fonts.ready.then === 'function') {
    document.fonts.ready.then(scheduleFit);
  }

  /* The announcement bar and the navbar are the only things above the hero.
     When either changes height (message dismissed, bar removed, custom
     property swapped) the hero re-fits immediately instead of waiting for a
     resize that may never come. */
  if (window.ResizeObserver) {
    var chromeObserver = new ResizeObserver(scheduleFit);
    ['.lufly-announcements', '.mnav'].forEach(function (selector) {
      var node = document.querySelector(selector);
      if (node) chromeObserver.observe(node);
    });
  }

  /* ---- Theme-aware responsive backgrounds ----
     Dark theme keeps the original cinematic shots; light theme swaps in the
     dedicated high-key variants. Three shapes per theme:

       -p   portrait phones   4:3.55 crop - the artwork is framed for the tall
                              box instead of being zoomed into a close-up
       -m   small landscape   tablet / narrow window crop
       ""   desktop           full landscape frame

     data-theme on <html> is watched, so the photos follow the switcher
     instantly, and the phone breakpoint is watched too, so rotating the
     device swaps artwork instead of waiting for a reload. */
  function themeIsLight() {
    return document.documentElement.getAttribute('data-theme') === 'light';
  }

  /* The breakpoint is read live, never from a cached flag: a cached one made
     the artwork lag a breakpoint behind (shrink the window and the desktop
     photo was kept in the phone box - a quarter of the frame, i.e. "zoomed";
     grow it back and the portrait crop was stretched over the wide hero). */
  function phoneNow() {
    return phoneQuery ? phoneQuery.matches : false;
  }

  function isPortrait() {
    return !portraitQuery || portraitQuery.matches;
  }

  /* Any tall box wants the portrait crop - phones, but also tablets and narrow
     desktop windows in portrait, where a landscape frame would otherwise be
     cropped to about 40% of its width. Up to ~900px wide the 800px artwork is
     still not stretched noticeably. */
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

  /* which artwork shape the current viewport wants - theme + breakpoint +
     orientation. When this changes, every scene is re-pointed at the right
     file; otherwise a stale crop stays on screen until a reload. */
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

  /* React instantly to <html> changes: the theme flips the artwork, and
     dismissing the announcement bar clears --luann-h - which moves the hero
     up the page, so its height has to be re-measured right away. */
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

  /* Small hook used by the responsive lab (tools/frontend_audit) and handy
     when debugging a live page: read what the hero measured, or force a
     re-fit after changing the page by hand. */
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
