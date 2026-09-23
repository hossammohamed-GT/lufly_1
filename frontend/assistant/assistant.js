/*
 * The finder — the floating chat on every storefront page.
 *
 * Two tabs. The first is the one that opens: a description in the visitor's own
 * language, or a photo, is posted to /assistant/ask and comes back as a short
 * answer, the closest pieces from our own catalogue as cards, and the promise
 * that a piece we do not show yet is still a piece we can get.
 *
 * Nothing about the catalogue lives here: the server does the searching and the
 * wording, the browser only draws what came back. The photo is shrunk here
 * first (longest side 1280 px, JPEG) so the upload — and the model's view of
 * it — stays small.
 *
 * The shell (bubble, panel, expand, drag-to-resize, remembered size) is the
 * same one the planner used; the second tab says the planner is on its way.
 */
(function () {
  'use strict';

  var root = document.querySelector('[data-assistant]');

  if (!root) return;

  var panel = root.querySelector('[data-aichat-panel]');
  var log = root.querySelector('[data-assistant-log]');
  var form = root.querySelector('[data-assistant-form]');
  var input = root.querySelector('[data-assistant-input]');
  var gate = root.querySelector('[data-assistant-gate]');
  var emailField = root.querySelector('[data-assistant-email]');
  var fileField = root.querySelector('[data-assistant-file]');
  var shot = root.querySelector('[data-assistant-shot]');
  var shotImage = root.querySelector('[data-assistant-shot-img]');
  var shotName = root.querySelector('[data-assistant-shot-name]');
  var welcome = root.querySelector('[data-assistant-welcome]');
  var endpoint = root.getAttribute('data-assistant-endpoint') || '';
  var context = parseJSON(root.getAttribute('data-assistant-context'));
  var photosOn = root.getAttribute('data-assistant-photos') === '1';
  var askEmail = root.getAttribute('data-assistant-ask-email') === '1';
  var plannerSoon = root.getAttribute('data-assistant-planner-soon') === '1';
  var waiting = parseJSON(root.getAttribute('data-assistant-waiting')) || [];
  var labels = parseJSON(root.getAttribute('data-assistant-labels')) || {};
  var EMAIL_KEY = 'lufly-chat-email';
  var SIZE_KEY = 'lufly-chat-size';
  var photo = null;
  var busy = false;

  if (!panel || !log || !form) return;

  function parseJSON(raw) {
    try {
      return raw ? JSON.parse(raw) : null;
    } catch (error) {
      return null;
    }
  }

  function el(tag, className, text) {
    var node = document.createElement(tag);
    if (className) node.className = className;
    if (text !== undefined && text !== null) node.textContent = text;

    return node;
  }

  function scrollLog() {
    var body = root.querySelector('[data-aichat-body]');
    if (body) body.scrollTop = body.scrollHeight;
  }

  function email() {
    try {
      return window.localStorage.getItem(EMAIL_KEY) || '';
    } catch (error) {
      return '';
    }
  }

  /* ---------- the shell: open, close, expand, drag ---------- */

  var toggle = root.querySelector('[data-aichat-toggle]');
  var closeButton = root.querySelector('[data-aichat-close]');
  var growButton = root.querySelector('[data-aichat-grow]');
  var grip = root.querySelector('[data-aichat-resize]');
  var growText = root.querySelector('[data-aichat-grow-text]');
  var size = null;

  function saveSize() {
    if (!size) return;
    try {
      window.localStorage.setItem(SIZE_KEY, JSON.stringify(size));
    } catch (error) { /* private mode */ }
  }

  function applySize() {
    if (!size) return;

    root.classList.toggle('is-wide', !!size.wide);

    if (size.wide) {
      panel.style.removeProperty('--aichat-w');
      panel.style.removeProperty('--aichat-h');
      return;
    }

    panel.style.setProperty('--aichat-w', size.w + 'px');
    panel.style.setProperty('--aichat-h', size.h + 'px');
  }

  function rememberSize() {
    try {
      var stored = JSON.parse(window.localStorage.getItem(SIZE_KEY) || 'null');
      if (stored && (stored.wide || (stored.w > 0 && stored.h > 0))) {
        size = { w: stored.w || 0, h: stored.h || 0, wide: !!stored.wide };
      }
    } catch (error) {
      size = null;
    }
  }

  /* one button, two jobs: expand the window or hand it back its corner */
  function labelGrow() {
    if (!growButton) return;

    var wide = !!(size && size.wide);
    var text = wide
      ? (growButton.getAttribute('data-aichat-shrink-label') || '')
      : (growButton.getAttribute('data-aichat-grow-label') || '');

    growButton.setAttribute('aria-pressed', wide ? 'true' : 'false');
    growButton.setAttribute('title', text);

    if (growText) growText.textContent = text;
  }

  function openPanel(open) {
    root.classList.toggle('is-open', open);
    panel.hidden = !open;
    if (toggle) toggle.setAttribute('aria-expanded', open ? 'true' : 'false');

    if (!open) return;

    applySize();
    /* the address is asked once, before the first answer */
    if (askEmail && gate && !email()) gate.hidden = false;
    if (input) input.focus();
    window.requestAnimationFrame(scrollLog);
  }

  rememberSize();
  applySize();
  labelGrow();

  if (toggle) toggle.addEventListener('click', function () { openPanel(panel.hidden); });

  /* a link that lands on ?chat=1 opens the chat by itself — that is where the
     promise page's "find a piece in the meantime" button points */
  if (/[?&]chat=1(&|$)/.test(window.location.search)) openPanel(true);

  if (closeButton) {
    closeButton.addEventListener('click', function () {
      openPanel(false);
      if (toggle) toggle.focus();
    });
  }

  if (growButton) {
    growButton.addEventListener('click', function () {
      size = { w: (size && size.w) || 0, h: (size && size.h) || 0, wide: !(size && size.wide) };
      applySize();
      saveSize();
      labelGrow();
    });
  }

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && !panel.hidden) openPanel(false);
  });

  if (grip) {
    grip.addEventListener('pointerdown', function (event) {
      if (window.matchMedia && window.matchMedia('(max-width: 720px)').matches) return;
      if (event.button !== undefined && event.button !== 0) return;

      event.preventDefault();
      grip.setPointerCapture(event.pointerId);

      var rect = panel.getBoundingClientRect();
      var rtl = window.getComputedStyle(panel).direction === 'rtl';
      var dragging = true;

      root.classList.add('is-resizing');
      if (document.body) document.body.classList.add('is-chat-resizing');

      function move(moveEvent) {
        if (!dragging) return;

        var w = rtl ? (moveEvent.clientX - rect.left) : (rect.right - moveEvent.clientX);
        var h = rect.bottom - moveEvent.clientY;

        size = {
          w: Math.round(Math.max(320, Math.min(w, window.innerWidth - 32))),
          h: Math.round(Math.max(360, Math.min(h, window.innerHeight - 110))),
          wide: false
        };

        applySize();
      }

      function stop() {
        dragging = false;
        grip.removeEventListener('pointermove', move);
        grip.removeEventListener('pointerup', stop);
        grip.removeEventListener('pointercancel', stop);
        root.classList.remove('is-resizing');
        if (document.body) document.body.classList.remove('is-chat-resizing');
        saveSize();
      }

      grip.addEventListener('pointermove', move);
      grip.addEventListener('pointerup', stop);
      grip.addEventListener('pointercancel', stop);
    });
  }

  /* ---------- the two tabs ---------- */

  var tabs = root.querySelectorAll('[data-assistant-tab]');
  var panes = root.querySelectorAll('[data-assistant-pane]');

  function showTab(name) {
    Array.prototype.forEach.call(tabs, function (tab) {
      var active = tab.getAttribute('data-assistant-tab') === name;
      tab.classList.toggle('is-active', active);
      tab.setAttribute('aria-selected', active ? 'true' : 'false');
    });

    Array.prototype.forEach.call(panes, function (pane) {
      pane.hidden = pane.getAttribute('data-assistant-pane') !== name;
    });

    var compose = root.querySelector('[data-assistant-form]');
    /* the planner tab is a promise, not a form */
    if (compose) compose.hidden = name !== 'find';
    scrollLog();
  }

  Array.prototype.forEach.call(tabs, function (tab) {
    tab.addEventListener('click', function () {
      showTab(tab.getAttribute('data-assistant-tab') || 'find');
      if (input && !root.querySelector('[data-assistant-form]').hidden) input.focus();
    });
  });

  if (plannerSoon) root.classList.add('is-planner-soon');

  /* ---------- the address, once ---------- */

  if (gate) {
    gate.addEventListener('submit', function (event) {
      event.preventDefault();
      rememberEmail((emailField && emailField.value) || '', true);
    });
  }

  var skip = root.querySelector('[data-assistant-skip]');

  if (skip) {
    skip.addEventListener('click', function () {
      if (gate) gate.hidden = true;
      if (input) input.focus();
    });
  }

  function rememberEmail(value, announce) {
    var clean = String(value || '').trim();

    if (clean !== '') {
      try {
        window.localStorage.setItem(EMAIL_KEY, clean);
      } catch (error) { /* private mode */ }
    }

    if (gate) gate.hidden = true;

    if (announce && clean !== '') {
      say('bot', labels.email_saved || '');
    }

    if (input) input.focus();
  }

  /* ---------- the photo ---------- */

  if (fileField) {
    fileField.addEventListener('change', function () {
      var file = fileField.files && fileField.files[0];

      if (!file) return;

      if (!/^image\//.test(file.type)) {
        note(labels.photo_error || '');
        return;
      }

      shrink(file, function (dataUrl, name) {
        photo = { data: dataUrl, name: name };

        if (shotImage) shotImage.src = dataUrl;
        if (shotName) shotName.textContent = labels.photo_ready || name;
        if (shot) shot.hidden = false;
        if (input) input.focus();
      });
    });
  }

  var removeShot = root.querySelector('[data-assistant-shot-remove]');

  if (removeShot) {
    removeShot.addEventListener('click', function () {
      photo = null;
      if (shot) shot.hidden = true;
      if (fileField) fileField.value = '';
    });
  }

  /*
   * Longest side 1280 px, JPEG at 0.82. A phone photo of 4 MB becomes roughly
   * 200 KB: a small upload, and a small picture for the model to look at.
   */
  function shrink(file, done) {
    var reader = new FileReader();

    reader.onload = function () {
      var image = new Image();

      image.onload = function () {
        var side = Math.max(image.width, image.height);
        var scale = side > 1280 ? 1280 / side : 1;
        var canvas = document.createElement('canvas');

        canvas.width = Math.max(1, Math.round(image.width * scale));
        canvas.height = Math.max(1, Math.round(image.height * scale));

        var context2d = canvas.getContext('2d');

        if (!context2d) {
          done(String(reader.result), file.name);
          return;
        }

        context2d.drawImage(image, 0, 0, canvas.width, canvas.height);

        var dataUrl;

        try {
          dataUrl = canvas.toDataURL('image/jpeg', 0.82);
        } catch (error) {
          dataUrl = String(reader.result);
        }

        done(dataUrl, file.name);
      };

      image.onerror = function () {
        done(String(reader.result), file.name);
      };

      image.src = String(reader.result);
    };

    reader.onerror = function () {
      note(labels.photo_error || '');
    };

    reader.readAsDataURL(file);
  }

  /* ---------- the conversation ---------- */

  function say(who, text, list) {
    var bubble = el('div', 'aichat-say is-' + who);
    bubble.appendChild(el('p', null, text));

    if (list) bubble.appendChild(list);

    log.appendChild(bubble);
    scrollLog();

    return bubble;
  }

  function note(text) {
    if (!text) return;

    var line = el('p', 'aichat-note', text);
    log.appendChild(line);
    scrollLog();
  }

  /* the cards: the same picture, code and link the catalogue shows */
  function bank(cards) {
    var grid = el('div', 'aichat-bank');

    cards.forEach(function (card) {
      var link = el('a', 'aichat-card');
      link.href = card.url || '#';

      var media = el('span', 'aichat-card-media');
      var image = el('img');
      image.src = card.image || '';
      image.alt = card.name || '';
      image.loading = 'lazy';
      image.decoding = 'async';
      media.appendChild(image);
      link.appendChild(media);

      var body = el('span', 'aichat-card-body');
      body.appendChild(el('strong', 'aichat-card-name', card.name || ''));

      if (card.code) body.appendChild(el('em', 'aichat-card-code', card.code));
      if (card.size) body.appendChild(el('span', 'aichat-card-size', card.size));

      link.appendChild(body);
      grid.appendChild(link);
    });

    return grid;
  }

  function links(data) {
    var wrap = el('div', 'aichat-links');

    if (data.search_url) {
      var all = el('a', 'aichat-link', labels.see_all || '');
      all.href = data.search_url;
      wrap.appendChild(all);
    }

    var support = data.support || {};

    if (support.whatsapp) {
      var chat = el('a', 'aichat-link', labels.support_whatsapp || 'WhatsApp');
      chat.href = 'https://wa.me/' + support.whatsapp;
      chat.target = '_blank';
      chat.rel = 'noopener';
      wrap.appendChild(chat);
    }

    if (support.email) {
      var mail = el('a', 'aichat-link', labels.support_mail || support.email);
      mail.href = 'mailto:' + support.email;
      wrap.appendChild(mail);
    }

    return wrap.childNodes.length > 0 ? wrap : null;
  }

  /* ---------- the round trip ---------- */

  var waitingIndex = 0;
  var waitingTimer = null;
  var waitingBubble = null;

  function startWaiting() {
    if (waiting.length === 0) return;

    waitingIndex = 0;
    waitingBubble = say('bot is-waiting', waiting[0]);

    waitingTimer = window.setInterval(function () {
      waitingIndex = (waitingIndex + 1) % waiting.length;
      var line = waitingBubble && waitingBubble.querySelector('p');
      if (line) line.textContent = waiting[waitingIndex];
    }, 2600);
  }

  function stopWaiting() {
    if (waitingTimer) window.clearInterval(waitingTimer);
    waitingTimer = null;

    if (waitingBubble && waitingBubble.parentNode) waitingBubble.parentNode.removeChild(waitingBubble);
    waitingBubble = null;
  }

  form.addEventListener('submit', function (event) {
    event.preventDefault();
    send();
  });

  if (input) {
    input.addEventListener('keydown', function (event) {
      if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        send();
      }
    });
  }

  function send(override, productId) {
    if (busy) return;

    var question = override !== undefined ? String(override) : (input ? input.value.trim() : '');
    var fromProduct = productId ? parseInt(productId, 10) : 0;

    if (question === '' && !photo && !fromProduct) return;

    if (askEmail && !email()) {
      if (gate) gate.hidden = false;
      if (input) input.focus();
      return;
    }

    busy = true;

    var shown = question;

    if (shown === '' && fromProduct && context) shown = context.name || '';
    if (shown === '' && photo) shown = labels.photo_ready || '';

    say('me', shown, photo ? previewShot(photo.data) : null);

    if (input) {
      input.value = '';
      input.style.height = '';
    }

    var sent = photo;
    photo = null;
    if (shot) shot.hidden = true;
    if (fileField) fileField.value = '';

    startWaiting();

    var payload = new URLSearchParams();
    payload.set('_token', csrf());
    payload.set('q', question);
    payload.set('email', email());
    payload.set('product_id', String(fromProduct));

    if (sent) {
      payload.set('photo_name', sent.name || 'photo.jpg');
      payload.set('photo_mime', 'image/jpeg');
      payload.set('photo_data', String(sent.data).replace(/^data:[^,]+,/, ''));
    }

    fetch(endpoint, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: payload.toString()
    })
      .then(function (response) {
        return response.json().catch(function () { return null; });
      })
      .then(function (body) {
        stopWaiting();
        busy = false;

        if (!body || body.success !== true || !body.data) {
          say('bot', (body && body.message) || labels.err || '');
          return;
        }

        answer(body.data);
      })
      .catch(function () {
        stopWaiting();
        busy = false;
        say('bot', labels.err || '');
      });
  }

  function previewShot(dataUrl) {
    var wrap = el('span', 'aichat-say-shot');
    var image = el('img');
    image.src = dataUrl;
    image.alt = '';
    wrap.appendChild(image);

    return wrap;
  }

  function answer(data) {
    var bubble = say('bot', data.text || '');

    if (data.cards && data.cards.length > 0) bubble.appendChild(bank(data.cards));

    if (data.note) bubble.appendChild(el('p', 'aichat-note', data.note));

    var more = links(data);

    if (more) bubble.appendChild(more);

    if (welcome && welcome.parentNode) welcome.hidden = true;

    scrollLog();
  }

  function csrf() {
    var meta = document.querySelector('meta[name="csrf-token"]');

    return meta ? meta.getAttribute('content') || '' : '';
  }

  /* ---------- the chips ---------- */

  Array.prototype.forEach.call(root.querySelectorAll('[data-assistant-chip]'), function (chip) {
    chip.addEventListener('click', function () {
      var productId = chip.getAttribute('data-assistant-chip-product');

      if (productId) {
        send('', productId);
        return;
      }

      send(chip.getAttribute('data-assistant-chip-text') || chip.textContent.trim());
    });
  });

})();
