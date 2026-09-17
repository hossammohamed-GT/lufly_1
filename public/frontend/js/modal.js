(function () {
  'use strict';

  function open(modal) {
    if (modal) { modal.hidden = false; }
  }

  function close(modal) {
    if (modal) { modal.hidden = true; }
  }

  document.addEventListener('click', function (event) {
    var opener = event.target.closest('[data-modal-open]');
    if (opener) {
      open(document.getElementById(opener.getAttribute('data-modal-open')));
      return;
    }

    var closer = event.target.closest('[data-modal-close]');
    if (closer) {
      close(closer.closest('[data-modal]'));
      return;
    }

    if (event.target.matches && event.target.matches('[data-modal]')) {
      close(event.target);
    }
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
      document.querySelectorAll('[data-modal]:not([hidden])').forEach(close);
    }
  });
})();
