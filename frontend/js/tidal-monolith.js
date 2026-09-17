/**
 * LUFLY — Direction 01 / 10: TIDAL MONOLITH
 * Interactive Client Controller (Finish Switcher, Instant Search, Hero Slider, Language Dropdown & Theme Switcher)
 */
document.addEventListener('DOMContentLoaded', () => {
  initFinishSelector();
  initQuickSearch();
  initStickyNav();
  initLanguageDropdown();
  initThemeSwitcher();
  initDeanteHeroSlider();
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

  const currentLocale = input.dataset.locale || 'en';
  let debounceTimer;

  async function fetchProducts(searchTerm) {
    const q = (searchTerm || '').trim();
    if (!q) {
      dropdown.style.display = 'none';
      return [];
    }

    try {
      const url = `/api/products/search?q=${encodeURIComponent(q)}&limit=8&locale=${encodeURIComponent(currentLocale)}`;
      const res = await fetch(url);
      if (!res.ok) {
        return [];
      }

      const json = await res.json();
      return Array.isArray(json.data) ? json.data : [];
    } catch (e) {
      console.warn('Instant search failed:', e);
      return [];
    }
  }

  input.addEventListener('input', () => {
    clearTimeout(debounceTimer);
    const query = input.value.trim();

    if (query.length < 2) {
      dropdown.style.display = 'none';
      dropdown.innerHTML = '';
      return;
    }

    debounceTimer = setTimeout(async () => {
      const matches = await fetchProducts(query);

      if (!matches.length) {
        dropdown.innerHTML = `<div style="padding: 14px; color: var(--lufly-muted); font-size: 13px; text-align: center;">No fixtures found matching "${escapeHtml(query)}"</div>`;
        dropdown.style.display = 'block';
        return;
      }

      dropdown.innerHTML = matches.map((p) => {
        const productName = escapeHtml(p.name || p.sku || 'Sanitary Fixture');
        const sku = escapeHtml(p.sku || '');
        const image = (() => {
          const raw = p.image || p.main_image_url || p.image_url || '';
          if (!raw) return '/images/logo.png';
          if (raw.startsWith('http://') || raw.startsWith('https://')) return raw;
          if (raw.startsWith('/')) return raw;
          return '/' + raw.replace(/^\/+/, '');
        })();
        const slug = p.slug || p.id || '';
        const link = `/${currentLocale}/products/${slug}`;

        return `
          <a href="${link}" style="display: flex; align-items: center; gap: 12px; padding: 10px 12px; border-radius: 8px; text-decoration: none; border-bottom: 1px solid var(--lufly-border); transition: background 0.2s ease;">
            <img src="${image}" style="width: 52px; height: 52px; object-fit: contain; background: #fff; border-radius: 8px; padding: 4px; border: 1px solid var(--lufly-border); flex-shrink: 0;" alt="${productName}">
            <div style="min-width: 0; flex: 1;">
              <div style="font-size: 13px; font-weight: 600; color: var(--lufly-text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${productName}</div>
              <div style="font-size: 11px; color: var(--lufly-teal); font-weight: 600; margin-top: 2px;">SKU: ${sku}</div>
            </div>
          </a>
        `;
      }).join('');

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
  const header = document.querySelector('.deante-header');
  if (!header) return;

  window.addEventListener('scroll', () => {
    if (window.scrollY > 20) {
      header.style.boxShadow = '0 10px 30px rgba(16, 42, 42, 0.08)';
    } else {
      header.style.boxShadow = 'none';
    }
  }, { passive: true });
}

/* ==========================================================================
   4. Multilingual Dropdown Controller
   ========================================================================== */
function initLanguageDropdown() {
  const menu = document.getElementById('lufly-lang-menu');
  const toggle = document.getElementById('lufly-lang-toggle');
  if (!menu || !toggle) return;

  toggle.addEventListener('click', (e) => {
    e.stopPropagation();
    const isOpen = menu.classList.toggle('is-open');
    toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
  });

  document.addEventListener('click', (e) => {
    if (!menu.contains(e.target)) {
      menu.classList.remove('is-open');
      toggle.setAttribute('aria-expanded', 'false');
    }
  });
}

/* ==========================================================================
   5. Dark / Light Theme Switcher Controller
   ========================================================================== */
function initThemeSwitcher() {
  const btn = document.getElementById('lufly-theme-toggle-btn');
  if (!btn) return;
  const sunIcon = btn.querySelector('.theme-icon-sun');
  const moonIcon = btn.querySelector('.theme-icon-moon');

  function updateIcons(theme) {
    if (theme === 'dark') {
      if (sunIcon) sunIcon.style.display = 'block';
      if (moonIcon) moonIcon.style.display = 'none';
    } else {
      if (sunIcon) sunIcon.style.display = 'none';
      if (moonIcon) moonIcon.style.display = 'block';
    }
  }

  const savedTheme = localStorage.getItem('lufly-theme') || document.documentElement.getAttribute('data-theme') || 'light';
  document.documentElement.setAttribute('data-theme', savedTheme);
  updateIcons(savedTheme);

  btn.addEventListener('click', () => {
    const currentTheme = document.documentElement.getAttribute('data-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', newTheme);
    localStorage.setItem('lufly-theme', newTheme);
    updateIcons(newTheme);
  });
}

/* ==========================================================================
   6. Deante-Style Cinematic Hero Slider Controller (4 Bespoke Slides + RTL)
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

  function isRTL() {
    return document.documentElement.getAttribute('dir') === 'rtl';
  }

  function goToSlide(index) {
    if (index < 0) index = total - 1;
    if (index >= total) index = 0;
    currentIndex = index;

    const multiplier = isRTL() ? 100 : -100;
    track.style.transform = `translateX(${currentIndex * multiplier}%)`;

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

  goToSlide(0);
  startAuto();
}
