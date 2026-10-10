/* Central vacuum story: one still image, a camera that zooms and pans as the
   user scrolls (scrubbed, not stepped), a small pointer tilt for depth, and
   copy that changes per chapter. */
(() => {
  const root = document.querySelector('[data-lcs]');
  if (!root) return;

  const stage = root.querySelector('.lcs-stage');
  const camera = root.querySelector('[data-lcs-camera]');
  const chapters = Array.from(root.querySelectorAll('[data-lcs-chapter]'));
  const dots = Array.from(root.querySelectorAll('[data-lcs-dot]'));
  const count = chapters.length;
  if (!stage || !camera || !count) return;

  /* Camera keyframes, one per chapter. fx/fy = focus point as a fraction of the
     image, s = zoom. 0 = whole villa; higher s = closer on that detail. */
  /* Typewriter reveal: each chapter's kicker, title and body are split into
     characters once. Every character has a staggered delay, and the CSS
     animation only runs while its chapter is active, so it replays each time
     the chapter comes back. Full text stays readable for screen readers. */
  const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  const typeInto = (el, start, stepMs) => {
    const text = el.textContent.replace(/\s+/g, ' ').trim();
    const sr = document.createElement('span');
    sr.className = 'lcs-sr';
    sr.textContent = text;
    el.textContent = '';
    el.appendChild(sr);

    let index = 0;
    let delay = start;
    text.split(' ').forEach((word, wi, words) => {
      const wordEl = document.createElement('span');
      wordEl.className = 'lcs-word';
      wordEl.setAttribute('aria-hidden', 'true');
      Array.from(word).forEach((ch) => {
        const c = document.createElement('span');
        c.className = 'lcs-ch';
        c.textContent = ch;
        delay = start + index * stepMs;
        c.style.animationDelay = `${delay}ms`;
        index += 1;
        wordEl.appendChild(c);
      });
      el.appendChild(wordEl);
      if (wi < words.length - 1) el.appendChild(document.createTextNode(' '));
    });
    return delay + stepMs * 2;
  };

  const setupTyping = (chapter) => {
    const kicker = chapter.querySelector('.lcs-kicker');
    const heading = chapter.querySelector('h1, h2');
    const body = chapter.querySelector('p');
    let t = 0;
    if (kicker) t = typeInto(kicker, t, 14);
    if (heading) t = typeInto(heading, t, 18);
    if (body) typeInto(body, t, 6);
  };

  if (!prefersReduced) {
    chapters.forEach(setupTyping);
  }

  const KEYFRAMES = [
    { fx: 0.50, fy: 0.50, s: 1.00 }, // 01 the big picture
    { fx: 0.68, fy: 0.40, s: 1.80 }, // 02 the network: riser and branches
    { fx: 0.48, fy: 0.52, s: 2.60 }, // 03 wall inlet in the living room
    { fx: 0.51, fy: 0.79, s: 2.60 }, // 04 hose storage cabinet in the service room
    { fx: 0.30, fy: 0.55, s: 2.20 }, // 05 cleaning the living room floor
    { fx: 0.79, fy: 0.77, s: 2.80 }, // 06 central power unit
    { fx: 0.50, fy: 0.50, s: 1.20 }, // 07 coverage across the home
    { fx: 0.50, fy: 0.50, s: 1.00 }, // 08 integrated living
  ];

  const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const finePointer = window.matchMedia('(pointer: fine)').matches;

  let progress = 0;
  let tiltX = 0;
  let tiltY = 0;
  let activeChapter = -1;
  let raf = 0;

  root.classList.add('is-js');

  const smooth = (t) => t * t * (3 - 2 * t);

  /* the camera for a given scroll progress (0..1) */
  const variants = Array.from(root.querySelectorAll('[data-lcs-variant]'))
    .map((el) => ({ el, idx: Number(el.getAttribute('data-lcs-variant')) }));

  const cameraAt = (p, width, height) => {
    const seg = p * (count - 1);
    const i = Math.min(count - 2, Math.floor(seg));
    const t = smooth(Math.min(1, Math.max(0, seg - i)));
    const a = KEYFRAMES[i];
    const b = KEYFRAMES[i + 1];
    const fx = a.fx + (b.fx - a.fx) * t;
    const fy = a.fy + (b.fy - a.fy) * t;
    /* on narrow screens the source photo cannot carry a deep zoom without going
       soft, so the camera zooms less there and still follows the same focus */
    const maxZoom = width < 760 ? 1.6 : 4;
    const s = Math.min(maxZoom, a.s + (b.s - a.s) * t);

    /* centre the focus point, then keep the image edges covered */
    let tx = width / 2 - s * fx * width;
    let ty = height / 2 - s * fy * height;
    tx = Math.min(0, Math.max(width - s * width, tx));
    ty = Math.min(0, Math.max(height - s * height, ty));
    return { tx, ty, s };
  };

  const render = () => {
    raf = 0;
    const width = stage.clientWidth;
    const height = stage.clientHeight;
    const { tx, ty, s } = cameraAt(progress, width, height);
    const rx = reduce ? 0 : tiltX;
    const ry = reduce ? 0 : tiltY;
    /* each variant: full strength on its own chapter, fading out either side */
    const seg = progress * (count - 1);
    variants.forEach(({ el, idx }) => {
      el.style.opacity = Math.max(0, 1 - Math.abs(seg - idx)).toFixed(3);
    });

    const tilt = rx || ry
      ? ` rotateX(${rx.toFixed(2)}deg) rotateY(${ry.toFixed(2)}deg)`
      : '';
    camera.style.transform =
      `translate(${tx.toFixed(1)}px, ${ty.toFixed(1)}px) scale(${s.toFixed(4)})${tilt}`;

    const idx = Math.min(count - 1, Math.max(0, Math.round(progress * (count - 1))));
    if (idx !== activeChapter) {
      activeChapter = idx;
      chapters.forEach((el, i) => {
        el.classList.toggle('is-active', i === idx);
        el.hidden = i !== idx;
      });
      dots.forEach((dot, i) => dot.classList.toggle('is-active', i === idx));
    }
  };

  const schedule = () => {
    if (!raf) raf = window.requestAnimationFrame(render);
  };

  const readProgress = () => {
    const rect = root.getBoundingClientRect();
    const span = root.offsetHeight - window.innerHeight;
    if (span <= 0) return 0;
    return Math.min(1, Math.max(0, -rect.top / span));
  };

  const onScroll = () => {
    progress = readProgress();
    schedule();
  };

  /* subtle depth: the camera leans a couple of degrees toward the pointer */
  if (finePointer && !reduce) {
    stage.addEventListener('pointermove', (event) => {
      const r = stage.getBoundingClientRect();
      const nx = (event.clientX - r.left) / r.width - 0.5;
      const ny = (event.clientY - r.top) / r.height - 0.5;
      tiltY = nx * 4;
      tiltX = -ny * 3;
      schedule();
    });
    stage.addEventListener('pointerleave', () => {
      tiltX = 0;
      tiltY = 0;
      schedule();
    });
  }

  dots.forEach((dot, i) => {
    dot.addEventListener('click', () => {
      const span = root.offsetHeight - window.innerHeight;
      const top = root.getBoundingClientRect().top + window.scrollY;
      const target = count > 1 ? i / (count - 1) : 0;
      window.scrollTo({ top: top + target * span + 2, behavior: reduce ? 'auto' : 'smooth' });
    });
  });

  window.addEventListener('scroll', onScroll, { passive: true });
  window.addEventListener('resize', onScroll);
  onScroll();
})();
