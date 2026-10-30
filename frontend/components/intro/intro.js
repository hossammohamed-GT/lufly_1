/* ============================================================
   LUFLY - First-entry brand intro controller
   The choreography is pure CSS (intro.css); this file only
   handles skip (click / Esc) and removing the stage + scroll
   lock once the curtain has lifted. Whether the intro plays
   at all was decided pre-paint by the gate script in <head>
   (html.has-intro). The classic loader is untouched.
   ============================================================ */

(function () {
  'use strict';

  var intro = document.getElementById('siteIntro');
  if (!intro) return;

  var root = document.documentElement;

  /* Rendered but gated off (already seen this visit / reduced motion):
     the stage is display:none — just take it out of the tree. */
  if (!root.classList.contains('has-intro')) {
    if (intro.parentNode) intro.parentNode.removeChild(intro);
    return;
  }

  var done = false;

  function cleanup() {
    root.classList.remove('has-intro');
    if (intro.parentNode) intro.parentNode.removeChild(intro);
  }

  function finish() {
    if (done) return;
    done = true;
    cleanup();
  }

  function skip() {
    if (done) return;
    done = true;
    intro.classList.add('is-leaving');
    window.setTimeout(cleanup, 700);
  }

  intro.addEventListener('click', skip);
  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') skip();
  });

  /* CSS curtain finishes lifting at ~3.5s; tidy up right after.
     The second timer is the absolute backstop. */
  window.setTimeout(finish, 3750);
  window.setTimeout(finish, 5500);
})();
