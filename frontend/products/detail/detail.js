/* Product detail interactive controller — vertical/horizontal studio rail,
   CAD quick-jump, dossier tab switcher, SKU copy, external zoom & WC bundle add. */
(function () {
    'use strict';

    var stage = document.querySelector('[data-pdp-stage]');
    if (!stage) return;

    var viewport = stage.querySelector('[data-pdp-viewport]');
    var slides = Array.prototype.slice.call(stage.querySelectorAll('[data-pdp-slide]'));
    var thumbs = Array.prototype.slice.call(stage.querySelectorAll('[data-pdp-thumb]'));
    var tabs = Array.prototype.slice.call(stage.querySelectorAll('[data-pdp-group]'));
    var counter = stage.querySelector('[data-pdp-counter]');
    var prevBtn = stage.querySelector('[data-pdp-prev]');
    var nextBtn = stage.querySelector('[data-pdp-next]');
    var zoomBtns = Array.prototype.slice.call(stage.querySelectorAll('[data-pdp-zoom]'));
    var lightbox = document.querySelector('[data-pdp-lightbox]');
    var lightboxImg = lightbox ? lightbox.querySelector('[data-pdp-lightbox-img]') : null;
    var lightboxClose = lightbox ? lightbox.querySelector('[data-pdp-lightbox-close]') : null;

    var active = 0;

    function show(index) {
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

        thumbs.forEach(function (el, i) {
            var on = i === active;
            el.classList.toggle('is-active', on);
            el.setAttribute('aria-current', on ? 'true' : 'false');
        });

        tabs.forEach(function (tab) {
            var on = tab.getAttribute('data-pdp-group') === currentGroup;
            tab.classList.toggle('is-active', on);
            tab.setAttribute('aria-selected', on ? 'true' : 'false');
        });

        if (viewport && currentGroup) {
            viewport.setAttribute('data-active-group', currentGroup);
        }

        if (counter) {
            counter.textContent = (active + 1) + ' / ' + slides.length;
        }
    }

    function switchToGroup(group, scrollStageIntoView) {
        for (var i = 0; i < slides.length; i++) {
            if (slides[i].getAttribute('data-slide-group') === group) {
                show(i);
                if (scrollStageIntoView && window.innerWidth < 980) {
                    stage.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
                break;
            }
        }
    }

    thumbs.forEach(function (btn) {
        btn.addEventListener('click', function () {
            show(parseInt(btn.getAttribute('data-pdp-thumb'), 10) || 0);
        });
    });

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            switchToGroup(tab.getAttribute('data-pdp-group'), false);
        });
    });

    Array.prototype.slice.call(document.querySelectorAll('[data-pdp-goto-group]')).forEach(function (btn) {
        btn.addEventListener('click', function () {
            switchToGroup(btn.getAttribute('data-pdp-goto-group'), true);
        });
    });

    if (prevBtn) prevBtn.addEventListener('click', function () { show(active - 1); });
    if (nextBtn) nextBtn.addEventListener('click', function () { show(active + 1); });

    /* Tabbed Architectural Dossier (#pdp-dossier) */
    var dossier = document.querySelector('[data-pdp-dossier]');
    if (dossier) {
        var dTabs = Array.prototype.slice.call(dossier.querySelectorAll('[data-dossier-tab]'));
        var dPanels = Array.prototype.slice.call(dossier.querySelectorAll('[data-dossier-panel]'));

        dTabs.forEach(function (dTab) {
            dTab.addEventListener('click', function () {
                var targetKey = dTab.getAttribute('data-dossier-tab');
                dTabs.forEach(function (t) {
                    var on = t.getAttribute('data-dossier-tab') === targetKey;
                    t.classList.toggle('is-active', on);
                    t.setAttribute('aria-selected', on ? 'true' : 'false');
                });
                dPanels.forEach(function (p) {
                    var on = p.getAttribute('data-dossier-panel') === targetKey;
                    p.classList.toggle('is-active', on);
                    if (on) {
                        p.removeAttribute('hidden');
                    } else {
                        p.setAttribute('hidden', '');
                    }
                });
            });
        });
    }

    function openLightboxSrc(src, alt) {
        if (!lightbox || !lightboxImg || !src) return;
        lightboxImg.src = src;
        lightboxImg.alt = alt || '';
        lightbox.removeAttribute('hidden');
        document.body.style.overflow = 'hidden';
        if (lightboxClose) lightboxClose.focus();
    }

    function openLightbox() {
        if (!slides[active]) return;
        var img = slides[active].tagName === 'IMG' ? slides[active] : slides[active].querySelector('img');
        if (!img) return;
        openLightboxSrc(img.currentSrc || img.src, img.alt);
    }

    function closeLightbox() {
        if (!lightbox) return;
        lightbox.setAttribute('hidden', '');
        document.body.style.overflow = '';
    }

    zoomBtns.forEach(function (btn) {
        btn.addEventListener('click', openLightbox);
    });
    slides.forEach(function (slide) {
        var img = slide.tagName === 'IMG' ? slide : slide.querySelector('img');
        if (img) img.addEventListener('click', openLightbox);
    });

    Array.prototype.slice.call(document.querySelectorAll('[data-pdp-external-zoom]')).forEach(function (el) {
        el.addEventListener('click', function () {
            openLightboxSrc(
                el.getAttribute('data-pdp-external-zoom'),
                el.getAttribute('data-pdp-external-alt') || ''
            );
        });
    });

    if (lightboxClose) lightboxClose.addEventListener('click', closeLightbox);
    if (lightbox) {
        lightbox.addEventListener('click', function (e) {
            if (e.target === lightbox) closeLightbox();
        });
    }

    document.addEventListener('keydown', function (e) {
        if (lightbox && !lightbox.hasAttribute('hidden')) {
            if (e.key === 'Escape') closeLightbox();
            if (e.key === 'ArrowLeft') { show(active - 1); openLightbox(); }
            if (e.key === 'ArrowRight') { show(active + 1); openLightbox(); }
            return;
        }
        if (e.target && /^(INPUT|TEXTAREA|SELECT)$/.test(e.target.tagName)) return;
        if (e.key === 'ArrowLeft') show(active - 1);
        if (e.key === 'ArrowRight') show(active + 1);
    });

    /* One-click copy for Model Code chip */
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

    Array.prototype.slice.call(document.querySelectorAll('[data-pdp-copy]')).forEach(function (btn) {
        var resetTimer = null;
        var textSpan = btn.querySelector('[data-copy-text]');
        var badge = btn.querySelector('[data-copy-badge]');
        var originalText = textSpan ? textSpan.textContent : '';

        btn.addEventListener('click', function () {
            var val = btn.getAttribute('data-pdp-copy') || '';
            if (!val) return;
            copyText(val).then(function () {
                btn.classList.add('is-copied');
                if (badge) badge.removeAttribute('hidden');
                if (textSpan) {
                    textSpan.textContent = btn.getAttribute('data-copied-label') || originalText;
                }
                if (resetTimer) clearTimeout(resetTimer);
                resetTimer = setTimeout(function () {
                    btn.classList.remove('is-copied');
                    if (badge) badge.setAttribute('hidden', '');
                    if (textSpan) textSpan.textContent = originalText;
                }, 1800);
            });
        });
    });

    /* WC + Compatible Seat Cover one-click bundle add */
    Array.prototype.slice.call(document.querySelectorAll('[data-box-bundle-add]')).forEach(function (btn) {
        btn.addEventListener('click', function () {
            var ids = (btn.getAttribute('data-box-bundle-add') || '')
                .split(',')
                .map(function (v) { return parseInt(v, 10) || 0; })
                .filter(function (v) { return v > 0; });
            if (!ids.length || btn.disabled) return;

            ids.forEach(function (id) {
                var target = document.querySelector('[data-box-add][data-box-product="' + id + '"]');
                if (target && !target.classList.contains('is-on')) {
                    target.click();
                }
            });

            btn.classList.add('is-in-box');
            var label = btn.getAttribute('data-bundle-added-label');
            var span = btn.querySelector('span');
            if (label && span) span.textContent = label;
        });
    });
})();
