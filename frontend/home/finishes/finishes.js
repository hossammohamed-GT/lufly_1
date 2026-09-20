/* Finish selector: swatch data, showcase swap, auto-scroll to the
   showcase panel on phones so the user always sees what changed. */
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
    chrome: {
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
    gunmetal: {
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

  function init() {
    var section = document.querySelector('.finishes-section');
    var buttons = Array.prototype.slice.call(document.querySelectorAll('.finish-swatch-btn'));
    var image = document.getElementById('finish-showcase-img');
    if (!section || !buttons.length || !image) {
      return;
    }

    var base = document.documentElement.getAttribute('data-base') || '';
    var showcase = section.querySelector('.finish-showcase-box');
    var title = document.getElementById('finish-title');
    var tag = document.getElementById('finish-tag');
    var desc = document.getElementById('finish-desc');
    var indexEl = document.getElementById('finish-index');
    var baseSpec = document.getElementById('finish-base');
    var coating = document.getElementById('finish-coating');
    var cartridge = document.getElementById('finish-cartridge');
    var aerator = document.getElementById('finish-aerator');

    var current = 0;
    var swapTimer = null;

    function setText(node, value) {
      if (node && value) {
        node.textContent = value;
      }
    }

    function pad(n) {
      return ('0' + n).slice(-2);
    }

    function apply(key) {
      var data = FINISHES[key];
      if (!data) {
        return;
      }

      setText(title, data.title);
      setText(tag, data.tag);
      setText(desc, data.desc);
      setText(baseSpec, data.base);
      setText(coating, data.coating);
      setText(cartridge, data.cartridge);
      setText(aerator, data.aerator);
      setText(indexEl, pad(ORDER.indexOf(key) + 1));

      image.style.opacity = '0';
      if (swapTimer) {
        window.clearTimeout(swapTimer);
      }
      swapTimer = window.setTimeout(function () {
        image.src = base + '/' + data.image;
        image.style.opacity = '1';
      }, 160);
    }

    /* on phones the showcase sits below the fold: bring it into view
       after a selection so the change is never missed */
    function revealShowcase() {
      if (!showcase || typeof showcase.scrollIntoView !== 'function') {
        return;
      }
      var rect = showcase.getBoundingClientRect();
      var vh = window.innerHeight || document.documentElement.clientHeight;
      if (rect.top > vh * 0.72 || rect.bottom < vh * 0.3) {
        var reduce = window.matchMedia &&
          window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        try {
          showcase.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'start' });
        } catch (e) { /* older browsers */ }
      }
    }

    function select(key, button) {
      current = ORDER.indexOf(key);
      buttons.forEach(function (other) {
        var active = other === button;
        other.classList.toggle('is-active', active);
        other.setAttribute('aria-pressed', active ? 'true' : 'false');
      });
      apply(key);
      revealShowcase();
    }

    buttons.forEach(function (button) {
      button.addEventListener('click', function () {
        select(button.dataset.finish, button);
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
