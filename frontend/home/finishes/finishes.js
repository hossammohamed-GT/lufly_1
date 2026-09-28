/* ==========================================================================
   The finishes lab: a finish configurator, Dornbracht-style.
   --------------------------------------------------------------------------
   One selection drives everything: the product photograph cross-fades into
   the chosen finish (600ms, two stacked layers), the ambient tint behind the
   stage washes to that finish's colour, the headline phrase and the story
   panel change, the spec sheet and the counter follow, and the URL grows a
   ?finish= parameter so a chosen finish is shareable and survives a reload.

   Copy is never hardcoded here: the per-finish phrase / story / tint ride on
   the cards as data attributes (rendered from translations by the server),
   and the spec data lives in the FINISHES map below.
   ========================================================================== */
(function () {
  'use strict';

  var FINISHES = {
    'brushed-rose-gold': {
      title: 'Brushed Rose Gold (PVD)',
      tag: 'PVD TITANIUM VAPOR DEPOSITION / 10-YEAR COLOR STABILITY',
      image: 'images/finishes/swatch-rose-gold.jpg',
      desc: 'An opulent, warm metallic hue crafted via vacuum plasma PVD. Ultra-resistant to micro-scratches, finger marks, and corrosion in coastal and humid spa environments.',
      base: 'Solid Brass CW617N',
      coating: 'PVD Titanium 0.4um',
      cartridge: 'Kerox Hungary 35mm Ceramic',
      aerator: 'Neoperl Coin-Slot Pro-Eco 5.7 L/min'
    },
    'chrome': {
      title: 'Polished Mirror Chrome',
      tag: '12-MICRON MULTI-STAGE ELECTROPLATING / ISO 9227 TESTED',
      image: 'images/finishes/swatch-chrome.jpg',
      desc: 'The quintessential architectural finish. Triple-layer nickel-chromium electroplating delivering flawless diamond mirror reflectivity and effortless descaling.',
      base: 'Low-Lead Architectural Brass',
      coating: 'Multi-layer Ni-Cr 12.5um',
      cartridge: 'Sedal European Ceramic 35mm',
      aerator: 'Anti-Limescale Laminar Stream'
    },
    'brushed-gold': {
      title: 'Brushed Royal Gold (PVD)',
      tag: 'ARCHITECTURAL MATTE BRASS / RESISTANT TO AGGRESSIVE ACIDS',
      image: 'images/finishes/swatch-gold.jpg',
      desc: 'A luminous, subtle champagne-gold with directional brushwork that captures natural bathroom lighting while resisting soap film and water spotting.',
      base: 'Dezincification Resistant Brass (DZR)',
      coating: 'Zirconium PVD Physical Vapor',
      cartridge: 'Kerox Ultra-Smooth 500k Cycles',
      aerator: 'PRO-ECO Water-Saving 4.8 L/min'
    },
    'mirror-gold': {
      title: 'Polished Mirror Gold (PVD)',
      tag: 'HIGH-GLOSS PVD GOLD / PERMANENT MIRROR BRILLIANCE',
      image: 'images/finishes/swatch-mirror-gold.jpg',
      desc: 'A flawless high-gloss gold mirror sealed in the PVD vacuum chamber, holding permanent brilliance and corrosion resistance where plated gold would fade.',
      base: 'Solid Brass CW617N',
      coating: 'PVD Zirconium Nitride Mirror',
      cartridge: 'Kerox Hungary 35mm Ceramic',
      aerator: 'Neoperl Coin-Slot Pro-Eco 5.7 L/min'
    },
    'matte-black': {
      title: 'Matte Obsidian Black',
      tag: 'ELECTROSTATIC SOFT-TOUCH POWDER COATING / ZERO GLARE',
      image: 'images/finishes/swatch-black.jpg',
      desc: 'A tactile, non-reflective velvety black engineered for bold monolithic contrasts with white vitreous china and natural stone vanities.',
      base: 'Solid Cast Brass & Duroplast',
      coating: 'Electrophoretic Matte Coating',
      cartridge: 'Ceramic Disc Heavy-Duty',
      aerator: 'Integrated Concealed Aerator'
    },
    'brushed-nickel': {
      title: 'Brushed Nickel (PVD)',
      tag: 'SATIN BRUSHED PVD / FINGERPRINT-RESISTANT SHEEN',
      image: 'images/finishes/swatch-brushed-nickel.jpg',
      desc: 'A warm satin-silver finish with fine directional brushing, bonded in a PVD vacuum chamber for enduring elegance that shrugs off fingerprints and daily wear.',
      base: 'Solid Brass CW617N',
      coating: 'PVD Satin Nickel 0.4um',
      cartridge: 'Kerox Hungary 35mm Ceramic',
      aerator: 'Neoperl Coin-Slot Pro-Eco 5.7 L/min'
    },
    'gunmetal': {
      title: 'Gunmetal Titanium Grey',
      tag: 'DEEP ANTHRACITE PVD / AEROSPACE HARDNESS',
      image: 'images/finishes/swatch-gunmetal.jpg',
      desc: 'A moody, deep charcoal metallic finish designed for modern loft, brutalist, and dark spa architecture with an iridescent aquatic undertone.',
      base: 'Solid Brass CW617N',
      coating: 'PVD Titanium Carbonitride',
      cartridge: 'Thermostatic 38C Safety Core',
      aerator: 'Cascade Soft Flow'
    },
    'brushed-gunmetal': {
      title: 'Brushed Gunmetal (PVD)',
      tag: 'BRUSHED ANTHRACITE PVD / SOFT MATTE METALLIC',
      image: 'images/finishes/swatch-brushed-gunmetal.jpg',
      desc: 'Directional brushing softens the deep gunmetal tone into a refined matte metallic sheen that hides water marks and pairs with stone and dark timber.',
      base: 'Solid Brass CW617N',
      coating: 'PVD Brushed Titanium Carbonitride',
      cartridge: 'Kerox Ultra-Smooth 500k Cycles',
      aerator: 'Anti-Limescale Laminar Stream'
    },
    'gun-gray': {
      title: 'Gun Gray (PVD)',
      tag: 'SATIN GRAY PVD / LOW-GLARE ARCHITECTURAL TONE',
      image: 'images/finishes/swatch-gun-gray.jpg',
      desc: 'A calm satin gray metallic engineered for minimalist architecture, with a low-glare surface that keeps its even tone under harsh bathroom lighting.',
      base: 'Solid Brass CW617N',
      coating: 'PVD Satin Gray Titanium',
      cartridge: 'Sedal European Ceramic 35mm',
      aerator: 'PRO-ECO Water-Saving 4.8 L/min'
    }
  };

  var ORDER = ['brushed-rose-gold', 'chrome', 'brushed-gold', 'mirror-gold', 'matte-black', 'brushed-nickel', 'gunmetal', 'brushed-gunmetal', 'gun-gray'];

  /* Each stage layer is a <picture>; swapping only img.src would leave the
     previous finish's srcset serving. Rebuild it from the widths the template
     published on the img. */
  function setStageImage(img, url) {
    var raw = img.getAttribute('data-respic-widths') || '';
    var widths = raw ? raw.split(',') : [];
    var host = img.parentNode;
    var source = host && host.tagName === 'PICTURE'
      ? host.querySelector('source[type="image/webp"]')
      : null;

    img.src = url;

    if (!source || !widths.length) {
      return;
    }

    var parts = [];
    for (var i = 0; i < widths.length; i++) {
      parts.push(url + '@' + widths[i] + 'w.webp ' + widths[i] + 'w');
    }
    source.srcset = parts.join(', ');
  }

  function pad(n) {
    return ('0' + n).slice(-2);
  }

  function init() {
    var section = document.querySelector('.finishes-section');
    var cards = Array.prototype.slice.call(document.querySelectorAll('.fs-card'));
    var imgA = document.querySelector('.fs-img-a');
    var imgB = document.querySelector('.fs-img-b');
    if (!section || !cards.length || !imgA || !imgB) {
      return;
    }

    var base = document.documentElement.getAttribute('data-base') || '';
    var phrase = document.getElementById('fs-phrase');
    var story = document.getElementById('fs-story');
    var title = document.getElementById('finish-title');
    var tag = document.getElementById('finish-tag');
    var desc = document.getElementById('finish-desc');
    var indexEl = document.getElementById('finish-index');
    var baseSpec = document.getElementById('finish-base');
    var coating = document.getElementById('finish-coating');
    var cartridge = document.getElementById('finish-cartridge');
    var aerator = document.getElementById('finish-aerator');
    var tintA = section.querySelector('.fs-tint-a');
    var tintB = section.querySelector('.fs-tint-b');
    var reduce = window.matchMedia &&
      window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    var tintOnA = true;          /* which tint layer is currently visible */
    var imgOnA = true;           /* which image layer is currently visible */
    var currentKey = null;

    function setText(node, value) {
      if (node && value) {
        node.textContent = value;
      }
    }

    /* headline + story swap with a micro-fade, so the change reads as one
       gesture with the image cross-fade instead of a hard text jump */
    var textTimer = null;
    function swapCopy(map) {
      var targets = [
        [phrase, map.phrase],
        [story, map.story],
        [title, map.title],
        [desc, map.desc]
      ];
      if (reduce) {
        targets.forEach(function (t) { setText(t[0], t[1]); });
        return;
      }
      targets.forEach(function (t) { if (t[0]) t[0].classList.add('is-swapping'); });
      if (textTimer) window.clearTimeout(textTimer);
      textTimer = window.setTimeout(function () {
        targets.forEach(function (t) { setText(t[0], t[1]); });
        targets.forEach(function (t) { if (t[0]) t[0].classList.remove('is-swapping'); });
      }, 180);
    }

    function applyTint(tint) {
      if (!tintA || !tintB || !tint) {
        return;
      }
      var showEl = tintOnA ? tintB : tintA;
      var hideEl = tintOnA ? tintA : tintB;
      showEl.style.background =
        'radial-gradient(90% 120% at 72% 38%, ' + tint + ', transparent 70%)';
      showEl.style.opacity = '1';
      hideEl.style.opacity = '0';
      tintOnA = !tintOnA;
    }

    function applyImage(key) {
      var data = FINISHES[key];
      if (!data) {
        return;
      }
      var from = imgOnA ? imgA : imgB;
      var to = imgOnA ? imgB : imgA;
      setStageImage(to, base + '/' + data.image);
      if (reduce) {
        to.classList.add('is-on');
        from.classList.remove('is-on');
      } else {
        /* let the new file start arriving before the fade begins */
        window.setTimeout(function () {
          to.classList.add('is-on');
          from.classList.remove('is-on');
        }, 120);
      }
      imgOnA = !imgOnA;
    }

    function select(key, options) {
      var data = FINISHES[key];
      var card = cards.filter(function (c) { return c.dataset.finish === key; })[0];
      if (!data || !card) {
        return;
      }
      options = options || {};
      currentKey = key;
      section.setAttribute('data-finish', key);

      /* the headline phrase takes the finish's own metal tone (lightened
         automatically on the dark theme by the color-mix in the CSS) */
      if (card.dataset.accent) {
        section.style.setProperty('--fs-accent', card.dataset.accent);
      }

      cards.forEach(function (other) {
        var active = other === card;
        other.classList.toggle('is-active', active);
        other.setAttribute('aria-pressed', active ? 'true' : 'false');
      });

      setText(tag, data.tag);
      setText(baseSpec, data.base);
      setText(coating, data.coating);
      setText(cartridge, data.cartridge);
      setText(aerator, data.aerator);
      setText(indexEl, pad(ORDER.indexOf(key) + 1));

      swapCopy({
        phrase: card.dataset.phrase,
        story: card.dataset.story,
        title: data.title,
        desc: data.desc
      });

      if (!options.skipImage) {
        applyImage(key);
      }
      applyTint(card.dataset.tint);

      /* shareable / reloadable selection: ?finish=chrome */
      if (options.updateUrl && window.history && window.history.replaceState) {
        try {
          var url = new URL(window.location.href);
          url.searchParams.set('finish', key);
          window.history.replaceState({}, '', url);
        } catch (e) { /* ancient browsers simply keep the old URL */ }
      }
    }

    cards.forEach(function (card) {
      card.addEventListener('click', function () {
        select(card.dataset.finish, { updateUrl: true });
      });
    });

    /* ---- deep link: ?finish=gunmetal opens the section pre-configured ---- */
    try {
      var wanted = new URLSearchParams(window.location.search).get('finish');
      if (wanted && FINISHES[wanted] && wanted !== cards[0].dataset.finish) {
        select(wanted, {});
      }
    } catch (e) { /* no URLSearchParams: the default finish stays */ }

    /* ---- hotspots: one open at a time, Escape closes ---- */
    var spots = Array.prototype.slice.call(section.querySelectorAll('.fs-hotspot'));
    spots.forEach(function (spot) {
      spot.addEventListener('click', function (event) {
        event.stopPropagation();
        var open = spot.classList.contains('is-open');
        spots.forEach(function (s) { s.classList.remove('is-open'); });
        if (!open) {
          spot.classList.add('is-open');
        }
      });
    });
    document.addEventListener('click', function () {
      spots.forEach(function (s) { s.classList.remove('is-open'); });
    });

    /* ---- compare drawer ---- */
    var drawer = section.querySelector('[data-fs-drawer]');
    var compareOpen = section.querySelector('[data-fs-compare]');
    var compareClose = section.querySelector('[data-fs-compare-close]');

    function setDrawer(open) {
      if (!drawer) return;
      if (open) {
        drawer.hidden = false;
        window.requestAnimationFrame(function () {
          drawer.classList.add('is-open');
        });
      } else {
        drawer.classList.remove('is-open');
        window.setTimeout(function () { drawer.hidden = true; }, 420);
      }
    }
    if (compareOpen) compareOpen.addEventListener('click', function () { setDrawer(true); });
    if (compareClose) compareClose.addEventListener('click', function () { setDrawer(false); });
    if (drawer) {
      drawer.addEventListener('click', function (event) {
        if (event.target === drawer) setDrawer(false);
      });
    }

    /* ---- view in spaces modal ---- */
    var modal = section.querySelector('[data-fs-modal]');
    var spacesOpen = section.querySelector('[data-fs-spaces]');
    var spacesClose = section.querySelector('[data-fs-spaces-close]');
    var places = Array.prototype.slice.call(section.querySelectorAll('.fs-place'));
    var modalImg = document.getElementById('fs-modal-img');

    function setModal(open) {
      if (!modal) return;
      if (open) {
        modal.hidden = false;
        window.requestAnimationFrame(function () {
          modal.classList.add('is-open');
        });
      } else {
        modal.classList.remove('is-open');
        window.setTimeout(function () { modal.hidden = true; }, 320);
      }
    }
    if (spacesOpen) spacesOpen.addEventListener('click', function () { setModal(true); });
    if (spacesClose) spacesClose.addEventListener('click', function () { setModal(false); });
    if (modal) {
      modal.addEventListener('click', function (event) {
        if (event.target === modal) setModal(false);
      });
    }

    places.forEach(function (place) {
      place.addEventListener('click', function () {
        places.forEach(function (p) { p.classList.toggle('is-active', p === place); });
        if (modalImg && place.dataset.placeImg) {
          modalImg.style.opacity = '0';
          window.setTimeout(function () {
            modalImg.src = place.dataset.placeImg;
            modalImg.alt = place.textContent.trim();
            modalImg.style.opacity = '1';
          }, reduce ? 0 : 160);
        }
      });
    });

    document.addEventListener('keydown', function (event) {
      if (event.key !== 'Escape') return;
      setDrawer(false);
      setModal(false);
      spots.forEach(function (s) { s.classList.remove('is-open'); });
    });

    /* ---- entrance choreography: title up, stage from the right,
            cards one by one, the strip, then the hotspots ---- */
    if ('IntersectionObserver' in window && !reduce) {
      var seen = false;
      new IntersectionObserver(function (entries, observer) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting && !seen) {
            seen = true;
            section.classList.add('fs-in');
            observer.disconnect();
          }
        });
      }, { rootMargin: '120px' }).observe(section);
    } else {
      section.classList.add('fs-in');
    }

    currentKey = cards[0].dataset.finish;
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
