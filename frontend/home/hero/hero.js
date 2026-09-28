/* ==========================================================================
   LUFLY - HERO "Crafting Water" engine
   --------------------------------------------------------------------------
   Vanilla JS, zero dependencies. Handles:

     * the scene stack: five full-bleed frames, cross-dissolved by toggling
       a single class (opacity + slow Ken Burns drift live in CSS, on the
       compositor)
     * the progressive image queue: frame 1 ships in the HTML, frames 2..5
       are promoted one by one after the first paint, so the visit never
       waits for five photographs
     * autoplay: every DWELL ms the next scene fades in; the timer restarts
       on interaction, pauses on hover of the selector, when the tab is
       hidden or when the hero leaves the viewport
     * the tab progress hairline (a CSS animation, restarted per scene)
     * smooth scroll for the cue + the story button
     * prefers-reduced-motion: no autoplay, no drift, a short plain fade
   ========================================================================== */
(function () {
  'use strict';

  var root = document.querySelector('[data-hero]');

  if (!root) {
    return;
  }

  var DWELL = 2000;                       /* hold per scene (ms)            */
  var FADE = 1050;                        /* cross-dissolve (ms)            */
  var PRELOAD_IDLE = 900;                 /* before the queue starts (ms)   */

  var reduceMotion = window.matchMedia
    && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  var frames = Array.prototype.slice.call(root.querySelectorAll('.hero-frame'));
  var panels = Array.prototype.slice.call(root.querySelectorAll('[data-hero-panel]'));
  var tabs = Array.prototype.slice.call(root.querySelectorAll('[data-hero-tab]'));
  var N = frames.length;

  if (N === 0) {
    return;
  }

  /* dwell drives the tab hairline in CSS */
  root.style.setProperty('--hero-dwell', (DWELL + FADE) + 'ms');

  var current = 0;
  var timer = null;
  var busy = false;
  var queued = null;                      /* a click that landed mid-fade   */
  var paused = false;

  /* ------------------------------------------------------------------
     1. progressive image queue
     The first frame ships eager + preloaded. Every other frame carries
     data-hero-src / data-hero-srcset instead: the browser would otherwise
     fetch all five photographs while the visitor is still looking at the
     first one. Promote them one at a time, in autoplay order, once the
     page has settled. When a scene's turn comes before its photo arrived,
     the switch waits for it (see goTo). */
  function promote(frame) {
    if (frame.dataset.heroDone === '1' || frame.dataset.heroReady === '1') {
      return Promise.resolve();
    }

    /* already in flight from the background queue? */
    if (frame.dataset.heroLoading === '1') {
      if (frame.complete && frame.naturalWidth > 0) {
        frame.dataset.heroDone = '1';
        return Promise.resolve();
      }

      return new Promise(function (resolve) {
        frame.addEventListener('load', function () {
          frame.dataset.heroDone = '1';
          resolve();
        }, { once: true });
        frame.addEventListener('error', function () {
          frame.dataset.heroDone = '1';
          resolve();
        }, { once: true });
      });
    }

    var src = frame.getAttribute('data-hero-src');
    var srcset = frame.getAttribute('data-hero-srcset');

    if (!src && !srcset) {
      frame.dataset.heroDone = '1';
      return Promise.resolve();
    }

    frame.dataset.heroLoading = '1';

    return new Promise(function (resolve) {
      frame.addEventListener('load', function () {
        frame.dataset.heroDone = '1';
        resolve();
      }, { once: true });
      frame.addEventListener('error', function () {
        /* a broken scene must never stall the queue */
        frame.dataset.heroDone = '1';
        resolve();
      }, { once: true });

      if (srcset) {
        frame.srcset = srcset;
      }
      if (src) {
        frame.src = src;
      }
    });
  }

  function startQueue() {
    var i = 1;

    function step() {
      if (i >= N || document.hidden) {
        if (i < N) {
          window.setTimeout(step, 1200);
        }
        return;
      }

      promote(frames[i]).then(function () {
        i += 1;
        step();
      });
    }

    step();
  }

  if (window.requestIdleCallback) {
    window.requestIdleCallback(startQueue, { timeout: PRELOAD_IDLE + 1600 });
  } else {
    window.setTimeout(startQueue, PRELOAD_IDLE);
  }

  /* ------------------------------------------------------------------
     2. scene switching
     A switch only starts once the incoming photo is decoded, so the fade
     is always a real cross-dissolve, never a blank flash. */
  function ready(frame) {
    var empty = !frame.getAttribute('src') && !frame.getAttribute('data-hero-src');

    if (empty || frame.complete) {
      var decode = typeof frame.decode === 'function' ? frame.decode() : null;
      return decode && typeof decode.catch === 'function'
        ? decode.catch(function () {})
        : Promise.resolve();
    }

    return promote(frame);
  }

  function paintTabs(next) {
    for (var i = 0; i < N; i++) {
      var panel = panels[i];
      var tab = tabs[i];

      if (tab) {
        tab.setAttribute('aria-pressed', i === next ? 'true' : 'false');
      }

      if (!panel) {
        continue;
      }

      if (i === next) {
        panel.classList.add('is-active');
        /* re-adding the class restarts the progress hairline */
        panel.classList.remove('is-active');
        void panel.offsetWidth;
        panel.classList.add('is-active');
      } else {
        panel.classList.remove('is-active');
      }
    }
  }

  function settle() {
    busy = false;

    if (queued !== null) {
      var next = queued;
      queued = null;
      goTo(next);
    } else {
      schedule();
    }
  }

  function goTo(next) {
    if (next < 0 || next >= N) {
      return;
    }

    if (busy) {
      /* remember the intent; it wins the moment the running fade ends */
      queued = next;
      return;
    }

    if (next === current) {
      schedule();
      return;
    }

    busy = true;
    window.clearTimeout(timer);

    ready(frames[next]).then(function () {
      frames[current].classList.remove('is-active');
      frames[next].classList.add('is-active');
      current = next;
      paintTabs(next);

      window.setTimeout(settle, reduceMotion ? 220 : FADE);
    });
  }

  /* ------------------------------------------------------------------
     3. autoplay */

  function schedule() {
    window.clearTimeout(timer);

    if (reduceMotion || paused || document.hidden) {
      return;
    }

    timer = window.setTimeout(function () {
      goTo((current + 1) % N);
    }, DWELL);
  }

  /* hover over the selector: let the visitor aim */
  var tabsEl = root.querySelector('[data-hero-tabs]');

  if (tabsEl) {
    tabsEl.addEventListener('mouseenter', function () {
      paused = true;
      window.clearTimeout(timer);
    }, { passive: true });
    tabsEl.addEventListener('mouseleave', function () {
      paused = false;

      if (!busy && queued === null) {
        schedule();
      }
    }, { passive: true });
  }

  /* off-screen hero: no timers, no paint */
  if (typeof IntersectionObserver === 'function') {
    var seen = false;

    new IntersectionObserver(function (entries) {
      var visible = entries[entries.length - 1].isIntersecting;

      if (visible && seen) {
        paused = false;

        if (!busy && queued === null) {
          schedule();
        }
      } else if (!visible) {
        paused = true;
        window.clearTimeout(timer);
      }

      seen = true;
    }, { threshold: 0.2 }).observe(root);
  }

  document.addEventListener('visibilitychange', function () {
    if (document.hidden) {
      window.clearTimeout(timer);
    } else if (!paused && !busy) {
      schedule();
    }
  });

  /* ------------------------------------------------------------------
     4. interaction */

  tabs.forEach(function (tab, index) {
    tab.addEventListener('click', function () {
      goTo(index);
    });
  });

  /* touch swipe on the photo: the natural gesture on phones */
  var touchX = null;

  root.addEventListener('touchstart', function (event) {
    if (event.touches.length === 1) {
      touchX = event.touches[0].clientX;
    }
  }, { passive: true });

  root.addEventListener('touchend', function (event) {
    if (touchX === null) {
      return;
    }

    var delta = event.changedTouches[0].clientX - touchX;
    touchX = null;

    if (Math.abs(delta) < 60) {
      return;
    }

    var rtl = (document.documentElement.dir || 'ltr') === 'rtl';
    var forward = rtl ? delta > 0 : delta < 0;
    goTo((current + (forward ? 1 : N - 1)) % N);
  }, { passive: true });

  /* smooth scroll for the cue and the story button */
  Array.prototype.forEach.call(
    root.querySelectorAll('[data-hero-scroll], [data-hero-story]'),
    function (link) {
      link.addEventListener('click', function (event) {
        var hash = link.getAttribute('href') || '';
        var node = hash.charAt(0) === '#' && hash.length > 1
          ? document.querySelector(hash)
          : null;

        if (!node) {
          return;
        }

        event.preventDefault();
        node.scrollIntoView({
          behavior: reduceMotion ? 'auto' : 'smooth',
          block: 'start'
        });
      });
    }
  );

  /* ------------------------------------------------------------------
     5. boot */

  paintTabs(0);
  schedule();
})();
