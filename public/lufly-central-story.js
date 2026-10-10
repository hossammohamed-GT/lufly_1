/* Central vacuum story: scroll picks the chapter; the house stays, the copy
   and the overlays change, and the camera pushes toward each chapter's focus. */
(() => {
  const root = document.querySelector('[data-lcs]');
  if (!root) return;

  const camera = root.querySelector('[data-lcs-camera]');
  const chapters = Array.from(root.querySelectorAll('[data-lcs-chapter]'));
  const dots = Array.from(root.querySelectorAll('[data-lcs-dot]'));
  const layers = Array.from(root.querySelectorAll('[data-ch]'));
  const count = chapters.length;
  if (!camera || !count) return;

  const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  let current = -1;

  root.classList.add('is-js');

  const setChapter = (index) => {
    if (index === current) return;
    current = index;

    chapters.forEach((el, i) => {
      el.classList.toggle('is-active', i === index);
      el.hidden = i !== index;
    });

    dots.forEach((dot, i) => dot.classList.toggle('is-active', i === index));

    layers.forEach((el) => {
      const list = el.getAttribute('data-ch').split(',').map(Number);
      el.classList.toggle('is-on', list.includes(index));
    });

    const chapter = chapters[index];
    camera.style.transformOrigin = chapter.getAttribute('data-focus') || '50% 50%';
    camera.style.transform = reduce ? 'none' : `scale(${chapter.getAttribute('data-scale') || '1'})`;
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
