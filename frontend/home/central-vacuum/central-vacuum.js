/* LUFLY Central Vacuum teaser interactions */
(function () {
  'use strict';

  var sections = document.querySelectorAll('[data-cv-home]');
  if (!sections.length) return;

  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  sections.forEach(function (section) {
    if (!reduce && 'IntersectionObserver' in window) {
      var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            section.classList.add('is-visible');
            observer.unobserve(section);
          }
        });
      }, { threshold: 0.28 });
      observer.observe(section);
    } else {
      section.classList.add('is-visible');
    }

    var card = section.querySelector('.cv-home-card');
    var visual = section.querySelector('.cv-home-visual');
    if (!card || !visual || reduce) return;

    card.addEventListener('pointermove', function (event) {
      var rect = card.getBoundingClientRect();
      var x = ((event.clientX - rect.left) / rect.width - 0.5) * 2;
      var y = ((event.clientY - rect.top) / rect.height - 0.5) * 2;
      visual.style.setProperty('--cv-home-tilt-x', (x * 4).toFixed(2));
      visual.style.setProperty('--cv-home-tilt-y', (y * 3).toFixed(2));
    }, { passive: true });

    card.addEventListener('pointerleave', function () {
      visual.style.removeProperty('--cv-home-tilt-x');
      visual.style.removeProperty('--cv-home-tilt-y');
    });
  });
})();
