/* Finish selector: swatch data, showcase swap, quote link. */
(function () {
  'use strict';

  var FINISHES = {
    'brushed-rose-gold': {
      title: 'Brushed Rose Gold (PVD)',
      tag: 'PVD TITANIUM VAPOR DEPOSITION / 10-YEAR COLOR STABILITY',
      image: 'images/finishes/brushed-rose-gold.jpg',
      desc: 'An opulent, warm metallic hue crafted via vacuum plasma PVD. Ultra-resistant to micro-scratches, finger marks, and corrosion in coastal and humid spa environments.',
      base: 'Solid Brass CW617N',
      coating: 'PVD Titanium 0.4um',
      cartridge: 'Kerox Hungary 35mm Ceramic',
      aerator: 'Neoperl Coin-Slot Pro-Eco 5.7 L/min'
    },
    chrome: {
      title: 'Polished Mirror Chrome',
      tag: '12-MICRON MULTI-STAGE ELECTROPLATING / ISO 9227 TESTED',
      image: 'images/finishes/chrome.jpg',
      desc: 'The quintessential architectural finish. Triple-layer nickel-chromium electroplating delivering flawless diamond mirror reflectivity and effortless descaling.',
      base: 'Low-Lead Architectural Brass',
      coating: 'Multi-layer Ni-Cr 12.5um',
      cartridge: 'Sedal European Ceramic 35mm',
      aerator: 'Anti-Limescale Laminar Stream'
    },
    'brushed-gold': {
      title: 'Brushed Royal Gold (PVD)',
      tag: 'ARCHITECTURAL MATTE BRASS / RESISTANT TO AGGRESSIVE ACIDS',
      image: 'images/finishes/brushed-gold.jpg',
      desc: 'A luminous, subtle champagne-gold with directional brushwork that captures natural bathroom lighting while resisting soap film and water spotting.',
      base: 'Dezincification Resistant Brass (DZR)',
      coating: 'Zirconium PVD Physical Vapor',
      cartridge: 'Kerox Ultra-Smooth 500k Cycles',
      aerator: 'PRO-ECO Water-Saving 4.8 L/min'
    },
    'matte-black': {
      title: 'Matte Obsidian Black',
      tag: 'ELECTROSTATIC SOFT-TOUCH POWDER COATING / ZERO GLARE',
      image: 'images/finishes/matte-black.jpg',
      desc: 'A tactile, non-reflective velvety black engineered for bold monolithic contrasts with white vitreous china and natural stone vanities.',
      base: 'Solid Cast Brass & Duroplast',
      coating: 'Electrophoretic Matte Coating',
      cartridge: 'Ceramic Disc Heavy-Duty',
      aerator: 'Integrated Concealed Aerator'
    },
    'brushed-steel': {
      title: 'Brushed Architectural Steel',
      tag: 'AISI 304/316 MARINE GRADE ALLOY / HYGIENIC SURFACE',
      image: 'images/finishes/brushed-steel.jpg',
      desc: 'Industrial precision and raw architectural honesty. Naturally antibacterial with linear satin polish that endures decades of heavy commercial use.',
      base: 'AISI 304 Stainless Steel',
      coating: 'Linear 320-Grit Satin Brush',
      cartridge: 'High-Pressure Ceramic Disc',
      aerator: 'Splash-Free Micro-Aerator'
    },
    gunmetal: {
      title: 'Gunmetal Titanium Grey',
      tag: 'DEEP ANTHRACITE PVD / AEROSPACE HARDNESS',
      image: 'images/finishes/gunmetal.jpg',
      desc: 'A moody, deep charcoal metallic finish designed for modern loft, brutalist, and dark spa architecture with an iridescent aquatic undertone.',
      base: 'Solid Brass CW617N',
      coating: 'PVD Titanium Carbonitride',
      cartridge: 'Thermostatic 38C Safety Core',
      aerator: 'Cascade Soft Flow'
    }
  };

  function init() {
    var section = document.querySelector('.finishes-section');
    var buttons = document.querySelectorAll('.finish-swatch-btn');
    var image = document.getElementById('finish-showcase-img');
    if (!section || !buttons.length || !image) {
      return;
    }

    var base = document.documentElement.getAttribute('data-base') || '';
    var title = document.getElementById('finish-title');
    var tag = document.getElementById('finish-tag');
    var desc = document.getElementById('finish-desc');
    var baseSpec = document.getElementById('finish-base');
    var coating = document.getElementById('finish-coating');
    var cartridge = document.getElementById('finish-cartridge');
    var aerator = document.getElementById('finish-aerator');

    function setText(node, value) {
      if (node && value) {
        node.textContent = value;
      }
    }

    buttons.forEach(function (button) {
      button.addEventListener('click', function () {
        var data = FINISHES[button.dataset.finish];
        if (!data) {
          return;
        }

        buttons.forEach(function (other) { other.classList.remove('is-active'); });
        button.classList.add('is-active');

        image.style.opacity = '0';

        setTimeout(function () {
          image.src = base + '/' + data.image;
          setText(title, button.dataset.title || data.title);
          setText(tag, data.tag);
          setText(desc, button.dataset.desc || data.desc);
          setText(baseSpec, data.base);
          setText(coating, data.coating);
          setText(cartridge, data.cartridge);
          setText(aerator, data.aerator);

          image.style.opacity = '1';
        }, 200);
      });
    });
  }

  document.addEventListener('DOMContentLoaded', init);
})();
