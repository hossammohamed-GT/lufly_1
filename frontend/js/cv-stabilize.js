/* ==========================================================================
   cv-stabilize: turn on content-visibility only when it cannot jump.
   ==========================================================================
   The home bands and the mega footer carry `content-visibility: auto` in
   app.css - but gated behind `body.cv-on`, which only this script adds.

   Why the gate: with content-visibility on from the first paint, a section
   the visitor has not reached yet is laid out at its estimated
   contain-intrinsic-size. On a fast scroll the section enters the viewport,
   gets rendered at its REAL height, and the document shifts under the
   visitor - the page appears to jump backwards a little. Exactly the kind
   of thing you feel around the category mosaic and never quite see.

   What this does instead: after load (fonts settled, and every band's media
   is aspect-boxed so heights are already final) it measures each band's real
   height, writes it inline as the intrinsic size, and only then switches
   content-visibility on. The estimate is never allowed to disagree with the
   rendered height, so there is nothing left to jump.

   On a real width change (rotate, resize - not the phone URL bar sliding)
   it measures again: off-screen sizes are re-read from plain layout, never
   from a content-visibility placeholder.
   ========================================================================== */
(function () {
  'use strict';

  var BANDS = [
    '.trust-bar',
    '.finishes-section',
    '.categories-section',
    '.inspiration-section',
    '.rituals-section',
    '.masterpieces-section',
    '.corporate-section',
    '.lufly-mega-footer'
  ];

  var CLASS_ON = 'cv-on';

  function bands() {
    var found = [];
    for (var i = 0; i < BANDS.length; i++) {
      var els = document.querySelectorAll(BANDS[i]);
      for (var j = 0; j < els.length; j++) {
        found.push(els[j]);
      }
    }
    return found;
  }

  function enable() {
    var els = bands();
    if (!els.length || !document.body) {
      return;
    }

    /* every read before every write: one forced layout for the whole page,
       not one per band */
    var heights = [];
    for (var i = 0; i < els.length; i++) {
      heights.push(els[i].offsetHeight);
    }
    for (var k = 0; k < els.length; k++) {
      if (heights[k] > 0) {
        els[k].style.containIntrinsicSize = 'auto ' + heights[k] + 'px';
      }
    }
    document.body.classList.add(CLASS_ON);
  }

  function disable() {
    if (!document.body) {
      return;
    }
    document.body.classList.remove(CLASS_ON);
    var els = bands();
    for (var i = 0; i < els.length; i++) {
      els[i].style.containIntrinsicSize = '';
    }
  }

  /* ---------- schedule: after load, after fonts, then when idle ---------- */

  function whenSettled(callback) {
    var waiting = 2;

    function done() {
      waiting -= 1;
      if (waiting === 0) {
        var ric = window.requestIdleCallback;
        if (typeof ric === 'function') {
          ric(callback, { timeout: 1200 });
        } else {
          window.setTimeout(callback, 0);
        }
      }
    }

    if (document.readyState === 'complete') {
      done();
    } else {
      window.addEventListener('load', done, { once: true });
    }

    if (document.fonts && typeof document.fonts.ready === 'object' && typeof document.fonts.ready.then === 'function') {
      document.fonts.ready.then(done, done);
    } else {
      done();
    }
  }

  /* ---------- width changes: re-measure from plain layout ---------- */

  var width = window.innerWidth;
  var recheckTimer = null;

  window.addEventListener('resize', function () {
    if (window.innerWidth === width) {
      return; /* the phone URL bar sliding is a resize without a width change */
    }
    width = window.innerWidth;
    if (recheckTimer !== null) {
      window.clearTimeout(recheckTimer);
    }
    recheckTimer = window.setTimeout(function () {
      recheckTimer = null;
      /* off: so the measurement below reads real layout, not a placeholder */
      disable();
      enable();
    }, 200);
  }, { passive: true });

  whenSettled(enable);
})();
