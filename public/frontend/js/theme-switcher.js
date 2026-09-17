(function () {
  'use strict';

  var STORAGE_KEY = 'lufly-theme';
  var root = document.documentElement;

  var stored = null;
  try {
    stored = localStorage.getItem(STORAGE_KEY);
  } catch (e) { /* storage unavailable */ }

  var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
  root.setAttribute('data-theme', stored || (prefersDark ? 'dark' : 'light'));

  document.addEventListener('click', function (event) {
    var toggle = event.target.closest('[data-theme-toggle]');
    if (!toggle) { return; }

    var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    root.setAttribute('data-theme', next);
    try {
      localStorage.setItem(STORAGE_KEY, next);
    } catch (e) { /* storage unavailable */ }
  });
})();
