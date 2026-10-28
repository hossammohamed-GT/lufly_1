/* ==========================================================================
   Reviewed cross-product colour/finish choices.

   Each swatch is a real product link. With JavaScript, the link updates its
   own card using the selected product's complete server payload: identity,
   media, name, code, description, features, specifications, price and visitor
   state. Without JavaScript, the same link opens that product normally.
   ========================================================================== */
(function () {
  'use strict';

  var base = document.documentElement.getAttribute('data-base') || '';

  function esc(value) {
    return String(value == null ? '' : value).replace(/[&<>"']/g, function (ch) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch];
    });
  }

  function img(raw) {
    if (!raw) return base + '/images/favicon.png';
    if (/^(?:https?:)?\/\//.test(raw)) return raw;
    return base + '/' + String(raw).replace(/^\/+/, '');
  }

  function variantsFor(card) {
    if (Array.isArray(card.__pcardColorVariants)) return card.__pcardColorVariants;
    var script = card.querySelector('[data-pcard-variant-data]');
    if (!script) return [];
    try {
      var data = JSON.parse(script.textContent || '[]');
      card.__pcardColorVariants = Array.isArray(data) ? data : [];
    } catch (error) {
      card.__pcardColorVariants = [];
    }
    return card.__pcardColorVariants;
  }

  /* Keep optimistic favorite/box changes when the visitor moves between
     colours and later returns to a previously displayed product. */
  function captureState(card, variants) {
    var id = parseInt(card.getAttribute('data-product-id') || '0', 10);
    if (!id) return;
    var current = variants.find(function (variant) { return parseInt(variant.id, 10) === id; });
    if (!current) return;
    var favorite = card.querySelector('[data-fav-toggle]');
    var box = card.querySelector('[data-box-product]');
    if (favorite) current.is_saved = favorite.classList.contains('is-on');
    if (box) current.in_box = box.classList.contains('is-on');
  }

  function shortDescription(product) {
    var text = String(product.short_description || product.description || '').trim();
    ['Verified drawing dimensions:', 'Doğrulanmış teknik çizim ölçüleri:', 'Ověřené rozměry z technického výkresu:'].forEach(function (m) {
      var marker = text.indexOf(m);
      if (marker !== -1) text = text.slice(0, marker).trim();
    });
    return text;
  }

  function cardIcon(name) {
    var paths = {
      dimensions: '<path d="M4 5h16v14H4z"/><path d="M8 5v4M12 5v3M16 5v4M8 19v-4M12 19v-3M16 19v-4"/>',
      material: '<path d="m12 3 8 4.5v9L12 21l-8-4.5v-9L12 3Z"/><path d="m4 7.5 8 4.5 8-4.5M12 12v9"/>',
      installation: '<path d="M5 4h14v16H5z"/><path d="M9 8h6M9 12h6M9 16h3"/>',
      finish: '<circle cx="12" cy="12" r="8"/><path d="M12 4a8 8 0 0 1 0 16M6.5 9.5l11 5"/>',
      cartridge: '<path d="M6 20V9a6 6 0 0 1 12 0v3"/><path d="M12 12v8M4 20h16M15 6h5"/>',
      rimless: '<ellipse cx="12" cy="9" rx="8" ry="4.5"/><path d="M4 9v4c0 3.2 3.2 5.5 8 5.5s8-2.3 8-5.5V9M8 21h8"/>',
      'soft-close': '<path d="M4 7h16M4 12h16M4 17h16"/><path d="m8 4 8 16"/>',
      'water-saving': '<path d="M12 3S6.5 9.2 6.5 13.4a5.5 5.5 0 0 0 11 0C17.5 9.2 12 3 12 3Z"/><path d="m9.5 13.5 1.6 1.6 3.4-3.6"/>',
      'easy-clean': '<path d="m12 3 1.5 5.5L19 10l-5.5 1.5L12 17l-1.5-5.5L5 10l5.5-1.5L12 3Z"/><path d="m18 16 .7 2.3L21 19l-2.3.7L18 22l-.7-2.3L15 19l2.3-.7L18 16Z"/>',
      'scratch-resistant': '<path d="M12 3 19 6.5v5.2c0 4.5-3 7.8-7 9.3-4-1.5-7-4.8-7-9.3V6.5L12 3Z"/><path d="m9 14 6-6"/>',
      'single-lever': '<path d="M7 20v-7a5 5 0 0 1 10 0v7"/><path d="M5 20h14M12 8V4m0 0h5"/>',
      thermostatic: '<path d="M12 14a3 3 0 1 0 0 6 3 3 0 0 0 0-6Z"/><path d="M10.5 14.5V6a1.5 1.5 0 0 1 3 0v8.5M16 7h3M16 10h2"/>',
      shower: '<path d="M12 3v5M6 12a6 6 0 0 1 12 0M5 12h14"/><path d="M8 16v1M12 16v1M16 16v1M8 20v1M12 20v1M16 20v1"/>',
      'high-flow': '<path d="M4 8h12M4 12h16M4 16h12"/><path d="m14 5 3 3-3 3M14 13l3 3-3 3"/>',
      'anti-odor': '<path d="M12 3 20 6v5c0 4.8-3.3 8.3-8 10-4.7-1.7-8-5.2-8-10V6l8-3Z"/><path d="m8.5 12 2.2 2.2 4.8-5"/>',
      connection: '<circle cx="8" cy="12" r="3.5"/><circle cx="16" cy="12" r="3.5"/><path d="M11.5 12h1M4.5 12H2M22 12h-2.5"/>',
      drainage: '<path d="M4 7h16M6 7l1.5 6h9L18 7"/><path d="M12 13v7m-3-2 3 2 3-2"/>',
      sensor: '<circle cx="12" cy="12" r="2.2"/><path d="M8.5 8.5a5 5 0 0 0 0 7M15.5 8.5a5 5 0 0 1 0 7M6 6a8.5 8.5 0 0 0 0 12M18 6a8.5 8.5 0 0 1 0 12"/>',
      'load-capacity': '<path d="M6 19h12l-1.5-9h-9L6 19Z"/><path d="M9.5 10a2.5 2.5 0 0 1 5 0M10 14.5h4"/>',
      hygiene: '<path d="M12 4v16M4 12h16"/><circle cx="12" cy="12" r="8.5"/>',
      certification: '<circle cx="12" cy="9.5" r="5.5"/><path d="m9.5 14.2-1.3 6.3L12 18.5l3.8 2-1.3-6.3"/><path d="m10 9.5 1.4 1.4 2.8-2.8"/>',
      warranty: '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>',
      pressure: '<path d="M5.5 17.5a8 8 0 1 1 13 0"/><path d="m12 12 3.5-3.5"/><circle cx="12" cy="12" r="1.3"/>',
      spec: '<circle cx="12" cy="12" r="8.5"/><path d="m8.5 12 2.2 2.2 4.8-5"/>'
    };
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.45" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' +
      (paths[name] || paths.spec) + '</svg>';
  }

  var badgeStar = '<svg class="pcard-badge-star" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" focusable="false">' +
    '<path d="M8 1.25l1.72 4.31 4.63.42-3.5 3.06 1.04 4.54L8 11.18l-3.89 2.4 1.04-4.54-3.5-3.06 4.63-.42L8 1.25z"/></svg>';

  function featurePanel(card, isFeatured) {
    var features = Array.isArray(card.features) ? card.features.slice(0, 3).map(function (feature) {
      return '<span class="pcard-feature" role="listitem">' + cardIcon(feature.icon) + '<span>' + esc(feature.label) + '</span></span>';
    }).join('') : '';
    if (!card.badge && !features) return '';
    var badgeHtml = '';
    if (card.badge) {
      badgeHtml = '<span class="pcard-badge' + (isFeatured ? ' is-featured-badge' : '') + '">' +
        (isFeatured ? badgeStar : '') + '<span>' + esc(card.badge) + '</span></span>';
    }
    return '<div class="pcard-feature-panel">' + badgeHtml +
      (features ? '<span class="pcard-features" role="list">' + features + '</span>' : '') + '</div>';
  }

  function specList(card) {
    if (!Array.isArray(card.specs) || !card.specs.length) return '';
    var items = card.specs.slice(0, 6);
    var isOdd = items.length % 2 === 1;
    return '<div class="pcard-specs' + (isOdd ? ' pcard-specs--odd' : '') + '" role="list" data-spec-count="' + items.length + '">' + items.map(function (spec, idx) {
      return '<div class="pcard-spec' + (isOdd && idx === 0 ? ' pcard-spec--wide' : '') + '" role="listitem">' +
        cardIcon(spec.icon) + '<div><span class="pcard-spec-label">' + esc(spec.label) + '</span>' +
        '<span class="pcard-spec-value">' + esc(spec.value) + '</span></div></div>';
    }).join('') + '</div>';
  }

  function insertBefore(body, html, selectors) {
    var template = document.createElement('template');
    template.innerHTML = html;
    var anchor = body.querySelector(selectors);
    if (anchor) body.insertBefore(template.content, anchor);
    else body.appendChild(template.content);
  }

  function replaceRegion(card, selector, html, before) {
    var current = card.querySelector(selector);
    if (current) {
      if (html) current.outerHTML = html;
      else current.remove();
      return;
    }
    if (html) insertBefore(card.querySelector('.pcard-body'), html, before);
  }

  function slidesFor(product) {
    var slides = [];
    (product.card_slides || []).forEach(function (slide) {
      if (!slide || !slide.path) return;
      var src = img(slide.path);
      if (slides.some(function (item) { return item.src === src; })) return;
      slides.push({ src: src, kind: slide.kind || 'photo' });
    });
    if (!slides.length) slides.push({ src: img(product.image), kind: 'photo' });
    return slides;
  }

  function dotHtml(slides) {
    return slides.map(function (slide, index) {
      return '<i class="pcard-dot' + (index === 0 ? ' is-on' : '') + '" data-pcard-dot="' + index + '"></i>';
    }).join('');
  }

  function finishKey(finish) {
    return String(finish && finish.key || 'neutral').toLowerCase().replace(/[^a-z-]/g, '') || 'neutral';
  }

  function toneParts(finishes) {
    return finishes.slice(0, 4).map(function (finish) {
      return '<i class="pcard-tone pcard-swatch--' + finishKey(finish) + '" aria-hidden="true"></i>';
    }).join('');
  }

  /* The piece's own two-tone finish is static information. Only the separate
     option links below it change the card, so two colours never look like two
     unrelated buttons. */
  function updateFinish(card, product, cardData) {
    var wrapper = card.querySelector('[data-pcard-colors]');
    if (!wrapper) return;
    var finishes = Array.isArray(cardData.finishes) ? cardData.finishes.slice(0, 4) : [];
    if (!finishes.length && product.finish && product.finish.label) finishes = [product.finish];
    var piece = wrapper.querySelector('[data-pcard-piece]');
    if (!finishes.length) {
      if (piece) piece.remove();
      return;
    }
    if (!piece) {
      piece = document.createElement('div');
      piece.className = 'pcard-finish-piece';
      piece.setAttribute('data-pcard-piece', '');
      piece.innerHTML = '<span class="pcard-finish-caption">' + esc(wrapper.getAttribute('data-product-finish-label') || 'Piece finish') + '</span>' +
        '<span class="pcard-finish-value"><span class="pcard-tone-swatch" data-pcard-piece-tones role="img"></span>' +
        '<span class="pcard-finish-count pcard-finish-current pcard-finish-name" data-pcard-finish-label aria-live="polite"></span></span>';
      wrapper.insertBefore(piece, wrapper.querySelector('.pcard-finish-choices') || wrapper.firstChild);
    }
    var label = String(product.finish && product.finish.label || finishes.map(function (finish) {
      return finish.label || '';
    }).filter(Boolean).join(' / '));
    var tones = piece.querySelector('[data-pcard-piece-tones]');
    var labelEl = piece.querySelector('[data-pcard-finish-label]');
    if (tones) {
      tones.innerHTML = toneParts(finishes);
      tones.setAttribute('aria-label', label);
      tones.setAttribute('title', label);
    }
    if (labelEl) {
      labelEl.textContent = label;
      labelEl.setAttribute('title', label);
    }
  }

  function updateMedia(card, product, name) {
    var media = card.querySelector('.pcard-media');
    if (!media) return;
    media.dispatchEvent(new CustomEvent('pcard:stop-cycle', { bubbles: true }));

    var slides = slidesFor(product);
    var drawingLabel = media.getAttribute('data-pcard-kind-drawing') || 'Drawing';
    var situLabel = media.getAttribute('data-pcard-kind-situ') || 'Installed';
    var href = product.url || '#';
    media.setAttribute('href', href);
    media.querySelectorAll('[data-pcard-slide]').forEach(function (image) { image.remove(); });

    var fragment = document.createDocumentFragment();
    slides.forEach(function (slide, index) {
      var image = document.createElement('img');
      var kindLabel = slide.kind === 'drawing' ? drawingLabel : slide.kind === 'situ' ? situLabel : '';
      image.className = 'pcard-img' + (index === 0 ? ' is-on' : '');
      image.setAttribute('data-pcard-slide', String(index));
      image.setAttribute('data-pcard-kind', slide.kind);
      image.src = slide.src;
      image.alt = index === 0 ? name : (kindLabel ? kindLabel + ' — ' + name : '');
      image.loading = 'lazy';
      image.decoding = 'async';
      image.width = 420;
      image.height = 320;
      image.onerror = function () { this.onerror = null; this.remove(); };
      fragment.appendChild(image);
    });
    media.insertBefore(fragment, media.firstChild);

    if (slides.length > 1) {
      media.setAttribute('data-pcard-cycle', '');
      media.setAttribute('data-pcard-kind-drawing', drawingLabel);
      media.setAttribute('data-pcard-kind-situ', situLabel);
    } else {
      media.removeAttribute('data-pcard-cycle');
    }
    media.setAttribute('data-pcard-index', '0');

    var stage = media.closest('.pcard-stage');
    if (stage) {
      var meta = stage.querySelector('.pcard-media-meta');
      if (slides.length > 1) {
        if (!meta) {
          meta = document.createElement('div');
          meta.className = 'pcard-media-meta';
          stage.appendChild(meta);
        }
        meta.innerHTML = '<span class="pcard-dots" aria-hidden="true">' + dotHtml(slides) + '</span>' +
          '<span class="pcard-kind" data-pcard-kind-label aria-hidden="true" hidden></span>';
      } else if (meta) {
        meta.remove();
      }
    } else {
      /* The compact home cards keep their dots inside the media link. */
      var dots = media.querySelector('.pcard-dots');
      var kind = media.querySelector('[data-pcard-kind-label]');
      if (slides.length > 1) {
        if (!dots) {
          dots = document.createElement('span');
          dots.className = 'pcard-dots';
          dots.setAttribute('aria-hidden', 'true');
          media.insertBefore(dots, media.querySelector('.pcard-hint'));
        }
        dots.innerHTML = dotHtml(slides);
        if (!kind) {
          kind = document.createElement('span');
          kind.className = 'pcard-kind';
          kind.setAttribute('data-pcard-kind-label', '');
          kind.setAttribute('aria-hidden', 'true');
          media.insertBefore(kind, media.querySelector('.pcard-hint'));
        }
        kind.hidden = true;
        kind.textContent = '';
      } else {
        if (dots) dots.remove();
        if (kind) kind.remove();
      }
    }

    media.dispatchEvent(new CustomEvent('pcard:reset-cycle', { bubbles: true }));
  }

  function updateFavorite(card, product, cardData, name) {
    var button = card.querySelector('[data-fav-toggle]');
    if (!button) return;
    var on = !!product.is_saved;
    var save = cardData.save_label || 'Save product';
    var saved = cardData.saved_label || 'Saved';
    var label = on ? saved : save;
    button.setAttribute('data-fav-product', String(product.id));
    button.setAttribute('data-fav-added', cardData.favorite_added || '');
    button.setAttribute('data-fav-removed', cardData.favorite_removed || '');
    button.setAttribute('data-fav-label-on', saved);
    button.setAttribute('data-fav-label-off', save);
    button.setAttribute('aria-pressed', on ? 'true' : 'false');
    button.setAttribute('aria-label', label + ' — ' + name);
    button.setAttribute('title', label);
    button.classList.toggle('is-on', on);
  }

  function updateBox(card, product, cardData, name) {
    var button = card.querySelector('[data-box-product]');
    if (!button) return;
    var host = document.querySelector('[data-livesearch][data-box-endpoint]');
    var endpoint = button.getAttribute('data-box-endpoint') || (host ? host.getAttribute('data-box-endpoint') : '') || '';
    var enabled = !!cardData.cart_enabled && !!endpoint;
    var on = enabled && !!product.in_box;
    var labelOn = cardData.box_label_on || 'In the box';
    var labelOff = cardData.box_label_off || 'Add to cart';
    var label = enabled ? (on ? labelOn : labelOff) : (cardData.cart_label || 'Available once a price is added');

    button.setAttribute('data-box-product', String(product.id));
    button.setAttribute('data-box-label-on', labelOn);
    button.setAttribute('data-box-label-off', labelOff);
    button.setAttribute('data-box-error', cardData.box_error || '');
    if (enabled) {
      button.disabled = false;
      button.removeAttribute('aria-disabled');
      button.setAttribute('data-box-add', '');
      button.setAttribute('data-box-endpoint', endpoint);
    } else {
      button.disabled = true;
      button.setAttribute('aria-disabled', 'true');
      button.removeAttribute('data-box-add');
    }
    button.setAttribute('aria-pressed', on ? 'true' : 'false');
    button.setAttribute('aria-label', label + ' — ' + name);
    button.setAttribute('title', label);
    button.classList.toggle('is-on', on);
  }

  function apply(card, product) {
    var variants = variantsFor(card);
    captureState(card, variants);

    var cardData = product.card && typeof product.card === 'object' ? product.card : {};
    var name = String(product.display_name || product.name || '');
    var code = String(product.sku || product.model_code || '');
    var href = product.url || '#';
    var full = !!card.querySelector('.pcard-actions');
    var isFeatured = !!(product.is_featured || cardData.is_featured || card.getAttribute('data-featured') === '1');
    card.setAttribute('data-product-id', String(product.id));
    card.classList.toggle('is-featured', isFeatured);
    card.classList.toggle('has-price', !!cardData.has_price);
    card.classList.toggle('is-price-pending', !cardData.has_price);

    updateMedia(card, product, name);

    card.querySelectorAll('.pcard-title a').forEach(function (link) {
      link.textContent = name;
      link.setAttribute('href', href);
    });
    card.querySelectorAll('.pcard-details').forEach(function (link) { link.setAttribute('href', href); });

    var codeEl = card.querySelector('.pcard-code');
    if (!codeEl && full && code) {
      codeEl = document.createElement('span');
      codeEl.className = 'pcard-code';
      var heading = card.querySelector('.pcard-heading');
      if (heading) heading.insertAdjacentElement('afterend', codeEl);
    }
    if (codeEl) {
      codeEl.textContent = code;
      codeEl.hidden = code === '';
    }

    var description = shortDescription(product);
    var descEl = card.querySelector('.pcard-desc');
    if (!descEl && full && description) {
      descEl = document.createElement('p');
      descEl.className = 'pcard-desc';
      insertBefore(card.querySelector('.pcard-body'), descEl.outerHTML, '.pcard-feature-panel, .pcard-finishes, .pcard-specs, .pcard-actions');
      descEl = card.querySelector('.pcard-desc');
    }
    if (descEl) {
      if (description) descEl.textContent = description;
      else descEl.remove();
    }

    if (full) {
      replaceRegion(card, '.pcard-feature-panel', featurePanel(cardData, isFeatured), '.pcard-finishes, .pcard-specs, .pcard-actions');
      replaceRegion(card, '.pcard-specs', specList(cardData), '.pcard-actions');

      var caption = card.querySelector('.pcard-price small');
      var price = card.querySelector('.pcard-price strong');
      if (caption) caption.textContent = cardData.price_caption || '';
      if (price) {
        price.textContent = cardData.price_label || '';
        price.classList.toggle('is-pending', !cardData.has_price);
      }
      updateFavorite(card, product, cardData, name);
      updateBox(card, product, cardData, name);
    }

    card.querySelectorAll('[data-pcard-variant]').forEach(function (link) {
      var selected = parseInt(link.getAttribute('data-pcard-variant'), 10) === parseInt(product.id, 10);
      link.classList.toggle('is-active', selected);
      if (selected) link.setAttribute('aria-current', 'true');
      else link.removeAttribute('aria-current');
    });
    updateFinish(card, product, cardData);

    card.dispatchEvent(new CustomEvent('pcard:variantchange', {
      bubbles: true,
      detail: { productId: parseInt(product.id, 10), product: product }
    }));
  }

  document.addEventListener('click', function (event) {
    if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
    var target = event.target.closest ? event.target.closest('[data-pcard-variant]') : null;
    if (!target) return;
    var card = target.closest('[data-product-card]');
    if (!card) return;
    var id = parseInt(target.getAttribute('data-pcard-variant'), 10);
    var product = variantsFor(card).find(function (variant) { return parseInt(variant.id, 10) === id; });
    if (!product) return; /* preserve the ordinary product-link fallback */

    event.preventDefault();
    if (parseInt(card.getAttribute('data-product-id'), 10) === id) return;
    apply(card, product);
  });

  window.LUFLYProductCardVariants = { apply: apply, variants: variantsFor };
})();
