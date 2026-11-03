/* ============================================================
   Public product-card lightbox.

   Clicking a card's image opens an enlarged view instead of navigating.
   The lightbox shows the big image (with prev/next arrows and arrow keys)
   plus a responsive grid of all the product's images: 1 column on small
   screens, 2 on medium, 3 on large. Clicking any grid image enlarges that
   one, and every image carries a checkbox so several can be selected at
   once (select-all + live counter in the header).
   ============================================================ */

(function () {
  'use strict';

  var lb = null;
  var state = null;

  function slidesOf(media) {
    return Array.prototype.slice.call(media.querySelectorAll('img[data-pcard-slide]'));
  }

  function open(card, media) {
    if (!lb) return;
    var imgs = slidesOf(media);
    if (!imgs.length) return;
    var titleEl = card.querySelector('.pcard-title a');
    state = {
      url: media.getAttribute('href') || '',
      title: titleEl ? titleEl.textContent.trim() : '',
      slides: imgs.map(function (img) {
        return {
          src: img.currentSrc || img.src,
          alt: img.alt || '',
          kind: img.getAttribute('data-pcard-kind') || 'photo'
        };
      }),
      index: parseInt(media.getAttribute('data-pcard-index') || '0', 10),
      selected: {}
    };
    buildGrid();
    setMain(state.index);
    lb.hidden = false;
    document.body.style.overflow = 'hidden';
    var closeBtn = lb.querySelector('[data-pcard-lb-close-btn]');
    if (closeBtn) closeBtn.focus();
  }

  function close() {
    if (!lb) return;
    lb.hidden = true;
    state = null;
    document.body.style.overflow = '';
  }

  function buildGrid() {
    var grid = lb.querySelector('[data-pcard-lb-grid]');
    if (!grid || !state) return;
    grid.innerHTML = '';
    state.slides.forEach(function (slide, i) {
      var fig = document.createElement('figure');
      fig.className = 'pcard-lb-item';
      fig.setAttribute('data-pcard-lb-item', String(i));

      var btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'pcard-lb-thumb';
      btn.setAttribute('data-pcard-lb-thumb', String(i));
      btn.setAttribute('aria-label', slide.alt || String(i + 1));
      var img = document.createElement('img');
      img.src = slide.src;
      img.alt = slide.alt || '';
      img.loading = 'lazy';
      img.decoding = 'async';
      btn.appendChild(img);
      fig.appendChild(btn);

      var check = document.createElement('input');
      check.type = 'checkbox';
      check.className = 'pcard-lb-check';
      check.setAttribute('data-pcard-lb-check', String(i));
      check.setAttribute('aria-label', slide.alt || String(i + 1));
      fig.appendChild(check);

      grid.appendChild(fig);
    });
  }

  function setMain(i) {
    if (!lb || !state || !state.slides.length) return;
    var n = state.slides.length;
    state.index = ((i % n) + n) % n;
    var slide = state.slides[state.index];
    var img = lb.querySelector('[data-pcard-lb-img]');
    if (img) {
      img.src = slide.src;
      img.alt = slide.alt;
    }
    Array.prototype.forEach.call(lb.querySelectorAll('[data-pcard-lb-item]'), function (fig) {
      fig.classList.toggle('is-main', parseInt(fig.getAttribute('data-pcard-lb-item'), 10) === state.index);
    });
    syncChrome();
  }

  function syncChrome() {
    if (!lb || !state) return;
    var n = state.slides.length;
    var multiple = n > 1;
    var prev = lb.querySelector('[data-pcard-lb-prev]');
    var next = lb.querySelector('[data-pcard-lb-next]');
    if (prev) prev.hidden = !multiple;
    if (next) next.hidden = !multiple;
    var counter = lb.querySelector('[data-pcard-lb-counter]');
    if (counter) counter.textContent = n > 0 ? (state.index + 1) + ' / ' + n : '';
    var title = lb.querySelector('[data-pcard-lb-title]');
    if (title) title.textContent = state.title;
    var link = lb.querySelector('[data-pcard-lb-product]');
    if (link) link.href = state.url;
    syncSelection();
  }

  function selectedCount() {
    if (!state) return 0;
    var c = 0;
    for (var k in state.selected) {
      if (state.selected[k]) c++;
    }
    return c;
  }

  function syncSelection() {
    if (!lb || !state) return;
    var count = selectedCount();
    var counter = lb.querySelector('[data-pcard-lb-selcount]');
    if (counter) {
      var label = counter.getAttribute('data-label') || '';
      counter.textContent = count > 0 ? label.replace(':n', String(count)) : '';
    }
    var all = lb.querySelector('[data-pcard-lb-selectall]');
    if (all) {
      var boxes = lb.querySelectorAll('[data-pcard-lb-check]');
      all.checked = boxes.length > 0 && count === boxes.length;
      all.indeterminate = count > 0 && count < boxes.length;
    }
    Array.prototype.forEach.call(lb.querySelectorAll('[data-pcard-lb-item]'), function (fig) {
      var i = parseInt(fig.getAttribute('data-pcard-lb-item'), 10);
      fig.classList.toggle('is-selected', !!state.selected[i]);
    });
  }

  function init() {
    lb = document.querySelector('[data-pcard-lb]');
    if (!lb) return;

    document.addEventListener('click', function (e) {
      var t = e.target;
      if (!t || !t.closest) return;

      /* card image -> open the lightbox instead of following the link */
      var media = t.closest('.pcard-media');
      if (media && !t.closest('[data-pcard-prev]') && !t.closest('[data-pcard-next]')) {
        var card = media.closest('[data-product-card]');
        if (card && slidesOf(media).length) {
          e.preventDefault();
          open(card, media);
        }
        return;
      }

      if (t.closest('[data-pcard-lb-close]')) { close(); return; }
      if (!state) return;

      var prev = t.closest('[data-pcard-lb-prev]');
      if (prev) { setMain(state.index - 1); return; }
      var next = t.closest('[data-pcard-lb-next]');
      if (next) { setMain(state.index + 1); return; }
      var thumb = t.closest('[data-pcard-lb-thumb]');
      if (thumb) { setMain(parseInt(thumb.getAttribute('data-pcard-lb-thumb'), 10)); }
    });

    document.addEventListener('change', function (e) {
      var t = e.target;
      if (!t || !t.matches || !state) return;
      if (t.matches('[data-pcard-lb-check]')) {
        state.selected[parseInt(t.getAttribute('data-pcard-lb-check'), 10)] = t.checked;
        syncSelection();
        return;
      }
      if (t.matches('[data-pcard-lb-selectall]')) {
        var boxes = lb.querySelectorAll('[data-pcard-lb-check]');
        Array.prototype.forEach.call(boxes, function (cb) {
          cb.checked = t.checked;
          state.selected[parseInt(cb.getAttribute('data-pcard-lb-check'), 10)] = t.checked;
        });
        syncSelection();
      }
    });

    document.addEventListener('keydown', function (e) {
      if (!lb || lb.hidden || !state) return;
      if (e.key === 'Escape') { close(); return; }
      if (e.key === 'ArrowLeft') { setMain(state.index - 1); return; }
      if (e.key === 'ArrowRight') { setMain(state.index + 1); }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
