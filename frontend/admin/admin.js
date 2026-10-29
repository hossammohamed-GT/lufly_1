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
        toast('Saved.');
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
  function featuresCount() {
    var badge = document.querySelector('[data-features-count]');
    var box = document.querySelector('[data-repeater="features"]');
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
    featuresCount();
    return row;
  }

  document.addEventListener('click', function (event) {
    var add = event.target.closest ? event.target.closest('[data-repeater-add]') : null;
    if (add) {
      var container = document.querySelector('[data-repeater="' + add.getAttribute('data-target') + '"]');
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
      if (rowToRemove) { rowToRemove.remove(); featuresCount(); }
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
      var preset = presetsData[target.value];
      var row = target.closest('[data-repeater-row]');
      if (!preset || !row) { return; }
      var iconSelect = row.querySelector('[data-icon-select]');
      if (iconSelect && preset.icon) {
        iconSelect.value = preset.icon;
        refreshIconPreview(iconSelect);
      }
      row.querySelectorAll('[data-feature-label]').forEach(function (input) {
        var locale = input.getAttribute('data-feature-label');
        if (!input.value.trim() && preset.labels && preset.labels[locale]) {
          input.value = preset.labels[locale];
        }
      });
      row.querySelectorAll('[data-feature-value]').forEach(function (input) {
        var locale = input.getAttribute('data-feature-value');
        if (preset.placeholders && preset.placeholders[locale]) {
          input.placeholder = preset.placeholders[locale];
        }
      });
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
    var container = document.querySelector('[data-repeater="specs"]');
    var row = addRow(container, 'spec-template');
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

  document.querySelectorAll('[data-picker]').forEach(function (picker) {
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
  });

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

  /* ---------------------------------------------------------------- init */
  document.querySelectorAll('[data-repeater-row]').forEach(initRow);
  document.querySelectorAll('[data-picker]').forEach(pickerEmptyNote);
  featuresCount();
})();
