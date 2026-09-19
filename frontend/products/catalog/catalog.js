/* ============================================================
   Catalog: the sort select re-renders live search results
   instantly; without an active live search it submits the form.
   ============================================================ */

(function () {
  'use strict';

  function init() {
    var select = document.querySelector('[data-catalog-sort]');
    if (!select || !select.form) return;

    select.addEventListener('change', function () {
      if (select.form.classList.contains('is-live')) {
        /* live search owns the grid: re-sort in place, no reload */
        select.form.dispatchEvent(new CustomEvent('livesearch:rerender'));
        return;
      }
      select.form.submit();
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
