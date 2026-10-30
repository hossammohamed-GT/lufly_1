/* Admin panel interactions: confirmations, click-to-edit product cards,
   repeatable editor rows (variants / specs / card features), finish picker,
   product relation pickers, category slug preview. */
(function () {
  'use strict';

  var csrfMeta = document.querySelector('meta[name="csrf-token"]');
  var csrfToken = csrfMeta ? csrfMeta.getAttribute('content') || '' : '';

  /* ------------------------------------------------------------- toast */
  var toastTimer = null;
  function toast(message, isError) {
    var el = document.getElementById('admin-toast');
    if (!el) {
      el = document.createElement('div');
      el.id = 'admin-toast';
      el.setAttribute('role', 'status');
      document.body.appendChild(el);
    }
    el.textContent = message;
    el.classList.toggle('is-error', !!isError);
    el.classList.add('is-visible');
    window.clearTimeout(toastTimer);
    toastTimer = window.setTimeout(function () { el.classList.remove('is-visible'); }, isError ? 4200 : 1800);
  }

  function flashSaved(el) {
    if (!el) return;
    el.classList.remove('is-saved');
    void el.offsetWidth;
    el.classList.add('is-saved');
    window.setTimeout(function () { el.classList.remove('is-saved'); }, 950);
  }

  function postJson(url, body) {
    return window.fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrfToken
      },
      body: JSON.stringify(body || {})
    }).then(function (response) {
      return response.json().catch(function () { return {}; }).then(function (data) {
        if (!response.ok || data.success === false) {
          throw new Error(data.message || ('HTTP ' + response.status));
        }
        return data;
      });
    });
  }

  function postFormData(url, formData) {
    return window.fetch(url, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrfToken
      },
      body: formData
    }).then(function (response) {
      return response.json().catch(function () { return {}; }).then(function (data) {
        if (!response.ok || data.success === false) {
          throw new Error(data.message || ('HTTP ' + response.status));
        }
        return data;
      });
    });
  }

  function fetchText(url) {
    return window.fetch(url, {
      headers: {
        'Accept': 'text/html',
        'X-Requested-With': 'XMLHttpRequest'
      }
    }).then(function (response) {
      if (!response.ok) { throw new Error('HTTP ' + response.status); }
      return response.text();
    });
  }

  function fetchJson(url, signal) {
    return window.fetch(url, {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      signal: signal
    }).then(function (response) {
      return response.json().catch(function () { return {}; }).then(function (data) {
        if (!response.ok || data.success === false) {
          throw new Error(data.message || ('HTTP ' + response.status));
        }
        return data;
      });
    });
  }

  function parseFragment(html) {
    var template = document.createElement('template');
    template.innerHTML = String(html || '').trim();
    return template.content.firstElementChild;
  }

  function assetUrl(path) {
    var value = String(path || '');
    if (!value || /^(?:https?:)?\/\//i.test(value) || value.charAt(0) === '/') { return value; }
    var base = document.documentElement.getAttribute('data-base') || '';
    return base + '/' + value.replace(/^\/+/, '');
  }

  function pickFile(options) {
    options = options || {};
    return new Promise(function (resolve) {
      var input = document.createElement('input');
      input.type = 'file';
      input.accept = options.accept || 'image/*';
      input.multiple = !!options.multiple;
      input.hidden = true;
      document.body.appendChild(input);

      var settled = false;
      function done(files) {
        if (settled) { return; }
        settled = true;
        window.setTimeout(function () { input.remove(); }, 0);
        resolve(files || []);
      }

      input.addEventListener('change', function () {
        done(Array.prototype.slice.call(input.files || []));
      });
      input.addEventListener('cancel', function () { done([]); });
      input.click();
      window.setTimeout(function () {
        if (!settled && !input.files.length) { /* the change/cancel events decide */ }
      }, 500);
    });
  }

  /* -------------------------------------------------------- confirmations */
  document.addEventListener('submit', function (event) {
    var form = event.target;
    if (!(form instanceof HTMLFormElement)) { return; }
    if (!form.hasAttribute('data-confirm')) { return; }
    if (!window.confirm(form.getAttribute('data-confirm') || 'Are you sure?')) {
      event.preventDefault();
    }
  });

  /* --------------------------------------------- category slug preview */
  var slugForm = document.querySelector('[data-category-form]');
  if (slugForm) {
    var parentSelect = slugForm.querySelector('[data-slug-parent]');
    var segmentInput = slugForm.querySelector('[data-slug-segment]');
    var preview = slugForm.querySelector('[data-slug-preview]');
    var updatePreview = function () {
      if (!parentSelect || !segmentInput || !preview) { return; }
      var option = parentSelect.options[parentSelect.selectedIndex];
      var parentSlug = option ? (option.getAttribute('data-slug') || '') : '';
      var segment = segmentInput.value.trim().toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
      preview.textContent = (parentSlug ? parentSlug + '.' : '') + (segment || '…');
    };
    if (parentSelect) parentSelect.addEventListener('change', updatePreview);
    if (segmentInput) segmentInput.addEventListener('input', updatePreview);
  }

  /* ------------------------------------------ inline editing (cards) */
  function rebuildFinishes(card, finishes) {
    var holder = card.querySelector('[data-finishes]');
    if (!holder) { return; }
    holder.textContent = '';
    (finishes || []).forEach(function (finish) {
      var dot = document.createElement('i');
      dot.className = 'adm-swatch adm-swatch--' + (finish.key || 'neutral');
      dot.title = finish.label || '';
      holder.appendChild(dot);
    });
  }

  function beginInlineEdit(el) {
    if (el.dataset.editing === '1') { return; }
    var card = el.closest('[data-inline-url]');
    if (!card) { return; }
    el.dataset.editing = '1';

    var original = el.dataset.value !== undefined ? el.dataset.value : el.textContent.trim();
    var type = el.getAttribute('data-input-type') || 'text';
    var input = document.createElement(type === 'textarea' ? 'textarea' : 'input');
    if (type !== 'textarea') { input.type = type; }
    input.className = 'input adm-inline-input';
    input.value = original;

    el.textContent = '';
    el.appendChild(input);
    input.focus();
    if (input.select) { input.select(); }

    var finished = false;
    function restore(value) {
      el.textContent = value;
      delete el.dataset.editing;
    }
    function commit() {
      if (finished) { return; }
      finished = true;
      var value = input.value;
      if (value.trim() === String(original).trim()) { restore(original); return; }
      postJson(card.getAttribute('data-inline-url'), {
        field: el.getAttribute('data-inline'),
        value: value,
        locale: el.getAttribute('data-locale') || undefined,
        feature_id: el.getAttribute('data-feature-id') || undefined,
        spec_id: el.getAttribute('data-spec-id') || undefined
      }).then(function (data) {
        var result = data.data || {};
        var next = result.value !== undefined ? String(result.value) : value;
        if (el.dataset.value !== undefined) { el.dataset.value = next; }
        restore(next);
        flashSaved(el);
        if (result.finishes) { rebuildFinishes(card, result.finishes); }
        if (result.slug) {
          var slugEl = card.querySelector('.cat-row-slug');
          if (slugEl) { slugEl.textContent = '/' + result.slug; }
        }
        document.dispatchEvent(new CustomEvent('admin:inline-saved', {
          detail: {
            element: el,
            host: card,
            field: el.getAttribute('data-inline'),
            result: result
          }
        }));
        toast(data.message || 'Saved.');
      }).catch(function (error) {
        restore(original);
        el.classList.add('is-error');
        window.setTimeout(function () { el.classList.remove('is-error'); }, 1600);
        toast(error.message || 'Could not save.', true);
      });
    }
    function cancel() {
      if (finished) { return; }
      finished = true;
      restore(original);
    }

    input.addEventListener('blur', commit);
    input.addEventListener('keydown', function (event) {
      if (event.key === 'Enter' && type !== 'textarea') { event.preventDefault(); input.blur(); }
      if (event.key === 'Escape') { cancel(); }
    });
    input.addEventListener('click', function (event) { event.stopPropagation(); });
  }

  document.addEventListener('click', function (event) {
    var el = event.target.closest ? event.target.closest('[data-inline]') : null;
    if (el && !event.target.closest('a, button, input, select, textarea')) {
      beginInlineEdit(el);
    }
  });
  document.addEventListener('keydown', function (event) {
    var el = event.target;
    if (el && el.hasAttribute && el.hasAttribute('data-inline') && (event.key === 'Enter' || event.key === 'F2')) {
      event.preventDefault();
      beginInlineEdit(el);
    }
  });

  /* inline selects on cards */
  document.addEventListener('focusin', function (event) {
    var select = event.target;
    if (select.matches && select.matches('[data-inline-select]')) {
      select.dataset.prev = select.value;
    }
  });
  document.addEventListener('change', function (event) {
    var select = event.target;
    if (!(select.matches && select.matches('[data-inline-select]'))) { return; }
    var card = select.closest('[data-inline-url]');
    if (!card) { return; }
    var previous = select.dataset.prev;
    postJson(card.getAttribute('data-inline-url'), {
      field: select.getAttribute('data-inline-select'),
      value: select.value
    }).then(function () {
      select.dataset.prev = select.value;
      flashSaved(select);
      toast('Saved.');
    }).catch(function (error) {
      if (previous !== undefined) { select.value = previous; }
      toast(error.message || 'Could not save.', true);
    });
  });

  /* featured star toggle */
  document.addEventListener('click', function (event) {
    var button = event.target.closest ? event.target.closest('[data-featured-toggle]') : null;
    if (!button) { return; }
    var card = button.closest('[data-featured-url]');
    if (!card) { return; }
    button.disabled = true;
    postJson(card.getAttribute('data-featured-url'), {})
      .then(function (data) {
        var result = data.data || {};
        var on = Number(result.is_featured) === 1;
        button.classList.toggle('is-on', on);
        button.setAttribute('aria-pressed', on ? 'true' : 'false');
        card.classList.toggle('is-featured', on);
        var orderEl = card.querySelector('[data-sort-order-display]');
        if (orderEl && result.sort_order !== undefined) { orderEl.textContent = String(result.sort_order); }
        toast('Saved.');
      })
      .catch(function (error) { toast(error.message || 'Could not save.', true); })
      .finally(function () { button.disabled = false; });
  });

  /* ------------------------------------------------------ editor JSON */
  function readJson(id) {
    var node = document.getElementById(id);
    if (!node) { return {}; }
    try { return JSON.parse(node.textContent || '{}'); } catch (error) { return {}; }
  }
  var presetsData = readJson('feature-presets-json');
  var iconsData = readJson('feature-icons-json');

  /* --------------------------------------------------------- repeaters */
  var rowCounter = 0;
  function featuresCount(scope) {
    var root = scope && scope.querySelector ? scope : document;
    var badge = root.querySelector('[data-features-count]');
    var box = root.querySelector('[data-repeater="features"]');
    if (!badge || !box) { return; }
    var count = box.querySelectorAll('[data-repeater-row]').length;
    badge.textContent = String(count);
    badge.classList.toggle('is-warn', count !== 0 && (count < 4 || count > 6));
  }

  function refreshFinishRow(row) {
    if (!row || !row.hasAttribute('data-spec-row')) { return; }
    var keyInput = row.querySelector('[data-spec-key-en]');
    var isFinish = !!keyInput && keyInput.value.trim().toLowerCase() === 'finish';
    row.classList.toggle('is-finish', isFinish);
  }

  function refreshIconPreview(select) {
    var row = select.closest('[data-repeater-row]');
    if (!row) { return; }
    var preview = row.querySelector('[data-icon-preview]');
    var icon = iconsData[select.value];
    if (preview) { preview.innerHTML = icon ? icon.svg : ''; }
  }

  function initRow(row) {
    refreshFinishRow(row);
    var iconSelect = row.querySelector('[data-icon-select]');
    if (iconSelect) { refreshIconPreview(iconSelect); }
  }

  function addRow(container, templateId) {
    var template = document.getElementById(templateId);
    if (!template || !container) { return null; }
    rowCounter += 1;
    var token = 'n' + Date.now().toString(36) + rowCounter;
    var wrapper = document.createElement('div');
    wrapper.innerHTML = template.innerHTML.split('__INDEX__').join(token);
    var row = wrapper.firstElementChild;
    if (!row) { return null; }
    container.appendChild(row);
    initRow(row);
    featuresCount(container.closest('form') || document);
    return row;
  }

  document.addEventListener('click', function (event) {
    var add = event.target.closest ? event.target.closest('[data-repeater-add]') : null;
    if (add) {
      var addScope = add.closest('form') || document;
      var container = addScope.querySelector('[data-repeater="' + add.getAttribute('data-target') + '"]');
      var row = addRow(container, add.getAttribute('data-template'));
      if (row) {
        var first = row.querySelector('input:not([type="hidden"]), select, textarea');
        if (first) { first.focus(); }
      }
      return;
    }

    var remove = event.target.closest ? event.target.closest('[data-repeater-remove]') : null;
    if (remove) {
      var rowToRemove = remove.closest('[data-repeater-row]');
      if (rowToRemove) {
        var removeScope = rowToRemove.closest('form') || document;
        rowToRemove.remove();
        featuresCount(removeScope);
      }
      return;
    }

    var up = event.target.closest ? event.target.closest('[data-repeater-up]') : null;
    var down = event.target.closest ? event.target.closest('[data-repeater-down]') : null;
    var mover = up || down;
    if (mover) {
      var row = mover.closest('[data-repeater-row]');
      if (!row || !row.parentNode) { return; }
      if (up && row.previousElementSibling) { row.parentNode.insertBefore(row, row.previousElementSibling); }
      if (down && row.nextElementSibling) { row.parentNode.insertBefore(row.nextElementSibling, row); }
    }
  });

  /* preset -> fill labels/icon, icon -> preview */
  document.addEventListener('change', function (event) {
    var target = event.target;
    if (target.matches && target.matches('[data-preset-select]')) {
      var row = target.closest('[data-repeater-row]');
      if (!row) { return; }
      var preset = presetsData[target.value] || null;
      row.classList.toggle('is-preset', !!preset);
      var iconSelect = row.querySelector('[data-icon-select]');
      if (preset && iconSelect && preset.icon) {
        iconSelect.value = preset.icon;
        refreshIconPreview(iconSelect);
      }
      row.querySelectorAll('[data-feature-label]').forEach(function (input) {
        var locale = input.getAttribute('data-feature-label');
        if (preset) {
          /* preset labels are fixed wording — never typed by hand */
          if (preset.labels && preset.labels[locale]) { input.value = preset.labels[locale]; }
          input.readOnly = true;
        } else {
          input.readOnly = false;
        }
      });
      if (preset) {
        row.querySelectorAll('[data-feature-value]').forEach(function (input) {
          var locale = input.getAttribute('data-feature-value');
          if (preset.placeholders && preset.placeholders[locale]) {
            input.placeholder = preset.placeholders[locale];
          }
        });
      }
    }

    if (target.matches && target.matches('[data-icon-select]')) {
      refreshIconPreview(target);
    }

    if (target.matches && target.matches('[data-finish-option]')) {
      var specRow = target.closest('[data-spec-row]');
      if (specRow) { syncFinishFromChecks(specRow); }
    }
  });

  /* finish rows: key watcher + manual value resync */
  document.addEventListener('input', function (event) {
    var target = event.target;
    if (target.matches && target.matches('[data-spec-key-en]')) {
      refreshFinishRow(target.closest('[data-repeater-row]'));
    }
    if (target.matches && target.matches('[data-spec-value="en"]')) {
      var row = target.closest('[data-spec-row]');
      if (row && row.classList.contains('is-finish')) { syncChecksFromValue(row); }
    }
  });

  function specValueInput(row, locale) {
    return row.querySelector('[data-spec-value="' + locale + '"]');
  }

  function syncFinishFromChecks(row) {
    var checked = Array.prototype.slice.call(row.querySelectorAll('[data-finish-option]:checked'));
    var en = specValueInput(row, 'en');
    if (en) { en.value = checked.map(function (c) { return c.value; }).join(' / '); }
    ['tr', 'cs'].forEach(function (locale) {
      var input = specValueInput(row, locale);
      if (input) {
        input.value = checked.map(function (c) { return c.getAttribute('data-' + locale) || c.value; }).join(' / ');
      }
    });
  }

  function syncChecksFromValue(row) {
    var en = specValueInput(row, 'en');
    if (!en) { return; }
    var parts = en.value.split('/').map(function (p) { return p.trim(); }).filter(Boolean);
    row.querySelectorAll('[data-finish-option]').forEach(function (checkbox) {
      checkbox.checked = parts.indexOf(checkbox.value) !== -1;
    });
  }

  document.addEventListener('click', function (event) {
    var button = event.target.closest ? event.target.closest('[data-add-finish]') : null;
    if (!button) { return; }
    var finishScope = button.closest('form') || document;
    var container = finishScope.querySelector('[data-repeater="specs"]');
    var row = addRow(container, button.getAttribute('data-template') || 'spec-template');
    if (!row) { return; }
    var keys = {};
    try { keys = JSON.parse(button.getAttribute('data-finish-keys') || '{}'); } catch (error) { keys = {}; }
    row.querySelectorAll('input[name*="[key]["]').forEach(function (input) {
      var match = /\[key\]\[([a-z]+)\]/.exec(input.name);
      if (match && keys[match[1]]) { input.value = keys[match[1]]; }
    });
    refreshFinishRow(row);
    row.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    var firstCheck = row.querySelector('[data-finish-option]');
    if (firstCheck) { firstCheck.focus(); }
  });

  /* ------------------------------------------- product relation pickers */
  function debounce(fn, wait) {
    var timer = null;
    return function () {
      var args = arguments, self = this;
      window.clearTimeout(timer);
      timer = window.setTimeout(function () { fn.apply(self, args); }, wait);
    };
  }

  function pickerEmptyNote(picker) {
    var note = picker.querySelector('[data-picker-empty]');
    var chips = picker.querySelector('[data-picker-chips]');
    if (note && chips) { note.hidden = chips.querySelectorAll('[data-id]').length > 0; }
  }

  function addPickerChip(picker, item) {
    var chips = picker.querySelector('[data-picker-chips]');
    if (!chips || chips.querySelector('[data-id="' + item.id + '"]')) { return; }
    var chip = document.createElement('span');
    chip.className = 'picker-chip';
    chip.setAttribute('data-id', String(item.id));

    var code = document.createElement('strong');
    code.textContent = item.model_code || ('#' + item.id);
    var name = document.createElement('span');
    name.textContent = item.name || '';
    var remove = document.createElement('button');
    remove.type = 'button';
    remove.setAttribute('data-picker-remove', '');
    remove.textContent = '×';
    var hidden = document.createElement('input');
    hidden.type = 'hidden';
    hidden.name = picker.getAttribute('data-field') + '[]';
    hidden.value = String(item.id);

    chip.appendChild(code);
    chip.appendChild(name);
    chip.appendChild(remove);
    chip.appendChild(hidden);
    chips.appendChild(chip);
    pickerEmptyNote(picker);
  }

  function initPicker(picker) {
    if (!picker || picker.dataset.pickerBound === '1') { return; }
    picker.dataset.pickerBound = '1';
    var search = picker.querySelector('[data-picker-search]');
    var results = picker.querySelector('[data-picker-results]');
    if (!search || !results) { return; }

    var runSearch = debounce(function () {
      var query = search.value.trim();
      if (query.length < 2) { results.hidden = true; results.textContent = ''; return; }
      var url = picker.getAttribute('data-search-url') + '?q=' + encodeURIComponent(query)
        + '&exclude=' + encodeURIComponent(picker.getAttribute('data-exclude') || '0');
      window.fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (response) { return response.json(); })
        .then(function (data) {
          results.textContent = '';
          (data.data || []).forEach(function (item) {
            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'picker-result';
            var code = document.createElement('strong');
            code.textContent = item.model_code || ('#' + item.id);
            var label = document.createElement('span');
            label.textContent = item.name || '';
            var status = document.createElement('small');
            status.textContent = item.status || '';
            button.appendChild(code);
            button.appendChild(label);
            button.appendChild(status);
            button.addEventListener('click', function () {
              addPickerChip(picker, item);
              search.value = '';
              results.hidden = true;
              results.textContent = '';
              search.focus();
            });
            results.appendChild(button);
          });
          results.hidden = !(data.data && data.data.length);
        })
        .catch(function () { results.hidden = true; });
    }, 260);

    search.addEventListener('input', runSearch);
    pickerEmptyNote(picker);
  }

  document.querySelectorAll('[data-picker]').forEach(initPicker);

  document.addEventListener('click', function (event) {
    var remove = event.target.closest ? event.target.closest('[data-picker-remove]') : null;
    if (remove) {
      var chip = remove.closest('.picker-chip');
      var picker = remove.closest('[data-picker]');
      if (chip) { chip.remove(); }
      if (picker) { pickerEmptyNote(picker); }
      return;
    }
    /* close any open picker results when clicking elsewhere */
    document.querySelectorAll('[data-picker-results]').forEach(function (results) {
      var picker = results.closest('[data-picker]');
      if (picker && !picker.contains(event.target)) { results.hidden = true; }
    });
  });

  /* ============================================================ v2 modules
     In-place everything: product grid + Miller category browser, card
     actions, lazy in-card editors, media manager + lightbox, drill-down
     category picker, categories tree page, collections/brands rows. */

  /* ---------------------------------------------------- dynamic fragments */
  function initDynamic(root) {
    if (!root || !root.querySelectorAll) { return; }
    root.querySelectorAll('[data-repeater-row]').forEach(initRow);
    if (root.matches && root.matches('[data-picker]')) { initPicker(root); }
    root.querySelectorAll('[data-picker]').forEach(initPicker);
    root.querySelectorAll('form').forEach(function (form) { featuresCount(form); });
    activateEditorStep(root, null);
  }

  function editorSlot(host) {
    return host ? host.querySelector(':scope > [data-editor]') : null;
  }

  function setEditorOpen(host, open, forceReload) {
    var slot = editorSlot(host);
    if (!slot) { return Promise.resolve(); }

    function apply() {
      slot.hidden = !open;
      host.classList.toggle('is-open', open);
      host.querySelectorAll('[data-expand-editor]').forEach(function (btn) {
        if (btn.hasAttribute('aria-expanded')) {
          btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        }
      });
    }

    if (open && (forceReload || slot.dataset.loaded !== '1')) {
      return fetchText(host.getAttribute('data-editor-url'))
        .then(function (html) {
          slot.innerHTML = html;
          slot.dataset.loaded = '1';
          initDynamic(slot);
          apply();
        })
        .catch(function (error) { toast(error.message || 'Could not load the editor.', true); });
    }

    apply();
    return Promise.resolve();
  }

  /* ------------------------------------------- product editor modal+steps
     The product "full edit" opens in one centered modal above everything,
     its sections shown one step at a time — no page-length scrolling. */
  var productEditorState = { card: null };

  function editorModalEls() {
    var modal = document.querySelector('[data-editor-modal]');
    if (!modal) { return null; }
    return {
      modal: modal,
      body: modal.querySelector('[data-editor-body]'),
      title: modal.querySelector('[data-editor-title]'),
    };
  }

  function editorTitleFor(card) {
    var codeEl = card.querySelector('[data-inline="model_code"]');
    var nameEl = card.querySelector('[data-inline="name"]');
    var parts = [];
    if (codeEl && codeEl.textContent.trim()) { parts.push(codeEl.textContent.trim()); }
    if (nameEl && nameEl.textContent.trim()) { parts.push(nameEl.textContent.trim()); }
    return parts.join(' · ');
  }

  function syncExpandAria(card, open) {
    if (!card) { return; }
    card.querySelectorAll('[data-expand-editor]').forEach(function (btn) {
      if (btn.hasAttribute('aria-expanded')) {
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
      }
    });
  }

  function activateEditorStep(scope, name) {
    if (!scope || !scope.querySelector) { return; }
    var root = scope.matches && scope.matches('[data-editor-root]')
      ? scope
      : scope.querySelector('[data-editor-root]');
    if (!root) { return; }
    var buttons = root.querySelectorAll('[data-step-btn]');
    var panels = root.querySelectorAll('[data-step-panel]');
    if (!buttons.length || !panels.length) { return; }

    var target = name || root.getAttribute('data-active-step');
    var exists = false;
    panels.forEach(function (panel) {
      if (panel.getAttribute('data-step-panel') === target) { exists = true; }
    });
    if (!target || !exists) { target = panels[0].getAttribute('data-step-panel'); }

    buttons.forEach(function (btn) {
      var active = btn.getAttribute('data-step-btn') === target;
      btn.classList.toggle('is-active', active);
      btn.setAttribute('aria-selected', active ? 'true' : 'false');
    });
    panels.forEach(function (panel) {
      panel.hidden = panel.getAttribute('data-step-panel') !== target;
    });
    root.setAttribute('data-active-step', target);
  }

  function openProductEditor(card, step) {
    var els = editorModalEls();
    if (!card || !els || !els.body) { return Promise.resolve(); }

    if (productEditorState.card && productEditorState.card !== card) {
      syncExpandAria(productEditorState.card, false);
    }
    var keepStep = step || null;
    if (!keepStep && productEditorState.card === card) {
      var oldRoot = els.body.querySelector('[data-editor-root]');
      keepStep = oldRoot ? oldRoot.getAttribute('data-active-step') : null;
    }

    productEditorState.card = card;
    if (els.title) {
      var title = editorTitleFor(card);
      if (title) { els.title.textContent = title; }
    }
    els.modal.hidden = false;
    document.body.classList.add('adm-modal-open');
    syncExpandAria(card, true);
    els.body.innerHTML = '<p class="adm-editor-loading" aria-busy="true">...</p>';

    return fetchText(card.getAttribute('data-editor-url'))
      .then(function (html) {
        if (productEditorState.card !== card || els.modal.hidden) { return; }
        els.body.innerHTML = html;
        initDynamic(els.body);
        activateEditorStep(els.body, keepStep);
        els.body.scrollTop = 0;
      })
      .catch(function (error) {
        toast(error.message || 'Could not load the editor.', true);
        closeProductEditor();
      });
  }

  function closeProductEditor() {
    var els = editorModalEls();
    if (!els) { return; }
    syncExpandAria(productEditorState.card, false);
    productEditorState.card = null;
    els.modal.hidden = true;
    if (els.body) { els.body.innerHTML = ''; }
    document.body.classList.remove('adm-modal-open');
  }

  document.addEventListener('click', function (event) {
    var stepBtn = event.target.closest ? event.target.closest('[data-step-btn]') : null;
    if (stepBtn) {
      var root = stepBtn.closest('[data-editor-root]');
      if (root) { activateEditorStep(root, stepBtn.getAttribute('data-step-btn')); }
      return;
    }
    if (event.target.closest && event.target.closest('[data-editor-close]')) {
      closeProductEditor();
    }
  });

  document.addEventListener('click', function (event) {
    var button = event.target.closest ? event.target.closest('[data-expand-editor]') : null;
    if (!button) { return; }
    var host = button.closest('[data-editor-url]');
    if (!host) { return; }

    /* Product cards edit in the centered modal; taxonomy rows stay inline. */
    if (host.hasAttribute('data-product-card')) {
      var els = editorModalEls();
      if (els && !els.modal.hidden && productEditorState.card === host) {
        closeProductEditor();
      } else {
        openProductEditor(host, button.classList.contains('adm-cover-empty') ? 'media' : null);
      }
      return;
    }

    var slot = editorSlot(host);
    setEditorOpen(host, !slot || slot.hidden, false);
  });

  /* ------------------------------------------------------- section saving */
  function refreshProductFace(card) {
    if (!card || !card.getAttribute('data-card-url')) { return Promise.resolve(); }
    return fetchText(card.getAttribute('data-card-url'))
      .then(function (html) {
        var fresh = parseFragment(html);
        if (!fresh) { return; }
        var newFace = fresh.querySelector('[data-card-face]');
        var oldFace = card.querySelector(':scope > [data-card-face]');
        if (newFace && oldFace) { oldFace.replaceWith(newFace); }
        card.className = fresh.className + (card.classList.contains('is-open') ? ' is-open' : '');
      })
      .catch(function () { /* the toast from the save already informed the admin */ });
  }

  function refreshMedia(card) {
    if (!card || !card.getAttribute('data-media-url')) { return Promise.resolve(); }
    var slot = null;
    var els = editorModalEls();
    if (els && els.body && !els.modal.hidden && productEditorState.card === card) {
      slot = els.body.querySelector('[data-media-slot]');
    }
    if (!slot) { slot = card.querySelector('[data-media-slot]'); }
    if (!slot) { return Promise.resolve(); }
    return fetchText(card.getAttribute('data-media-url'))
      .then(function (html) { slot.innerHTML = html; })
      .catch(function () { /* noop */ });
  }

  function syncGenericRow(form, row) {
    var nameEl = row.querySelector('[data-inline="name"]');
    if (nameEl) {
      var locale = nameEl.getAttribute('data-locale');
      var nameInput = locale ? form.elements['name_' + locale] : form.elements['name'];
      if (nameInput && nameInput.value.trim() !== '') { nameEl.textContent = nameInput.value.trim(); }
    }
    var slugInput = form.elements['slug'];
    var slugEl = row.querySelector('.cat-row-slug');
    if (slugInput && slugEl) { slugEl.textContent = '/' + slugInput.value.trim(); }
    var statusSelect = row.querySelector('[data-inline-select="status"]');
    if (statusSelect && form.elements['status']) {
      statusSelect.value = form.elements['status'].value;
      statusSelect.dataset.prev = statusSelect.value;
      row.classList.toggle('is-inactive', statusSelect.value !== 'active');
    }
    var sortEl = row.querySelector('[data-inline="sort_order"]');
    if (sortEl && form.elements['sort_order']) { sortEl.textContent = form.elements['sort_order'].value; }
    ['country', 'website'].forEach(function (field) {
      var el = row.querySelector('[data-inline="' + field + '"]');
      if (el && form.elements[field]) { el.textContent = form.elements[field].value.trim(); }
    });
  }

  document.addEventListener('submit', function (event) {
    var form = event.target;
    if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-section-form')) { return; }
    event.preventDefault();

    var submitter = form.querySelector('[type="submit"]');
    if (submitter) { submitter.disabled = true; }

    postFormData(form.action, new FormData(form))
      .then(function (data) {
        var flag = form.querySelector('[data-saved-flag]');
        if (flag) {
          flag.hidden = false;
          window.setTimeout(function () { flag.hidden = true; }, 1800);
        }
        toast(data.message || 'Saved.');

        var productCard = mediaCardOf(form);
        if (productCard) {
          if (form.getAttribute('data-section-form') === 'documents') {
            openProductEditor(productCard);
          } else {
            refreshProductFace(productCard);
          }
          return;
        }

        var categoryRow = form.closest('[data-category-row]');
        if (categoryRow) {
          refreshCategoryTree(categoryRow.getAttribute('data-id'));
          return;
        }

        var genericRow = form.closest('[data-generic-row]');
        if (genericRow) { syncGenericRow(form, genericRow); }
      })
      .catch(function (error) { toast(error.message || 'Could not save.', true); })
      .finally(function () { if (submitter) { submitter.disabled = false; } });
  });

  /* --------------------------------------------------------- category data */
  var categoryTreeData = readJson('category-tree-json');
  if (!Array.isArray(categoryTreeData)) { categoryTreeData = []; }
  var categoryById = {};
  var categoryPathById = {};

  (function indexCategories() {
    function walk(nodes, trail) {
      (nodes || []).forEach(function (node) {
        var next = trail.concat([node]);
        categoryById[String(node.id)] = node;
        categoryPathById[String(node.id)] = next;
        walk(node.children || [], next);
      });
    }
    walk(categoryTreeData, []);
  })();

  function categoryPathLabel(id) {
    var node = categoryById[String(id)];
    if (!node) { return ''; }
    if (node.path) { return String(node.path).replace(/ → /g, ' › '); }
    var trail = categoryPathById[String(id)] || [node];
    return trail.map(function (n) { return n.name; }).join(' › ');
  }

  /* ------------------------------------------- drill-down category picker */
  var catpop = document.querySelector('[data-catpop]');
  var catpopState = { anchor: null, trail: [], emptyLabel: '' };

  function closeCatpop() {
    if (!catpop) { return; }
    catpop.hidden = true;
    catpopState.anchor = null;
  }

  function catpopNodes() {
    var trail = catpopState.trail;
    if (!trail.length) { return categoryTreeData; }
    return trail[trail.length - 1].children || [];
  }

  function renderCatpop() {
    if (!catpop) { return; }
    var crumbs = catpop.querySelector('[data-catpop-crumbs]');
    var list = catpop.querySelector('[data-catpop-list]');
    var isParentMode = !!(catpopState.anchor && catpopState.anchor.hasAttribute('data-picker-field'));
    crumbs.textContent = '';
    list.textContent = '';

    var rootCrumb = document.createElement('button');
    rootCrumb.type = 'button';
    rootCrumb.className = 'adm-catcrumb';
    rootCrumb.textContent = '⌂';
    rootCrumb.addEventListener('click', function () {
      catpopState.trail = [];
      renderCatpop();
    });
    crumbs.appendChild(rootCrumb);

    catpopState.trail.forEach(function (node, index) {
      var sep = document.createElement('span');
      sep.className = 'adm-catcrumb-sep';
      sep.textContent = '›';
      crumbs.appendChild(sep);
      var crumb = document.createElement('button');
      crumb.type = 'button';
      crumb.className = 'adm-catcrumb';
      crumb.textContent = node.name;
      crumb.addEventListener('click', function () {
        catpopState.trail = catpopState.trail.slice(0, index + 1);
        renderCatpop();
      });
      crumbs.appendChild(crumb);
    });

    catpopNodes().forEach(function (node) {
      var hasChildren = (node.children || []).length > 0;
      var row = document.createElement('div');
      row.className = 'adm-catpop-item';

      var main = document.createElement('button');
      main.type = 'button';
      main.className = 'adm-catpop-main';
      var label = document.createElement('span');
      label.textContent = node.name;
      main.appendChild(label);
      if (node.count !== undefined) {
        var count = document.createElement('small');
        count.textContent = String(node.count);
        main.appendChild(count);
      }
      if (hasChildren) {
        var arrow = document.createElement('span');
        arrow.className = 'adm-catpop-arrow';
        arrow.textContent = '›';
        main.appendChild(arrow);
      }
      main.addEventListener('click', function () {
        if (hasChildren) {
          catpopState.trail.push(node);
          renderCatpop();
        } else {
          commitCategoryPick(node.id);
        }
      });
      row.appendChild(main);

      if (isParentMode) {
        var choose = document.createElement('button');
        choose.type = 'button';
        choose.className = 'adm-catpop-choose';
        choose.textContent = '✓';
        choose.title = node.name;
        choose.addEventListener('click', function () { commitCategoryPick(node.id); });
        row.appendChild(choose);
      }

      list.appendChild(row);
    });

    if (!catpopNodes().length) {
      var empty = document.createElement('p');
      empty.className = 'adm-catpop-empty';
      empty.textContent = '—';
      list.appendChild(empty);
    }
  }

  function commitCategoryPick(id) {
    var anchor = catpopState.anchor;
    if (!anchor) { closeCatpop(); return; }
    var numericId = Number(id) || 0;
    var labelEl = anchor.querySelector('[data-cat-label]');
    var label = numericId > 0 ? categoryPathLabel(numericId) : (catpopState.emptyLabel || '—');

    function apply() {
      anchor.setAttribute('data-category-id', String(numericId));
      anchor.classList.toggle('is-empty', numericId === 0);
      if (labelEl) { labelEl.textContent = label; }
      closeCatpop();
    }

    if (anchor.hasAttribute('data-picker-field')) {
      var fieldWrap = anchor.closest('.field');
      var hidden = fieldWrap ? fieldWrap.querySelector('input[name="' + anchor.getAttribute('data-picker-field') + '"]') : null;
      if (hidden) { hidden.value = String(numericId); }
      apply();
      return;
    }

    var card = anchor.closest('[data-inline-url]');
    if (!card) { apply(); return; }
    postJson(card.getAttribute('data-inline-url'), { field: 'category_id', value: numericId })
      .then(function (data) {
        apply();
        toast(data.message || 'Saved.');
      })
      .catch(function (error) { toast(error.message || 'Could not save.', true); });
  }

  function openCatpop(anchor) {
    if (!catpop) { return; }
    catpopState.anchor = anchor;
    var currentId = Number(anchor.getAttribute('data-category-id') || 0);
    var labelEl = anchor.querySelector('[data-cat-label]');
    catpopState.emptyLabel = currentId === 0 && labelEl ? labelEl.textContent.trim() : catpopState.emptyLabel;
    var trail = currentId > 0 ? (categoryPathById[String(currentId)] || []) : [];
    /* open one level above the current choice so siblings stay visible */
    catpopState.trail = trail.slice(0, Math.max(0, trail.length - 1));
    renderCatpop();

    catpop.hidden = false;
    var rect = anchor.getBoundingClientRect();
    var width = 330;
    var left = Math.max(8, Math.min(rect.left, window.innerWidth - width - 8));
    var top = rect.bottom + 6;
    if (top + 380 > window.innerHeight) { top = Math.max(8, rect.top - 386); }
    catpop.style.left = left + 'px';
    catpop.style.top = top + 'px';
  }

  if (catpop) {
    document.addEventListener('click', function (event) {
      var anchor = event.target.closest ? event.target.closest('[data-cat-picker]') : null;
      if (anchor) {
        event.preventDefault();
        if (!catpop.hidden && catpopState.anchor === anchor) { closeCatpop(); return; }
        openCatpop(anchor);
        return;
      }
      if (event.target.closest && event.target.closest('[data-catpop-close]')) { closeCatpop(); return; }
      if (event.target.closest && event.target.closest('[data-catpop-clear]')) { commitCategoryPick(0); return; }
      if (!catpop.hidden && !catpop.contains(event.target)) { closeCatpop(); }
    });
    window.addEventListener('resize', closeCatpop);
  }

  /* ------------------------------------------------- card feature popover */
  var featpop = document.querySelector('[data-featpop]');
  var featpopState = { card: null, anchor: null, featureId: 0, locale: 'en' };

  function closeFeatpop() {
    if (!featpop) { return; }
    featpop.hidden = true;
    featpopState.card = null;
    featpopState.anchor = null;
  }

  function featpopPresetOptions(selectedId, locale) {
    var select = featpop.querySelector('[data-featpop-preset]');
    select.innerHTML = '';
    var placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = featpop.getAttribute('data-preset-placeholder') || '—';
    select.appendChild(placeholder);
    Object.keys(presetsData).forEach(function (id) {
      var preset = presetsData[id] || {};
      var labels = preset.labels || {};
      var option = document.createElement('option');
      option.value = id;
      option.textContent = labels[locale] || labels.en || ('#' + id);
      if (Number(id) === Number(selectedId)) { option.selected = true; }
      select.appendChild(option);
    });
  }

  function refreshFeatpopIcon() {
    var select = featpop.querySelector('[data-featpop-preset]');
    var preview = featpop.querySelector('[data-featpop-icon]');
    var preset = presetsData[select.value];
    var icon = preset ? iconsData[preset.icon] : null;
    if (preview) { preview.innerHTML = icon ? icon.svg : ''; }
    var input = featpop.querySelector('[data-featpop-value]');
    if (input && preset && preset.placeholders) {
      input.placeholder = preset.placeholders[featpopState.locale] || preset.placeholders.en || '';
    }
  }

  function openFeatpop(anchor) {
    if (!featpop) { return; }
    var card = anchor.closest('[data-inline-url]');
    if (!card) { return; }
    closeCatpop();
    featpopState.card = card;
    featpopState.anchor = anchor;
    featpopState.featureId = Number(anchor.getAttribute('data-feature-id') || 0);
    featpopState.locale = anchor.getAttribute('data-locale') || 'en';
    featpopPresetOptions(anchor.getAttribute('data-preset-id') || '', featpopState.locale);
    featpop.querySelector('[data-featpop-value]').value = anchor.getAttribute('data-value') || '';
    var del = featpop.querySelector('[data-featpop-delete]');
    if (del) { del.hidden = featpopState.featureId <= 0; }
    refreshFeatpopIcon();

    featpop.hidden = false;
    var rect = anchor.getBoundingClientRect();
    var width = 330;
    var left = Math.max(8, Math.min(rect.left, window.innerWidth - width - 8));
    var top = rect.bottom + 6;
    if (top + 300 > window.innerHeight) { top = Math.max(8, rect.top - 306); }
    featpop.style.left = left + 'px';
    featpop.style.top = top + 'px';
    var select = featpop.querySelector('[data-featpop-preset]');
    if (select) { select.focus(); }
  }

  function saveFeatpop(action) {
    var card = featpopState.card;
    if (!card) { return; }
    var payload = {
      field: 'card_feature',
      action: action,
      feature_id: featpopState.featureId || undefined,
      locale: featpopState.locale
    };
    if (action === 'upsert') {
      payload.preset_id = featpop.querySelector('[data-featpop-preset]').value;
      payload.value = featpop.querySelector('[data-featpop-value]').value;
    }
    postJson(card.getAttribute('data-inline-url'), payload)
      .then(function (data) {
        closeFeatpop();
        refreshProductFace(card);
        toast(data.message || 'Saved.');
      })
      .catch(function (error) { toast(error.message || 'Could not save.', true); });
  }

  if (featpop) {
    featpop.querySelector('[data-featpop-preset]').addEventListener('change', refreshFeatpopIcon);
    featpop.querySelector('[data-featpop-value]').addEventListener('keydown', function (event) {
      if (event.key === 'Enter') { event.preventDefault(); saveFeatpop('upsert'); }
    });
    document.addEventListener('click', function (event) {
      var anchor = event.target.closest ? event.target.closest('[data-feature-chip], [data-feature-add]') : null;
      if (anchor) {
        event.preventDefault();
        if (!featpop.hidden && featpopState.anchor === anchor) { closeFeatpop(); return; }
        openFeatpop(anchor);
        return;
      }
      if (event.target.closest && event.target.closest('[data-featpop-close]')) { closeFeatpop(); return; }
      if (event.target.closest && event.target.closest('[data-featpop-save]')) { saveFeatpop('upsert'); return; }
      if (event.target.closest && event.target.closest('[data-featpop-delete]')) { saveFeatpop('delete'); return; }
      if (!featpop.hidden && !featpop.contains(event.target)) { closeFeatpop(); }
    });
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && !featpop.hidden) { closeFeatpop(); }
    });
    window.addEventListener('resize', closeFeatpop);
  }

  /* --------------------------------------------------- card finish popover */
  var finishpop = document.querySelector('[data-finishpop]');
  var finishCatalogData = readJson('finish-catalog-json');
  if (!Array.isArray(finishCatalogData)) { finishCatalogData = []; }
  var finishpopState = { card: null, anchor: null, locale: 'en' };

  function closeFinishpop() {
    if (!finishpop) { return; }
    finishpop.hidden = true;
    finishpopState.card = null;
    finishpopState.anchor = null;
  }

  function openFinishpop(anchor) {
    if (!finishpop) { return; }
    var card = anchor.closest('[data-inline-url]');
    if (!card) { return; }
    closeCatpop();
    closeFeatpop();
    finishpopState.card = card;
    finishpopState.anchor = anchor;
    finishpopState.locale = anchor.getAttribute('data-locale') || 'en';
    var chosen = (anchor.getAttribute('data-value') || '').split('/')
      .map(function (p) { return p.trim(); }).filter(Boolean);

    var list = finishpop.querySelector('[data-finishpop-list]');
    list.innerHTML = '';
    finishCatalogData.forEach(function (option) {
      var label = document.createElement('label');
      label.className = 'adm-finishpop-option';
      var box = document.createElement('input');
      box.type = 'checkbox';
      box.value = option.label;
      box.checked = chosen.indexOf(option.label) !== -1;
      label.appendChild(box);
      var swatch = document.createElement('i');
      swatch.className = 'adm-swatch adm-swatch--' + (option.swatch || 'neutral');
      label.appendChild(swatch);
      var name = document.createElement('span');
      var names = { en: option.label, tr: option.tr, cs: option.cs };
      name.textContent = names[finishpopState.locale] || option.label;
      label.appendChild(name);
      list.appendChild(label);
    });

    finishpop.hidden = false;
    var rect = anchor.getBoundingClientRect();
    var width = 330;
    var left = Math.max(8, Math.min(rect.left, window.innerWidth - width - 8));
    var top = rect.bottom + 6;
    if (top + 420 > window.innerHeight) { top = Math.max(8, rect.top - 426); }
    finishpop.style.left = left + 'px';
    finishpop.style.top = top + 'px';
  }

  function saveFinishpop() {
    var card = finishpopState.card;
    if (!card) { return; }
    var values = Array.prototype.slice.call(
      finishpop.querySelectorAll('[data-finishpop-list] input[type="checkbox"]:checked')
    ).map(function (box) { return box.value; });
    postJson(card.getAttribute('data-inline-url'), {
      field: 'finish_set',
      value: values,
      locale: finishpopState.locale
    }).then(function (data) {
      closeFinishpop();
      refreshProductFace(card);
      toast(data.message || 'Saved.');
    }).catch(function (error) { toast(error.message || 'Could not save.', true); });
  }

  if (finishpop) {
    document.addEventListener('click', function (event) {
      var anchor = event.target.closest ? event.target.closest('[data-finish-open]') : null;
      if (anchor) {
        event.preventDefault();
        if (!finishpop.hidden && finishpopState.anchor === anchor) { closeFinishpop(); return; }
        openFinishpop(anchor);
        return;
      }
      if (event.target.closest && event.target.closest('[data-finishpop-close]')) { closeFinishpop(); return; }
      if (event.target.closest && event.target.closest('[data-finishpop-clear]')) {
        finishpop.querySelectorAll('[data-finishpop-list] input[type="checkbox"]').forEach(function (box) {
          box.checked = false;
        });
        return;
      }
      if (event.target.closest && event.target.closest('[data-finishpop-save]')) { saveFinishpop(); return; }
      if (!finishpop.hidden && !finishpop.contains(event.target)) { closeFinishpop(); }
    });
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && !finishpop.hidden) { closeFinishpop(); }
    });
    window.addEventListener('resize', closeFinishpop);
  }

  /* ---------------------------------------------------- card cover carousel */
  var carouselTimers = new WeakMap();

  function carouselSlides(root) {
    try { return JSON.parse(root.getAttribute('data-gallery') || '[]'); }
    catch (error) { return []; }
  }

  function carouselShow(root, index) {
    var slides = carouselSlides(root);
    if (!slides.length) { return; }
    var total = slides.length;
    var next = ((index % total) + total) % total;
    root.dataset.index = String(next);
    var slide = slides[next];
    var img = root.querySelector('[data-carousel-img]');
    var stage = root.querySelector('[data-carousel-stage]');
    if (img && slide.src && img.getAttribute('src') !== slide.src) {
      img.classList.add('is-fading');
      var token = String(next);
      var swap = new Image();
      swap.onload = function () {
        if (root.dataset.index === token) { img.src = slide.src; }
        img.classList.remove('is-fading');
      };
      swap.onerror = function () { img.classList.remove('is-fading'); };
      swap.src = slide.src;
      if (swap.complete && swap.naturalWidth) {
        img.src = slide.src;
        img.classList.remove('is-fading');
      }
    }
    if (stage) {
      /* the lightbox reads these, so it always opens the visible slide */
      stage.setAttribute('data-attachment-id', String(slide.attachment_id || ''));
      stage.setAttribute('data-path', slide.path || '');
    }
    var count = root.querySelector('[data-carousel-count]');
    if (count) { count.textContent = (next + 1) + ' / ' + total; }
  }

  function carouselStep(root, direction) {
    carouselShow(root, (Number(root.dataset.index) || 0) + direction);
  }

  function carouselAutoStart(root) {
    if (carouselTimers.has(root)) { return; }
    carouselSlides(root).forEach(function (slide) {
      if (slide.src) { var preload = new Image(); preload.src = slide.src; }
    });
    var timer = window.setInterval(function () {
      if (!document.contains(root)) {
        window.clearInterval(timer);
        carouselTimers.delete(root);
        return;
      }
      carouselStep(root, 1);
    }, 3000);
    carouselTimers.set(root, timer);
  }

  function carouselAutoStop(root) {
    var timer = carouselTimers.get(root);
    if (timer) {
      window.clearInterval(timer);
      carouselTimers.delete(root);
    }
    carouselShow(root, 0);
  }

  document.addEventListener('click', function (event) {
    var prev = event.target.closest ? event.target.closest('[data-carousel-prev]') : null;
    var nextBtn = event.target.closest ? event.target.closest('[data-carousel-next]') : null;
    if (!prev && !nextBtn) { return; }
    event.preventDefault();
    var root = (prev || nextBtn).closest('[data-carousel]');
    if (root) { carouselStep(root, prev ? -1 : 1); }
  });
  document.addEventListener('mouseover', function (event) {
    var root = event.target.closest ? event.target.closest('[data-carousel]') : null;
    if (root && !root.contains(event.relatedTarget)) { carouselAutoStart(root); }
  });
  document.addEventListener('mouseout', function (event) {
    var root = event.target.closest ? event.target.closest('[data-carousel]') : null;
    if (root && !root.contains(event.relatedTarget)) { carouselAutoStop(root); }
  });

  /* ------------------------------------------------------- quick-add wizard */
  var wizard = document.querySelector('[data-wizard]');
  var wizardState = {
    step: 1, productId: 0, inlineUrl: '', uploadUrl: '', cardUrl: '',
    appliedCategory: 0, busy: false, inserted: false,
    counts: { photos: 0, drawings: 0 }, opts: {}
  };

  function wizardEl(selector) { return wizard ? wizard.querySelector(selector) : null; }

  function wizardError(message) {
    var box = wizardEl('[data-wizard-error]');
    if (!box) { return; }
    box.hidden = !message;
    box.textContent = message || '';
  }

  function wizardBusy(on) {
    wizardState.busy = on;
    ['[data-wizard-next]', '[data-wizard-back]', '[data-wizard-finish]'].forEach(function (sel) {
      var btn = wizardEl(sel);
      if (btn) { btn.disabled = on; }
    });
  }

  function wizardShowStep(step) {
    wizardState.step = step;
    wizardError('');
    wizard.querySelectorAll('[data-wizard-panel]').forEach(function (panel) {
      panel.hidden = Number(panel.getAttribute('data-wizard-panel')) !== step;
    });
    wizard.querySelectorAll('[data-wizard-step-label]').forEach(function (label) {
      var n = Number(label.getAttribute('data-wizard-step-label'));
      label.classList.toggle('is-active', n === step);
      label.classList.toggle('is-done', n < step);
    });
    wizardEl('[data-wizard-back]').hidden = step === 1;
    wizardEl('[data-wizard-next]').hidden = step === 4;
    wizardEl('[data-wizard-finish]').hidden = step !== 4;
    if (step === 4) { wizardSummary(); }
    if (step === 1) {
      var nameInput = wizardEl('[data-wizard-name="en"]');
      if (nameInput) { nameInput.focus(); }
    }
  }

  function wizardCategoryId() {
    var anchor = wizardEl('[data-wizard-category]');
    return anchor ? Number(anchor.getAttribute('data-category-id') || 0) : 0;
  }

  function wizardFindNode(nodes, id) {
    for (var i = 0; i < nodes.length; i++) {
      if (Number(nodes[i].id) === id) { return nodes[i]; }
      var found = wizardFindNode(nodes[i].children || [], id);
      if (found) { return found; }
    }
    return null;
  }

  function wizardPresetCategory(id) {
    var anchor = wizardEl('[data-wizard-category]');
    if (!anchor) { return; }
    var label = anchor.querySelector('[data-cat-label]');
    anchor.setAttribute('data-category-id', '0');
    anchor.classList.add('is-empty');
    if (label) { label.textContent = anchor.getAttribute('title') || label.textContent; }
    if (!id) { return; }
    var tree = readJson('category-tree-json');
    var node = Array.isArray(tree) ? wizardFindNode(tree, id) : null;
    if (!node || (node.children || []).length) { return; } /* only leaves hold products */
    anchor.setAttribute('data-category-id', String(id));
    anchor.classList.remove('is-empty');
    if (label) { label.textContent = categoryPathLabel(id) || node.name; }
  }

  function wizardInline(payload) {
    return postJson(wizardState.inlineUrl, payload);
  }

  function wizardCreateIfNeeded() {
    if (wizardState.productId) { return Promise.resolve(); }
    var categoryId = wizardCategoryId();
    return postJson(wizardState.opts.draftUrl, { category_id: categoryId })
      .then(function (data) {
        wizardState.productId = Number(data.id) || 0;
        wizardState.appliedCategory = categoryId;
        var card = parseFragment(data.html || '');
        if (card) {
          wizardState.inlineUrl = card.getAttribute('data-inline-url') || '';
          wizardState.cardUrl = card.getAttribute('data-card-url') || '';
          var editorUrl = card.getAttribute('data-editor-url') || '';
          wizardState.uploadUrl = editorUrl.replace(/\/editor$/, '/media');
        }
      });
  }

  function wizardApplyBasics() {
    return wizardCreateIfNeeded().then(function () {
      var chain = wizardInline({ field: 'name', value: wizardEl('[data-wizard-name="en"]').value.trim(), locale: 'en' });
      ['tr', 'cs'].forEach(function (loc) {
        var v = wizardEl('[data-wizard-name="' + loc + '"]').value.trim();
        if (v) {
          chain = chain.then(function () { return wizardInline({ field: 'name', value: v, locale: loc }); });
        }
      });
      var code = wizardEl('[data-wizard-code]').value.trim();
      if (code) {
        chain = chain.then(function () { return wizardInline({ field: 'model_code', value: code }); });
      }
      var price = wizardEl('[data-wizard-price]').value.trim();
      if (price !== '' && Number(price) > 0) {
        chain = chain.then(function () { return wizardInline({ field: 'price', value: price }); });
      }
      var categoryId = wizardCategoryId();
      if (categoryId !== wizardState.appliedCategory) {
        chain = chain.then(function () {
          return wizardInline({ field: 'category_id', value: categoryId }).then(function () {
            wizardState.appliedCategory = categoryId;
          });
        });
      }
      return chain;
    });
  }

  function wizardUpload(section, button) {
    if (!wizardState.uploadUrl) { return; }
    pickFile({ multiple: true }).then(function (files) {
      if (!files.length) { return null; }
      var fd = new FormData();
      fd.append('section', section);
      files.forEach(function (file) { fd.append('images[]', file); });
      button.disabled = true;
      return postFormData(wizardState.uploadUrl, fd)
        .then(function (data) {
          var attachments = ((data.data || {}).attachments || []);
          var box = wizardEl('[data-wizard-thumbs="' + section + '"]');
          attachments.forEach(function (att) {
            wizardState.counts[section] += 1;
            if (box && att.path) {
              var img = document.createElement('img');
              img.src = assetUrl(att.path);
              img.alt = '';
              box.appendChild(img);
            }
          });
          var errors = ((data.data || {}).errors || []);
          if (errors.length) { wizardError(errors[0]); }
        })
        .finally(function () { button.disabled = false; });
    }).catch(function (error) { wizardError(error.message || 'Upload failed.'); });
  }

  function wizardBuildFinishes() {
    var list = wizardEl('[data-wizard-finishes]');
    if (!list) { return; }
    list.innerHTML = '';
    finishCatalogData.forEach(function (option) {
      var label = document.createElement('label');
      label.className = 'adm-finishpop-option';
      var box = document.createElement('input');
      box.type = 'checkbox';
      box.value = option.label;
      box.setAttribute('data-wizard-finish', '');
      label.appendChild(box);
      var swatch = document.createElement('i');
      swatch.className = 'adm-swatch adm-swatch--' + (option.swatch || 'neutral');
      label.appendChild(swatch);
      var name = document.createElement('span');
      name.textContent = option.label;
      label.appendChild(name);
      list.appendChild(label);
    });
  }

  function wizardFeatureRow() {
    var row = document.createElement('div');
    row.className = 'adm-wizard-feature-row';
    row.setAttribute('data-wizard-feature-row', '');
    var select = document.createElement('select');
    select.className = 'input';
    select.setAttribute('data-wizard-feature-preset', '');
    var placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = wizard.getAttribute('data-preset-placeholder') || '—';
    select.appendChild(placeholder);
    Object.keys(presetsData).forEach(function (id) {
      var preset = presetsData[id] || {};
      var labels = preset.labels || {};
      var option = document.createElement('option');
      option.value = id;
      option.textContent = labels.en || ('#' + id);
      select.appendChild(option);
    });
    var value = document.createElement('input');
    value.className = 'input';
    value.type = 'text';
    value.maxLength = 255;
    value.setAttribute('data-wizard-feature-value', '');
    value.placeholder = wizard.getAttribute('data-value-label') || '';
    select.addEventListener('change', function () {
      var preset = presetsData[select.value];
      if (preset && preset.placeholders && preset.placeholders.en) {
        value.placeholder = preset.placeholders.en;
      }
    });
    var remove = document.createElement('button');
    remove.type = 'button';
    remove.className = 'btn btn-sm btn-ghost';
    remove.textContent = '×';
    remove.addEventListener('click', function () { row.remove(); });
    row.appendChild(select);
    row.appendChild(value);
    row.appendChild(remove);
    return row;
  }

  function wizardApplyLook() {
    var finishes = Array.prototype.slice.call(wizard.querySelectorAll('[data-wizard-finish]:checked'))
      .map(function (box) { return box.value; });
    var chain = Promise.resolve();
    if (finishes.length) {
      chain = chain.then(function () {
        return wizardInline({ field: 'finish_set', value: finishes, locale: 'en' });
      });
    }
    wizard.querySelectorAll('[data-wizard-feature-row]').forEach(function (row) {
      var presetId = row.querySelector('[data-wizard-feature-preset]').value;
      var value = row.querySelector('[data-wizard-feature-value]').value.trim();
      if (!presetId) { return; }
      chain = chain.then(function () {
        return wizardInline({
          field: 'card_feature', action: 'upsert',
          feature_id: Number(row.dataset.featureId) || undefined,
          preset_id: presetId, value: value, locale: 'en'
        }).then(function (data) {
          var fid = (data.data || {}).feature_id;
          if (fid) { row.dataset.featureId = String(fid); }
        });
      });
    });
    return chain;
  }

  function wizardSummary() {
    var box = wizardEl('[data-wizard-summary]');
    if (!box) { return; }
    box.textContent = '';
    var name = wizardEl('[data-wizard-name="en"]').value.trim();
    var title = document.createElement('strong');
    title.textContent = name || '—';
    box.appendChild(title);
    var bits = [];
    var code = wizardEl('[data-wizard-code]').value.trim();
    if (code) { bits.push(code); }
    if (wizardCategoryId() > 0) {
      var anchor = wizardEl('[data-wizard-category]');
      var catLabel = anchor && anchor.querySelector('[data-cat-label]');
      if (catLabel) { bits.push(catLabel.textContent.trim()); }
    }
    var price = wizardEl('[data-wizard-price]').value.trim();
    if (price) { bits.push(price); }
    ['photos', 'drawings'].forEach(function (section) {
      var zone = wizardEl('[data-wizard-thumbs="' + section + '"]');
      var zoneTitle = zone && zone.closest('.adm-wizard-zone')
        ? zone.closest('.adm-wizard-zone').querySelector('strong') : null;
      bits.push(wizardState.counts[section] + ' × ' + (zoneTitle ? zoneTitle.textContent.trim() : section));
    });
    var line = document.createElement('p');
    line.textContent = bits.join(' · ');
    box.appendChild(line);
  }

  function wizardInsertCard() {
    if (!wizardState.productId || !wizardState.cardUrl || !wizardState.opts.onCreated) {
      return Promise.resolve();
    }
    return fetchText(wizardState.cardUrl).then(function (html) {
      wizardState.inserted = true;
      wizardState.opts.onCreated({ id: wizardState.productId, html: html });
    }).catch(function () { /* the grid shows it on next refresh */ });
  }

  function closeQuickAddWizard() {
    if (!wizard) { return; }
    wizard.hidden = true;
    document.body.classList.remove('adm-modal-open');
  }

  function wizardRequestClose() {
    if (wizardState.busy) { return; }
    if (wizardState.productId && !wizardState.inserted) {
      /* a draft was already created — surface its card instead of hiding it */
      wizardBusy(true);
      wizardInsertCard().finally(function () {
        wizardBusy(false);
        closeQuickAddWizard();
      });
      return;
    }
    closeQuickAddWizard();
  }

  function wizardNext() {
    if (!wizard || wizardState.busy) { return; }
    if (wizardState.step === 1) {
      var nameInput = wizardEl('[data-wizard-name="en"]');
      if (!nameInput.value.trim()) {
        wizardError(wizard.getAttribute('data-msg-name-required') || 'Name required.');
        nameInput.focus();
        return;
      }
      wizardBusy(true);
      wizardApplyBasics()
        .then(function () { wizardShowStep(2); })
        .catch(function (error) { wizardError(error.message || 'Could not save.'); })
        .finally(function () { wizardBusy(false); });
    } else if (wizardState.step === 2) {
      wizardShowStep(3);
    } else if (wizardState.step === 3) {
      wizardBusy(true);
      wizardApplyLook()
        .then(function () { wizardShowStep(4); })
        .catch(function (error) { wizardError(error.message || 'Could not save.'); })
        .finally(function () { wizardBusy(false); });
    }
  }

  function wizardFinish() {
    if (!wizard || wizardState.busy) { return; }
    var publish = wizard.querySelector('[data-wizard-publish]:checked');
    var chain = Promise.resolve();
    if (publish && publish.value === 'active') {
      chain = wizardInline({ field: 'status', value: 'active' });
    }
    wizardBusy(true);
    chain.then(function () {
      return wizardInsertCard();
    }).then(function () {
      closeQuickAddWizard();
      toast(wizard.getAttribute('data-msg-created') || 'Saved.');
    }).catch(function (error) {
      wizardError(error.message || 'Could not save.');
    }).finally(function () { wizardBusy(false); });
  }

  function openQuickAddWizard(opts) {
    if (!wizard) { return; }
    wizardState = {
      step: 1, productId: 0, inlineUrl: '', uploadUrl: '', cardUrl: '',
      appliedCategory: 0, busy: false, inserted: false,
      counts: { photos: 0, drawings: 0 }, opts: opts || {}
    };
    wizard.querySelectorAll('input[type="text"], input[type="number"]').forEach(function (input) {
      input.value = '';
    });
    wizard.querySelectorAll('[data-wizard-thumbs]').forEach(function (box) { box.innerHTML = ''; });
    var features = wizardEl('[data-wizard-features]');
    if (features) { features.innerHTML = ''; }
    wizardBuildFinishes();
    wizardPresetCategory(Number(wizardState.opts.categoryId) || 0);
    var publishDefault = wizard.querySelector('[data-wizard-publish][value="active"]');
    if (publishDefault) { publishDefault.checked = true; }
    wizard.hidden = false;
    document.body.classList.add('adm-modal-open');
    wizardShowStep(1);
  }

  if (wizard) {
    wizard.addEventListener('click', function (event) {
      var t = event.target;
      if (!t.closest) { return; }
      if (t.closest('[data-wizard-close]')) { wizardRequestClose(); return; }
      if (t.closest('[data-wizard-next]')) { wizardNext(); return; }
      if (t.closest('[data-wizard-back]')) {
        if (!wizardState.busy) { wizardShowStep(Math.max(1, wizardState.step - 1)); }
        return;
      }
      if (t.closest('[data-wizard-finish]')) { wizardFinish(); return; }
      var uploadBtn = t.closest('[data-wizard-upload]');
      if (uploadBtn) { wizardUpload(uploadBtn.getAttribute('data-wizard-upload'), uploadBtn); return; }
      if (t.closest('[data-wizard-feature-add]')) {
        var box = wizardEl('[data-wizard-features]');
        if (box && box.querySelectorAll('[data-wizard-feature-row]').length < 6) {
          box.appendChild(wizardFeatureRow());
        }
      }
    });
    wizard.addEventListener('keydown', function (event) {
      if (event.key === 'Enter' && event.target.matches
          && event.target.matches('input[type="text"], input[type="number"]')) {
        event.preventDefault();
        if (wizardState.step < 4) { wizardNext(); }
      }
    });
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && !wizard.hidden) { wizardRequestClose(); }
    });
  }

  /* ------------------------------------------------------- product browser */
  function setupProductsBrowser() {
    var root = document.querySelector('[data-products-browser]');
    if (!root) { return; }

    var state = readJson('grid-state-json');
    state = {
      q: state.q || '',
      status: state.status || '',
      sort: state.sort || 'model',
      featured: Number(state.featured) ? 1 : 0,
      is_new: Number(state.is_new) ? 1 : 0,
      incomplete: Number(state.incomplete) ? 1 : 0,
      category_id: Number(state.category_id) || 0,
      page: Math.max(1, Number(state.page) || 1)
    };

    var grid = root.querySelector('[data-grid]');
    var totalEl = root.querySelector('[data-grid-total]');
    var pager = root.querySelector('[data-grid-pager]');
    var pagerLabel = root.querySelector('[data-pager-label]');
    var columns = Array.prototype.slice.call(root.querySelectorAll('[data-catcol]'));
    var crumbsEl = root.querySelector('[data-catcrumbs]');
    var browserPath = [];
    var aborter = null;

    function stateQuery() {
      var params = new URLSearchParams();
      if (state.q) { params.set('q', state.q); }
      if (state.status) { params.set('status', state.status); }
      if (state.sort && state.sort !== 'model') { params.set('sort', state.sort); }
      if (state.featured) { params.set('featured', '1'); }
      if (state.is_new) { params.set('is_new', '1'); }
      if (state.incomplete) { params.set('incomplete', '1'); }
      if (state.category_id) { params.set('category_id', String(state.category_id)); }
      if (state.page > 1) { params.set('page', String(state.page)); }
      return params;
    }

    function loadGrid() {
      if (aborter) { aborter.abort(); }
      aborter = typeof AbortController !== 'undefined' ? new AbortController() : null;
      grid.classList.add('is-loading');

      var params = stateQuery();
      params.set('page', String(state.page));
      if (state.sort) { params.set('sort', state.sort); }

      fetchJson(root.getAttribute('data-grid-url') + '?' + params.toString(), aborter ? aborter.signal : undefined)
        .then(function (data) {
          grid.innerHTML = data.html || '';
          if (totalEl) { totalEl.textContent = String(data.total || 0); }
          state.page = Number(data.page) || 1;
          var pages = Math.max(1, Number(data.pages) || 1);
          if (pager) {
            pager.dataset.page = String(state.page);
            pager.dataset.pages = String(pages);
            var prev = pager.querySelector('[data-pager="prev"]');
            var next = pager.querySelector('[data-pager="next"]');
            if (prev) { prev.disabled = state.page <= 1; }
            if (next) { next.disabled = state.page >= pages; }
          }
          if (pagerLabel) { pagerLabel.textContent = state.page + ' / ' + pages; }
          var qs = stateQuery().toString();
          window.history.replaceState(null, '', window.location.pathname + (qs ? '?' + qs : ''));
        })
        .catch(function (error) {
          if (error && error.name === 'AbortError') { return; }
          toast(error.message || 'Could not load products.', true);
        })
        .finally(function () { grid.classList.remove('is-loading'); });
    }

    function nodesAt(level) {
      if (level === 0) { return categoryTreeData; }
      var parent = categoryById[String(browserPath[level - 1])];
      return parent ? (parent.children || []) : [];
    }

    function renderBrowser() {
      crumbsEl.textContent = '';
      var all = document.createElement('button');
      all.type = 'button';
      all.className = 'adm-catcrumb' + (browserPath.length === 0 ? ' is-active' : '');
      all.textContent = '⌂';
      all.addEventListener('click', function () {
        browserPath = [];
        state.category_id = 0;
        state.page = 1;
        renderBrowser();
        loadGrid();
      });
      crumbsEl.appendChild(all);

      browserPath.forEach(function (id, index) {
        var node = categoryById[String(id)];
        if (!node) { return; }
        var sep = document.createElement('span');
        sep.className = 'adm-catcrumb-sep';
        sep.textContent = '›';
        crumbsEl.appendChild(sep);
        var crumb = document.createElement('button');
        crumb.type = 'button';
        crumb.className = 'adm-catcrumb' + (index === browserPath.length - 1 ? ' is-active' : '');
        crumb.textContent = node.name;
        crumb.addEventListener('click', function () {
          browserPath = browserPath.slice(0, index + 1);
          state.category_id = id;
          state.page = 1;
          renderBrowser();
          loadGrid();
        });
        crumbsEl.appendChild(crumb);
      });

      columns.forEach(function (col, level) {
        var nodes = nodesAt(level);
        var list = col.querySelector('[data-catlist]');
        var search = col.querySelector('[data-catsearch]');
        var query = search ? search.value.trim().toLowerCase() : '';
        col.hidden = nodes.length === 0;
        list.textContent = '';

        nodes.forEach(function (node) {
          if (query && String(node.name).toLowerCase().indexOf(query) === -1) { return; }
          var item = document.createElement('button');
          item.type = 'button';
          item.className = 'adm-catitem'
            + (Number(browserPath[level]) === Number(node.id) ? ' is-active' : '')
            + (node.status && node.status !== 'active' ? ' is-inactive' : '');
          var name = document.createElement('span');
          name.textContent = node.name;
          item.appendChild(name);
          if (node.count !== undefined) {
            var count = document.createElement('small');
            count.textContent = String(node.count);
            item.appendChild(count);
          }
          if ((node.children || []).length) {
            var arrow = document.createElement('span');
            arrow.className = 'adm-catpop-arrow';
            arrow.textContent = '›';
            item.appendChild(arrow);
          }
          item.addEventListener('click', function () {
            browserPath = browserPath.slice(0, level).concat([Number(node.id)]);
            state.category_id = Number(node.id);
            state.page = 1;
            renderBrowser();
            loadGrid();
          });
          list.appendChild(item);
        });
      });
    }

    columns.forEach(function (col) {
      var search = col.querySelector('[data-catsearch]');
      if (search) { search.addEventListener('input', renderBrowser); }
    });

    if (state.category_id > 0) {
      browserPath = (categoryPathById[String(state.category_id)] || []).map(function (n) { return Number(n.id); });
    }
    renderBrowser();

    var runSearch = debounce(function () {
      state.page = 1;
      loadGrid();
    }, 300);

    root.querySelectorAll('[data-filter]').forEach(function (input) {
      var key = input.getAttribute('data-filter');
      input.addEventListener(input.tagName === 'SELECT' ? 'change' : 'input', function () {
        state[key] = input.value;
        if (input.tagName === 'SELECT') {
          state.page = 1;
          loadGrid();
        } else {
          runSearch();
        }
      });
    });

    root.querySelectorAll('[data-filter-toggle]').forEach(function (toggle) {
      toggle.addEventListener('click', function () {
        var key = toggle.getAttribute('data-filter-toggle');
        state[key] = state[key] ? 0 : 1;
        toggle.classList.toggle('is-on', !!state[key]);
        toggle.setAttribute('aria-pressed', state[key] ? 'true' : 'false');
        state.page = 1;
        loadGrid();
      });
    });

    if (pager) {
      pager.addEventListener('click', function (event) {
        var button = event.target.closest ? event.target.closest('[data-pager]') : null;
        if (!button || button.disabled) { return; }
        var pages = Math.max(1, Number(pager.dataset.pages) || 1);
        if (button.getAttribute('data-pager') === 'prev') { state.page = Math.max(1, state.page - 1); }
        if (button.getAttribute('data-pager') === 'next') { state.page = Math.min(pages, state.page + 1); }
        loadGrid();
        root.scrollIntoView({ block: 'start', behavior: 'smooth' });
      });
      var initPrev = pager.querySelector('[data-pager="prev"]');
      var initNext = pager.querySelector('[data-pager="next"]');
      if (initPrev) { initPrev.disabled = state.page <= 1; }
      if (initNext) { initNext.disabled = state.page >= Math.max(1, Number(pager.dataset.pages) || 1); }
    }

    window.addEventListener('popstate', function () {
      var params = new URLSearchParams(window.location.search);
      state.q = params.get('q') || '';
      state.status = params.get('status') || '';
      state.sort = params.get('sort') || 'model';
      state.featured = params.get('featured') === '1' ? 1 : 0;
      state.is_new = params.get('is_new') === '1' ? 1 : 0;
      state.category_id = Number(params.get('category_id')) || 0;
      state.page = Math.max(1, Number(params.get('page')) || 1);
      root.querySelectorAll('[data-filter]').forEach(function (input) {
        input.value = state[input.getAttribute('data-filter')] || '';
      });
      root.querySelectorAll('[data-filter-toggle]').forEach(function (toggle) {
        var on = !!state[toggle.getAttribute('data-filter-toggle')];
        toggle.classList.toggle('is-on', on);
        toggle.setAttribute('aria-pressed', on ? 'true' : 'false');
      });
      browserPath = state.category_id > 0
        ? (categoryPathById[String(state.category_id)] || []).map(function (n) { return Number(n.id); })
        : [];
      renderBrowser();
      loadGrid();
    });

    var addButton = root.querySelector('[data-add-product]');
    if (addButton) {
      addButton.addEventListener('click', function () {
        /* the easy path: a 4-step wizard over the same endpoints */
        openQuickAddWizard({
          draftUrl: root.getAttribute('data-draft-url'),
          categoryId: state.category_id,
          onCreated: function (data) {
            var emptyNote = grid.querySelector('.media-empty');
            if (emptyNote) { emptyNote.remove(); }
            var card = parseFragment(data.html);
            if (card) {
              grid.insertBefore(card, grid.firstChild);
              card.scrollIntoView({ block: 'center', behavior: 'smooth' });
            }
            if (totalEl) { totalEl.textContent = String((Number(totalEl.textContent) || 0) + 1); }
          }
        });
      });
    }
  }

  /* ----------------------------------------------------- product card bits */
  document.addEventListener('click', function (event) {
    var target = event.target;
    if (!target.closest) { return; }

    var newToggle = target.closest('[data-new-toggle]');
    if (newToggle) {
      var newCard = newToggle.closest('[data-inline-url]');
      if (!newCard) { return; }
      var nextNew = newToggle.getAttribute('aria-pressed') !== 'true';
      newToggle.disabled = true;
      postJson(newCard.getAttribute('data-inline-url'), { field: 'is_new', value: nextNew ? 1 : 0 })
        .then(function (data) {
          var on = Number((data.data || {}).value) === 1;
          newToggle.classList.toggle('is-on', on);
          newToggle.setAttribute('aria-pressed', on ? 'true' : 'false');
          toast(data.message || 'Saved.');
        })
        .catch(function (error) { toast(error.message || 'Could not save.', true); })
        .finally(function () { newToggle.disabled = false; });
      return;
    }

    var memberDot = target.closest('[data-member-card]');
    if (memberDot) {
      var memberCard = memberDot.closest('[data-product-card]');
      if (!memberCard || memberDot.classList.contains('is-active')) { return; }
      fetchText(memberDot.getAttribute('data-member-card'))
        .then(function (html) {
          var fresh = parseFragment(html);
          if (fresh) { memberCard.replaceWith(fresh); }
        })
        .catch(function (error) { toast(error.message || 'Could not switch color.', true); });
      return;
    }

    var deleteProduct = target.closest('[data-delete-product]');
    if (deleteProduct) {
      var productCard = deleteProduct.closest('[data-product-card]');
      if (!productCard) { return; }
      if (!window.confirm(deleteProduct.getAttribute('data-confirm') || 'Are you sure?')) { return; }
      deleteProduct.disabled = true;
      postJson(productCard.getAttribute('data-delete-url'), {})
        .then(function (data) {
          productCard.remove();
          toast(data.message || 'Deleted.');
        })
        .catch(function (error) {
          deleteProduct.disabled = false;
          toast(error.message || 'Could not delete.', true);
        });
    }
  });

  /* ------------------------------------------------------------- media */
  var chooser = document.querySelector('[data-type-chooser]');
  var chooserResolve = null;

  function chooseImageType() {
    if (!chooser) { return Promise.resolve('photos'); }
    return new Promise(function (resolve) {
      chooserResolve = resolve;
      chooser.hidden = false;
    });
  }

  function settleChooser(value) {
    if (!chooser) { return; }
    chooser.hidden = true;
    if (chooserResolve) {
      var resolve = chooserResolve;
      chooserResolve = null;
      resolve(value);
    }
  }

  if (chooser) {
    chooser.addEventListener('click', function (event) {
      var choice = event.target.closest ? event.target.closest('[data-type-choice]') : null;
      if (choice) { settleChooser(choice.getAttribute('data-type-choice')); return; }
      if (event.target.closest && event.target.closest('[data-type-cancel]')) { settleChooser(null); }
    });
  }

  function mediaCardOf(el) {
    var card = el && el.closest ? el.closest('[data-product-card]') : null;
    if (card) { return card; }
    /* Inside the editor modal the card is found via the editor root's id. */
    var root = el && el.closest ? el.closest('[data-editor-root]') : null;
    var pid = root ? root.getAttribute('data-product-id') : null;
    return pid ? document.querySelector('[data-product-card="' + pid + '"]') : null;
  }

  function afterMediaChange(card) {
    return refreshMedia(card).then(function () { return refreshProductFace(card); });
  }

  document.addEventListener('click', function (event) {
    var target = event.target;
    if (!target.closest) { return; }

    var addMedia = target.closest('[data-media-add]');
    if (addMedia) {
      var mediaRoot = addMedia.closest('[data-media-root]');
      var addCard = mediaCardOf(addMedia);
      if (!mediaRoot) { return; }
      chooseImageType().then(function (section) {
        if (!section) { return null; }
        return pickFile({ multiple: true }).then(function (files) {
          if (!files.length) { return null; }
          var fd = new FormData();
          fd.append('section', section);
          files.forEach(function (file) { fd.append('images[]', file); });
          addMedia.disabled = true;
          return postFormData(mediaRoot.getAttribute('data-upload-url'), fd)
            .then(function (data) {
              toast(data.message || 'Saved.');
              var errors = (data.data || {}).errors || [];
              if (errors.length) { toast(errors[0], true); }
              return afterMediaChange(addCard);
            })
            .finally(function () { addMedia.disabled = false; });
        });
      }).catch(function (error) { toast(error.message || 'Upload failed.', true); });
      return;
    }

    var replaceAllBtn = target.closest('[data-media-replace-all]');
    if (replaceAllBtn) {
      if (!window.confirm(replaceAllBtn.getAttribute('data-confirm') || 'Are you sure?')) { return; }
      var replaceCard = mediaCardOf(replaceAllBtn);
      pickFile({ multiple: true }).then(function (files) {
        if (!files.length) { return null; }
        var fd = new FormData();
        fd.append('section', replaceAllBtn.getAttribute('data-section') || 'photos');
        files.forEach(function (file) { fd.append('images[]', file); });
        replaceAllBtn.disabled = true;
        return postFormData(replaceAllBtn.getAttribute('data-url'), fd)
          .then(function (data) {
            toast(data.message || 'Saved.');
            var errors = (data.data || {}).errors || [];
            if (errors.length) { toast(errors[0], true); }
            return afterMediaChange(replaceCard);
          })
          .finally(function () { replaceAllBtn.disabled = false; });
      }).catch(function (error) { toast(error.message || 'Upload failed.', true); });
      return;
    }

    var orderBtn = target.closest('[data-media-order]');
    if (orderBtn) {
      postJson(orderBtn.getAttribute('data-url'), { direction: orderBtn.getAttribute('data-media-order') })
        .then(function () { return refreshMedia(mediaCardOf(orderBtn)); })
        .catch(function (error) { toast(error.message || 'Could not reorder.', true); });
      return;
    }

    var primaryBtn = target.closest('[data-media-primary]');
    if (primaryBtn) {
      postJson(primaryBtn.getAttribute('data-url'), {})
        .then(function (data) {
          toast(data.message || 'Saved.');
          return afterMediaChange(mediaCardOf(primaryBtn));
        })
        .catch(function (error) { toast(error.message || 'Could not save.', true); });
      return;
    }

    var deleteBtn = target.closest('[data-media-delete]');
    if (deleteBtn) {
      if (!window.confirm(deleteBtn.getAttribute('data-confirm') || 'Are you sure?')) { return; }
      postJson(deleteBtn.getAttribute('data-url'), {})
        .then(function (data) {
          toast(data.message || 'Deleted.');
          return afterMediaChange(mediaCardOf(deleteBtn));
        })
        .catch(function (error) { toast(error.message || 'Could not delete.', true); });
    }
  });

  /* ------------------------------------------------------------ lightbox */
  var lightbox = document.querySelector('.adm-lightbox');
  var lightboxState = null;

  function deriveMediaUrls(card, attachmentId) {
    if (!card) { return {}; }
    var mediaUrl = card.getAttribute('data-media-url') || '';
    var base = mediaUrl.replace(/\/media-fragment$/, '');
    if (!base || base === mediaUrl) { return {}; }
    return {
      replace: base + '/media/' + attachmentId + '/replace',
      destroy: base + '/media/' + attachmentId + '/delete',
      primary: base + '/media/' + attachmentId + '/primary',
      move: base + '/media/' + attachmentId + '/section'
    };
  }

  function syncLightboxPrimary() {
    if (!lightbox || !lightboxState) { return; }
    var btn = lightbox.querySelector('[data-lightbox-primary]');
    if (btn) {
      btn.hidden = !(lightboxState.section === 'photos' && !lightboxState.primary && lightboxState.urls.primary);
    }
  }

  function openLightbox(trigger) {
    if (!lightbox) { return; }
    var card = mediaCardOf(trigger);
    var attachmentId = trigger.getAttribute('data-attachment-id') || '';
    var derived = deriveMediaUrls(card, attachmentId);
    lightboxState = {
      card: card,
      attachmentId: attachmentId,
      section: trigger.getAttribute('data-section') || 'photos',
      primary: trigger.getAttribute('data-primary') === '1',
      urls: {
        replace: trigger.getAttribute('data-url-replace') || derived.replace || '',
        destroy: trigger.getAttribute('data-url-destroy') || derived.destroy || '',
        primary: trigger.getAttribute('data-url-primary') || derived.primary || '',
        move: trigger.getAttribute('data-url-move') || derived.move || ''
      }
    };

    var img = lightbox.querySelector('[data-lightbox-img]');
    var sourceImg = trigger.querySelector('img');
    if (img) {
      img.src = sourceImg ? (sourceImg.currentSrc || sourceImg.src) : assetUrl(trigger.getAttribute('data-path'));
      img.alt = sourceImg ? (sourceImg.alt || '') : '';
    }
    var caption = lightbox.querySelector('[data-lightbox-caption]');
    if (caption) { caption.textContent = trigger.getAttribute('data-caption') || ''; }
    var select = lightbox.querySelector('[data-lightbox-section]');
    if (select) { select.value = lightboxState.section; }
    syncLightboxPrimary();

    lightbox.hidden = false;
    document.body.style.overflow = 'hidden';
    var closeBtn = lightbox.querySelector('.adm-lightbox-actions [data-lightbox-close]');
    if (closeBtn) { closeBtn.focus(); }
  }

  function closeLightbox() {
    if (!lightbox) { return; }
    lightbox.hidden = true;
    lightboxState = null;
    document.body.style.overflow = '';
  }

  if (lightbox) {
    document.addEventListener('click', function (event) {
      var trigger = event.target.closest ? event.target.closest('[data-lightbox="product"]') : null;
      if (trigger) { openLightbox(trigger); }
    });

    lightbox.addEventListener('click', function (event) {
      var target = event.target;
      if (!target.closest || !lightboxState) {
        if (target.closest && target.closest('[data-lightbox-close]')) { closeLightbox(); }
        return;
      }
      if (target.closest('[data-lightbox-close]')) { closeLightbox(); return; }

      if (target.closest('[data-lightbox-primary]')) {
        if (!lightboxState.urls.primary) { return; }
        postJson(lightboxState.urls.primary, {})
          .then(function (data) {
            lightboxState.primary = true;
            syncLightboxPrimary();
            toast(data.message || 'Saved.');
            return afterMediaChange(lightboxState.card);
          })
          .catch(function (error) { toast(error.message || 'Could not save.', true); });
        return;
      }

      if (target.closest('[data-lightbox-replace]')) {
        if (!lightboxState.urls.replace) { return; }
        pickFile({ multiple: false }).then(function (files) {
          if (!files.length || !lightboxState) { return; }
          var fd = new FormData();
          fd.append('image', files[0]);
          postFormData(lightboxState.urls.replace, fd)
            .then(function (data) {
              var payload = data.data || {};
              var img = lightbox.querySelector('[data-lightbox-img]');
              if (img && payload.path) { img.src = assetUrl(payload.path) + '?t=' + Date.now(); }
              toast(data.message || 'Saved.');
              return afterMediaChange(lightboxState.card);
            })
            .catch(function (error) { toast(error.message || 'Replace failed.', true); });
        });
        return;
      }

      if (target.closest('[data-lightbox-delete]')) {
        if (!lightboxState.urls.destroy) { return; }
        if (!window.confirm('Are you sure?')) { return; }
        var card = lightboxState.card;
        postJson(lightboxState.urls.destroy, {})
          .then(function (data) {
            toast(data.message || 'Deleted.');
            closeLightbox();
            return afterMediaChange(card);
          })
          .catch(function (error) { toast(error.message || 'Could not delete.', true); });
      }
    });

    lightbox.addEventListener('change', function (event) {
      var select = event.target;
      if (!lightboxState || !select.matches || !select.matches('[data-lightbox-section]')) { return; }
      if (!lightboxState.urls.move) { return; }
      var previous = lightboxState.section;
      postJson(lightboxState.urls.move, { section: select.value })
        .then(function (data) {
          lightboxState.section = (data.data || {}).section || select.value;
          if (lightboxState.section !== 'photos') { lightboxState.primary = false; }
          syncLightboxPrimary();
          toast(data.message || 'Saved.');
          return afterMediaChange(lightboxState.card);
        })
        .catch(function (error) {
          select.value = previous;
          toast(error.message || 'Could not move.', true);
        });
    });
  }

  document.addEventListener('keydown', function (event) {
    if (event.key !== 'Escape') { return; }
    if (chooser && !chooser.hidden) { settleChooser(null); return; }
    if (lightbox && !lightbox.hidden) { closeLightbox(); return; }
    if (catpop && !catpop.hidden) { closeCatpop(); return; }
    var editorModal = document.querySelector('[data-editor-modal]');
    if (editorModal && !editorModal.hidden) { closeProductEditor(); }
  });

  /* ------------------------------------------------------- categories page */
  function categoryPageRoot() {
    return document.querySelector('[data-categories-page]');
  }

  function refreshCategoryTree(reopenId) {
    var page = categoryPageRoot();
    if (!page) { return Promise.resolve(); }
    var tree = page.querySelector('[data-category-tree]');
    return fetchJson(page.getAttribute('data-tree-url'))
      .then(function (data) {
        if (tree) { tree.innerHTML = data.html || ''; }
        if (reopenId) {
          var row = page.querySelector('[data-category-row="' + reopenId + '"]');
          if (row) {
            row.scrollIntoView({ block: 'center', behavior: 'smooth' });
            return setEditorOpen(row, true, true);
          }
        }
        return null;
      })
      .catch(function (error) { toast(error.message || 'Could not refresh.', true); });
  }

  function quickCategory(name, parentId) {
    var page = categoryPageRoot();
    if (!page || !name.trim()) { return Promise.resolve(); }
    return postJson(page.getAttribute('data-quick-url'), { name: name.trim(), parent_id: parentId || 0 })
      .then(function (data) {
        toast(data.message || 'Saved.');
        return refreshCategoryTree(data.id || null);
      });
  }

  function setupCategoriesPage() {
    var page = categoryPageRoot();
    if (!page) { return; }

    var search = page.querySelector('[data-category-search]');
    if (search) {
      search.addEventListener('input', function () {
        var query = search.value.trim().toLowerCase();
        page.querySelectorAll('[data-category-row]').forEach(function (row) {
          var haystack = (row.getAttribute('data-name') || '') + ' ' + (row.getAttribute('data-path') || '');
          row.hidden = query !== '' && haystack.indexOf(query) === -1;
        });
      });
    }

    var addRoot = page.querySelector('[data-quick-add-root]');
    var rootName = page.querySelector('[data-quick-name]');
    function submitRoot() {
      if (!rootName || !rootName.value.trim()) { return; }
      var value = rootName.value;
      quickCategory(value, 0)
        .then(function () { rootName.value = ''; rootName.focus(); })
        .catch(function (error) { toast(error.message || 'Could not create.', true); });
    }
    if (addRoot) { addRoot.addEventListener('click', submitRoot); }
    if (rootName) {
      rootName.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') { event.preventDefault(); submitRoot(); }
      });
    }

    page.addEventListener('click', function (event) {
      var target = event.target;
      if (!target.closest) { return; }

      var childBtn = target.closest('[data-quick-child]');
      if (childBtn) {
        var parentRow = childBtn.closest('[data-category-row]');
        if (!parentRow || parentRow.nextElementSibling && parentRow.nextElementSibling.classList.contains('is-new-row')) { return; }
        var depth = Math.min(3, Number(parentRow.getAttribute('data-depth') || 1) + 1);
        var temp = document.createElement('div');
        temp.className = 'cat-row is-depth-' + depth + ' is-new-row';
        var main = document.createElement('div');
        main.className = 'cat-row-main';
        var input = document.createElement('input');
        input.type = 'text';
        input.className = 'input input-sm';
        input.maxLength = 255;
        input.placeholder = rootName ? rootName.placeholder : '';
        main.appendChild(input);
        temp.appendChild(main);
        parentRow.after(temp);
        input.focus();

        var done = false;
        function cancel() { if (!done) { done = true; temp.remove(); } }
        input.addEventListener('keydown', function (keyEvent) {
          if (keyEvent.key === 'Enter') {
            keyEvent.preventDefault();
            if (!input.value.trim()) { cancel(); return; }
            done = true;
            quickCategory(input.value, Number(parentRow.getAttribute('data-id') || 0))
              .catch(function (error) { toast(error.message || 'Could not create.', true); })
              .finally(function () { temp.remove(); });
          }
          if (keyEvent.key === 'Escape') { cancel(); }
        });
        input.addEventListener('blur', function () {
          if (!input.value.trim()) { cancel(); }
        });
        return;
      }

      var featuredBtn = target.closest('[data-cat-featured]');
      if (featuredBtn) {
        var featuredRow = featuredBtn.closest('[data-category-row]');
        if (!featuredRow) { return; }
        var next = featuredBtn.getAttribute('aria-pressed') !== 'true';
        postJson(featuredRow.getAttribute('data-inline-url'), { field: 'is_featured', value: next ? 1 : 0 })
          .then(function (data) {
            var on = Number((data.data || {}).value) === 1;
            featuredBtn.classList.toggle('is-on', on);
            featuredBtn.setAttribute('aria-pressed', on ? 'true' : 'false');
            toast(data.message || 'Saved.');
          })
          .catch(function (error) { toast(error.message || 'Could not save.', true); });
        return;
      }

      var moveBtn = target.closest('[data-move]');
      if (moveBtn) {
        var moveRow = moveBtn.closest('[data-category-row]');
        if (!moveRow) { return; }
        postJson(moveRow.getAttribute('data-move-url'), { direction: moveBtn.getAttribute('data-move') })
          .then(function () { return refreshCategoryTree(null); })
          .catch(function (error) { toast(error.message || 'Could not move.', true); });
        return;
      }

      var deleteBtn = target.closest('[data-delete-category]');
      if (deleteBtn) {
        var deleteRow = deleteBtn.closest('[data-category-row]');
        if (!deleteRow) { return; }
        if (!window.confirm(deleteBtn.getAttribute('data-confirm') || 'Are you sure?')) { return; }
        postJson(deleteRow.getAttribute('data-delete-url'), {})
          .then(function (data) {
            toast(data.message || 'Deleted.');
            return refreshCategoryTree(null);
          })
          .catch(function (error) { toast(error.message || 'Could not delete.', true); });
      }
    });

    document.addEventListener('admin:inline-saved', function (event) {
      var detail = event.detail || {};
      if (detail.host && detail.host.hasAttribute('data-category-row') && detail.field === 'name') {
        refreshCategoryTree(null);
      }
      if (detail.host && detail.host.hasAttribute('data-category-row') && detail.field === 'status') {
        detail.host.classList.toggle('is-inactive', String(detail.result.value) !== 'active');
      }
    });
  }

  /* ------------------------------------------------- single-image boxes */
  document.addEventListener('click', function (event) {
    var target = event.target;
    if (!target.closest) { return; }

    var pickBtn = target.closest('[data-category-image-pick], [data-generic-image-pick]');
    var deleteBtn = target.closest('[data-category-image-delete], [data-generic-image-delete]');
    var actionBtn = pickBtn || deleteBtn;
    if (!actionBtn) { return; }

    var box = actionBtn.closest('[data-category-image-box], [data-generic-image-box]');
    var host = actionBtn.closest('[data-category-row], [data-generic-row]');
    if (!box || !host) { return; }
    var url = box.getAttribute('data-image-url');

    function updateThumb(path) {
      var thumb = host.querySelector('.cat-row-thumb');
      if (!thumb) { return; }
      if (path) {
        thumb.classList.remove('cat-row-thumb-empty');
        thumb.textContent = '';
        var img = document.createElement('img');
        img.src = assetUrl(path) + '?t=' + Date.now();
        img.alt = '';
        img.loading = 'lazy';
        thumb.appendChild(img);
      } else {
        thumb.classList.add('cat-row-thumb-empty');
        thumb.textContent = '🖼';
      }
    }

    if (pickBtn) {
      pickFile({ multiple: false }).then(function (files) {
        if (!files.length) { return; }
        var fd = new FormData();
        fd.append('image', files[0]);
        postFormData(url, fd)
          .then(function (data) {
            toast(data.message || 'Saved.');
            updateThumb(data.path || '');
            return setEditorOpen(host, true, true);
          })
          .catch(function (error) { toast(error.message || 'Upload failed.', true); });
      });
      return;
    }

    if (!window.confirm(actionBtn.getAttribute('data-confirm') || 'Are you sure?')) { return; }
    var deleteFd = new FormData();
    deleteFd.append('action', 'delete');
    postFormData(url, deleteFd)
      .then(function (data) {
        toast(data.message || 'Deleted.');
        updateThumb('');
        return setEditorOpen(host, true, true);
      })
      .catch(function (error) { toast(error.message || 'Could not delete.', true); });
  });

  /* ------------------------------------------- collections / brands pages */
  function setupGenericPages() {
    document.querySelectorAll('[data-generic-page]').forEach(function (page) {
      var nameInput = page.querySelector('[data-quick-name]');
      var addBtn = page.querySelector('[data-quick-add]');
      var list = page.querySelector('[data-generic-list]');

      function submitQuick() {
        if (!nameInput || !nameInput.value.trim() || !list) { return; }
        var value = nameInput.value.trim();
        postJson(page.getAttribute('data-quick-url'), { name: value })
          .then(function (data) {
            var empty = list.querySelector('.media-empty');
            if (empty) { empty.remove(); }
            var row = parseFragment(data.html);
            if (row) {
              list.appendChild(row);
              row.scrollIntoView({ block: 'center', behavior: 'smooth' });
            }
            nameInput.value = '';
            nameInput.focus();
            toast(data.message || 'Saved.');
          })
          .catch(function (error) { toast(error.message || 'Could not create.', true); });
      }

      if (addBtn) { addBtn.addEventListener('click', submitQuick); }
      if (nameInput) {
        nameInput.addEventListener('keydown', function (event) {
          if (event.key === 'Enter') { event.preventDefault(); submitQuick(); }
        });
      }

      page.addEventListener('click', function (event) {
        var deleteBtn = event.target.closest ? event.target.closest('[data-delete-row]') : null;
        if (!deleteBtn) { return; }
        var row = deleteBtn.closest('[data-generic-row]');
        if (!row) { return; }
        if (!window.confirm(deleteBtn.getAttribute('data-confirm') || 'Are you sure?')) { return; }
        postJson(row.getAttribute('data-delete-url'), {})
          .then(function (data) {
            row.remove();
            toast(data.message || 'Deleted.');
          })
          .catch(function (error) { toast(error.message || 'Could not delete.', true); });
      });
    });
  }

  /* ---------------------------------------------------------------- init */
  document.querySelectorAll('[data-repeater-row]').forEach(initRow);
  document.querySelectorAll('[data-picker]').forEach(pickerEmptyNote);
  featuresCount();
  setupProductsBrowser();
  setupCategoriesPage();
  setupGenericPages();
})();
