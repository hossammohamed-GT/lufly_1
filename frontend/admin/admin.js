/* Admin panel interactions (confirmations, row selection). */
(function () {
  'use strict';

  document.addEventListener('submit', function (event) {
    var form = event.target;
    if (!(form instanceof HTMLFormElement)) { return; }
    if (!form.hasAttribute('data-confirm')) { return; }

    if (!window.confirm(form.getAttribute('data-confirm') || 'Are you sure?')) {
      event.preventDefault();
    }
  });
})();
