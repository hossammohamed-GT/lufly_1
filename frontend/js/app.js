/* App bootstrap: theme + modal plumbing shared by the frontend and the panel.
   Header, navigation, search and language behaviour live in
   frontend/components/navbar/navbar.js (loaded with the navbar component). */
(function () {
  'use strict';

  var root = document.documentElement;
  var STORAGE_KEY = 'lufly-theme';

  /* ---------- Theme ---------- */

  function storedTheme() {
    try {
      return localStorage.getItem(STORAGE_KEY);
    } catch (error) {
      return null;
    }
  }

  function saveTheme(theme) {
    try {
      localStorage.setItem(STORAGE_KEY, theme);
    } catch (error) {
      /* storage unavailable */
    }
  }

  function preferredTheme() {
    var stored = storedTheme();

    if (stored) {
      return stored;
    }

    var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;

    return prefersDark ? 'dark' : 'light';
  }

  function applyTheme(theme) {
    root.setAttribute('data-theme', theme);

    document.dispatchEvent(new CustomEvent('lufly:theme', { detail: { theme: theme } }));
  }

  function toggleTheme() {
    var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';

    applyTheme(next);
    saveTheme(next);
  }

  applyTheme(preferredTheme());

  document.addEventListener('click', function (event) {
    var toggle = event.target.closest('[data-theme-toggle], #lufly-theme-toggle-btn');

    if (!toggle) {
      return;
    }

    event.preventDefault();
    toggleTheme();
  });

  /* ---------- Modal ---------- */

  function setModal(modal, open) {
    if (!modal) {
      return;
    }

    modal.hidden = !open;
  }

  document.addEventListener('click', function (event) {
    var opener = event.target.closest('[data-modal-open]');

    if (opener) {
      setModal(document.getElementById(opener.getAttribute('data-modal-open')), true);
      return;
    }

    var closer = event.target.closest('[data-modal-close]');

    if (closer) {
      setModal(closer.closest('[data-modal]'), false);
      return;
    }

    if (event.target.matches && event.target.matches('[data-modal]')) {
      setModal(event.target, false);
    }
  });

  document.addEventListener('keydown', function (event) {
    if (event.key !== 'Escape') {
      return;
    }

    document.querySelectorAll('[data-modal]:not([hidden])').forEach(function (modal) {
      setModal(modal, false);
    });
  });
})();
