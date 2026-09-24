/*
 * The quotation box, in the browser.
 *
 *   1. any [data-box-add] button (the chat's cards, a product page) puts a piece
 *      in or takes it out again - one endpoint, one state, everywhere,
 *   2. the box page's send step posts the list and shows what the team answered,
 *   3. every [data-box-count] badge on the page follows along, and the chat
 *      hears about the change through a "box:changed" event.
 *
 * The endpoints come from data attributes and the CSRF token from the <head>,
 * so nothing here depends on where the button happens to live.
 */
(function () {
  'use strict';

  var root = document.documentElement;
  /* the box's own token, kept next to the chat's history: a browser that drops
     the cookie still keeps the same box */
  var TOKEN_KEY = 'lufly-box-token';

  function token() {
    try {
      return window.localStorage.getItem(TOKEN_KEY) || '';
    } catch (error) {
      return '';
    }
  }

  function keepToken(value) {
    if (!value) return;

    try {
      window.localStorage.setItem(TOKEN_KEY, String(value));
    } catch (error) { /* private mode */ }
  }

  function csrf() {
    var meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
  }

  /* The page the visitor is looking at knows which box it is: the box page prints
     its token, and a link the team mailed (?t=… or /box/TOKEN) already put that
     box on the server's side. Learning it here means the next piece he saves goes
     into this list instead of quietly starting another one. */
  function learnPageToken() {
    var page = document.querySelector('[data-box-page][data-box-token]');
    var printed = page ? (page.getAttribute('data-box-token') || '') : '';

    if (printed) {
      keepToken(printed);
      return;
    }

    /* the token may also be in the address itself */
    var match = /[?&]t=([A-Za-z0-9]{16,64})/.exec(window.location.search)
      || /\/([A-Za-z0-9]{32,64})\/?(?:\?|#|$)/.exec(window.location.pathname);

    if (match) keepToken(match[1]);
  }

  function addEndpoint() {
    var page = document.querySelector('[data-box-page]');
    var button = document.querySelector('[data-box-add]');
    if (button && button.getAttribute('data-box-endpoint')) return button.getAttribute('data-box-endpoint');
    if (page) return page.getAttribute('data-box-endpoint') || '';
    return '';
  }

  function removeEndpoint(node) {
    var page = document.querySelector('[data-box-page]');
    if (node && node.getAttribute('data-box-endpoint')) return node.getAttribute('data-box-endpoint');
    if (page) return page.getAttribute('data-box-remove-endpoint') || '';
    return '';
  }

  function post(url, payload) {
    /* the browser's copy of the token rides along, so the same box answers even
       when the cookie did not survive */
    var boxToken = token();
    if (boxToken && !payload.token) payload.token = boxToken;

    return fetch(url, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrf()
      },
      body: new URLSearchParams(payload).toString()
    }).then(function (response) {
      return response.json().catch(function () { return {}; }).then(function (data) {
        var body = data && data.data ? data.data : {};
        if (data && data.message) body.message = data.message;
        body.ok = !!(data && data.success);
        body.status = response.status;
        return body;
      });
    });
  }
// s
  function paintCount(count) {
    var nodes = document.querySelectorAll('[data-box-count]');
    for (var i = 0; i < nodes.length; i++) {
      nodes[i].textContent = count > 0 ? String(count) : '';
      var holder = nodes[i].closest('.mnav-box, .aichat-foot-link');
      if (holder) {
        if (count > 0) holder.classList.add('has-items');
        else holder.classList.remove('has-items');
      }
    }
  }

  function paintButtons(productId, added, labelOn, labelOff) {
    var buttons = document.querySelectorAll('[data-box-add][data-box-product="' + productId + '"]');
    for (var i = 0; i < buttons.length; i++) {
      buttons[i].classList.toggle('is-on', !!added);
      buttons[i].setAttribute('aria-pressed', added ? 'true' : 'false');
      var label = buttons[i].querySelector('[data-box-label]');
      if (label && labelOn && labelOff) label.textContent = added ? labelOn : labelOff;
    }
  }

  function announce(detail) {
    document.dispatchEvent(new CustomEvent('box:changed', { detail: detail }));
  }

  /* the message the chat or the toast shows: whatever the server answered */
  function say(node, message, state) {
    if (!node) return;
    node.textContent = message || '';
    node.setAttribute('data-state', state || '');
    if (!message) return;
    window.clearTimeout(node.__boxTimer);
    node.__boxTimer = window.setTimeout(function () {
      node.textContent = '';
      node.removeAttribute('data-state');
    }, 4200);
  }

  /* ---- 1. add / remove one piece ---------------------------------------- */

  document.addEventListener('click', function (event) {
    var button = event.target.closest ? event.target.closest('[data-box-add]') : null;
    if (!button) return;

    event.preventDefault();

    var productId = button.getAttribute('data-box-product');
    var url = button.getAttribute('data-box-endpoint') || addEndpoint();
    if (!productId || !url || button.classList.contains('is-busy')) return;

    /* The tap is answered at once: the piece is in the box from the visitor's
       point of view the moment he presses, and the server's answer only confirms
       it (or puts it back when it could not). A round trip that has to reach a
       database — and, on a real host, the shop's own mail — must never be
       something the visitor waits behind. */
    var wasOn = button.classList.contains('is-on');
    var before = currentCount();
    var wanted = !wasOn;

    paintButtons(productId, wanted, button.getAttribute('data-box-label-on'), button.getAttribute('data-box-label-off'));
    paintCount(before + (wanted ? 1 : -1));
    button.classList.add('is-busy');

    post(url, { product_id: productId }).then(function (data) {
      button.classList.remove('is-busy');

      if (!data.ok && data.status === 422 && data.errors && data.errors.product_id) {
        undo(productId, wasOn, before);
        say(document.querySelector('[data-box-status]'), data.errors.product_id[0], 'error');
        announce({ productId: productId, added: false, failed: true, message: data.errors.product_id[0] });
        return;
      }

      if (!data.ok) {
        undo(productId, wasOn, before);
        say(document.querySelector('[data-box-status]'), button.getAttribute('data-box-error') || '', 'error');
        announce({ productId: productId, added: false, failed: true });
        return;
      }

      /* the server is the truth: it knows whether the piece went in or came out */
      keepToken(data.token);
      paintCount(data.count);
      paintButtons(productId, data.added, button.getAttribute('data-box-label-on'), button.getAttribute('data-box-label-off'));
      say(document.querySelector('[data-box-status]'), data.message, 'ok');
      announce({ productId: productId, added: data.added, count: data.count, message: data.message });
    }).catch(function () {
      button.classList.remove('is-busy');
      undo(productId, wasOn, before);
      announce({ productId: productId, added: false, failed: true });
    });
  });

  /** The badge's number right now (the optimistic paint needs a starting point). */
  function currentCount() {
    var node = document.querySelector('[data-box-count]');
    var value = node ? parseInt(node.textContent, 10) : 0;

    return isNaN(value) ? 0 : value;
  }

  /** The server said no: put the button and the badge back where they were. */
  function undo(productId, wasOn, before) {
    paintButtons(productId, wasOn, null, null);
    paintCount(before);
  }

  /* ---- 2. take one out from the box page -------------------------------- */

  document.addEventListener('click', function (event) {
    var button = event.target.closest ? event.target.closest('[data-box-remove]') : null;
    if (!button) return;

    event.preventDefault();

    var productId = button.getAttribute('data-box-product');
    var url = removeEndpoint(button);
    if (!productId || !url || button.classList.contains('is-busy')) return;

    button.classList.add('is-busy');

    post(url, { product_id: productId }).then(function (data) {
      if (!data.ok) {
        button.classList.remove('is-busy');
        return;
      }

      var card = document.querySelector('[data-box-card="' + productId + '"]');
      if (card) {
        card.classList.add('is-leaving');
        window.setTimeout(function () {
          card.remove();
          if (!document.querySelector('[data-box-card]')) window.location.reload();
        }, 220);
      }

      keepToken(data.token);
      paintCount(data.count);
      announce({ productId: productId, added: false, count: data.count, message: data.message });
    });
  });

  /* ---- 3. the send step -------------------------------------------------- */

  var sendForm = document.querySelector('[data-box-send-form]');

  if (sendForm) {
    sendForm.addEventListener('submit', function (event) {
      event.preventDefault();

      var button = sendForm.querySelector('[data-box-send-submit]');
      var label = sendForm.querySelector('[data-box-send-label]');
      var status = sendForm.querySelector('[data-box-status]');
      var email = sendForm.querySelector('input[name="email"]');
      var note = sendForm.querySelector('textarea[name="note"]');
      var idle = label ? label.textContent : '';

      if (button) button.disabled = true;
      if (label && status) label.textContent = status.getAttribute('data-box-sending') || idle;

      post(sendForm.getAttribute('action'), {
        email: email ? email.value : '',
        note: note ? note.value : ''
      }).then(function (data) {
        if (button) button.disabled = false;
        if (label) label.textContent = idle;

        if (data.ok) {
          keepToken(data.token ? data.token : token());
          say(status, data.message, 'ok');
          sendForm.classList.add('is-sent');
          announce({ sent: true, count: data.count, message: data.message });
          return;
        }

        var message = data.message || '';
        if (data.errors && data.errors.email) message = data.errors.email[0];
        say(status, message, 'error');
      }).catch(function () {
        if (button) button.disabled = false;
        if (label) label.textContent = idle;
        say(status, '', 'error');
      });
    });
  }

  /* ---- 4. copy the one link --------------------------------------------- */

  var copy = document.querySelector('[data-box-copy]');

  if (copy) {
    copy.addEventListener('click', function () {
      var field = document.querySelector('[data-box-link]');
      var label = copy.querySelector('[data-box-copy-label]') || copy;
      if (!field) return;

      var done = function (ok) {
        var original = copy.getAttribute('data-box-label') || label.textContent;
        copy.setAttribute('data-box-label', original);
        label.textContent = ok ? copy.getAttribute('data-box-copied') : copy.getAttribute('data-box-copy-failed');
        window.setTimeout(function () { label.textContent = original; }, 2400);
      };

      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(field.value).then(function () { done(true); }, function () { done(false); });
        return;
      }

      field.select();
      try { done(document.execCommand('copy')); } catch (error) { done(false); }
    });
  }

  /* ---- 5. empty it -------------------------------------------------------- */

  var clearForm = document.querySelector('[data-box-clear-form]');

  if (clearForm) {
    clearForm.addEventListener('submit', function (event) {
      event.preventDefault();

      var confirmText = clearForm.getAttribute('data-box-confirm');
      if (confirmText && !window.confirm(confirmText)) return;

      var button = clearForm.querySelector('button[type="submit"]');
      if (button) button.disabled = true;

      post(clearForm.getAttribute('action'), {}).then(function (data) {
        if (data.ok) {
          try { window.localStorage.removeItem(TOKEN_KEY); } catch (error) { /* private mode */ }
          paintCount(0);
          announce({ productId: 0, added: false, count: 0, message: data.message });
          window.location.reload();
          return;
        }

        if (button) button.disabled = false;
      }).catch(function () {
        if (button) button.disabled = false;
      });
    });
  }

  /* which box is this browser looking at? the page knows — remember it before
     anything is added, so the second piece joins the first list */
  learnPageToken();

  /* the storefront may render after this script (the chat adds cards) — the
     delegated handlers above cover those, so nothing else is needed here */
  if (root) root.setAttribute('data-box-ready', '1');
}());
