/* ==========================================================================
   LUFLY - HERO "Crafting Water" engine
   --------------------------------------------------------------------------
   Vanilla JS, zero dependencies. Handles:

     * the scene stack: four full-bleed frames, handed off from the side
       with two compositor-friendly layers (transform + opacity)
     * the progressive image queue: frame 1 ships in the HTML, frames 2..4
       are promoted one by one after the first paint, so the visit never
       waits for four photographs
     * autoplay: every DWELL ms the next scene fades in; the timer restarts
       on interaction, pauses on hover of the selector, when the tab is
       hidden or when the hero leaves the viewport
     * the tab progress hairline (a CSS animation, restarted per scene)
     * smooth scroll for the cue + the story button
     * dark / light theme treatment: the copy-side wash and typography
       adjust when the site theme flips, while the image master stays stable
     * the launch sequence: the Explore Collections arrow spirals into
       itself and the hero settles back before the catalogue takes over
     * prefers-reduced-motion: no autoplay, no drift, a short plain fade
   ========================================================================== */
(function () {
  'use strict';

  var root = document.querySelector('[data-hero]');

  if (!root) {
    return;
  }

  var DWELL = 3500;                       /* hold per scene (ms)            */
  var FADE = 780;                         /* side handoff (ms)              */
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
     data-hero-src instead: the browser would otherwise fetch all four
     photographs while the visitor is still looking at the first one.
     Promote them one at a time, in autoplay order, once the page has
     settled. When a scene's turn comes before its photo arrived, the
     switch waits for it (see goTo). Every frame is the same master-quality
     file - only the loading ORDER is progressive, never the quality. */
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

    if (!src) {
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

      frame.src = src;
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
     A switch only starts once the incoming photo is decoded, so the two
     frames can hand off side-to-side without exposing a blank flash. */
  function ready(frame) {
    /* An <img> without src can report complete=true even though its
     * data-hero-src has not been promoted. Promote first; only decode a
     * frame that already has a real request, otherwise a fast click could
     * expose an empty incoming layer. */
    if (!frame.getAttribute('src')) {
      return promote(frame);
    }

    if (frame.complete) {
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
      var previous = frames[current];
      var incoming = frames[next];
      var forward = next > current || (current === N - 1 && next === 0);
      var rtl = (document.documentElement.dir || 'ltr') === 'rtl';

      /* Mirror the handoff in RTL so the new scene still arrives from the
         reading-direction side. */
      if (rtl) {
        forward = !forward;
      }

      var enteringClass = forward ? 'is-entering-right' : 'is-entering-left';
      var leavingClass = forward ? 'is-leaving-left' : 'is-leaving-right';

      incoming.classList.remove('is-leaving-left', 'is-leaving-right');
      incoming.classList.add(enteringClass);
      previous.classList.add(leavingClass);

      /* Capture the incoming off-canvas state, then let the active state
         transition it into place. The old frame remains underneath until
         this movement is complete, so there is never a blank frame. */
      void incoming.offsetWidth;
      incoming.classList.add('is-active');
      incoming.classList.remove('is-entering-left', 'is-entering-right');

      current = next;
      paintTabs(next);

      window.setTimeout(function () {
        previous.classList.remove('is-active', 'is-leaving-left', 'is-leaving-right');
        settle();
      }, reduceMotion ? 220 : FADE);
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
     4.5 the launch sequence (Explore Collections)
     The primary CTA is the one playful moment on the page: on a plain
     left click the arrow spirals into itself, ripple rings pulse out of
     the button and the hero settles back - only then does the catalogue
     take over. Modified clicks (new tab / new window), non-left buttons
     and reduced-motion visitors skip the show and navigate at once. */
  (function () {
    var cta = root.querySelector('.hero-cta--primary');

    if (!cta) {
      return;
    }

    var NAVIGATE_MS = 460;               /* end of the CSS choreography  */
    var launching = false;

    function launch(event) {
      if (event.defaultPrevented) {
        return;
      }

      if (event.button !== 0 || event.metaKey || event.ctrlKey
        || event.shiftKey || event.altKey) {
        return;                          /* the browser knows better      */
      }

      var href = cta.getAttribute('href') || '';

      if (!href || href.charAt(0) === '#') {
        return;
      }

      if (reduceMotion) {
        return;                          /* plain navigation, no show     */
      }

      /* swallow every plain click from here on: a mid-flight double click
       * must never race the choreography to the catalogue */
      event.preventDefault();

      if (launching) {
        return;
      }

      launching = true;
      root.classList.add('is-launching');

      window.setTimeout(function () {
        if (window.location) {
          window.location.href = href;
        }
      }, NAVIGATE_MS);
    }

    cta.addEventListener('click', launch);

    /* warm the catalogue while the visitor is still deciding - the page
     * is usually already in the cache when the spiral finishes */
    if (document.createElement && !reduceMotion) {
      var warmed = false;

      var warm = function () {
        if (warmed) {
          return;
        }
        warmed = true;

        try {
          var link = document.createElement('link');
          link.rel = 'prefetch';
          link.href = cta.getAttribute('href') || '';
          if (document.head) {
            document.head.appendChild(link);
          }
        } catch (err) { /* prefetch is a bonus, never a requirement */ }
      };

      cta.addEventListener('pointerenter', warm);
      cta.addEventListener('focus', warm);
    }

    /* coming back through the back button (bfcache): the hero must look
     * untouched again, so a new click can play the show once more */
    if (window.addEventListener) {
      window.addEventListener('pageshow', function (event) {
        if (!event || !event.persisted) {
          return;
        }
        launching = false;
        root.classList.remove('is-launching');
      });
    }
  })();

  /* ------------------------------------------------------------------
     5. boot
     The CSS owns the light/dark shade treatment; the image queue only
     controls when each single master image is decoded. */
  paintTabs(0);
  schedule();
})();
