/* ============================================================
   Contact enquiry form.

   There is no mail backend on this site, so the form composes the answers
   into a single e-mail draft addressed to the export desk and opens the
   visitor's mail app with it. That keeps one written channel rather than
   promising an inbox nobody reads, and nothing the user types is stored or
   transmitted anywhere else.
   ============================================================ */

(function () {
  'use strict';

  function val(form, name) {
    var el = form.elements[name];
    return el && el.value ? el.value.trim() : '';
  }

  function init() {
    var form = document.querySelector('[data-contact-form]');
    if (!form) return;

    var address = form.getAttribute('data-mail-to') || '';

    form.addEventListener('submit', function (e) {
      e.preventDefault();

      /* let the browser surface its own validation messages */
      if (typeof form.reportValidity === 'function' && !form.reportValidity()) return;

      var lines = [
        'Hello LUFLY team,',
        '',
        'Name: ' + val(form, 'name'),
        'Email: ' + val(form, 'email')
      ];

      var company = val(form, 'company');
      if (company) lines.push('Company: ' + company);

      var country = val(form, 'country');
      if (country) lines.push('Country: ' + country);

      var type = val(form, 'type');
      lines.push('Enquiry: ' + type);
      lines.push('');
      lines.push(val(form, 'message'));
      lines.push('');
      lines.push('Thank you.');

      var subject = 'Project enquiry' + (type ? ' — ' + type : '');
      var url = 'mailto:' + address +
        '?subject=' + encodeURIComponent(subject) +
        '&body=' + encodeURIComponent(lines.join('\n'));

      window.location.href = url;
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
