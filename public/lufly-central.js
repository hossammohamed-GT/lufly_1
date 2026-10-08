(() => {
  const root = document.querySelector('[data-lufly-central]');
  if (!root) return;

  const textSelector = '.lc-hero .lc-eyebrow,.lc-hero h1,.lc-hero p,.lc-intro h2,.lc-intro > p,.lc-feature__copy .lc-eyebrow,.lc-feature__copy h2,.lc-feature__copy p,.lc-feature__copy b,.lc-feature__copy .lc-points span,.lc-feature__copy li,.lc-benefits > h2,.lc-benefits > .lc-lead,.lc-benefits__grid h3,.lc-benefits__grid p,.lc-compare > h2,.lc-compare__grid h3,.lc-compare__grid li,.lc-cta > h2,.lc-cta > p';
  const blockSelector = '.lc-hero .lc-button,.lc-cta .lc-button';

  const splitTextNodes = (element, direction) => {
    if (element.dataset.splitDone) return;
    element.dataset.splitDone = '1';
    element.classList.add('lc-copy-target');
    element.setAttribute('aria-label', element.textContent.trim());
    const walker = document.createTreeWalker(element, NodeFilter.SHOW_TEXT);
    const nodes = [];
    while (walker.nextNode()) nodes.push(walker.currentNode);
    nodes.forEach((node) => {
      const fragment = document.createDocumentFragment();
      node.textContent.split(/(\s+)/).forEach((token) => {
        if (/^\s+$/.test(token)) {
          fragment.appendChild(document.createTextNode(token));
          return;
        }
        const word = document.createElement('span');
        word.className = 'lc-word';
        word.setAttribute('aria-hidden', 'true');
        [...token].forEach((letter, index) => {
          const char = document.createElement('span');
          char.className = 'lc-char';
          char.textContent = letter;
          char.style.setProperty('--char-index', index);
          word.appendChild(char);
        });
        fragment.appendChild(word);
      });
      node.parentNode.replaceChild(fragment, node);
    });
    element.style.setProperty('--lc-from-x', `${direction}px`);
  };

  const prepare = (section) => {
    const isHero = section.classList.contains('lc-hero');
    section.querySelectorAll(textSelector).forEach((element) => {
      let direction = -52;
      if (element.closest('.lc-feature--reverse')) direction = -52;
      else if (element.closest('.lc-feature')) direction = 52;
      else if (element.matches('.lc-intro > p,.lc-benefits__grid,.lc-compare__grid')) direction = 52;
      if (isHero) direction = -52;
      splitTextNodes(element, direction);
    });
    section.querySelectorAll(blockSelector).forEach((element) => element.classList.add('lc-block-target'));
  };

  const setLineTiming = (element) => {
    const chars = [...element.querySelectorAll('.lc-char')];
    const lines = [];
    chars.forEach((char) => {
      const top = Math.round(char.getBoundingClientRect().top);
      let line = lines.find((item) => Math.abs(item.top - top) < 3);
      if (!line) {
        line = { top, chars: [] };
        lines.push(line);
      }
      line.chars.push(char);
    });
    lines.sort((a, b) => a.top - b.top);
    lines.forEach((line, lineIndex) => {
      line.chars.forEach((char, charIndex) => {
        char.style.setProperty('--line-delay', `${lineIndex * 190 + Math.min(charIndex * 18, 150)}ms`);
      });
    });
  };

  const sections = [...root.querySelectorAll('.lc-reveal')];
  sections.forEach(prepare);
  requestAnimationFrame(() => root.querySelectorAll('.lc-copy-target').forEach(setLineTiming));

  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (!entry.isIntersecting || entry.target.dataset.textAnimated) return;
      entry.target.dataset.textAnimated = '1';
      entry.target.classList.add('lc-copy-visible');
      observer.unobserve(entry.target);
    });
  }, { threshold: 0.12, rootMargin: '0px 0px -10% 0px' });

  sections.forEach((section) => observer.observe(section));
})();
