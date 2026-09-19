/* Finish selector: swatch data, showcase swap, full-screen viewer. */
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
    'brushed-steel': {
      title: 'Brushed Architectural Steel',
      tag: 'AISI 304/316 MARINE GRADE ALLOY / HYGIENIC SURFACE',
      image: 'images/finishes/swatch-steel.jpg',
      desc: 'Industrial precision and raw architectural honesty. Naturally antibacterial with linear satin polish that endures decades of heavy commercial use.',
      base: 'AISI 304 Stainless Steel',
      coating: 'Linear 320-Grit Satin Brush',
      cartridge: 'High-Pressure Ceramic Disc',
      aerator: 'Splash-Free Micro-Aerator'
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
    }
  };

  var ORDER = ['brushed-rose-gold', 'chrome', 'brushed-gold', 'matte-black', 'brushed-steel', 'gunmetal'];

  function init() {
    var section = document.querySelector('.finishes-section');
    var buttons = Array.prototype.slice.call(document.querySelectorAll('.finish-swatch-btn'));
    var image = document.getElementById('finish-showcase-img');
    if (!section || !buttons.length || !image) {
      return;
    }

    var base = document.documentElement.getAttribute('data-base') || '';
    var viewBtn = document.getElementById('finish-view-btn');
    var title = document.getElementById('finish-title');
    var tag = document.getElementById('finish-tag');
    var desc = document.getElementById('finish-desc');
    var indexEl = document.getElementById('finish-index');
    var baseSpec = document.getElementById('finish-base');
    var coating = document.getElementById('finish-coating');
    var cartridge = document.getElementById('finish-cartridge');
    var aerator = document.getElementById('finish-aerator');

    var lightbox = document.getElementById('finish-lightbox');
    var lbImg = document.getElementById('finish-lightbox-img');
    var lbTitle = document.getElementById('finish-lightbox-title');
    var lbTag = document.getElementById('finish-lightbox-tag');
    var lbClose = document.getElementById('finish-lightbox-close');
    var lbPrev = document.getElementById('finish-lightbox-prev');
    var lbNext = document.getElementById('finish-lightbox-next');

    var current = 0;
    var swapTimer = null;
    var lastTrigger = null;

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

      /* single clean image swap, no fancy framing */
      image.style.opacity = '0';
      if (swapTimer) {
        window.clearTimeout(swapTimer);
      }
      swapTimer = window.setTimeout(function () {
        image.src = base + '/' + data.image;
        image.style.opacity = '1';
      }, 160);

      if (lbImg) {
        lbImg.src = base + '/' + data.image;
        setText(lbTitle, data.title);
        setText(lbTag, data.tag);
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
    }

    buttons.forEach(function (button) {
      button.addEventListener('click', function () {
        select(button.dataset.finish, button);
      });
    });

    /* ---------- full-screen viewer ---------- */

    function openLightbox() {
      if (!lightbox) {
        return;
      }
      /* show the exact image the visitor was just looking at */
      if (lbImg && image.src) {
        lbImg.src = image.src;
      }
      var key = buttons[current] ? buttons[current].dataset.finish : ORDER[current];
      var data = FINISHES[key];
      if (data) {
        setText(lbTitle, data.title);
        setText(lbTag, data.tag);
      }
      lastTrigger = document.activeElement;
      lightbox.hidden = false;
      document.body.classList.add('finish-lightbox-open');
      if (lbClose) {
        lbClose.focus();
      }
    }

    function closeLightbox() {
      if (!lightbox || lightbox.hidden) {
        return;
      }
      lightbox.hidden = true;
      document.body.classList.remove('finish-lightbox-open');
      if (lastTrigger && typeof lastTrigger.focus === 'function') {
        lastTrigger.focus();
      }
    }

    function step(delta) {
      var next = (current + delta + ORDER.length) % ORDER.length;
      current = next;
      var btn = buttons[next] || null;
      if (btn) {
        buttons.forEach(function (other) {
          var active = other === btn;
          other.classList.toggle('is-active', active);
          other.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
      }
      apply(ORDER[next]);
    }

    if (viewBtn) {
      viewBtn.addEventListener('click', openLightbox);
    }
    if (lbClose) {
      lbClose.addEventListener('click', closeLightbox);
    }
    if (lbPrev) {
      lbPrev.addEventListener('click', function () { step(-1); });
    }
    if (lbNext) {
      lbNext.addEventListener('click', function () { step(1); });
    }
    if (lightbox) {
      lightbox.addEventListener('click', function (e) {
        if (e.target === lightbox) {
          closeLightbox();
        }
      });
    }

    document.addEventListener('keydown', function (e) {
      if (!lightbox || lightbox.hidden) {
        return;
      }
      if (e.key === 'Escape') {
        closeLightbox();
      } else if (e.key === 'ArrowLeft') {
        step(-1);
      } else if (e.key === 'ArrowRight') {
        step(1);
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
