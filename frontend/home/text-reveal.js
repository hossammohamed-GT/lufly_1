(() => {
  const scope = document.querySelector('.home-animation-scope');
  if (!scope || window.matchMedia?.('(prefers-reduced-motion: reduce)').matches) return;

  const targets = [...scope.querySelectorAll('h2,h3,h4,p,.section-tag,.section-kicker,.eyebrow,.card-label')]
    .filter((element) => !element.closest('.hero') && !element.closest('nav') && !element.closest('button'));

  targets.forEach((element, index) => {
    element.classList.add('home-copy-target');
    element.style.setProperty('--home-copy-delay', `${Math.min(index * 20, 180)}ms`);
    element.style.setProperty('--home-copy-x', index % 2 ? '28px' : '-28px');
  });

  if (!('IntersectionObserver' in window)) {
    targets.forEach((element) => element.classList.add('home-copy-visible'));
    return;
  }

  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (!entry.isIntersecting) return;
      entry.target.classList.add('home-copy-visible');
      observer.unobserve(entry.target);
    });
  }, { threshold: 0.12, rootMargin: '0px 0px -8% 0px' });

  targets.forEach((element) => observer.observe(element));
})();
