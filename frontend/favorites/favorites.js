/* ==========================================================================
   LUFLY — saved products ("favorites")
   --------------------------------------------------------------------------
   One script for every page that can save a product:
     * [data-fav-toggle]         add / remove a product (card heart, product
                                 page pill, "remove" on the saved list)
     * [data-fav-mail-form]      e-mail the list to the visitor
     * [data-fav-clear-form]     empty the list
     * [data-fav-copy]           copy the permanent link
     * [data-fav-prompt]         the "mail it to me" sheet that opens after the
                                 first product is saved (all pages except the
                                 saved-list page, which has its own panel)

   The endpoints come from data attributes, the CSRF token from the <head>
   meta tag, so nothing here is hard-coded to a locale or a route.
   ========================================================================== */
(function () {
  'use strict';

  var container = document.querySelector('[data-favorites]');
  var promptHost = document.querySelector('[data-fav-prompt]');
  var token = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
  var toastTimer = null;

  function meta(name) {
    var el = document.querySelector('meta[name="' + name + '"]');
    return el ? el.content : '';
  }

  if (!token) {
    token = meta('csrf-token');
  }

  /* A stale page (long-idle tab) is answered with 419; the sheet carries a
     sentence a visitor understands, so the toast says that instead of "CSRF". */
  function messageFor(json) {
    var message = (json && (json.message || '')) || '';

    if (json && json.status === 419 && promptHost && promptHost.dataset.favExpired) {
      return promptHost.dataset.favExpired;
    }

    return message;
  }

  /* the "e-mail me when I save something new" checkbox of either form */
  function wantsUpdates(form) {
    var box = form ? form.querySelector('input[name="notify"]') : null;

    return box && box.checked ? '1' : '0';
  }

  function endpointFor(button, key) {
    if (button && button.dataset[key]) return button.dataset[key];
    if (container && container.dataset[key]) return container.dataset[key];
    return '';
  }

  function post(url, payload) {
    var body = new URLSearchParams();
    body.set('_token', token);
    Object.keys(payload).forEach(function (key) { body.set(key, payload[key]); });

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

  /* ---------- toast ---------- */

  function toast(message) {
    if (!message) return;

    var host = document.querySelector('.fav-toast');
    if (!host) {
      host = document.createElement('div');
      host.className = 'fav-toast';
      host.setAttribute('role', 'status');
      host.setAttribute('aria-live', 'polite');
      host.innerHTML = '<div class="fav-toast-inner">' +
        '<svg class="fav-toast-heart" viewBox="0 0 24 24" aria-hidden="true">' +
        '<path d="M12 20.6 4.2 12.8a5.1 5.1 0 0 1 0-7.2 5.1 5.1 0 0 1 7.2 0l.6.6.6-.6a5.1 5.1 0 0 1 7.2 0 5.1 5.1 0 0 1 0 7.2Z"/></svg>' +
        '<span data-fav-toast-text></span></div>';
      document.body.appendChild(host);
    }

    host.querySelector('[data-fav-toast-text]').textContent = message;
    host.classList.add('is-on');

    if (toastTimer) window.clearTimeout(toastTimer);
    toastTimer = window.setTimeout(function () { host.classList.remove('is-on'); }, 4200);
  }

  /* ---------- counters ---------- */

  function setCount(count) {
    var nodes = document.querySelectorAll('[data-fav-count]');
    for (var i = 0; i < nodes.length; i++) {
      nodes[i].textContent = String(count);
      nodes[i].classList.remove('is-bump');
      /* restart the pop animation */
      void nodes[i].offsetWidth;
      nodes[i].classList.add('is-bump');
    }

    /* the badge in the header follows along, filled or outlined */
    var link = document.querySelector('.mnav-fav');
    if (link) {
      link.classList.toggle('has-items', count > 0);
      var heart = link.querySelector('svg');
      if (heart) {
        heart.setAttribute('fill', count > 0 ? 'currentColor' : 'none');
      }
    }
  }

  /* ---------- button state ---------- */

  function paint(button, on) {
    button.classList.toggle('is-on', on);
    button.setAttribute('aria-pressed', on ? 'true' : 'false');

    var label = button.querySelector('[data-fav-label]');
    if (label) {
      label.textContent = on
        ? (button.dataset.favLabelOn || label.textContent)
        : (button.dataset.favLabelOff || label.textContent);
    }

    button.setAttribute('title', on ? (button.dataset.favLabelOn || '') : (button.dataset.favLabelOff || ''));
  }

  function paintProduct(productId, on) {
    var buttons = document.querySelectorAll('[data-fav-toggle][data-fav-product="' + productId + '"]');
    for (var i = 0; i < buttons.length; i++) {
      paint(buttons[i], on);
    }
  }

  function burst(button, on) {
    if (!on) return;
    button.classList.remove('is-bursting');
    void button.offsetWidth;
    button.classList.add('is-bursting');
    window.setTimeout(function () { button.classList.remove('is-bursting'); }, 560);
  }

  /* ---------- toggle ---------- */

  document.addEventListener('click', function (event) {
    var button = event.target.closest ? event.target.closest('[data-fav-toggle]') : null;
    if (!button) return;

    event.preventDefault();
    event.stopPropagation();

    if (button.classList.contains('is-busy')) return;

    var productId = button.dataset.favProduct;
    var url = endpointFor(button, 'favEndpoint');
    if (!productId || !url) return;

    var wasOn = button.classList.contains('is-on');
    var willBeOn = !wasOn;

    button.classList.add('is-busy');
    button.disabled = true;

    /* Paint first, confirm after: when the list is mailed on save the round
       trip can take a moment, and the heart should not wait for the postman. */
    paintProduct(productId, willBeOn);
    burst(button, willBeOn);

    post(url, { product_id: productId }).then(function (json) {
      var data = json.data || {};

      if (!json.success) {
        paintProduct(productId, wasOn);
        toast(messageFor(json));
        return;
      }

      paintProduct(productId, !!data.added);
      burst(button, !!data.added);

      if (typeof data.count === 'number') {
        setCount(data.count);
      }

      toast(data.message || json.message || '');

      if (data.added) {
        offerMailPrompt();
      }

      /* the list went out again automatically: say so once the save has landed */
      if (data.mailed && data.mail_message) {
        window.setTimeout(function () { toast(data.mail_message); }, 1800);
      }

      /* on the saved list a product that was removed leaves the grid */
      if (button.hasAttribute('data-fav-removing') && !data.added) {
        var card = button.closest('[data-fav-card]');
        if (card) {
          card.classList.add('is-leaving');
          window.setTimeout(function () {
            card.remove();
            var left = document.querySelectorAll('[data-fav-card]').length;
            var grid = document.querySelector('[data-fav-grid]');
            if (left === 0 && grid) {
              window.location.reload();
            }
          }, 220);
        }
      }
    }).catch(function () {
      paintProduct(productId, wasOn);
      toast('');
    }).then(function () {
      button.classList.remove('is-busy');
      button.disabled = false;
    });
  }, false);

  /* ---------- mail the list ---------- */

  var mailForm = document.querySelector('[data-fav-mail-form]');
  if (mailForm) {
    mailForm.addEventListener('submit', function (event) {
      event.preventDefault();

      var status = mailForm.querySelector('[data-fav-mail-status]');
      var submit = mailForm.querySelector('[data-fav-mail-submit]');
      var label = mailForm.querySelector('[data-fav-mail-label]');
      var input = mailForm.querySelector('input[type="email"]');
      var original = label ? label.textContent : '';

      function say(message, kind) {
        if (!status) return;
        status.textContent = message;
        status.classList.remove('is-ok', 'is-error');
        if (kind) status.classList.add(kind);
      }

      if (!input || !input.value || input.checkValidity() === false) {
        say(input && input.validationMessage ? input.validationMessage : '', 'is-error');
        if (input) input.focus();
        return;
      }

      if (submit) submit.disabled = true;
      if (label) label.textContent = status ? (status.dataset.sending || original) : original;
      say('');

      post(mailForm.getAttribute('action'), {
        email: input.value,
        notify: wantsUpdates(mailForm)
      }).then(function (json) {
        var data = json.data || {};

        if (!json.success) {
          var errors = json.errors || {};
          var first = errors.email && errors.email[0] ? errors.email[0] : '';
          say(first || messageFor(json), 'is-error');
          toast(messageFor(json));
          return;
        }

        say(data.message || json.message || '', 'is-ok');
        toast(data.message || json.message || '');
        if (label) label.textContent = original;
      }).catch(function () {
        say('', 'is-error');
      }).then(function () {
        if (submit) submit.disabled = false;
      });
    }, false);
  }

  /* ---------- "mail it to me" prompt ---------- */

  /* The prompt is offered once: after it has sent the list, or after the visitor
     said "not now", localStorage remembers the answer for two weeks. */
  var prompt = promptHost;
  var promptStoreKey = 'lufly-fav-mail';
  var promptEmailKey = 'lufly-fav-email';
  var promptTimer = null;
  var promptSnooze = 14 * 24 * 60 * 60 * 1000;

  function store(key, value) {
    try {
      if (value === undefined) return window.localStorage.getItem(key);
      if (value === null) window.localStorage.removeItem(key);
      else window.localStorage.setItem(key, value);
    } catch (error) { /* private mode: the prompt simply asks every time */ }
    return null;
  }

  function readPromptState() {
    try {
      return JSON.parse(store(promptStoreKey) || '{}') || {};
    } catch (error) {
      return {};
    }
  }

  function writePromptState(state) {
    store(promptStoreKey, JSON.stringify({ state: state, at: Date.now() }));
  }

  function promptAvailable() {
    if (!prompt) return false;
    /* the saved-list page runs the full mail panel instead */
    if (document.querySelector('[data-favorites]')) return false;
    /* never interrupt a visitor who is already typing somewhere */
    var active = document.activeElement;
    if (active && /^(INPUT|TEXTAREA|SELECT)$/.test(active.tagName)) return false;

    var state = readPromptState();
    if (state.state === 'sent') return false;
    if (state.state === 'later' && Date.now() - (state.at || 0) < promptSnooze) return false;

    return true;
  }

  function openPrompt() {
    if (!prompt || prompt.classList.contains('is-on')) return;

    var input = prompt.querySelector('[data-fav-prompt-input]');
    if (input && !input.value) {
      input.value = store(promptEmailKey) || '';
    }

    prompt.hidden = false;
    /* the planning chat floats in the same corner: it steps aside while this
       sheet is on screen (frontend/planner/planner.css) */
    if (document.body) document.body.classList.add('has-favmail');
    window.requestAnimationFrame(function () { prompt.classList.add('is-on'); });

    /* a keyboard popping up is helpful on a desktop, intrusive on a phone */
    var touch = window.matchMedia && window.matchMedia('(hover: none)').matches;
    if (input && !touch) {
      window.setTimeout(function () { input.focus(); }, 220);
    }
  }

  function closePrompt(state) {
    if (!prompt) return;
    writePromptState(state || 'later');
    prompt.classList.remove('is-on');
    if (document.body) document.body.classList.remove('has-favmail');
    window.setTimeout(function () { prompt.hidden = true; }, 260);
  }

  function offerMailPrompt() {
    if (!promptAvailable()) return;
    if (promptTimer) window.clearTimeout(promptTimer);
    /* let the heart burst and the toast land first */
    promptTimer = window.setTimeout(openPrompt, 1100);
  }

  if (prompt) {
    var promptForm = prompt.querySelector('[data-fav-prompt-form]');
    var promptStatus = prompt.querySelector('[data-fav-prompt-status]');
    var promptSubmit = prompt.querySelector('[data-fav-prompt-submit]');
    var promptLabel = prompt.querySelector('[data-fav-prompt-label]');
    var promptClose = prompt.querySelector('[data-fav-prompt-close]');

    function promptSay(message, kind) {
      if (!promptStatus) return;
      promptStatus.textContent = message;
      promptStatus.classList.remove('is-ok', 'is-error');
      if (kind) promptStatus.classList.add(kind);
    }

    if (promptClose) {
      promptClose.addEventListener('click', function () { closePrompt('later'); }, false);
    }

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && prompt.classList.contains('is-on')) {
        closePrompt('later');
      }
    }, false);

    if (promptForm) {
      promptForm.addEventListener('submit', function (event) {
        event.preventDefault();

        var input = promptForm.querySelector('input[type="email"]');
        var original = promptLabel ? promptLabel.textContent : '';

        if (!input || !input.value || input.checkValidity() === false) {
          promptSay(input && input.validationMessage ? input.validationMessage : '', 'is-error');
          if (input) input.focus();
          return;
        }

        promptSay('', '');
        if (promptSubmit) promptSubmit.disabled = true;
        if (promptLabel) promptLabel.textContent = promptStatus ? (promptStatus.dataset.sending || original) : original;

        post(promptForm.getAttribute('action') || prompt.dataset.favMailEndpoint, {
          email: input.value,
          notify: wantsUpdates(promptForm)
        })
          .then(function (json) {
            var data = json.data || {};

            if (!json.success) {
              var errors = json.errors || {};
              var first = errors.email && errors.email[0] ? errors.email[0] : '';
              promptSay(first || messageFor(json), 'is-error');
              return;
            }

            store(promptEmailKey, input.value);
            writePromptState('sent');
            promptSay(data.message || json.message || '', 'is-ok');
            toast(data.message || json.message || '');
            if (promptLabel) promptLabel.textContent = original;

            window.setTimeout(function () {
              prompt.classList.remove('is-on');
              window.setTimeout(function () { prompt.hidden = true; }, 260);
            }, 2400);
          })
          .catch(function () {
            promptSay('', 'is-error');
          })
          .then(function () {
            if (promptSubmit) promptSubmit.disabled = false;
            if (promptLabel) promptLabel.textContent = original;
          });
      }, false);
    }
  }

  /* ---------- clear the list ---------- */

  var clearForm = document.querySelector('[data-fav-clear-form]');
  if (clearForm) {
    clearForm.addEventListener('submit', function (event) {
      event.preventDefault();

      if (clearForm.dataset.confirm && !window.confirm(clearForm.dataset.confirm)) {
        return;
      }

      post(clearForm.getAttribute('action'), {}).then(function (json) {
        toast(json.message || '');
        if (json.success) {
          window.setTimeout(function () { window.location.reload(); }, 400);
        }
      }).catch(function () { /* offline: leave the list as it is */ });
    }, false);
  }

  /* ---------- copy the permanent link ---------- */

  document.addEventListener('click', function (event) {
    var button = event.target.closest ? event.target.closest('[data-fav-copy]') : null;
    if (!button) return;

    event.preventDefault();

    var input = document.querySelector('[data-fav-link]');
    if (!input) return;

    var done = function (ok) {
      button.classList.add('is-done');
      button.textContent = ok ? (button.dataset.copied || '') : (button.dataset.copyError || '');
      toast(ok ? (button.dataset.copied || '') : '');
      window.setTimeout(function () {
        button.classList.remove('is-done');
        button.textContent = button.dataset.label || '';
      }, 2600);
    };

    input.removeAttribute('readonly');
    input.select();
    input.setSelectionRange(0, input.value.length);

    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(input.value).then(function () { done(true); }, function () { done(false); });
    } else {
      var copied = false;
      try { copied = document.execCommand('copy'); } catch (error) { copied = false; }
      done(copied);
    }

    input.setAttribute('readonly', 'readonly');
  }, false);
})();
