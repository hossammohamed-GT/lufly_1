/* Central vacuum story: scroll picks the chapter; the image cross-fades to the
   chapter's own picture and the copy changes. Dots jump to a chapter. */
(() => {
  const root = document.querySelector('[data-lcs]');
  if (!root) return;

  const images = Array.from(root.querySelectorAll('[data-lcs-img]'));
  const chapters = Array.from(root.querySelectorAll('[data-lcs-chapter]'));
  const dots = Array.from(root.querySelectorAll('[data-lcs-dot]'));
  const count = Math.min(images.length, chapters.length);
  if (!count) return;

  let current = -1;

  root.classList.add('is-js');

  const setChapter = (index) => {
    if (index === current) return;
    current = index;

    images.forEach((img, i) => {
      img.classList.toggle('is-on', i === index);
      img.setAttribute('aria-hidden', i === index ? 'false' : 'true');
    });

    chapters.forEach((el, i) => {
      el.classList.toggle('is-active', i === index);
      el.hidden = i !== index;
    });

    dots.forEach((dot, i) => dot.classList.toggle('is-active', i === index));
  };

  const progress = () => {
    const rect = root.getBoundingClientRect();
    const span = root.offsetHeight - window.innerHeight;
    if (span <= 0) return 0;
    return Math.min(1, Math.max(0, -rect.top / span));
  };

  let ticking = false;
  const onScroll = () => {
    if (ticking) return;
    ticking = true;
    window.requestAnimationFrame(() => {
      ticking = false;
      setChapter(Math.min(count - 1, Math.floor(progress() * count)));
    });
  };

  const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  dots.forEach((dot, i) => {
    dot.addEventListener('click', () => {
      const span = root.offsetHeight - window.innerHeight;
      const top = root.getBoundingClientRect().top + window.scrollY;
      window.scrollTo({ top: top + (i / count) * span + 2, behavior: reduce ? 'auto' : 'smooth' });
    });
  });

  window.addEventListener('scroll', onScroll, { passive: true });
  window.addEventListener('resize', onScroll);
  setChapter(0);
  onScroll();
})();
