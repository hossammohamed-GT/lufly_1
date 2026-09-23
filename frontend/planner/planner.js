/* ==========================================================================
   LUFLY — bathroom planner
   --------------------------------------------------------------------------
   Three questions, three taps, one plan:

     [data-planner]               the page: endpoints, waiting sentences and the
                                  answers gathered so far live on it
     [data-planner-answer]        one choice (size, wet area, look) — the button
                                  carries the field and the value it answers
     [data-planner-custom]        "my own size": two numbers and a submit
     [data-planner-thinking]      the line that rotates while the server works
     [data-planner-board-plan]    where the finished plan lands
     [data-planner-render]        the optional picture of the finished room
     [data-planner-send]          hand the plan to the LUFLY team by e-mail

   Everything the browser sends is a tap the visitor made; the plan itself is
   always built (and cached) on the server.
   ========================================================================== */
(function () {
  'use strict';

  var root = document.querySelector('[data-planner]');
  if (!root) return;

  var questions = root.querySelector('[data-planner-questions]');
  var thinking = root.querySelector('[data-planner-thinking]');
  var thinkingText = root.querySelector('[data-planner-thinking-text]');
  var boardPlan = root.querySelector('[data-planner-board-plan]');
  var boardEmpty = root.querySelector('[data-planner-board-empty]');
  var toast = root.querySelector('[data-planner-toast]');
  var trail = root.querySelectorAll('.planner-trail-step');
  var token = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
  var busy = false;
  var waitTimer = null;
  var waitIndex = 0;
  var toastTimer = null;

  /* the few sentences the page has to hand to the script */
  var renderText = root.getAttribute('data-planner-render-wait') || '';
  var againText = root.getAttribute('data-planner-render-again') || '';
  var sentWaiting = root.getAttribute('data-planner-send-wait') || '';
  var customError = root.getAttribute('data-planner-custom-error') || '';

  var waiting = [];
  try {
    waiting = JSON.parse(root.getAttribute('data-planner-waiting') || '[]') || [];
  } catch (error) {
    waiting = [];
  }

  /* ---------- small helpers ---------- */

  function post(url, payload) {
    if (!url) return Promise.resolve({ success: false, message: '' });

    var body = new URLSearchParams();
    body.set('_token', token);

    Object.keys(payload).forEach(function (key) {
      var value = payload[key];

      if (Object.prototype.toString.call(value) === '[object Array]') {
        value.forEach(function (entry) { body.append(key + '[]', entry); });
      } else if (value !== null && value !== undefined) {
        body.set(key, String(value));
      }
    });

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
    }).catch(function () {
      return { success: false, status: 0, message: '' };
    });
  }

  function showToast(message) {
    if (!toast || !message) return;

    toast.textContent = message;
    toast.classList.add('is-on');
    if (toastTimer) window.clearTimeout(toastTimer);
    toastTimer = window.setTimeout(function () { toast.classList.remove('is-on'); }, 5200);
  }

  function scrollToBoard() {
    if (!boardPlan || !boardPlan.firstChild) return;

    var narrow = window.matchMedia && window.matchMedia('(max-width: 1000px)').matches;
    var top = boardPlan.getBoundingClientRect().top + window.pageYOffset - (narrow ? 16 : 90);

    window.scrollTo({ top: top, behavior: 'smooth' });
  }

  /* ---------- the waiting line: never the same sentence twice ---------- */

  function startWaiting() {
    if (!thinking || !thinkingText) return;

    var lines = waiting.length ? waiting : [''];
    waitIndex = 0;
    thinkingText.textContent = lines[0];
    thinking.hidden = false;
    thinking.classList.add('is-on');

    if (waitTimer) window.clearInterval(waitTimer);
    waitTimer = window.setInterval(function () {
      waitIndex = (waitIndex + 1) % lines.length;
      thinking.classList.remove('is-fresh');
      thinkingText.textContent = lines[waitIndex];
      /* restart the fade so each line arrives on its own */
      void thinkingText.offsetWidth;
      thinking.classList.add('is-fresh');
    }, 2600);
  }

  function stopWaiting() {
    if (waitTimer) window.clearInterval(waitTimer);
    waitTimer = null;

    if (thinking) {
      thinking.classList.remove('is-on', 'is-fresh');
      thinking.hidden = true;
    }
  }

  /* ---------- the conversation ---------- */

  function answered() {
    var raw = (root.getAttribute('data-planner-answered') || '').split(',');
    return raw.map(function (value) { return value.trim(); }).filter(function (value) { return value !== ''; });
  }

  function pushAnswered(step) {
    var list = answered();
    if (list.indexOf(step) === -1) list.push(step);
    root.setAttribute('data-planner-answered', list.join(','));
  }

  function answerBubble(text) {
    var node = document.createElement('div');
    node.className = 'planner-msg is-visitor';
    node.innerHTML = '<div class="planner-bubble"></div>';
    node.querySelector('.planner-bubble').textContent = text;

    return node;
  }

  function botBubble(text) {
    var node = document.createElement('div');
    node.className = 'planner-msg is-bot is-plain';
    node.innerHTML = '<div class="planner-bubble"></div>';
    node.querySelector('.planner-bubble').textContent = text;

    return node;
  }

  function markDone(block) {
    if (!block) return;

    block.classList.add('is-done');
    var controls = block.querySelectorAll('button, input');
    for (var i = 0; i < controls.length; i++) {
      controls[i].disabled = true;
    }
  }

  function showError(block, message) {
    var node = block ? block.querySelector('[data-planner-error]') : null;
    if (!node) return;

    node.textContent = message || '';
    node.hidden = !message;
  }

  function setProgress(step) {
    for (var i = 0; i < trail.length; i++) {
      var index = i + 1;
      trail[i].classList.toggle('is-done', index < step);
      trail[i].classList.toggle('is-on', index === step);
    }
  }

  function payload(extra) {
    var state = root.__plannerState || {};
    var body = {
      answered: answered(),
      size: state.size || 'standard',
      wet: state.wet || 'shower',
      look: state.look || 'modern-chrome',
      w: state.w || '',
      l: state.l || ''
    };

    if (extra) {
      Object.keys(extra).forEach(function (key) { body[key] = extra[key]; });
    }

    return body;
  }

  /* ---------- one answer ---------- */

  function sendStep(step, values, block, bubbleText) {
    if (busy) return;

    busy = true;
    showError(block, '');
    markDone(block);

    if (questions && bubbleText) {
      questions.appendChild(answerBubble(bubbleText));
    }

    startWaiting();

    post(root.getAttribute('data-planner-step-endpoint'), payload(values)).then(function (json) {
      stopWaiting();
      busy = false;

      var data = json.data || {};

      if (!json.success) {
        /* the visitor can simply tap again — the question stays on screen */
        if (block) {
          block.classList.remove('is-done');
          var controls = block.querySelectorAll('button, input');
          for (var i = 0; i < controls.length; i++) controls[i].disabled = false;
        }
        showError(block, json.message || '');
        return;
      }

      pushAnswered(step === 'custom' ? 'size' : step);
      if (step === 'custom') pushAnswered('custom');

      if (typeof data.progress === 'number') setProgress(data.progress);

      if (data.html && questions) {
        var host = document.createElement('div');
        host.className = 'planner-question-slot';
        host.innerHTML = data.html.trim();
        while (host.firstChild) questions.appendChild(host.firstChild);
      }

      if (data.intro && data.intro.text && questions) {
        questions.appendChild(botBubble(data.intro.text));
      }

      if (data.plan_html && boardPlan) {
        boardPlan.innerHTML = data.plan_html;
        if (boardEmpty) boardEmpty.hidden = true;
        boardPlan.classList.add('is-ready');
        wirePlan();
        scrollToBoard();
      }
    });
  }

  /* ---------- the pieces of the finished plan ---------- */

  function wirePlan() {
    if (!boardPlan) return;

    var printButton = boardPlan.querySelector('[data-planner-print]');
    if (printButton) {
      printButton.addEventListener('click', function () { window.print(); });
    }

    var restart = boardPlan.querySelector('[data-planner-restart]');
    if (restart) {
      restart.addEventListener('click', function () {
        root.setAttribute('data-planner-answered', '');
        root.__plannerState = {};
        window.location.reload();
      });
    }

    var renderButton = boardPlan.querySelector('[data-planner-render]');
    var renderOut = boardPlan.querySelector('[data-planner-render-out]');
    if (renderButton && renderOut) {
      renderButton.addEventListener('click', function () {
        if (renderButton.classList.contains('is-busy')) return;

        renderButton.classList.add('is-busy');
        renderButton.disabled = true;
        renderOut.hidden = false;
        renderOut.classList.add('is-loading');
        renderOut.textContent = renderText || '';
        startWaiting();

        post(root.getAttribute('data-planner-render-endpoint'), payload({})).then(function (json) {
          stopWaiting();
          renderButton.classList.remove('is-busy');
          renderButton.disabled = false;
          renderOut.classList.remove('is-loading');

          if (!json.success) {
            renderOut.textContent = json.message || '';
            return;
          }

          var data = json.data || {};
          renderOut.innerHTML = '';
          var img = document.createElement('img');
          img.src = data.src || '';
          img.alt = data.alt || '';
          img.loading = 'lazy';
          renderOut.appendChild(img);
          renderOut.classList.add('is-image');
          renderButton.textContent = againText || renderButton.textContent;
        });
      });
    }

    var form = boardPlan.querySelector('[data-planner-send]');
    if (form) {
      var state = boardPlan.querySelector('[data-planner-send-state]');

      form.addEventListener('submit', function (event) {
        event.preventDefault();

        var field = form.querySelector('input[name="email"]');
        var button = form.querySelector('button');
        var email = field ? field.value.trim() : '';

        if (state) {
          state.hidden = false;
          state.textContent = sentWaiting || '';
          state.classList.remove('is-ok');
        }

        if (button) button.disabled = true;

        post(root.getAttribute('data-planner-send-endpoint'), payload({ email: email })).then(function (json) {
          if (button) button.disabled = false;

          if (!state) {
            showToast(json.message || '');
            return;
          }

          state.textContent = json.message || '';
          state.classList.toggle('is-ok', !!json.success);
        });
      });
    }
  }

  /* ---------- wiring ---------- */

  root.addEventListener('click', function (event) {
    var button = event.target.closest ? event.target.closest('[data-planner-answer]') : null;
    if (!button || button.disabled) return;

    var field = button.getAttribute('data-planner-answer');
    var value = button.getAttribute('data-planner-value');
    var block = button.closest('[data-planner-question]');
    var text = (button.querySelector('.planner-chip-text') || button).textContent.trim();

    root.__plannerState = root.__plannerState || {};

    if (field === 'custom') return; /* handled by the form below */

    if (field === 'size') {
      root.__plannerState.size = value;
      /* a preset size replaces any numbers typed before */
      root.__plannerState.w = '';
      root.__plannerState.l = '';
      sendStep('size', {}, block, text);
      return;
    }

    root.__plannerState[field] = value;
    sendStep(field, {}, block, text);
  });

  root.addEventListener('submit', function (event) {
    var form = event.target.closest ? event.target.closest('[data-planner-custom]') : null;
    if (!form) return;

    event.preventDefault();
    event.stopPropagation();

    var block = form.closest('[data-planner-question]');
    var w = parseInt((form.querySelector('input[name="w"]') || {}).value, 10);
    var l = parseInt((form.querySelector('input[name="l"]') || {}).value, 10);
    var send = form.querySelector('button[data-planner-answer]');

    if (!w || !l) {
      showError(block, customError);
      return;
    }

    root.__plannerState = root.__plannerState || {};
    root.__plannerState.size = 'custom';
    root.__plannerState.w = w;
    root.__plannerState.l = l;

    sendStep('custom', { w: w, l: l }, block, w + ' × ' + l + ' cm');
  });
})();
