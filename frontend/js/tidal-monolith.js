/**
 * LUFLY — Direction 01 / 10: TIDAL MONOLITH
 * Interactive Client Controller (Finish Switcher, Instant Search, & Room Sliders)
 */
document.addEventListener('DOMContentLoaded', () => {
  initFinishSelector();
  initQuickSearch();
  initStickyNav();
});

/* ==========================================================================
   1. Interactive Finish & Color Selector ("Choose color of fittings")
   ========================================================================== */
function initFinishSelector() {
  const finishData = {
    'brushed-rose-gold': {
      title: 'Brushed Rose Gold (PVD)',
      tag: 'PVD TITANIUM VAPOR DEPOSITION • 10-YEAR COLOR STABILITY',
      image: '/images/finishes/brushed-rose-gold.jpg',
      desc: 'An opulent, warm metallic hue crafted via vacuum plasma PVD. Ultra-resistant to micro-scratches, finger marks, and corrosion in coastal and humid spa environments.',
      base: 'Solid Brass CW617N',
      coating: 'PVD Titanium 0.4µm',
      cartridge: 'Kerox® Hungary 35mm Ceramic',
      aerator: 'Neoperl® Coin-Slot Pro-Eco 5.7 L/min'
    },
    'chrome': {
      title: 'Polished Mirror Chrome',
      tag: '12-MICRON MULTI-STAGE ELECTROPLATING • ISO 9227 TESTED',
      image: '/images/finishes/chrome.jpg',
      desc: 'The quintessential architectural finish. Triple-layer nickel-chromium electroplating delivering flawless diamond mirror reflectivity and effortless descaling.',
      base: 'Low-Lead Architectural Brass',
      coating: 'Multi-layer Ni-Cr 12.5µm',
      cartridge: 'Sedal® European Ceramic 35mm',
      aerator: 'Anti-Limescale Laminar Stream'
    },
    'brushed-gold': {
      title: 'Brushed Royal Gold (PVD)',
      tag: 'ARCHITECTURAL MATTE BRASS • RESISTANT TO AGGRESSIVE ACIDS',
      image: '/images/finishes/brushed-gold.jpg',
      desc: 'A luminous, subtle champagne-gold with directional brushwork that captures natural bathroom lighting while resisting soap film and water spotting.',
      base: 'Dezincification Resistant Brass (DZR)',
      coating: 'Zirconium PVD Physical Vapor',
      cartridge: 'Kerox® Ultra-Smooth 500k Cycles',
      aerator: 'PRO-ECO Water-Saving 4.8 L/min'
    },
    'matte-black': {
      title: 'Matte Obsidian Black',
      tag: 'ELECTROSTATIC SOFT-TOUCH POWDER COATING • ZERO GLARE',
      image: '/images/finishes/matte-black.jpg',
      desc: 'A tactile, non-reflective velvety black engineered for bold monolithic contrasts with white vitreous china and natural stone vanities.',
      base: 'Solid Cast Brass & Duroplast',
      finish: 'Electrophoretic Matte Coating',
      cartridge: 'Ceramic Disc Heavy-Duty',
      aerator: 'Integrated Concealed Aerator'
    },
    'brushed-steel': {
      title: 'Brushed Architectural Steel',
      tag: 'AISI 304/316 MARINE GRADE ALLOY • HYGIENIC SURFACE',
      image: '/images/finishes/brushed-steel.jpg',
      desc: 'Industrial precision and raw architectural honesty. Naturally antibacterial with linear satin polish that endures decades of heavy commercial use.',
      base: 'AISI 304 Stainless Steel',
      surface: 'Linear 320-Grit Satin Brush',
      cartridge: 'High-Pressure Ceramic Disc',
      aerator: 'Splash-Free Micro-Aerator'
    },
    'gunmetal': {
      title: 'Gunmetal Titanium Grey',
      tag: 'DEEP ANTHRACITE PVD • AEROSPACE HARDNESS',
      image: '/images/finishes/gunmetal.jpg',
      desc: 'A moody, deep charcoal metallic finish designed for modern loft, brutalist, and dark spa architecture with an iridescent aquatic undertone.',
      base: 'Solid Brass CW617N',
      coating: 'PVD Titanium Carbonitride',
      cartridge: 'Thermostatic 38°C Safety Core',
      aerator: 'Cascade Soft Flow'
    }
  };

  const swatchBtns = document.querySelectorAll('.finish-swatch-btn');
  const imgEl = document.getElementById('finish-showcase-img');
  const titleEl = document.getElementById('finish-title');
  const tagEl = document.getElementById('finish-tag');
  const descEl = document.getElementById('finish-desc');
  const baseEl = document.getElementById('finish-base');
  const coatingEl = document.getElementById('finish-coating');
  const cartEl = document.getElementById('finish-cartridge');
  const aeratorEl = document.getElementById('finish-aerator');
  const waBtn = document.getElementById('finish-wa-btn');

  if (!swatchBtns.length || !imgEl) return;

  swatchBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      const finishKey = btn.dataset.finish;
      const data = finishData[finishKey];
      if (!data) return;

      swatchBtns.forEach(b => b.classList.remove('is-active'));
      btn.classList.add('is-active');

      imgEl.style.opacity = '0';
      imgEl.style.transform = 'scale(0.95)';

      setTimeout(() => {
        imgEl.src = data.image;
        if (titleEl) titleEl.textContent = data.title;
        if (tagEl) tagEl.textContent = data.tag;
        if (descEl) descEl.textContent = data.desc;
        if (baseEl) baseEl.textContent = data.base;
        if (coatingEl) coatingEl.textContent = data.coating || data.surface || data.finish;
        if (cartEl) cartEl.textContent = data.cartridge;
        if (aeratorEl) aeratorEl.textContent = data.aerator;

        if (waBtn) {
          const text = encodeURIComponent(`Hello LUFLY, I am specifying the "${data.title}" finish for an architectural project. Please share high-res renders and export pricing.`);
          waBtn.href = `https://wa.me/908503040817?text=${text}`;
        }

        imgEl.style.opacity = '1';
        imgEl.style.transform = 'scale(1)';
      }, 200);
    });
  });
}

