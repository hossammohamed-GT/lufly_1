/**
 * Lufly Architectural Ceramics - Cinematic Hero Video & Scene Controller
 */
document.addEventListener('DOMContentLoaded', function () {
  const hero = document.querySelector('.hero-cinematic');
  if (!hero) return;

  const scenes = [
    {
      id: 0,
      videoId: 'hero-video-1',
      tag: '01 • ARCHITECTURAL SANITARY CERAMICS',
      title: 'Architectural Sanitary Ceramics Engineered for European Living.',
      desc: 'Precision-engineered rimless wall-hung toilets, designer countertop washbasins, and architectural tapware. Fired at 1,250°C for exceptional durability and European project specification.',
      badge: 'FLAGSHIP • EN 997',
      sku: 'SKU: 1620-111',
      name: 'Rimless Wall-Hung WC Pan',
      specs: '540 × 360 mm • Concealed Wall-Hung',
      img: '/images/products/prod_146_1620-111-a.jpg',
      url: '/en/products'
    },
    {
      id: 1,
      videoId: 'hero-video-2',
      tag: '02 • 1,250°C KILN PRECISION',
      title: '1,250°C High-Density Vitreous China Resilience.',
      desc: 'Sub-micron glaze uniformity, ultra-low <0.5% water absorption rate, and lifetime structural integrity achieved through robotic kiln firing in our integrated Gaziantep facility.',
      badge: 'MASTERPIECE • CE',
      sku: 'SKU: 1610-554',
      name: 'DUERO Countertop Washbasin',
      specs: '600 × 420 × 140 mm • Ultra-Slim Rim',
      img: '/images/products/prod_205_ESINO.jpg',
      url: '/en/products'
    },
    {
      id: 2,
      videoId: 'hero-video-3',
      tag: '03 • MINIMALIST HYDRODYNAMICS',
      title: 'Geometric Elegance Meets Silent Rimless Flush Dynamics.',
      desc: 'Hydrodynamic flush geometry eliminates rim crevices for 99.9% antibacterial cleanliness and water conservation in compliance with EN 997 class 1 standards.',
      badge: 'ARCHITECTURAL • PVD',
      sku: 'SKU: 1654-001',
      name: 'Thermostatic Concealed Mixer System',
      specs: 'Solid Brass • PVD Brushed Surface',
      img: '/images/products/prod_2109_1654-001.jpg',
      url: '/en/products'
    }
  ];

  let currentSceneIdx = 0;
  let progress = 0;
  const durationMs = 6000;
  const stepMs = 50;
  let timer = null;
  let isPaused = false;

  const tagEl = document.getElementById('hero-scene-tag');
  const titleEl = document.getElementById('hero-scene-title');
  const descEl = document.getElementById('hero-scene-desc');
  const badgeEl = document.getElementById('hero-spotlight-badge');
  const skuEl = document.getElementById('hero-spotlight-sku');
  const nameEl = document.getElementById('hero-spotlight-title');
  const specsEl = document.getElementById('hero-spotlight-specs');
  const imgEl = document.getElementById('hero-spotlight-img');
  const linkEl = document.getElementById('hero-spotlight-link');
  const tabs = document.querySelectorAll('.hero-tab-btn');
  const videos = document.querySelectorAll('.hero-bg-video');

  function applyScene(idx) {
    const s = scenes[idx];
    if (!s) return;

    // Cross-fade videos
    videos.forEach((v) => {
      if (v.id === s.videoId) {
        v.classList.add('active');
        v.play().catch(() => {});
      } else {
        v.classList.remove('active');
      }
    });

    // Content fade animation
    if (titleEl) {
      titleEl.style.opacity = '0';
      titleEl.style.transform = 'translateY(8px)';
    }
    if (descEl) {
      descEl.style.opacity = '0';
    }

    setTimeout(() => {
      if (tagEl) tagEl.textContent = s.tag;
      if (titleEl) {
        titleEl.textContent = s.title;
        titleEl.style.opacity = '1';
        titleEl.style.transform = 'translateY(0)';
      }
      if (descEl) {
        descEl.textContent = s.desc;
        descEl.style.opacity = '1';
      }
      if (badgeEl) badgeEl.textContent = s.badge;
      if (skuEl) skuEl.textContent = s.sku;
      if (nameEl) nameEl.textContent = s.name;
      if (specsEl) specsEl.textContent = s.specs;
      if (imgEl) {
        imgEl.src = s.img;
        imgEl.alt = s.name;
      }
      if (linkEl) linkEl.href = s.url;
    }, 200);

    // Update active tab styles
    tabs.forEach((tab, i) => {
      const fill = tab.querySelector('.hero-tab-progress-fill');
      if (i === idx) {
        tab.classList.add('active');
        tab.setAttribute('aria-selected', 'true');
      } else {
        tab.classList.remove('active');
        tab.setAttribute('aria-selected', 'false');
        if (fill) fill.style.width = i < idx ? '100%' : '0%';
      }
    });

    progress = 0;
  }

  function tick() {
    if (!isPaused) {
      progress += (stepMs / durationMs) * 100;
      const activeTab = tabs[currentSceneIdx];
      if (activeTab) {
        const fill = activeTab.querySelector('.hero-tab-progress-fill');
        if (fill) fill.style.width = Math.min(progress, 100) + '%';
      }

      if (progress >= 100) {
        currentSceneIdx = (currentSceneIdx + 1) % scenes.length;
        applyScene(currentSceneIdx);
      }
    }
  }

  // Click on tab to jump to scene
  tabs.forEach((tab) => {
    tab.addEventListener('click', function () {
      const targetIdx = parseInt(this.getAttribute('data-scene'), 10);
      if (!isNaN(targetIdx) && targetIdx !== currentSceneIdx) {
        currentSceneIdx = targetIdx;
        applyScene(currentSceneIdx);
      }
    });
  });

  // Pause timer on hover
  hero.addEventListener('mouseenter', () => (isPaused = true));
  hero.addEventListener('mouseleave', () => (isPaused = false));

  // Initialize first scene
  applyScene(0);
  timer = setInterval(tick, stepMs);

  // Watch Reel Modal
  const reelBtn = document.getElementById('hero-watch-reel-btn');
  const modal = document.getElementById('theater-modal');
  const modalClose = document.getElementById('theater-modal-close');
  const modalVideo = document.getElementById('theater-modal-video');

  if (reelBtn && modal) {
    reelBtn.addEventListener('click', function (e) {
      e.preventDefault();
      modal.classList.add('active');
      if (modalVideo) {
        modalVideo.currentTime = 0;
        modalVideo.play().catch(() => {});
      }
    });
  }

  if (modalClose && modal) {
    modalClose.addEventListener('click', function () {
      modal.classList.remove('active');
      if (modalVideo) modalVideo.pause();
    });
  }

  if (modal) {
    modal.addEventListener('click', function (e) {
      if (e.target === modal) {
        modal.classList.remove('active');
        if (modalVideo) modalVideo.pause();
      }
    });
  }
});
