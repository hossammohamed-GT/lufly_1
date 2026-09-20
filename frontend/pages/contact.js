/* ============================================================
   Contact enquiry form.

   There is no mail backend on this site, so the form composes the answers
   into a single WhatsApp message and opens the chat. That keeps one reply
   channel rather than promising an inbox nobody reads, and nothing the user
   types is stored or transmitted anywhere else.
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

    var number = form.getAttribute('data-wa-number') || '';

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

      lines.push('Enquiry: ' + val(form, 'type'));
      lines.push('');
      lines.push(val(form, 'message'));

      var url = 'https://wa.me/' + number + '?text=' + encodeURIComponent(lines.join('\n'));
      window.open(url, '_blank', 'noopener');
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
