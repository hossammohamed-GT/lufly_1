/* ==========================================================================
   LUFLY Product Detail Controller
   - Delegated studio gallery rail, view-group switcher, CAD jump & lightbox
   - Shared-element variant flight (FLIP + live rAF scroll-tracked arc)
   - Synchronized staggered showroom info & specification cascade + pushState
   ========================================================================== */
(function () {
    'use strict';

    var pdpRoot = document.querySelector('.pdp');
    if (!pdpRoot) return;

    var active = 0;
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

    /* ---- Gallery Slide & View Group Controller ---- */
    function show(index) {
        var slides = getSlides();
        if (!slides.length) return;
        active = ((index % slides.length) + slides.length) % slides.length;
        var currentGroup = slides[active].getAttribute('data-slide-group');

        slides.forEach(function (el, i) {
            var on = i === active;
            el.classList.toggle('is-active', on);
            if (on) {
                el.removeAttribute('hidden');
            } else {
                el.setAttribute('hidden', '');
            }
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
        var stage = getStage();
        for (var i = 0; i < slides.length; i++) {
            if (slides[i].getAttribute('data-slide-group') === group) {
                show(i);
                if (scrollStageIntoView && stage && window.innerWidth < 980) {
                    stage.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
                break;
            }
        }
    }

    /* ---- Lightbox Controller ---- */
    function getLightbox() {
        return pdpRoot.querySelector('[data-pdp-lightbox]') || document.querySelector('[data-pdp-lightbox]');
    }

    function openLightboxSrc(src, alt) {
        var lightbox = getLightbox();
        var lightboxImg = lightbox ? lightbox.querySelector('[data-pdp-lightbox-img]') : null;
        var lightboxClose = lightbox ? lightbox.querySelector('[data-pdp-lightbox-close]') : null;
        if (!lightbox || !lightboxImg || !src) return;
        lightboxImg.src = src;
        lightboxImg.alt = alt || '';
        lightbox.removeAttribute('hidden');
        document.body.style.overflow = 'hidden';
        if (lightboxClose) lightboxClose.focus();
    }

    function openLightbox() {
        var slides = getSlides();
        var img = slideImage(slides[active]);
        if (!img) return;
        openLightboxSrc(img.currentSrc || img.src, img.alt);
    }

    function closeLightbox() {
        var lightbox = getLightbox();
        if (!lightbox) return;
        lightbox.setAttribute('hidden', '');
        document.body.style.overflow = '';
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
       Shared-Element Variant Flight + Synchronized Info Choreography
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
            if (htmlCache.size >= 20) {
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

    /* Seed current PDP HTML so returning to the initial finish is 0ms instant */
    try {
        htmlCache.set(normalizeUrl(window.location.href), document.documentElement.outerHTML);
        if (window.history && window.history.replaceState) {
            window.history.replaceState({ pdpUrl: window.location.href }, document.title, window.location.href);
        }
    } catch (_) {}

    /* Idle warmup of visible variant card images */
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

    /* Hover / touch / focus prefetch for zero-latency clicks */
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
        var swatch = t.closest('.pdp-colors-mobile a[data-pcard-variant]');
        if (swatch && swatch.href) {
            fetchVariantHtml(swatch.href).catch(function () {});
        }
    }
    pdpRoot.addEventListener('pointerenter', handlePrefetchIntent, true);
    pdpRoot.addEventListener('focusin', handlePrefetchIntent, true);
    pdpRoot.addEventListener('touchstart', handlePrefetchIntent, { passive: true, capture: true });

    function lerp(a, b, t) {
        return a + (b - a) * t;
    }

    function cancelActiveFlight() {
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
        Array.prototype.slice.call(pdpRoot.querySelectorAll('.is-launching')).forEach(function (el) {
            el.classList.remove('is-launching');
        });
        var vp = getViewport();
        if (vp) vp.classList.remove('is-receiving');
    }

    function computeHeroTargetScrollY() {
        var grid = pdpRoot.querySelector('.pdp-grid') || getStage();
        if (!grid) return window.pageYOffset;
        var rect = grid.getBoundingClientRect();
        var docTop = rect.top + window.pageYOffset;
        var navOffset = 84;
        var desired = Math.max(0, docTop - navOffset);
        var maxScroll = Math.max(0, document.documentElement.scrollHeight - window.innerHeight);
        return Math.min(desired, maxScroll);
    }

    function smoothGlideToHero() {
        var startY = window.pageYOffset;
        var targetY = computeHeroTargetScrollY();
        if (Math.abs(targetY - startY) < 16 || reducedMotion) {
            window.scrollTo(0, targetY);
            var cardNow = pdpRoot.querySelector('.pdp-stage-card');
            if (cardNow) {
                cardNow.classList.remove('is-landed');
                void cardNow.offsetWidth;
                cardNow.classList.add('is-landed');
                cleanupTimers.push(setTimeout(function () { cardNow.classList.remove('is-landed'); }, 720));
            }
            return;
        }
        var t0 = performance.now();
        var dur = 500;
        function step(now) {
            var p = Math.min(1, (now - t0) / dur);
            var e = 1 - Math.pow(1 - p, 4);
            window.scrollTo(0, lerp(startY, targetY, e));
            if (p < 1) {
                requestAnimationFrame(step);
            } else {
                var card = pdpRoot.querySelector('.pdp-stage-card');
                if (card) {
                    card.classList.remove('is-landed');
                    void card.offsetWidth;
                    card.classList.add('is-landed');
                    cleanupTimers.push(setTimeout(function () { card.classList.remove('is-landed'); }, 720));
                }
            }
        }
        requestAnimationFrame(step);
    }

    /* Resolve image URL for mobile swatch from embedded JSON if available */
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

    function syncHeadAndNav(nextDoc, targetUrl) {
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
        /* Sync navbar language-switcher links so switching EN/TR/CS stays on the selected finish */
        Array.prototype.slice.call(nextDoc.querySelectorAll('a.mnav-lang-option[lang]')).forEach(function (nextLangLink) {
            var code = nextLangLink.getAttribute('lang');
            var href = nextLangLink.getAttribute('href');
            if (!code || !href) return;
            Array.prototype.slice.call(document.querySelectorAll('a.mnav-lang-option[lang="' + code + '"]')).forEach(function (curLink) {
                curLink.setAttribute('href', href);
            });
        });
    }

    function hydratePdpFromDocument(nextDoc) {
        var nextRoot = nextDoc.querySelector('.pdp');
        if (!nextRoot) return false;

        /* Remember user's active dossier tab before swapping */
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

        /* 2. Left Gallery Stage (preserving featured golden bezel class dynamically) */
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

        /* 3. Right Editorial Info Panel */
        var curInfo = pdpRoot.querySelector('.pdp-info');
        var nextInfo = nextRoot.querySelector('.pdp-info');
        if (curInfo && nextInfo) {
            curInfo.classList.remove('is-morphing-out', 'is-morphing-in');
            curInfo.innerHTML = nextInfo.innerHTML;
            void curInfo.offsetWidth;
            curInfo.classList.add('is-morphing-in');
        }

        /* 4. Desktop Variant Cards Band */
        var curBand = pdpRoot.querySelector('[data-pdp-variant-cards]');
        var nextBand = nextRoot.querySelector('[data-pdp-variant-cards]');
        if (curBand && nextBand) {
            curBand.innerHTML = nextBand.innerHTML;
            var activeVcard = curBand.querySelector('.pdp-vcard.is-current');
            if (activeVcard) {
                activeVcard.classList.add('is-just-selected');
            }
        }

        /* 5. Tabbed Architectural Dossier */
        var curDossier = pdpRoot.querySelector('[data-pdp-dossier]');
        var nextDossier = nextRoot.querySelector('[data-pdp-dossier]');
        if (curDossier && nextDossier) {
            curDossier.classList.remove('is-morphing-in');
            curDossier.innerHTML = nextDossier.innerHTML;
            if (currentDossierTabKey && currentDossierTabKey !== 'overview') {
                var targetTab = curDossier.querySelector('[data-dossier-tab="' + currentDossierTabKey + '"]');
                if (targetTab) targetTab.click();
            }
            void curDossier.offsetWidth;
            curDossier.classList.add('is-morphing-in');
        }

        /* 6. Finish-Matched Marketplace Discovery Sections */
        ['[data-pdp-similar]', '[data-pdp-complementary]'].forEach(function (sel) {
            var curSec = pdpRoot.querySelector(sel);
            var nextSec = nextRoot.querySelector(sel);
            if (curSec && nextSec) {
                curSec.innerHTML = nextSec.innerHTML;
            }
        });

        /* 7. Mobile Sticky Conversion Dock & Lightbox Label */
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

        /* Cascade thumbnail rail & stage landing sheen */
        var curThumbs = pdpRoot.querySelector('.pdp-thumbs');
        if (curThumbs) {
            curThumbs.classList.remove('is-morphing-out', 'is-morphing-in');
            void curThumbs.offsetWidth;
            curThumbs.classList.add('is-morphing-in');
        }

        var stageCard = pdpRoot.querySelector('.pdp-stage-card');
        if (stageCard) {
            stageCard.classList.remove('is-landed');
            void stageCard.offsetWidth;
            stageCard.classList.add('is-landed');
        }

        if (window.LUFLYSkeleton && typeof window.LUFLYSkeleton.scan === 'function') {
            window.LUFLYSkeleton.scan();
        }

        cleanupTimers.push(setTimeout(function () {
            if (curInfo) curInfo.classList.remove('is-morphing-in');
            if (curThumbs) curThumbs.classList.remove('is-morphing-in');
            if (curDossier) curDossier.classList.remove('is-morphing-in');
            if (stageCard) stageCard.classList.remove('is-landed');
            var justSelected = pdpRoot.querySelector('.pdp-vcard.is-just-selected');
            if (justSelected) justSelected.classList.remove('is-just-selected');
        }, 860));

        return true;
    }

    function transitionToVariant(targetUrl, sourceSpec, pushHistory) {
        if (!targetUrl) return;
        cancelActiveFlight();

        var token = { cancelled: false, rafId: 0, pinRafId: 0, ghost: null };
        activeFlightToken = token;

        var fetchPromise = fetchVariantHtml(targetUrl);

        /* Start synchronized exit cascade on info panel & rail */
        var curInfo = pdpRoot.querySelector('.pdp-info');
        var curThumbs = pdpRoot.querySelector('.pdp-thumbs');
        var curViewport = getViewport();
        if (curInfo) curInfo.classList.add('is-morphing-out');
        if (curThumbs) curThumbs.classList.add('is-morphing-out');
        if (curViewport) curViewport.classList.add('is-receiving');

        /* Instant tactile feedback on the clicked variant card */
        if (sourceSpec && sourceSpec.vcardEl) {
            Array.prototype.slice.call(pdpRoot.querySelectorAll('.pdp-vcard.is-current')).forEach(function (c) {
                c.classList.remove('is-current');
            });
            sourceSpec.vcardEl.classList.add('is-current', 'is-launching');
        }

        /* Flight Promise: live rAF scroll-tracked arc from card media into .pdp-viewport */
        var flightPromise = new Promise(function (resolveFlight) {
            var sourceEl = sourceSpec && sourceSpec.rectEl ? sourceSpec.rectEl : null;
            var flyImgSrc = sourceSpec && sourceSpec.imgSrc ? sourceSpec.imgSrc : '';
            var flyImgAlt = sourceSpec && sourceSpec.imgAlt ? sourceSpec.imgAlt : '';

            if (reducedMotion || !curViewport || !sourceEl || !flyImgSrc) {
                var targetScroll = computeHeroTargetScrollY();
                if (Math.abs(window.pageYOffset - targetScroll) > 24) {
                    window.scrollTo(0, targetScroll);
                }
                setTimeout(resolveFlight, reducedMotion ? 40 : 180);
                return;
            }

            var startRect = sourceEl.getBoundingClientRect();
            if (startRect.width < 8 || startRect.height < 8) {
                setTimeout(resolveFlight, 160);
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

            var startRadius = sourceSpec.isSwatch ? Math.min(startRect.width, startRect.height) / 2 : 12;
            var startPad = sourceSpec.isSwatch ? 4 : 14;

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

            var FLIGHT_MS = 610;
            var t0 = performance.now();

            function tick(now) {
                if (token.cancelled) return;
                var rawT = Math.min(1, (now - t0) / FLIGHT_MS);
                /* Smooth quartic ease-out for responsive lift-off and cushioned landing */
                var ease = 1 - Math.pow(1 - rawT, 4);

                if (shouldScroll) {
                    var scrollP = Math.min(1, rawT / 0.88);
                    var scrollEase = 1 - Math.pow(1 - scrollP, 4);
                    window.scrollTo(0, lerp(startScrollY, targetScrollY, scrollEase));
                }

                var liveVp = getViewport() || curViewport;
                var endRect = liveVp.getBoundingClientRect();

                var travelDist = Math.hypot(endRect.left - startRect.left, endRect.top - startRect.top);
                var arcPeak = Math.min(36, Math.max(12, travelDist * 0.075));
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
                    /* Keep ghost pinned to viewport while awaiting slow network response if any */
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

            if (!hydratePdpFromDocument(nextDoc)) {
                window.location.href = targetUrl;
                return;
            }

            syncHeadAndNav(nextDoc, targetUrl);

            if (pushHistory && window.history && window.history.pushState) {
                window.history.pushState({ pdpUrl: targetUrl }, document.title, targetUrl);
            }

            /* Decode the newly swapped hero image BEFORE dissolving the ghost so there is zero flash */
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
                    /* Snap ghost one final time to exact hydrated viewport rect before dissolving */
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

    /* Browser Back / Forward support with smooth choreography */
    window.addEventListener('popstate', function () {
        var destUrl = window.location.href;
        var normDest = normalizeUrl(destUrl);
        var matchingCard = null;
        Array.prototype.slice.call(pdpRoot.querySelectorAll('[data-pdp-vcard-url]')).forEach(function (card) {
            if (normalizeUrl(card.getAttribute('data-pdp-vcard-url')) === normDest) {
                matchingCard = card;
            }
        });
        var spec = null;
        if (matchingCard) {
            spec = {
                vcardEl: matchingCard,
                rectEl: matchingCard.querySelector('.pdp-vcard-media') || matchingCard,
                imgSrc: matchingCard.getAttribute('data-pdp-vcard-img') || '',
                imgAlt: matchingCard.getAttribute('data-pdp-vcard-alt') || '',
                isSwatch: false
            };
        }
        transitionToVariant(destUrl, spec, false);
    });

    /* ========================================================================
       Unified Delegated Click Dispatcher on .pdp
       ======================================================================== */
    pdpRoot.addEventListener('click', function (e) {
        var target = e.target && e.target.closest ? e.target : null;
        if (!target) return;

        /* 1. Desktop Variant Card Click -> Smooth Flight to Hero + Info Cascade */
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

        /* 2. Mobile / Compact Swatch Click (outside catalog .pcard grids) */
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

        /* 3. Thumbnail Rail */
        var thumbBtn = target.closest('[data-pdp-thumb]');
        if (thumbBtn) {
            show(parseInt(thumbBtn.getAttribute('data-pdp-thumb'), 10) || 0);
            return;
        }

        /* 4. Media View Group Switcher (Product / Drawing / In-Situ) */
        var groupTab = target.closest('[data-pdp-group]');
        if (groupTab) {
            switchToGroup(groupTab.getAttribute('data-pdp-group'), false);
            return;
        }

        /* 5. Quick Jump to CAD Drawing */
        var gotoGroupBtn = target.closest('[data-pdp-goto-group]');
        if (gotoGroupBtn) {
            switchToGroup(gotoGroupBtn.getAttribute('data-pdp-goto-group'), true);
            return;
        }

        /* 6. Stage Prev / Next Arrows */
        if (target.closest('[data-pdp-prev]')) {
            show(active - 1);
            return;
        }
        if (target.closest('[data-pdp-next]')) {
            show(active + 1);
            return;
        }

        /* 7. Lightbox Triggers & Close */
        if (target.closest('[data-pdp-zoom]') || target.closest('[data-pdp-slide]')) {
            openLightbox();
            return;
        }
        var extZoom = target.closest('[data-pdp-external-zoom]');
        if (extZoom) {
            openLightboxSrc(
                extZoom.getAttribute('data-pdp-external-zoom'),
                extZoom.getAttribute('data-pdp-external-alt') || ''
            );
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

        /* 8. Tabbed Architectural Dossier (#pdp-dossier) */
        var dTab = target.closest('[data-dossier-tab]');
        if (dTab) {
            var dossier = dTab.closest('[data-pdp-dossier]');
            if (dossier) {
                var targetKey = dTab.getAttribute('data-dossier-tab');
                Array.prototype.slice.call(dossier.querySelectorAll('[data-dossier-tab]')).forEach(function (t) {
                    var on = t.getAttribute('data-dossier-tab') === targetKey;
                    t.classList.toggle('is-active', on);
                    t.setAttribute('aria-selected', on ? 'true' : 'false');
                });
                Array.prototype.slice.call(dossier.querySelectorAll('[data-dossier-panel]')).forEach(function (p) {
                    var on = p.getAttribute('data-dossier-panel') === targetKey;
                    p.classList.toggle('is-active', on);
                    if (on) p.removeAttribute('hidden');
                    else p.setAttribute('hidden', '');
                });
            }
            return;
        }

        /* 9. One-Click Model Code Copy */
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

        /* 10. WC + Matched Seat Cover Bundle Add */
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
        if (lb && !lb.hasAttribute('hidden')) {
            if (e.key === 'Escape') closeLightbox();
            if (e.key === 'ArrowLeft') { show(active - 1); openLightbox(); }
            if (e.key === 'ArrowRight') { show(active + 1); openLightbox(); }
            return;
        }
        if (e.target && /^(INPUT|TEXTAREA|SELECT)$/.test(e.target.tagName)) return;
        if (e.key === 'ArrowLeft') show(active - 1);
        if (e.key === 'ArrowRight') show(active + 1);
    });
})();
