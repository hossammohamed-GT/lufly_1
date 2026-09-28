/* ==========================================================================
   The finishes lab: staged after the owner's reference.
   --------------------------------------------------------------------------
   One selection drives everything: the band's scene photograph cross-fades
   into the same bathroom with the faucet wearing the chosen finish (600ms,
   two stacked layers), the callout on the scene and the glass spec sheet
   follow, the headline phrase changes, the URL grows a ?finish= parameter
   so a chosen finish is shareable, and the selected card is scrolled into
   view inside the finish rail (the rail's chevrons scroll it, the cards
   select).

   Copy is never hardcoded here: the per-finish phrase / story ride on the
   cards as data attributes (rendered from translations by the server), and
   the spec data lives in the FINISHES map below.

   The callout and the hotspots carry data-x / data-y in percentages of the
   PHOTOGRAPH; placeOverlays() maps them onto the rendered scene box
   (object-fit: cover crops from the left), so the annotations stay glued
   to the faucet at every width.
   ========================================================================== */
(function () {
  'use strict';

  var FINISHES = {
    'brushed-rose-gold': {
      title: 'Brushed Rose Gold (PVD)',
      tag: 'PVD TITANIUM VAPOR DEPOSITION / 10-YEAR COLOR STABILITY',
      image: 'images/finishes/scene-brushed-rose-gold.jpg',
      desc: 'An opulent, warm metallic hue crafted via vacuum plasma PVD. Ultra-resistant to micro-scratches, finger marks, and corrosion in coastal and humid spa environments.',
      base: 'Solid Brass CW617N',
      coating: 'PVD Titanium 0.4um',
      cartridge: 'Kerox Hungary 35mm Ceramic',
      aerator: 'Neoperl Coin-Slot Pro-Eco 5.7 L/min'
    },
    'chrome': {
      title: 'Polished Mirror Chrome',
      tag: '12-MICRON MULTI-STAGE ELECTROPLATING / ISO 9227 TESTED',
      image: 'images/finishes/scene-chrome.jpg',
      desc: 'The quintessential architectural finish. Triple-layer nickel-chromium electroplating delivering flawless diamond mirror reflectivity and effortless descaling.',
      base: 'Low-Lead Architectural Brass',
      coating: 'Multi-layer Ni-Cr 12.5um',
      cartridge: 'Sedal European Ceramic 35mm',
      aerator: 'Anti-Limescale Laminar Stream'
    },
    'brushed-gold': {
      title: 'Brushed Royal Gold (PVD)',
      tag: 'ARCHITECTURAL MATTE BRASS / RESISTANT TO AGGRESSIVE ACIDS',
      image: 'images/finishes/scene-brushed-gold.jpg',
      desc: 'A luminous, subtle champagne-gold with directional brushwork that captures natural bathroom lighting while resisting soap film and water spotting.',
      base: 'Dezincification Resistant Brass (DZR)',
      coating: 'Zirconium PVD Physical Vapor',
      cartridge: 'Kerox Ultra-Smooth 500k Cycles',
      aerator: 'PRO-ECO Water-Saving 4.8 L/min'
    },
    'mirror-gold': {
      title: 'Polished Mirror Gold (PVD)',
      tag: 'HIGH-GLOSS PVD GOLD / PERMANENT MIRROR BRILLIANCE',
      image: 'images/finishes/scene-mirror-gold.jpg',
      desc: 'A flawless high-gloss gold mirror sealed in the PVD vacuum chamber, holding permanent brilliance and corrosion resistance where plated gold would fade.',
      base: 'Solid Brass CW617N',
      coating: 'PVD Zirconium Nitride Mirror',
      cartridge: 'Kerox Hungary 35mm Ceramic',
      aerator: 'Neoperl Coin-Slot Pro-Eco 5.7 L/min'
    },
    'matte-black': {
      title: 'Matte Obsidian Black',
      tag: 'ELECTROSTATIC SOFT-TOUCH POWDER COATING / ZERO GLARE',
      image: 'images/finishes/scene-matte-black.jpg',
      desc: 'A tactile, non-reflective velvety black engineered for bold monolithic contrasts with white vitreous china and natural stone vanities.',
      base: 'Solid Cast Brass & Duroplast',
      coating: 'Electrophoretic Matte Coating',
      cartridge: 'Ceramic Disc Heavy-Duty',
      aerator: 'Integrated Concealed Aerator'
    },
    'brushed-nickel': {
      title: 'Brushed Nickel (PVD)',
      tag: 'SATIN BRUSHED PVD / FINGERPRINT-RESISTANT SHEEN',
      image: 'images/finishes/scene-brushed-nickel.jpg',
      desc: 'A warm satin-silver finish with fine directional brushing, bonded in a PVD vacuum chamber for enduring elegance that shrugs off fingerprints and daily wear.',
      base: 'Solid Brass CW617N',
      coating: 'PVD Satin Nickel 0.4um',
      cartridge: 'Kerox Hungary 35mm Ceramic',
      aerator: 'Neoperl Coin-Slot Pro-Eco 5.7 L/min'
    },
    'gunmetal': {
      title: 'Gunmetal Titanium Grey',
      tag: 'DEEP ANTHRACITE PVD / AEROSPACE HARDNESS',
      image: 'images/finishes/scene-gunmetal.jpg',
      desc: 'A moody, deep charcoal metallic finish designed for modern loft, brutalist, and dark spa architecture with an iridescent aquatic undertone.',
      base: 'Solid Brass CW617N',
      coating: 'PVD Titanium Carbonitride',
      cartridge: 'Thermostatic 38C Safety Core',
      aerator: 'Cascade Soft Flow'
    },
    'brushed-gunmetal': {
      title: 'Brushed Gunmetal (PVD)',
      tag: 'BRUSHED ANTHRACITE PVD / SOFT MATTE METALLIC',
      image: 'images/finishes/scene-brushed-gunmetal.jpg',
      desc: 'Directional brushing softens the deep gunmetal tone into a refined matte metallic sheen that hides water marks and pairs with stone and dark timber.',
      base: 'Solid Brass CW617N',
      coating: 'PVD Brushed Titanium Carbonitride',
      cartridge: 'Kerox Ultra-Smooth 500k Cycles',
      aerator: 'Anti-Limescale Laminar Stream'
    },
    'gun-gray': {
      title: 'Gun Gray (PVD)',
      tag: 'SATIN GRAY PVD / LOW-GLARE ARCHITECTURAL TONE',
      image: 'images/finishes/scene-gun-gray.jpg',
      desc: 'A calm satin gray metallic engineered for minimalist architecture, with a low-glare surface that keeps its even tone under harsh bathroom lighting.',
      base: 'Solid Brass CW617N',
      coating: 'PVD Satin Gray Titanium',
      cartridge: 'Sedal European Ceramic 35mm',
      aerator: 'PRO-ECO Water-Saving 4.8 L/min'
    }
  };

  var ORDER = ['brushed-rose-gold', 'chrome', 'brushed-gold', 'mirror-gold', 'matte-black', 'brushed-nickel', 'gunmetal', 'brushed-gunmetal', 'gun-gray'];

  /* Each scene layer is a plain <img> at full original quality (no rendition
     ladder anymore) - swapping the src is all it takes. */
  function setStageImage(img, url) {
    img.src = url;
  }

  function pad(n) {
    return ('0' + n).slice(-2);
  }

  function init() {
    var section = document.querySelector('.finishes-section');
    var cards = Array.prototype.slice.call(document.querySelectorAll('.fs-card'));
    var imgA = document.querySelector('.fs-scene-img-a');
    var imgB = document.querySelector('.fs-scene-img-b');
    if (!section || !cards.length || !imgA || !imgB) {
      return;
    }

    var base = document.documentElement.getAttribute('data-base') || '';
    var phrase = document.getElementById('fs-phrase');
    var story = document.getElementById('fs-story');
    var captionTitle = document.getElementById('fs-caption-title');
    var title = document.getElementById('finish-title');
    var desc = document.getElementById('finish-desc');
    var indexEl = document.getElementById('finish-index');
    var tag = document.getElementById('finish-tag');
    var baseSpec = document.getElementById('finish-base');
    var coating = document.getElementById('finish-coating');
    var cartridge = document.getElementById('finish-cartridge');
    var aerator = document.getElementById('finish-aerator');
    var scene = section.querySelector('.fs-scene');
    var ui = section.querySelector('.fs-scene-ui');
    var reduce = window.matchMedia &&
      window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    var imgOnA = true;           /* which scene layer is currently visible */
    var currentKey = null;

    function setText(node, value) {
      if (node && value) {
        node.textContent = value;
      }
    }

    /* headline + callout + specs swap with a micro-fade, so the change reads
       as one gesture with the photo cross-fade instead of a hard text jump */
    var textTimer = null;
    function swapCopy(map) {
      var targets = [
        [phrase, map.phrase],
        [story, map.story],
        [captionTitle, map.captionTitle],
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

    /* ---- the finish rail: chevrons scroll, cards select ---- */

    var strip = section.querySelector('.fs-cards');
    var prevBtn = section.querySelector('[data-fs-prev]');
    var nextBtn = section.querySelector('[data-fs-next]');

    function scrollStrip(x) {
      if (typeof strip.scrollTo === 'function') {
        try {
          strip.scrollTo({ left: x, behavior: reduce ? 'auto' : 'smooth' });
          return;
        } catch (e) { /* very old engines fall through to the assignment */ }
      }
      strip.scrollLeft = x;
    }

    function navState() {
      if (!strip || !prevBtn || !nextBtn) {
        return;
      }
      var max = strip.scrollWidth - strip.clientWidth;
      prevBtn.disabled = strip.scrollLeft <= 1;
      nextBtn.disabled = max <= 1 || strip.scrollLeft >= max - 1;
    }

    function nudge(dir) {
      var max = strip.scrollWidth - strip.clientWidth;
      var target = strip.scrollLeft + dir * strip.clientWidth * 0.75;
      if (target < 0) { target = 0; }
      if (target > max) { target = max; }
      scrollStrip(target);
    }

    /* bring the freshly selected card into view, but never jog the rail
       while the customer is already looking at that card */
    function revealCard(card) {
      if (!strip) {
        return;
      }
      var left = card.offsetLeft;
      var right = left + card.offsetWidth;
      var max = strip.scrollWidth - strip.clientWidth;
      if (left >= strip.scrollLeft && right <= strip.scrollLeft + strip.clientWidth) {
        return;
      }
      var target = Math.min(Math.max(0, left - 8), Math.max(0, max));
      scrollStrip(target);
    }

    if (prevBtn && nextBtn && strip) {
      prevBtn.addEventListener('click', function () { nudge(-1); });
      nextBtn.addEventListener('click', function () { nudge(1); });
      strip.addEventListener('scroll', navState, { passive: true });
      window.addEventListener('resize', navState);
      navState();
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
        captionTitle: data.title,
        title: data.title,
        desc: data.desc
      });

      if (!options.skipImage) {
        applyImage(key);
      }
      revealCard(card);

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

    /* ---- overlays: keep the callout and the hotspots glued to the faucet.
            data-x / data-y are percentages of the photograph; the scene is
            painted with object-fit: cover, anchored right, so the crop eats
            into the photograph's left edge first. ---- */
    var pinned = Array.prototype.slice.call(section.querySelectorAll('.fs-scene-ui [data-x]'));

    function placeOverlays() {
      if (!scene || !ui || !imgA.naturalWidth || !imgA.naturalHeight || !pinned.length) {
        return;
      }
      var sr = scene.getBoundingClientRect();
      var ur = ui.getBoundingClientRect();
      if (!sr.width || !sr.height || !ur.width || !ur.height) {
        return;
      }
      var scale = Math.max(sr.width / imgA.naturalWidth, sr.height / imgA.naturalHeight);
      var rw = imgA.naturalWidth * scale;   /* rendered photo size (px) */
      var rh = imgA.naturalHeight * scale;
      var offX = sr.width - rw;             /* right-anchored: <= 0 */
      var offY = (sr.height - rh) / 2;      /* vertically centred  */

      pinned.forEach(function (el) {
        var x = (parseFloat(el.getAttribute('data-x')) || 0) / 100 * rw + offX;
        var y = (parseFloat(el.getAttribute('data-y')) || 0) / 100 * rh + offY;
        var leftPct = (sr.left - ur.left + x) / ur.width * 100;
        var topPct = (sr.top - ur.top + y) / ur.height * 100;
        el.style.left = Math.max(0, Math.min(100, leftPct)) + '%';
        el.style.top = Math.max(0, Math.min(100, topPct)) + '%';
      });
    }

    if (imgA.addEventListener) {
      imgA.addEventListener('load', placeOverlays);
      imgA.addEventListener('error', function () { /* CSS defaults stay */ });
    }
    window.addEventListener('resize', placeOverlays);
    placeOverlays();

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

    document.addEventListener('keydown', function (event) {
      if (event.key !== 'Escape') return;
      setDrawer(false);
      spots.forEach(function (s) { s.classList.remove('is-open'); });
    });

    /* ---- entrance choreography: title up, scene settling in, cards one by
            one, the rail, then the hotspots ---- */
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
