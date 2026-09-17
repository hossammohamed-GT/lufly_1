/**
 * LUFLY Hero Slider — "Living Monolith"
 * Vanilla JS, no dependencies. Handles autoplay, progress-fill dots,
 * arrow/dot navigation, and pauses all CSS animation loops when the
 * hero scrolls off-screen or the user prefers reduced motion.
 */
(function () {
  const viewport = document.getElementById("hero-slider-viewport");
  if (!viewport) return;

  const track = document.getElementById("hero-slider-track");
  const slides = Array.from(track.children);
  const dotsBox = document.getElementById("hero-slider-dots");
  const dots = dotsBox ? Array.from(dotsBox.children) : [];
  const prevBtn = document.getElementById("hero-prev-btn");
  const nextBtn = document.getElementById("hero-next-btn");

  const AUTOPLAY_MS = 6000;
  const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  viewport.style.setProperty("--hero-autoplay-ms", AUTOPLAY_MS + "ms");

  let index = 0;
  let timer = null;
  let isVisible = true;

  function render() {
    track.style.transform = `translate3d(-${index * 100}%, 0, 0)`;

    slides.forEach((slide, i) => slide.classList.toggle("is-active", i === index));

    dots.forEach((dot, i) => {
      dot.classList.toggle("is-active", i === index);
      // restart the fill animation on the active dot by forcing reflow
      const fill = dot.querySelector(".dot-progress");
      if (fill && i === index) {
        fill.style.animation = "none";
        // eslint-disable-next-line no-unused-expressions
        fill.offsetHeight;
        fill.style.animation = "";
      }
    });
  }

  function goTo(newIndex) {
    index = (newIndex + slides.length) % slides.length;
    render();
    restartAutoplay();
  }

  function next() {
    goTo(index + 1);
  }
  function prev() {
    goTo(index - 1);
  }

  function startAutoplay() {
    if (reduceMotion || !isVisible) return;
    stopAutoplay();
    timer = setInterval(next, AUTOPLAY_MS);
  }
  function stopAutoplay() {
    if (timer) clearInterval(timer);
    timer = null;
  }
  function restartAutoplay() {
    stopAutoplay();
    startAutoplay();
  }

  nextBtn && nextBtn.addEventListener("click", next);
  prevBtn && prevBtn.addEventListener("click", prev);
  dots.forEach((dot, i) => dot.addEventListener("click", () => goTo(i)));

  // Pause on hover/focus so users can actually read a slide
  viewport.addEventListener("mouseenter", stopAutoplay);
  viewport.addEventListener("mouseleave", startAutoplay);
  viewport.addEventListener("focusin", stopAutoplay);
  viewport.addEventListener("focusout", startAutoplay);

  // Pause everything (autoplay + CSS animation loops) when off-screen —
  // saves battery/CPU when the hero has scrolled out of view.
  if ("IntersectionObserver" in window) {
    const io = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          isVisible = entry.isIntersecting;
          viewport.classList.toggle("is-offscreen", !isVisible);
          if (isVisible) startAutoplay();
          else stopAutoplay();
        });
      },
      { threshold: 0.15 },
    );
    io.observe(viewport);
  }

  // Basic swipe support for touch devices
  let touchStartX = null;
  viewport.addEventListener(
    "touchstart",
    (e) => {
      touchStartX = e.touches[0].clientX;
    },
    { passive: true },
  );
  viewport.addEventListener(
    "touchend",
    (e) => {
      if (touchStartX === null) return;
      const dx = e.changedTouches[0].clientX - touchStartX;
      if (Math.abs(dx) > 40) (dx < 0 ? next : prev)();
      touchStartX = null;
    },
    { passive: true },
  );

  render();
  startAutoplay();
})();