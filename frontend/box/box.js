/*
 * The quotation box — the list a visitor fills with the pieces he wants priced.
 *
 * One script, two places:
 *
 *   * the box page   — the list, the "send it to the team" form, the permanent
 *                      link and the button that empties it
 *   * anywhere else  — the little box button on every card the finder shows
 *
 * Every button is wired by delegation on the document, so a card that the chat
 * draws a second later works exactly like one that was in the page from the
 * start. The count in the chat's footer follows along, and a `box:changed`
 * event is raised so the chat can say what just happened.
 */
(function () {
  'use strict';

  var page = document.querySelector('[data-box-page]');
  var token = '';

  function csrf() {
    var meta = document.querySelector('meta[name="csrf-token"]');
    if (meta) token = meta.getAttribute('content') || '';
    return token;
  }

  function endpoint(node) {
    if (node && node.getAttribute('data-box-endpoint')) return node.getAttribute('data-box-endpoint');
    if (page) return page.getAttribute('data-box-endpoint') || '';
    return '';
  }

  function fromPage(key) {
    return page ? (page.getAttribute('data-' + key) || '') : '';
  }

  function post(url, payload) {
    var body = new URLSearchParams();
    body.set('_token', csrf());
    Object.keys(payload || {}).forEach(function (key) { body.set(key, payload[key]); });

    return fetch(url, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'X-CSRF-TOKEN': token,
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
      },
      body: body.toString()
    }).then(function (response) {
      return response.json().catch(function () { return {}; }).then(function (json) {
        json = json || {};
        json.status = response.status;
        return json;
      });
    });
  }

  function say(message) {
    if (!message) return;

    var host = document.querySelector('[data-box-status]');

    if (!host) {
      host = document.createElement('div');
      host.className = 'box-toast';
      host.setAttribute('data-box-status', '');
      host.setAttribute('role', 'status');
      host.setAttribute('aria-live', 'polite');
      document.body.appendChild(host);
    }

    host.textContent = message;
    host.classList.remove('is-on');
    void host.offsetWidth;
    host.classList.add('is-on');

    window.clearTimeout(say.timer);
    say.timer = window.setTimeout(function () { host.classList.remove('is-on'); }, 3800);
  }

  function paintCount(count) {
    if (typeof count !== 'number') return;

    var nodes = document.querySelectorAll('[data-box-count]');

    for (var i = 0; i < nodes.length; i++) {
      nodes[i].textContent = String(count);
    }
  }

  function paintProduct(productId, on) {
    var buttons = document.querySelectorAll('[data-box-add][data-box-product="' + productId + '"]');

    for (var i = 0; i < buttons.length; i++) {
      var label = buttons[i].querySelector('[data-box-label]');

      paint(buttons[i], on);
      if (label) label.textContent = on ? (buttons[i].dataset.boxLabelOn || label.textContent) : (buttons[i].dataset.boxLabelOff || label.textContent);
    }
  }

  function paint(button, on) {
    button.classList.toggle('is-on', on);
    button.setAttribute('aria-pressed', on ? 'true' : 'false');
    button.setAttribute('title', on ? (button.dataset.boxLabelOn || '') : (button.dataset.boxLabelOff || ''));
  }

  function announce(detail) {
    document.dispatchEvent(new CustomEvent('box:changed', { detail: detail }));
  }

  /* ---------- add / take out ---------- */

  document.addEventListener('click', function (event) {
    var add = event.target.closest ? event.target.closest('[data-box-add]') : null;

    if (add) {
      event.preventDefault();
      event.stopPropagation();

      if (add.classList.contains('is-busy')) return;

      var productId = add.getAttribute('data-box-product');
      var url = endpoint(add);

      if (!productId || !url) return;

      add.classList.add('is-busy');
      add.disabled = true;

      post(url, { product_id: productId }).then(function (json) {
        add.classList.remove('is-busy');
        add.disabled = false;

        var data = json.data || {};

        if (!json.success) {
          say(json.message || fromPage('box-removed'));
          return;
        }

        paintProduct(productId, !!data.added);
        paintCount(data.count);
        say(data.message);
        announce({ productId: Number(productId), added: !!data.added, count: data.count, message: data.message });
      }).catch(function () {
        add.classList.remove('is-busy');
        add.disabled = false;
      });

      return;
    }

    var remove = event.target.closest ? event.target.closest('[data-box-remove]') : null;

    if (!remove) return;

    event.preventDefault();

    var removeId = remove.getAttribute('data-box-product');
    var removeUrl = remove.getAttribute('data-box-endpoint') || (page ? page.getAttribute('data-box-endpoint') : '');

    if (!removeId || !removeUrl) return;

    remove.disabled = true;

    post(removeUrl, { product_id: removeId }).then(function (json) {
      if (!json.success) {
        remove.disabled = false;
        say(json.message);
        return;
      }

      var data = json.data || {};
      var card = document.querySelector('[data-box-card="' + removeId + '"]');

      if (card && card.parentNode) card.parentNode.removeChild(card);

      paintCount(data.count);
      announce({ productId: Number(removeId), added: false, count: data.count, message: data.message });

      if (data.count === 0) {
        window.location.reload();
      }
    });
  });

  /* ---------- the send form ---------- */

  var sendForm = document.querySelector('[data-box-send-form]');

  if (sendForm) {
    sendForm.addEventListener('submit', function (event) {
      event.preventDefault();

      var button = sendForm.querySelector('[data-box-send-submit]');
      var label = sendForm.querySelector('[data-box-send-label]');
      var status = sendForm.querySelector('[data-box-send-status]');
      var original = label ? label.textContent : '';
      var fields = new FormData(sendForm);

      if (button) button.disabled = true;
      if (label) label.textContent = status ? (status.dataset.sending || original) : original;

      post(sendForm.getAttribute('action'), {
        email: String(fields.get('email') || ''),
        note: String(fields.get('note') || '')
      }).then(function (json) {
        if (button) button.disabled = false;
        if (label) label.textContent = original;

        var message = (json && json.message) || '';

        if (json && json.success) {
          if (status) status.textContent = message;
          say(message);
          return;
        }

        if (status) status.textContent = message;
      }).catch(function () {
        if (button) button.disabled = false;
        if (label) label.textContent = original;
      });
    });
  }

  /* ---------- the permanent link ---------- */

  var copy = document.querySelector('[data-box-copy]');

  if (copy) {
    copy.addEventListener('click', function () {
      var field = document.querySelector('[data-box-link]');
      var original = copy.textContent;

      var done = function (ok) {
        copy.textContent = ok ? (copy.dataset.copied || original) : (copy.dataset.copyError || original);
        window.setTimeout(function () { copy.textContent = original; }, 2000);
      };

      if (field) {
        field.removeAttribute('readonly');
        field.select();
        field.setSelectionRange(0, 99999);

        if (navigator.clipboard && navigator.clipboard.writeText) {
          navigator.clipboard.writeText(field.value).then(function () { done(true); }, function () { done(document.execCommand('copy')); });
        } else {
          done(document.execCommand('copy'));
        }

        field.setAttribute('readonly', 'readonly');
      }
    });
  }

  /* ---------- empty the box ---------- */

  var clearForm = document.querySelector('[data-box-clear-form]');

  if (clearForm) {
    clearForm.addEventListener('submit', function (event) {
      event.preventDefault();

      var button = clearForm.querySelector('button');

      if (button && button.dataset.confirm && !window.confirm(button.dataset.confirm)) return;

      post(clearForm.getAttribute('action'), {}).then(function (json) {
        if (json && json.success) {
          window.location.reload();
          return;
        }

        say(json && json.message);
      });
    });
  }
})();