/* ==========================================================================
   2. Real-Time Architectural Search Bar
   ========================================================================== */
function initQuickSearch() {
  const input = document.querySelector('.lufly-search-input');
  const dropdown = document.querySelector('.lufly-search-results');
  if (!input || !dropdown) return;

  let debounceTimer;
  let cachedProducts = null;

  async function fetchProducts() {
    if (cachedProducts) return cachedProducts;
    try {
      const res = await fetch('/api/products');
      if (res.ok) {
        const json = await res.json();
        cachedProducts = json.data || json;
        return cachedProducts;
      }
    } catch (e) {
      console.warn('API lookup failed, fallback to local', e);
    }
    return [];
  }

  input.addEventListener('input', () => {
    clearTimeout(debounceTimer);
    const query = input.value.trim().toLowerCase();

    if (query.length < 2) {
      dropdown.style.display = 'none';
      return;
    }

    debounceTimer = setTimeout(async () => {
      const products = await fetchProducts();
      const matches = products.filter(p => {
        const name = (p.name || p.sku || '').toLowerCase();
        const sku = (p.sku || '').toLowerCase();
        return name.includes(query) || sku.includes(query);
      }).slice(0, 6);

      if (matches.length === 0) {
        dropdown.innerHTML = `<div style="padding: 12px; color: var(--lufly-muted); font-size: 13px; text-align: center;">No fixtures found matching "${escapeHtml(query)}"</div>`;
      } else {
        dropdown.innerHTML = matches.map(p => {
          const img = p.main_image_url || p.image_url || '/images/logo.png';
          const name = escapeHtml(p.name || p.sku || 'Sanitary Fixture');
          const sku = escapeHtml(p.sku || '');
          const link = `/en/products/${p.id || ''}`;
          return `
            <a href="${link}" style="display: flex; align-items: center; gap: 12px; padding: 8px; border-radius: 4px; text-decoration: none; border-bottom: 1px solid var(--lufly-border);">
              <img src="${img}" style="width: 44px; height: 44px; object-fit: contain; background: #fff; border-radius: 4px;" alt="${name}">
              <div>
                <div style="font-size: 13px; font-weight: 600; color: var(--lufly-text);">${name}</div>
                <div style="font-size: 11px; color: var(--lufly-teal);">${sku}</div>
              </div>
            </a>
          `;
        }).join('');
      }
      dropdown.style.display = 'block';
    }, 200);
  });

  document.addEventListener('click', (e) => {
    if (!input.contains(e.target) && !dropdown.contains(e.target)) {
      dropdown.style.display = 'none';
    }
  });
}

function escapeHtml(str) {
  return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

/* ==========================================================================
   3. Sticky Nav Backdrop Enhancement
   ========================================================================== */
function initStickyNav() {
  const nav = document.querySelector('.lufly-nav');
  if (!nav) return;

  window.addEventListener('scroll', () => {
    if (window.scrollY > 30) {
      nav.style.boxShadow = '0 8px 30px rgba(16, 42, 42, 0.08)';
    } else {
      nav.style.boxShadow = 'none';
    }
  }, { passive: true });
}

/* ==========================================================================
   4. Deante-Style Cinematic Hero Slider Controller
   ========================================================================== */
function initDeanteHeroSlider() {
  const track = document.getElementById('hero-slider-track');
  const slides = document.querySelectorAll('.deante-hero-slide');
  const dots = document.querySelectorAll('.deante-hero-dot');
  const prevBtn = document.getElementById('hero-prev-btn');
  const nextBtn = document.getElementById('hero-next-btn');

  if (!track || slides.length === 0) return;

  let currentIndex = 0;
  const total = slides.length;
  let autoTimer = null;

  function goToSlide(index) {
    if (index < 0) index = total - 1;
    if (index >= total) index = 0;
    currentIndex = index;

    track.style.transform = `translateX(-${currentIndex * 100}%)`;

    dots.forEach((d, i) => {
      if (i === currentIndex) {
        d.classList.add('is-active');
      } else {
        d.classList.remove('is-active');
      }
    });
  }

  function startAuto() {
    stopAuto();
    autoTimer = setInterval(() => {
      goToSlide(currentIndex + 1);
    }, 6000);
  }

  function stopAuto() {
    if (autoTimer) {
      clearInterval(autoTimer);
      autoTimer = null;
    }
  }

  if (nextBtn) {
    nextBtn.addEventListener('click', () => {
      goToSlide(currentIndex + 1);
      startAuto();
    });
  }

  if (prevBtn) {
    prevBtn.addEventListener('click', () => {
      goToSlide(currentIndex - 1);
      startAuto();
    });
  }

  dots.forEach(dot => {
    dot.addEventListener('click', () => {
      const idx = parseInt(dot.dataset.slide, 10);
      goToSlide(idx);
      startAuto();
    });
  });

  const viewport = document.getElementById('hero-slider-viewport');
  if (viewport) {
    viewport.addEventListener('mouseenter', stopAuto);
    viewport.addEventListener('mouseleave', startAuto);
  }

  startAuto();
}

// Call inside DOMContentLoaded
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initDeanteHeroSlider);
} else {
  initDeanteHeroSlider();
}
