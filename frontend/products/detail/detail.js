/* ==========================================================================
   LUFLY Product Detail Choreography Engine
   - Directional hero gallery slide cross-glide & gliding segmented pill
   - Gliding dossier underline & smooth height-morphed tab transitions
   - Shared-zoom fullscreen lightbox with directional in-lightbox navigation
   - Universal product-to-product choreography (Variant Cards, Mobile Swatches,
     Similar & Complementary Product Cards, Category Prev/Next Stepper & popstate)
   - Eased in-page anchor glides & scroll-triggered section reveals
   ========================================================================== */
(function () {
    'use strict';

    var pdpRoot = document.querySelector('.pdp');
    if (!pdpRoot) return;

    var active = 0;
    var lightboxCanStepSlides = true;
    var lightboxCloseTimer = null;
    var lightboxSwapTimer = null;
    var dossierHeightTimer = null;
    var activeScrollRaf = 0;
    var reducedMotion = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);

    function getStage() {
        return pdpRoot.querySelector('[data-pdp-stage]');
    }

    function getViewport() {
        var stage = getStage();
        return stage ? stage.querySelector('[data-pdp-viewport]') : null;
    }

    function getSlides() {
        var stage = getStage();
        return stage ? Array.prototype.slice.call(stage.querySelectorAll('[data-pdp-slide]')) : [];
    }

    function getThumbs() {
        var stage = getStage();
        return stage ? Array.prototype.slice.call(stage.querySelectorAll('[data-pdp-thumb]')) : [];
    }

    function getGroupTabs() {
        var stage = getStage();
        return stage ? Array.prototype.slice.call(stage.querySelectorAll('[data-pdp-group]')) : [];
    }

    function slideImage(slideEl) {
        if (!slideEl) return null;
        return slideEl.tagName === 'IMG' ? slideEl : slideEl.querySelector('img');
    }

    function lerp(a, b, t) {
        return a + (b - a) * t;
    }

    /* ========================================================================
       1. Gliding Pill & Tab Indicators (.pdp-views & .pdp-dossier-tabs)
       ======================================================================== */
    function syncViewsGlider(instant) {
        var views = pdpRoot.querySelector('[data-pdp-views]');
        if (!views) return;
        var glider = views.querySelector('[data-pdp-views-glider]');
        var activeTab = views.querySelector('.pdp-view-tab.is-active');
        if (!glider || !activeTab) return;

        var w = activeTab.offsetWidth;
        var x = activeTab.offsetLeft;
        if (w <= 0) return;

        if (instant) {
            glider.style.transition = 'none';
        }
        glider.style.width = w + 'px';
        glider.style.transform = 'translate3d(' + x + 'px, 0, 0)';
        views.setAttribute('data-glider-ready', '1');
        if (instant) {
            void glider.offsetWidth;
            glider.style.transition = '';
        }
    }

    function syncDossierGlider(instant) {
        var tabsWrap = pdpRoot.querySelector('[data-dossier-tabs]');
        if (!tabsWrap) return;
        var glider = tabsWrap.querySelector('[data-dossier-glider]');
        var activeTab = tabsWrap.querySelector('.pdp-dtab.is-active');
        if (!glider || !activeTab) return;

        var w = activeTab.offsetWidth;
        var x = activeTab.offsetLeft;
        if (w <= 0) return;

        if (instant) {
            glider.style.transition = 'none';
        }
        glider.style.width = w + 'px';
        glider.style.transform = 'translate3d(' + x + 'px, 0, 0)';
        tabsWrap.setAttribute('data-glider-ready', '1');
        if (instant) {
            void glider.offsetWidth;
            glider.style.transition = '';
        }
    }

    function syncAllGliders(instant) {
        syncViewsGlider(instant);
        syncDossierGlider(instant);
    }

    window.addEventListener('resize', function () {
        syncAllGliders(true);
    });

    /* ========================================================================
       2. Directional Hero Gallery Slide & View-Group Cross-Glide
       ======================================================================== */
    function show(index, explicitDir) {
        var slides = getSlides();
        if (!slides.length) return;
        var nextIndex = ((index % slides.length) + slides.length) % slides.length;
        var prevIndex = active;
        var dir = explicitDir || (nextIndex >= prevIndex ? 1 : -1);
        active = nextIndex;

        var currentGroup = slides[active].getAttribute('data-slide-group');

        if (prevIndex !== active && !reducedMotion && slides[prevIndex] && slides[active]) {
            var outgoing = slides[prevIndex];
            var incoming = slides[active];
            outgoing.style.setProperty('--pdp-slide-shift', dir > 0 ? '-18px' : '18px');

            incoming.style.transition = 'none';
            incoming.style.setProperty('--pdp-slide-shift', dir > 0 ? '20px' : '-20px');
            incoming.classList.remove('is-active');
            void incoming.offsetWidth;
            incoming.style.transition = '';
        }

        slides.forEach(function (el, i) {
            var on = i === active;
            el.removeAttribute('hidden');
            el.classList.toggle('is-active', on);
            el.setAttribute('aria-hidden', on ? 'false' : 'true');
        });

        getThumbs().forEach(function (el, i) {
            var on = i === active;
            el.classList.toggle('is-active', on);
            el.setAttribute('aria-current', on ? 'true' : 'false');
        });

        getGroupTabs().forEach(function (tab) {
            var on = tab.getAttribute('data-pdp-group') === currentGroup;
            tab.classList.toggle('is-active', on);
            tab.setAttribute('aria-selected', on ? 'true' : 'false');
        });

        syncViewsGlider(false);

        var viewport = getViewport();
        if (viewport && currentGroup) {
            viewport.setAttribute('data-active-group', currentGroup);
        }

        var stage = getStage();
        var counter = stage ? stage.querySelector('[data-pdp-counter]') : null;
        if (counter) {
            counter.textContent = (active + 1) + ' / ' + slides.length;
        }
    }

    function switchToGroup(group, scrollStageIntoView) {
        var slides = getSlides();
        for (var i = 0; i < slides.length; i++) {
            if (slides[i].getAttribute('data-slide-group') === group) {
                if (i !== active) {
                    show(i, i > active ? 1 : -1);
                }
                if (scrollStageIntoView) {
                    var stageEl = pdpRoot.querySelector('.pdp-grid') || getStage();
                    if (stageEl) smoothScrollToElement(stageEl, 84, getStage() ? getStage().querySelector('.pdp-stage-card') : null);
                }
                break;
            }
        }
    }

    /* ========================================================================
       3. Smooth Eased In-Page Scroll Glide (No Abrupt Browser Anchor Jumps!)
       ======================================================================== */
    function cancelSmoothScroll() {
        if (activeScrollRaf) {
            cancelAnimationFrame(activeScrollRaf);
            activeScrollRaf = 0;
        }
    }

    function smoothScrollToY(targetY, duration, onDone) {
        cancelSmoothScroll();
        var maxScroll = Math.max(0, document.documentElement.scrollHeight - window.innerHeight);
        var clampedY = Math.min(Math.max(0, targetY), maxScroll);
        var startY = window.pageYOffset;

        if (reducedMotion || Math.abs(clampedY - startY) < 10) {
            window.scrollTo(0, clampedY);
            if (typeof onDone === 'function') onDone();
            return;
        }

        var dur = duration || 540;
        var t0 = performance.now();
        function step(now) {
            var p = Math.min(1, (now - t0) / dur);
            var ease = 1 - Math.pow(1 - p, 4);
            window.scrollTo(0, lerp(startY, clampedY, ease));
            if (p < 1) {
                activeScrollRaf = requestAnimationFrame(step);
            } else {
                activeScrollRaf = 0;
                if (typeof onDone === 'function') onDone();
            }
        }
        activeScrollRaf = requestAnimationFrame(step);
    }

    function smoothScrollToElement(el, offset, pulseEl) {
        if (!el) return;
        var top = el.getBoundingClientRect().top + window.pageYOffset - (typeof offset === 'number' ? offset : 88);
        smoothScrollToY(top, 560, function () {
            var targetPulse = pulseEl || el;
            if (targetPulse && !reducedMotion) {
                targetPulse.classList.remove('is-anchor-arrived');
                void targetPulse.offsetWidth;
                targetPulse.classList.add('is-anchor-arrived');
                setTimeout(function () {
                    targetPulse.classList.remove('is-anchor-arrived');
                }, 760);
            }
        });
    }

    /* ========================================================================
       4. Height-Morphed Tabbed Architectural Dossier (#pdp-dossier)
       ======================================================================== */
    function switchDossierTab(dTab, instant) {
        var dossier = dTab ? dTab.closest('[data-pdp-dossier]') : null;
        if (!dossier) return;

        var tabs = Array.prototype.slice.call(dossier.querySelectorAll('[data-dossier-tab]'));
        var panels = Array.prototype.slice.call(dossier.querySelectorAll('[data-dossier-panel]'));
        var body = dossier.querySelector('[data-dossier-body]');
        var targetKey = dTab.getAttribute('data-dossier-tab');

        var prevIdx = 0;
        var nextIdx = 0;
        tabs.forEach(function (t, idx) {
            if (t.classList.contains('is-active')) prevIdx = idx;
            if (t.getAttribute('data-dossier-tab') === targetKey) nextIdx = idx;
        });

        if (prevIdx === nextIdx && dTab.classList.contains('is-active') && !instant) {
            syncDossierGlider(false);
            return;
        }

        var fromH = (body && !instant && !reducedMotion) ? body.offsetHeight : 0;

        tabs.forEach(function (t) {
            var on = t.getAttribute('data-dossier-tab') === targetKey;
            t.classList.toggle('is-active', on);
            t.setAttribute('aria-selected', on ? 'true' : 'false');
        });

        syncDossierGlider(!!instant);

        /* Scroll clicked tab gently into view if tabs bar overflows on small screens */
        var tabsWrap = dossier.querySelector('[data-dossier-tabs]');
        if (tabsWrap && !instant && tabsWrap.scrollWidth > tabsWrap.clientWidth) {
            var idealScroll = dTab.offsetLeft - (tabsWrap.clientWidth - dTab.offsetWidth) / 2;
            try {
                tabsWrap.scrollTo({ left: Math.max(0, idealScroll), behavior: reducedMotion ? 'auto' : 'smooth' });
            } catch (_) {
                tabsWrap.scrollLeft = Math.max(0, idealScroll);
            }
        }

        var enterClass = nextIdx >= prevIdx ? 'is-entering-next' : 'is-entering-prev';

        panels.forEach(function (p) {
            var on = p.getAttribute('data-dossier-panel') === targetKey;
            p.classList.remove('is-entering-next', 'is-entering-prev');
            p.classList.toggle('is-active', on);
            if (on) {
                p.removeAttribute('hidden');
                if (!instant && !reducedMotion) {
                    void p.offsetWidth;
                    p.classList.add(enterClass);
                }
            } else {
                p.setAttribute('hidden', '');
            }
        });

        if (body && !instant && !reducedMotion && fromH > 0) {
            if (dossierHeightTimer) clearTimeout(dossierHeightTimer);
            body.style.height = '';
            var toH = body.offsetHeight;
            if (Math.abs(toH - fromH) > 2) {
                body.style.transition = 'none';
                body.style.height = fromH + 'px';
                void body.offsetWidth;
                body.style.transition = '';
                body.style.height = toH + 'px';
                dossierHeightTimer = setTimeout(function () {
                    body.style.height = '';
                    dossierHeightTimer = null;
                }, 390);
            }
        }
    }

    /* ========================================================================
       5. Shared-Zoom Lightbox with Smooth Open, Close & Directional Stepping
       ======================================================================== */
    function getLightbox() {
        return pdpRoot.querySelector('[data-pdp-lightbox]') || document.querySelector('[data-pdp-lightbox]');
    }

    function syncLightboxNavVisibility() {
        var lightbox = getLightbox();
        if (!lightbox) return;
        var prevBtn = lightbox.querySelector('[data-pdp-lightbox-prev]');
        var nextBtn = lightbox.querySelector('[data-pdp-lightbox-next]');
        var showNav = lightboxCanStepSlides && getSlides().length > 1;
        if (prevBtn) {
            if (showNav) prevBtn.removeAttribute('hidden');
            else prevBtn.setAttribute('hidden', '');
        }
        if (nextBtn) {
            if (showNav) nextBtn.removeAttribute('hidden');
            else nextBtn.setAttribute('hidden', '');
        }
    }

    function openLightboxSrc(src, alt, allowSlideStepping) {
        var lightbox = getLightbox();
        var lightboxImg = lightbox ? lightbox.querySelector('[data-pdp-lightbox-img]') : null;
        var lightboxClose = lightbox ? lightbox.querySelector('[data-pdp-lightbox-close]') : null;
        if (!lightbox || !lightboxImg || !src) return;

        if (lightboxCloseTimer) {
            clearTimeout(lightboxCloseTimer);
            lightboxCloseTimer = null;
        }

        lightboxCanStepSlides = !!allowSlideStepping;
        syncLightboxNavVisibility();

        lightboxImg.classList.remove('is-swapping-next', 'is-swapping-prev');
        lightboxImg.src = src;
        lightboxImg.alt = alt || '';

        var wasAlreadyOpen = !lightbox.hasAttribute('hidden') && lightbox.classList.contains('is-open');
        lightbox.classList.remove('is-closing');
        lightbox.removeAttribute('hidden');
        document.body.style.overflow = 'hidden';

        if (!wasAlreadyOpen) {
            if (reducedMotion) {
                lightbox.classList.add('is-open');
            } else {
                lightbox.classList.remove('is-open');
                void lightbox.offsetWidth;
                requestAnimationFrame(function () {
                    lightbox.classList.add('is-open');
                });
            }
            if (lightboxClose) lightboxClose.focus();
        }
    }

    function openLightbox() {
        var slides = getSlides();
        var img = slideImage(slides[active]);
        if (!img) return;
        openLightboxSrc(img.currentSrc || img.src, img.alt, true);
    }

    function stepLightbox(delta) {
        var slides = getSlides();
        if (!lightboxCanStepSlides || slides.length <= 1) return;
        var lightbox = getLightbox();
        var lightboxImg = lightbox ? lightbox.querySelector('[data-pdp-lightbox-img]') : null;
        if (!lightbox || !lightboxImg) return;

        var dir = delta >= 0 ? 1 : -1;
        show(active + delta, dir);
        var nextImg = slideImage(getSlides()[active]);
        if (!nextImg) return;

        if (reducedMotion) {
            lightboxImg.src = nextImg.currentSrc || nextImg.src;
            lightboxImg.alt = nextImg.alt || '';
            return;
        }

        if (lightboxSwapTimer) clearTimeout(lightboxSwapTimer);
        lightboxImg.classList.remove('is-swapping-next', 'is-swapping-prev');
        lightboxImg.classList.add(dir > 0 ? 'is-swapping-next' : 'is-swapping-prev');

        lightboxSwapTimer = setTimeout(function () {
            lightboxImg.src = nextImg.currentSrc || nextImg.src;
            lightboxImg.alt = nextImg.alt || '';
            lightboxImg.style.transition = 'none';
            lightboxImg.style.transform = 'scale(0.975) translate3d(' + (dir > 0 ? '22px' : '-22px') + ', 0, 0)';
            lightboxImg.style.opacity = '0';
            lightboxImg.classList.remove('is-swapping-next', 'is-swapping-prev');
            void lightboxImg.offsetWidth;
            lightboxImg.style.transition = '';
            lightboxImg.style.transform = '';
            lightboxImg.style.opacity = '';
            lightboxSwapTimer = null;
        }, 150);
    }

    function closeLightbox() {
        var lightbox = getLightbox();
        if (!lightbox || lightbox.hasAttribute('hidden')) return;
        document.body.style.overflow = '';

        if (reducedMotion) {
            lightbox.classList.remove('is-open', 'is-closing');
            lightbox.setAttribute('hidden', '');
            return;
        }

        lightbox.classList.remove('is-open');
        lightbox.classList.add('is-closing');
        if (lightboxCloseTimer) clearTimeout(lightboxCloseTimer);
        lightboxCloseTimer = setTimeout(function () {
            lightbox.classList.remove('is-closing');
            lightbox.setAttribute('hidden', '');
            lightboxCloseTimer = null;
        }, 240);
    }

    /* ---- Clipboard Helper ---- */
    function copyText(text) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            return navigator.clipboard.writeText(text);
        }
        return new Promise(function (resolve) {
            var ta = document.createElement('textarea');
            ta.value = text;
            ta.setAttribute('readonly', '');
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.select();
            try { document.execCommand('copy'); } catch (_) {}
            document.body.removeChild(ta);
            resolve();
        });
    }

    /* ========================================================================
       6. Scroll-Triggered Reveal Choreography for Below-the-Fold Sections
       ======================================================================== */
    var revealObserver = null;
    if (!reducedMotion && 'IntersectionObserver' in window) {
        revealObserver = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.setAttribute('data-pdp-reveal', 'shown');
                    revealObserver.unobserve(entry.target);
                }
            });
        }, { rootMargin: '0px 0px -6% 0px', threshold: 0.04 });
    }

    function armSectionReveals() {
        if (!revealObserver || reducedMotion) return;
        var sections = Array.prototype.slice.call(
            pdpRoot.querySelectorAll('[data-pdp-variant-cards], [data-pdp-dossier], [data-pdp-similar], [data-pdp-complementary]')
        );
        sections.forEach(function (sec) {
            if (sec.hasAttribute('data-pdp-reveal')) return;
            var rect = sec.getBoundingClientRect();
            if (rect.top < window.innerHeight * 0.94) {
                sec.setAttribute('data-pdp-reveal', 'shown');
            } else {
                sec.setAttribute('data-pdp-reveal', 'pending');
                revealObserver.observe(sec);
                /* Safety ceiling so no section can ever stay hidden */
                setTimeout(function () {
                    if (sec.getAttribute('data-pdp-reveal') === 'pending') {
                        sec.setAttribute('data-pdp-reveal', 'shown');
                    }
                }, 2400);
            }
        });
    }

    /* ========================================================================
       7. Universal Product-to-Product Choreographed Navigation Engine
       ======================================================================== */
    var htmlCache = new Map();
    var inflightRequests = new Map();
    var activeFlightToken = null;
    var cleanupTimers = [];

    function normalizeUrl(rawUrl) {
        try {
            var u = new URL(rawUrl, window.location.href);
            return u.origin + u.pathname.replace(/\/+$/, '') + u.search;
        } catch (_) {
            return String(rawUrl || '');
        }
    }

    function preloadImage(src) {
        if (!src) return;
        var im = new Image();
        im.decoding = 'async';
        im.src = src;
    }

    function fetchVariantHtml(url) {
        var key = normalizeUrl(url);
        if (htmlCache.has(key)) {
            return Promise.resolve(htmlCache.get(key));
        }
        if (inflightRequests.has(key)) {
            return inflightRequests.get(key);
        }

        var req = fetch(url, {
            method: 'GET',
            credentials: 'same-origin',
            headers: { 'Accept': 'text/html' }
        }).then(function (res) {
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return res.text();
        }).then(function (html) {
            inflightRequests.delete(key);
            if (htmlCache.size >= 24) {
                var oldestKey = htmlCache.keys().next().value;
                if (oldestKey) htmlCache.delete(oldestKey);
            }
            htmlCache.set(key, html);
            return html;
        }).catch(function (err) {
            inflightRequests.delete(key);
            throw err;
        });

        inflightRequests.set(key, req);
        return req;
    }

    try {
        htmlCache.set(normalizeUrl(window.location.href), document.documentElement.outerHTML);
        if (window.history && window.history.replaceState) {
            window.history.replaceState({ pdpUrl: window.location.href }, document.title, window.location.href);
        }
    } catch (_) {}

    function warmVariantImages() {
        Array.prototype.slice.call(pdpRoot.querySelectorAll('[data-pdp-vcard-img]')).forEach(function (el) {
            preloadImage(el.getAttribute('data-pdp-vcard-img'));
        });
    }
    if ('requestIdleCallback' in window) {
        window.requestIdleCallback(warmVariantImages, { timeout: 1500 });
    } else {
        setTimeout(warmVariantImages, 650);
    }

    /* Prefetch on hover / focus / touch for Variant Cards, Mobile Swatches,
       Category Prev/Next Stepper, and Similar/Complementary .pcard links! */
    function handlePrefetchIntent(e) {
        var t = e.target && e.target.closest ? e.target : null;
        if (!t) return;

        var vcard = t.closest('[data-pdp-vcard-url]');
        if (vcard && !vcard.classList.contains('is-current')) {
            var vUrl = vcard.getAttribute('data-pdp-vcard-url');
            var vImg = vcard.getAttribute('data-pdp-vcard-img');
            if (vImg) preloadImage(vImg);
            if (vUrl) fetchVariantHtml(vUrl).catch(function () {});
            return;
        }

        var stepLink = t.closest('a[data-pdp-step]');
        if (stepLink && stepLink.href) {
            fetchVariantHtml(stepLink.href).catch(function () {});
            return;
        }

        var swatch = t.closest('.pdp-colors-mobile a[data-pcard-variant]');
        if (swatch && swatch.href) {
            fetchVariantHtml(swatch.href).catch(function () {});
            return;
        }

        var pcard = t.closest('.pdp-market-section [data-product-card]');
        if (pcard && !t.closest('[data-pcard-variant], [data-fav-toggle], [data-box-add]')) {
            var pcardLink = pcard.querySelector('.pcard-media[href], .pcard-title a[href], .pcard-details[href]');
            if (pcardLink && pcardLink.href) {
                fetchVariantHtml(pcardLink.href).catch(function () {});
            }
        }
    }
    pdpRoot.addEventListener('pointerenter', handlePrefetchIntent, true);
    pdpRoot.addEventListener('focusin', handlePrefetchIntent, true);
    pdpRoot.addEventListener('touchstart', handlePrefetchIntent, { passive: true, capture: true });

    function cancelActiveFlight() {
        cancelSmoothScroll();
        if (activeFlightToken) {
            activeFlightToken.cancelled = true;
            if (activeFlightToken.rafId) cancelAnimationFrame(activeFlightToken.rafId);
            if (activeFlightToken.pinRafId) cancelAnimationFrame(activeFlightToken.pinRafId);
            if (activeFlightToken.ghost && activeFlightToken.ghost.parentNode) {
                activeFlightToken.ghost.parentNode.removeChild(activeFlightToken.ghost);
            }
            activeFlightToken = null;
        }
        while (cleanupTimers.length) {
            clearTimeout(cleanupTimers.pop());
        }
        Array.prototype.slice.call(document.querySelectorAll('.pdp-fly-ghost')).forEach(function (g) {
            if (g.parentNode) g.parentNode.removeChild(g);
        });
        Array.prototype.slice.call(pdpRoot.querySelectorAll('.is-launching, .is-pdp-launching')).forEach(function (el) {
            el.classList.remove('is-launching', 'is-pdp-launching');
        });
        var sc = pdpRoot.querySelector('.pdp-stage-card');
        if (sc) sc.classList.remove('is-stepping-out-next', 'is-stepping-out-prev', 'is-stepping-in-next', 'is-stepping-in-prev');
        var vp = getViewport();
        if (vp) vp.classList.remove('is-receiving');
    }

    function computeHeroTargetScrollY() {
        var grid = pdpRoot.querySelector('.pdp-grid') || getStage();
        if (!grid) return window.pageYOffset;
        var rect = grid.getBoundingClientRect();
        var docTop = rect.top + window.pageYOffset;
        var desired = Math.max(0, docTop - 84);
        var maxScroll = Math.max(0, document.documentElement.scrollHeight - window.innerHeight);
        return Math.min(desired, maxScroll);
    }

    function smoothGlideToHero() {
        smoothScrollToY(computeHeroTargetScrollY(), 500, function () {
            var card = pdpRoot.querySelector('.pdp-stage-card');
            if (card && !reducedMotion) {
                card.classList.remove('is-landed');
                void card.offsetWidth;
                card.classList.add('is-landed');
                cleanupTimers.push(setTimeout(function () { card.classList.remove('is-landed'); }, 720));
            }
        });
    }

    function findMobileSwatchImage(swatchLink) {
        var host = swatchLink.closest('[data-pcard-colors]') || pdpRoot.querySelector('.pdp-colors-mobile');
        if (!host) return '';
        var script = host.querySelector('[data-pcard-variant-data]');
        if (!script) return '';
        var targetId = parseInt(swatchLink.getAttribute('data-pcard-variant') || '0', 10);
        if (!targetId) return '';
        try {
            var list = JSON.parse(script.textContent || '[]');
            var hit = Array.isArray(list) ? list.find(function (v) { return parseInt(v.id, 10) === targetId; }) : null;
            if (!hit || !hit.image) return '';
            var raw = String(hit.image);
            if (/^(?:https?:)?\/\//.test(raw)) return raw;
            var base = document.documentElement.getAttribute('data-base') || '';
            return base + '/' + raw.replace(/^\/+/, '');
        } catch (_) {
            return '';
        }
    }

    function syncHeadAndNav(nextDoc) {
        if (nextDoc.title) {
            document.title = nextDoc.title;
        }
        var curCanon = document.querySelector('link[rel="canonical"]');
        var nextCanon = nextDoc.querySelector('link[rel="canonical"]');
        if (curCanon && nextCanon && nextCanon.href) {
            curCanon.setAttribute('href', nextCanon.getAttribute('href'));
        }
        Array.prototype.slice.call(nextDoc.querySelectorAll('link[rel="alternate"][hreflang]')).forEach(function (nextAlt) {
            var lang = nextAlt.getAttribute('hreflang');
            var curAlt = lang ? document.querySelector('link[rel="alternate"][hreflang="' + lang + '"]') : null;
            if (curAlt && nextAlt.getAttribute('href')) {
                curAlt.setAttribute('href', nextAlt.getAttribute('href'));
            }
        });
        Array.prototype.slice.call(nextDoc.querySelectorAll('a.mnav-lang-option[lang]')).forEach(function (nextLangLink) {
            var code = nextLangLink.getAttribute('lang');
            var href = nextLangLink.getAttribute('href');
            if (!code || !href) return;
            Array.prototype.slice.call(document.querySelectorAll('a.mnav-lang-option[lang="' + code + '"]')).forEach(function (curLink) {
                curLink.setAttribute('href', href);
            });
        });
    }

    /* Reconciles optional below-the-hero bands in exact canonical DOM order so
       navigating between ANY two products (single-color <-> multi-color <-> WC) works! */
    function reconcileOptionalSections(curInner, nextInner) {
        if (!curInner || !nextInner) return;
        var anchor = curInner.querySelector('.pdp-grid');
        if (!anchor) return;

        var selectors = [
            '[data-pdp-variant-cards]',
            '[data-pdp-dossier]',
            '[data-pdp-similar]',
            '[data-pdp-complementary]'
        ];

        selectors.forEach(function (sel) {
            var curSec = curInner.querySelector(sel);
            var nextSec = nextInner.querySelector(sel);

            if (nextSec && curSec) {
                curSec.className = nextSec.className;
                if (nextSec.getAttribute('aria-label')) {
                    curSec.setAttribute('aria-label', nextSec.getAttribute('aria-label'));
                }
                curSec.innerHTML = nextSec.innerHTML;
                curSec.setAttribute('data-pdp-reveal', 'shown');
                curSec.classList.remove('is-morphing-in');
                void curSec.offsetWidth;
                curSec.classList.add('is-morphing-in');
                anchor = curSec;
            } else if (nextSec && !curSec) {
                var cloned = nextSec.cloneNode(true);
                cloned.setAttribute('data-pdp-reveal', 'shown');
                cloned.classList.add('is-morphing-in');
                anchor.insertAdjacentElement('afterend', cloned);
                anchor = cloned;
            } else if (!nextSec && curSec) {
                curSec.parentNode.removeChild(curSec);
            }
        });
    }

    function hydratePdpFromDocument(nextDoc, stepDir) {
        var nextRoot = nextDoc.querySelector('.pdp');
        if (!nextRoot) return false;

        var currentDossierTabKey = null;
        var activeDTab = pdpRoot.querySelector('[data-dossier-tab].is-active');
        if (activeDTab) {
            currentDossierTabKey = activeDTab.getAttribute('data-dossier-tab');
        }

        var nextPid = nextRoot.getAttribute('data-pdp-product-id');
        if (nextPid) pdpRoot.setAttribute('data-pdp-product-id', nextPid);

        /* 1. Top Breadcrumb & Category Lookbook Stepper */
        var curTop = pdpRoot.querySelector('.pdp-topbar');
        var nextTop = nextRoot.querySelector('.pdp-topbar');
        if (curTop && nextTop) {
            curTop.innerHTML = nextTop.innerHTML;
        }

        /* 2. Left Gallery Stage */
        var curStage = getStage();
        var nextStage = nextRoot.querySelector('[data-pdp-stage]');
        if (curStage && nextStage) {
            curStage.className = nextStage.className;
            if (nextStage.getAttribute('aria-label')) {
                curStage.setAttribute('aria-label', nextStage.getAttribute('aria-label'));
            }
            curStage.innerHTML = nextStage.innerHTML;
            active = 0;
        }

        /* 3. Right Editorial Info Panel (Staggered Cascade) */
        var curInfo = pdpRoot.querySelector('.pdp-info');
        var nextInfo = nextRoot.querySelector('.pdp-info');
        if (curInfo && nextInfo) {
            curInfo.classList.remove('is-morphing-out', 'is-morphing-in');
            curInfo.innerHTML = nextInfo.innerHTML;
            void curInfo.offsetWidth;
            curInfo.classList.add('is-morphing-in');
        }

        /* 4. Reconcile Variant Cards, Dossier, Similar & Complementary Sections */
        reconcileOptionalSections(
            pdpRoot.querySelector('.pdp-inner'),
            nextRoot.querySelector('.pdp-inner')
        );

        var activeVcard = pdpRoot.querySelector('[data-pdp-variant-cards] .pdp-vcard.is-current');
        if (activeVcard) {
            activeVcard.classList.add('is-just-selected');
        }

        var curDossier = pdpRoot.querySelector('[data-pdp-dossier]');
        if (curDossier && currentDossierTabKey && currentDossierTabKey !== 'overview') {
            var targetTab = curDossier.querySelector('[data-dossier-tab="' + currentDossierTabKey + '"]');
            if (targetTab) switchDossierTab(targetTab, true);
        }

        /* 5. Mobile Sticky Conversion Dock & Lightbox Label */
        var curDock = pdpRoot.querySelector('.pdp-mobile-dock');
        var nextDock = nextRoot.querySelector('.pdp-mobile-dock');
        if (curDock && nextDock) {
            curDock.innerHTML = nextDock.innerHTML;
            if (nextDock.getAttribute('aria-label')) {
                curDock.setAttribute('aria-label', nextDock.getAttribute('aria-label'));
            }
        }

        var curLb = getLightbox();
        var nextLb = nextRoot.querySelector('[data-pdp-lightbox]');
        if (curLb && nextLb && nextLb.getAttribute('aria-label')) {
            curLb.setAttribute('aria-label', nextLb.getAttribute('aria-label'));
        }
        syncLightboxNavVisibility();

        /* 6. Thumbnail Rail Cascade, Stage Sheen & Gliders */
        var curThumbs = pdpRoot.querySelector('.pdp-thumbs');
        if (curThumbs) {
            curThumbs.classList.remove('is-morphing-out', 'is-morphing-in');
            void curThumbs.offsetWidth;
            curThumbs.classList.add('is-morphing-in');
        }

        var stageCard = pdpRoot.querySelector('.pdp-stage-card');
        if (stageCard) {
            stageCard.classList.remove('is-landed', 'is-stepping-out-next', 'is-stepping-out-prev', 'is-stepping-in-next', 'is-stepping-in-prev');
            void stageCard.offsetWidth;
            if (stepDir === 'next') {
                stageCard.classList.add('is-stepping-in-next');
            } else if (stepDir === 'prev') {
                stageCard.classList.add('is-stepping-in-prev');
            }
            stageCard.classList.add('is-landed');
        }

        requestAnimationFrame(function () {
            syncAllGliders(true);
        });

        if (window.LUFLYSkeleton && typeof window.LUFLYSkeleton.scan === 'function') {
            window.LUFLYSkeleton.scan();
        }

        cleanupTimers.push(setTimeout(function () {
            if (curInfo) curInfo.classList.remove('is-morphing-in');
            if (curThumbs) curThumbs.classList.remove('is-morphing-in');
            if (stageCard) {
                stageCard.classList.remove('is-landed', 'is-stepping-in-next', 'is-stepping-in-prev');
            }
            Array.prototype.slice.call(pdpRoot.querySelectorAll('.is-morphing-in, .is-just-selected')).forEach(function (el) {
                el.classList.remove('is-morphing-in', 'is-just-selected');
            });
        }, 880));

        return true;
    }

    function transitionToVariant(targetUrl, sourceSpec, pushHistory) {
        if (!targetUrl) return;
        var lbOpen = getLightbox();
        if (lbOpen && !lbOpen.hasAttribute('hidden')) {
            closeLightbox();
        }
        cancelActiveFlight();

        var token = { cancelled: false, rafId: 0, pinRafId: 0, ghost: null };
        activeFlightToken = token;

        var stepDir = sourceSpec && sourceSpec.stepDir ? sourceSpec.stepDir : null;
        var fetchPromise = fetchVariantHtml(targetUrl);

        /* Start synchronized exit cascade on info panel, rail & stage */
        var curInfo = pdpRoot.querySelector('.pdp-info');
        var curThumbs = pdpRoot.querySelector('.pdp-thumbs');
        var curViewport = getViewport();
        var curStageCard = pdpRoot.querySelector('.pdp-stage-card');

        if (curInfo) curInfo.classList.add('is-morphing-out');
        if (curThumbs) curThumbs.classList.add('is-morphing-out');

        if (stepDir && curStageCard) {
            curStageCard.classList.add(stepDir === 'prev' ? 'is-stepping-out-prev' : 'is-stepping-out-next');
        } else if (curViewport) {
            curViewport.classList.add('is-receiving');
        }

        /* Instant tactile lift-off feedback on source card */
        if (sourceSpec && sourceSpec.vcardEl) {
            Array.prototype.slice.call(pdpRoot.querySelectorAll('.pdp-vcard.is-current')).forEach(function (c) {
                c.classList.remove('is-current');
            });
            sourceSpec.vcardEl.classList.add('is-current', 'is-launching');
        } else if (sourceSpec && sourceSpec.pcardEl) {
            sourceSpec.pcardEl.classList.add('is-pdp-launching');
        }

        /* Flight / Step Motion Promise */
        var flightPromise = new Promise(function (resolveFlight) {
            var sourceEl = sourceSpec && sourceSpec.rectEl ? sourceSpec.rectEl : null;
            var flyImgSrc = sourceSpec && sourceSpec.imgSrc ? sourceSpec.imgSrc : '';
            var flyImgAlt = sourceSpec && sourceSpec.imgAlt ? sourceSpec.imgAlt : '';

            /* Directional Category Stepper Glide or Reduced Motion */
            if (reducedMotion || stepDir || !curViewport || !sourceEl || !flyImgSrc) {
                var targetScroll = computeHeroTargetScrollY();
                if (Math.abs(window.pageYOffset - targetScroll) > 20) {
                    smoothScrollToY(targetScroll, 380);
                }
                setTimeout(resolveFlight, reducedMotion ? 40 : 230);
                return;
            }

            var startRect = sourceEl.getBoundingClientRect();
            if (startRect.width < 8 || startRect.height < 8) {
                setTimeout(resolveFlight, 180);
                return;
            }

            var ghost = document.createElement('div');
            ghost.className = 'pdp-fly-ghost';
            ghost.setAttribute('aria-hidden', 'true');

            var ghostImg = document.createElement('img');
            ghostImg.className = 'pdp-fly-ghost-img';
            ghostImg.src = flyImgSrc;
            ghostImg.alt = flyImgAlt;
            ghostImg.decoding = 'sync';
            ghost.appendChild(ghostImg);

            var startRadius = sourceSpec.isSwatch ? Math.min(startRect.width, startRect.height) / 2 : 14;
            var startPad = sourceSpec.isSwatch ? 4 : 15;

            ghost.style.width = startRect.width.toFixed(2) + 'px';
            ghost.style.height = startRect.height.toFixed(2) + 'px';
            ghost.style.borderRadius = startRadius + 'px';
            ghost.style.transform = 'translate3d(' + startRect.left.toFixed(2) + 'px,' + startRect.top.toFixed(2) + 'px,0)';
            ghostImg.style.padding = startPad + 'px';

            document.body.appendChild(ghost);
            token.ghost = ghost;

            var startScrollY = window.pageYOffset;
            var targetScrollY = computeHeroTargetScrollY();
            var shouldScroll = Math.abs(targetScrollY - startScrollY) > 20;

            var FLIGHT_MS = Math.abs(targetScrollY - startScrollY) > 650 ? 670 : 610;
            var t0 = performance.now();

            function tick(now) {
                if (token.cancelled) return;
                var rawT = Math.min(1, (now - t0) / FLIGHT_MS);
                var ease = 1 - Math.pow(1 - rawT, 4);

                if (shouldScroll) {
                    var scrollP = Math.min(1, rawT / 0.88);
                    var scrollEase = 1 - Math.pow(1 - scrollP, 4);
                    window.scrollTo(0, lerp(startScrollY, targetScrollY, scrollEase));
                }

                var liveVp = getViewport() || curViewport;
                var endRect = liveVp.getBoundingClientRect();

                var travelDist = Math.hypot(endRect.left - startRect.left, endRect.top - startRect.top);
                var arcPeak = Math.min(40, Math.max(14, travelDist * 0.075));
                var arcLift = Math.sin(Math.PI * ease) * arcPeak;

                var curX = lerp(startRect.left, endRect.left, ease);
                var curY = lerp(startRect.top, endRect.top, ease) - arcLift;
                var curW = lerp(startRect.width, endRect.width, ease);
                var curH = lerp(startRect.height, endRect.height, ease);
                var curRadius = lerp(startRadius, 21, ease);
                var curPad = lerp(startPad, 22, ease);

                ghost.style.transform = 'translate3d(' + curX.toFixed(2) + 'px,' + curY.toFixed(2) + 'px,0)';
                ghost.style.width = curW.toFixed(2) + 'px';
                ghost.style.height = curH.toFixed(2) + 'px';
                ghost.style.borderRadius = curRadius.toFixed(1) + 'px';
                ghostImg.style.padding = curPad.toFixed(1) + 'px';

                if (rawT < 1) {
                    token.rafId = requestAnimationFrame(tick);
                } else {
                    function pinLoop() {
                        if (token.cancelled || !token.ghost) return;
                        var vpNow = getViewport();
                        if (vpNow) {
                            var r = vpNow.getBoundingClientRect();
                            ghost.style.transform = 'translate3d(' + r.left.toFixed(2) + 'px,' + r.top.toFixed(2) + 'px,0)';
                            ghost.style.width = r.width.toFixed(2) + 'px';
                            ghost.style.height = r.height.toFixed(2) + 'px';
                        }
                        token.pinRafId = requestAnimationFrame(pinLoop);
                    }
                    token.pinRafId = requestAnimationFrame(pinLoop);
                    resolveFlight();
                }
            }

            token.rafId = requestAnimationFrame(tick);
        });

        Promise.all([fetchPromise, flightPromise]).then(function (results) {
            if (token.cancelled) return;
            var html = results[0];
            var parser = new DOMParser();
            var nextDoc = parser.parseFromString(html, 'text/html');

            if (!hydratePdpFromDocument(nextDoc, stepDir)) {
                window.location.href = targetUrl;
                return;
            }

            syncHeadAndNav(nextDoc);

            if (pushHistory && window.history && window.history.pushState) {
                window.history.pushState({ pdpUrl: targetUrl }, document.title, targetUrl);
            }

            var newSlides = getSlides();
            var heroImg = slideImage(newSlides[0]);
            var decodeReady = (heroImg && typeof heroImg.decode === 'function')
                ? heroImg.decode().catch(function () {})
                : Promise.resolve();

            decodeReady.then(function () {
                if (token.cancelled) return;
                if (token.pinRafId) {
                    cancelAnimationFrame(token.pinRafId);
                    token.pinRafId = 0;
                }
                if (token.ghost) {
                    var g = token.ghost;
                    var vpFinal = getViewport();
                    if (vpFinal) {
                        var rf = vpFinal.getBoundingClientRect();
                        g.style.transform = 'translate3d(' + rf.left.toFixed(2) + 'px,' + rf.top.toFixed(2) + 'px,0)';
                        g.style.width = rf.width.toFixed(2) + 'px';
                        g.style.height = rf.height.toFixed(2) + 'px';
                    }
                    g.classList.add('is-dissolving');
                    setTimeout(function () {
                        if (g.parentNode) g.parentNode.removeChild(g);
                        if (activeFlightToken === token) activeFlightToken = null;
                    }, 220);
                } else if (activeFlightToken === token) {
                    activeFlightToken = null;
                }
                warmVariantImages();
            });
        }).catch(function () {
            if (token.cancelled) return;
            cancelActiveFlight();
            window.location.href = targetUrl;
        });
    }

    /* Browser Back / Forward with full choreographed transition */
    window.addEventListener('popstate', function () {
        var destUrl = window.location.href;
        var normDest = normalizeUrl(destUrl);
        var spec = null;

        Array.prototype.slice.call(pdpRoot.querySelectorAll('[data-pdp-vcard-url]')).forEach(function (card) {
            if (normalizeUrl(card.getAttribute('data-pdp-vcard-url')) === normDest) {
                spec = {
                    vcardEl: card,
                    rectEl: card.querySelector('.pdp-vcard-media') || card,
                    imgSrc: card.getAttribute('data-pdp-vcard-img') || '',
                    imgAlt: card.getAttribute('data-pdp-vcard-alt') || '',
                    isSwatch: false
                };
            }
        });

        if (!spec) {
            Array.prototype.slice.call(pdpRoot.querySelectorAll('.pdp-market-section [data-product-card]')).forEach(function (pcard) {
                var lnk = pcard.querySelector('.pcard-media[href], .pcard-title a[href]');
                if (lnk && normalizeUrl(lnk.href) === normDest) {
                    var mediaBox = pcard.querySelector('.pcard-media') || pcard;
                    var onImg = pcard.querySelector('.pcard-img.is-on') || pcard.querySelector('.pcard-img');
                    spec = {
                        pcardEl: pcard,
                        rectEl: mediaBox,
                        imgSrc: onImg ? (onImg.currentSrc || onImg.src) : '',
                        imgAlt: onImg ? onImg.alt : '',
                        isSwatch: false
                    };
                }
            });
        }

        if (!spec) {
            spec = { stepDir: 'prev' };
        }

        transitionToVariant(destUrl, spec, false);
    });

    /* ========================================================================
       8. Unified Delegated Click Dispatcher Across Entire PDP
       ======================================================================== */
    pdpRoot.addEventListener('click', function (e) {
        var target = e.target && e.target.closest ? e.target : null;
        if (!target) return;

        /* 1. Top Category Stepper (< Prev / Next >) -> Directional Stage Glide */
        var stepBtn = target.closest('a[data-pdp-step]');
        if (stepBtn && stepBtn.href) {
            if (e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
            e.preventDefault();
            var sDir = stepBtn.getAttribute('data-pdp-step') === 'prev' ? 'prev' : 'next';
            transitionToVariant(stepBtn.href, { stepDir: sDir }, true);
            return;
        }

        /* 2. Desktop Variant Card Click -> Shared-Element Flight to Hero + Info Cascade */
        var vcard = target.closest('[data-pdp-vcard]');
        if (vcard && !target.closest('[data-product-card]')) {
            if (e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
            e.preventDefault();
            if (vcard.classList.contains('is-current')) {
                smoothGlideToHero();
                return;
            }
            var vUrl = vcard.getAttribute('data-pdp-vcard-url');
            var mediaEl = vcard.querySelector('.pdp-vcard-media') || vcard;
            var mediaImg = mediaEl.querySelector('img');
            var vImgSrc = (mediaImg && (mediaImg.currentSrc || mediaImg.src)) || vcard.getAttribute('data-pdp-vcard-img') || '';
            var vImgAlt = vcard.getAttribute('data-pdp-vcard-alt') || (mediaImg ? mediaImg.alt : '');
            transitionToVariant(vUrl, {
                vcardEl: vcard,
                rectEl: mediaEl,
                imgSrc: vImgSrc,
                imgAlt: vImgAlt,
                isSwatch: false
            }, true);
            return;
        }

        /* 3. Similar & Complementary .pcard Navigation -> Shared-Element Flight to Hero!
              (Leaves swatch finish preview, favorite toggle & box buttons untouched) */
        var marketLink = target.closest('.pdp-market-section .pcard-media[href], .pdp-market-section .pcard-title a[href], .pdp-market-section .pcard-details[href]');
        if (marketLink && marketLink.href && !target.closest('[data-pcard-variant], [data-fav-toggle], [data-box-add]')) {
            if (e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
            var pcard = marketLink.closest('[data-product-card]');
            if (pcard) {
                e.preventDefault();
                var pcardMedia = pcard.querySelector('.pcard-media') || pcard;
                /* Prefer primary studio photo for flight so mix-blend-mode looks seamless */
                var activePcardImg = pcard.querySelector('.pcard-img.is-on[data-pcard-kind="photo"]')
                    || pcard.querySelector('.pcard-img[data-pcard-kind="photo"]')
                    || pcard.querySelector('.pcard-img.is-on')
                    || pcard.querySelector('.pcard-img');
                var pImgSrc = activePcardImg ? (activePcardImg.currentSrc || activePcardImg.src) : '';
                var pImgAlt = activePcardImg ? activePcardImg.alt : '';
                transitionToVariant(marketLink.href, {
                    pcardEl: pcard,
                    rectEl: pcardMedia,
                    imgSrc: pImgSrc,
                    imgAlt: pImgAlt,
                    isSwatch: false
                }, true);
                return;
            }
        }

        /* 4. Mobile / Compact Swatch Click (outside catalog .pcard grids) */
        var mobileSwatch = target.closest('.pdp-colors-mobile a[data-pcard-variant]');
        if (mobileSwatch && !mobileSwatch.closest('[data-product-card]')) {
            if (e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
            e.preventDefault();
            if (mobileSwatch.classList.contains('is-active')) return;
            var swUrl = mobileSwatch.href;
            var swImg = findMobileSwatchImage(mobileSwatch);
            transitionToVariant(swUrl, {
                vcardEl: null,
                rectEl: mobileSwatch,
                imgSrc: swImg,
                imgAlt: mobileSwatch.getAttribute('aria-label') || '',
                isSwatch: true
            }, true);
            return;
        }

        /* 5. Smooth In-Page Hash Anchor Jumps (#pdp-variants-band, #pdp-dossier, etc.) */
        var hashAnchor = target.closest('a[href^="#"]');
        if (hashAnchor) {
            var hashId = hashAnchor.getAttribute('href');
            if (hashId && hashId.length > 1) {
                var destSection = document.getElementById(hashId.slice(1));
                if (destSection) {
                    e.preventDefault();
                    destSection.setAttribute('data-pdp-reveal', 'shown');
                    smoothScrollToElement(destSection, 86, destSection);
                    return;
                }
            }
        }

        /* 6. Thumbnail Rail */
        var thumbBtn = target.closest('[data-pdp-thumb]');
        if (thumbBtn) {
            var nextIdx = parseInt(thumbBtn.getAttribute('data-pdp-thumb'), 10) || 0;
            if (nextIdx !== active) {
                show(nextIdx, nextIdx > active ? 1 : -1);
            }
            return;
        }

        /* 7. Media View Group Switcher (Product / Drawing / In-Situ) */
        var groupTab = target.closest('[data-pdp-group]');
        if (groupTab) {
            switchToGroup(groupTab.getAttribute('data-pdp-group'), false);
            return;
        }

        /* 8. Quick Jump to CAD Drawing */
        var gotoGroupBtn = target.closest('[data-pdp-goto-group]');
        if (gotoGroupBtn) {
            switchToGroup(gotoGroupBtn.getAttribute('data-pdp-goto-group'), true);
            return;
        }

        /* 9. Stage Prev / Next Arrows */
        if (target.closest('[data-pdp-prev]')) {
            show(active - 1, -1);
            return;
        }
        if (target.closest('[data-pdp-next]')) {
            show(active + 1, 1);
            return;
        }

        /* 10. Lightbox Triggers, Prev/Next & Close */
        if (target.closest('[data-pdp-zoom]') || target.closest('[data-pdp-slide]')) {
            openLightbox();
            return;
        }
        var extZoom = target.closest('[data-pdp-external-zoom]');
        if (extZoom) {
            openLightboxSrc(
                extZoom.getAttribute('data-pdp-external-zoom'),
                extZoom.getAttribute('data-pdp-external-alt') || '',
                false
            );
            return;
        }
        if (target.closest('[data-pdp-lightbox-prev]')) {
            stepLightbox(-1);
            return;
        }
        if (target.closest('[data-pdp-lightbox-next]')) {
            stepLightbox(1);
            return;
        }
        if (target.closest('[data-pdp-lightbox-close]')) {
            closeLightbox();
            return;
        }
        var lb = getLightbox();
        if (lb && e.target === lb) {
            closeLightbox();
            return;
        }

        /* 11. Height-Morphed Tabbed Architectural Dossier (#pdp-dossier) */
        var dTab = target.closest('[data-dossier-tab]');
        if (dTab) {
            switchDossierTab(dTab, false);
            return;
        }

        /* 12. One-Click Model Code Copy */
        var copyBtn = target.closest('[data-pdp-copy]');
        if (copyBtn) {
            var val = copyBtn.getAttribute('data-pdp-copy') || '';
            if (!val) return;
            var badge = copyBtn.querySelector('[data-copy-badge]');
            copyText(val).then(function () {
                copyBtn.classList.add('is-copied');
                if (badge) badge.removeAttribute('hidden');
                if (copyBtn.__copyTimer) clearTimeout(copyBtn.__copyTimer);
                copyBtn.__copyTimer = setTimeout(function () {
                    copyBtn.classList.remove('is-copied');
                    if (badge) badge.setAttribute('hidden', '');
                }, 1800);
            });
            return;
        }

        /* 13. WC + Matched Seat Cover Bundle Add */
        var bundleBtn = target.closest('[data-box-bundle-add]');
        if (bundleBtn && !bundleBtn.disabled) {
            var ids = (bundleBtn.getAttribute('data-box-bundle-add') || '')
                .split(',')
                .map(function (v) { return parseInt(v, 10) || 0; })
                .filter(function (v) { return v > 0; });
            if (!ids.length) return;
            ids.forEach(function (id) {
                var boxBtn = pdpRoot.querySelector('[data-box-add][data-box-product="' + id + '"]')
                    || document.querySelector('[data-box-add][data-box-product="' + id + '"]');
                if (boxBtn && !boxBtn.classList.contains('is-on')) {
                    boxBtn.click();
                }
            });
            bundleBtn.classList.add('is-in-box');
            var addedLabel = bundleBtn.getAttribute('data-bundle-added-label');
            var span = bundleBtn.querySelector('span');
            if (addedLabel && span) span.textContent = addedLabel;
        }
    });

    /* Global Keyboard Navigation */
    document.addEventListener('keydown', function (e) {
        var lb = getLightbox();
        if (lb && !lb.hasAttribute('hidden') && !lb.classList.contains('is-closing')) {
            if (e.key === 'Escape') closeLightbox();
            if (e.key === 'ArrowLeft') stepLightbox(-1);
            if (e.key === 'ArrowRight') stepLightbox(1);
            return;
        }
        if (e.target && /^(INPUT|TEXTAREA|SELECT)$/.test(e.target.tagName)) return;
        if (e.key === 'ArrowLeft') show(active - 1, -1);
        if (e.key === 'ArrowRight') show(active + 1, 1);
    });

    /* Initial Boot: Glider Alignment, Gentle Hero Entrance & Scroll Reveals */
    requestAnimationFrame(function () {
        syncAllGliders(true);
        armSectionReveals();
        if (!reducedMotion) {
            pdpRoot.classList.add('is-initial-enter');
            var initInfo = pdpRoot.querySelector('.pdp-info');
            var initThumbs = pdpRoot.querySelector('.pdp-thumbs');
            if (initInfo) initInfo.classList.add('is-morphing-in');
            if (initThumbs) initThumbs.classList.add('is-morphing-in');
            setTimeout(function () {
                pdpRoot.classList.remove('is-initial-enter');
                if (initInfo) initInfo.classList.remove('is-morphing-in');
                if (initThumbs) initThumbs.classList.remove('is-morphing-in');
            }, 900);
        }
    });
})();
