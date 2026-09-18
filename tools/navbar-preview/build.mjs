/**
 * Navbar design-review harness (dev only, not part of the application).
 *
 * The project runs on PHP + XAMPP, but this sandbox has no PHP runtime, so the
 * real navbar partial (resources/views/components/navbar.php) is rendered into
 * a static page by a very small, purpose-built evaluator.
 *
 * Font Awesome is deliberately not linked here (no network in the harness), so
 * the page shows exactly what the hero arrows look like before/without the icon
 * font: the CSS chevron fallback. It understands only
 * the constructs that file uses: foreach/if blocks, <?= ?> echoes and the
 * handful of helpers below. Whenever navbar.php changes, this preview follows —
 * it is a mirror for design review, never a second source of truth.
 */
import { readFileSync } from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

export const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');

const read = (p) => readFileSync(path.join(ROOT, p), 'utf8');

/* Values the real template echoes raw (SVG components, attribute fragments) are
   wrapped in this sentinel so escaping leaves them untouched. */
const RAW = '\u0001';
const raw = (value) => RAW + value + RAW;

const escape = (value) => String(value).startsWith(RAW)
  ? String(value).slice(1, -1)
  : String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');

/* ---------- data mirrored from navbar.php ---------- */

const labels = {};
for (const [, key, value] of read('resources/lang/en/nav.php')
  .matchAll(/'([\w.-]+)'\s*=>\s*'([^']*)'/g)) {
  labels[key] = value;
}

const indexLinks = [
  { key: 'bathroom', url: '#' },
  { key: 'kitchen', url: '#' },
  { key: 'latest', url: '#' },
  { key: 'collections', url: '#' },
  { key: 'finishes', url: '#finishes' },
  { key: 'rituals', url: '#rituals' },
  { key: 'inspirations', url: '#inspiration' },
  { key: 'news', url: '#corporate' },
  { key: 'contact', url: '#' }
];

const railLinks = indexLinks.slice(0, 5);
const supported = { en: 'English', tr: 'Türkçe', cs: 'Čeština' };
const languageLinks = Object.fromEntries(
  Object.entries(supported).map(([code, label]) => [code, { label, target: '#' }])
);

const currentLocale = 'en';

/* ---------- helpers parsed straight out of the real templates ---------- */

const flagTemplate = read('resources/views/components/flag.php');

const flagStyle = 'border-radius: 2px; display: inline-block; vertical-align: middle;';

function flagSvg(code) {
  const branches = [...flagTemplate.matchAll(
    /<\?php (?:if \(([^)]+)\)|elseif \(([^)]+)\)|else): \?>([\s\S]*?)(?=<\?php (?:elseif|else|endif))/g
  )];

  for (const [, ifCond, elseifCond, body] of branches) {
    const cond = ifCond || elseifCond;

    if (!cond) {
      if (code === 'en') {
        return body.replace(/<\?= e\(\$flagStyle\) \?>/g, flagStyle).trim();
      }
    } else if (cond.includes(`'${code}'`)) {
      return body.replace(/<\?= e\(\$flagStyle\) \?>/g, flagStyle).trim();
    }
  }

  return '';
}

/* ---------- tiny evaluator for the expressions navbar.php uses ---------- */

function evaluate(expression, ctx) {
  const expr = expression.trim()
    .replace(/^e\((.*)\)$/s, '$1')
    .trim();

  if (expr === "'#'" || expr === '""') {
    return '';
  }

  if (expr === 'route(\'home\')' || expr === 'route(\'login\')' || expr === 'route(\'admin.dashboard\')' ||
      expr === '$catalogUrl' || expr === 'request()->url()' || expr === '$link[\'target\']') {
    return '#';
  }

  if (expr === 'asset(\'frontend/design-system/logo.png\')') {
    return 'frontend/design-system/logo.png';
  }

  if (expr === '$currentLocale') {
    return currentLocale;
  }

  if (expr === 'strtoupper($currentLocale)') {
    return currentLocale.toUpperCase();
  }

  if (expr === 'strtoupper($code)') {
    return String(ctx.code).toUpperCase();
  }

  if (expr === 'trans(\'nav.search\', [], $currentLocale)') return labels.search;
  if (expr === 'trans(\'nav.close\', [], $currentLocale)') return labels.close;
  if (expr === 'trans(\'nav.menu\', [], $currentLocale)') return labels.menu;
  if (expr === 'trans(\'nav.index\', [], $currentLocale)') return labels.index;
  if (expr === 'trans(\'nav.language\', [], $currentLocale)') return labels.language;
  if (expr === 'trans(\'nav.quick_links\', [], $currentLocale)') return labels.quick_links;
  if (expr === 'trans(\'nav.open_index\', [], $currentLocale)') return labels.open_index;
  if (expr === 'trans(\'nav.account\', [], $currentLocale)') return labels.account;
  if (expr === 'trans(\'nav.theme_toggle\', [], $currentLocale)') return labels.theme_toggle;
  if (expr === 'trans(\'nav.download_catalog\', [], $currentLocale)') return labels.download_catalog;
  if (expr === 'trans(\'nav.search_placeholder\', [], $currentLocale)') return labels.search_placeholder;

  if (expr.startsWith('trans(\'nav.\' . $link[\'key\']')) {
    return labels[ctx.link.key];
  }

  if (expr === 'auth()->check() ? e(route(\'admin.dashboard\')) : e(route(\'login\'))') {
    return '#';
  }

  if (expr === '$link[\'url\']' || expr === '$link[\'key\']') {
    return expr.includes('url') ? ctx.link.url : ctx.link.key;
  }

  if (expr === '$link[\'label\']') {
    return ctx.link.label;
  }

  if (expr === '$code === $currentLocale ? \' is-current\' : \'\'') {
    return ctx.code === currentLocale ? ' is-current' : '';
  }

  if (expr === '$code') {
    return ctx.code;
  }

  if (expr === 'str_starts_with($link[\'url\'], \'http\') ? \'target="_blank" rel="noopener"\' : \'\'') {
    return '';
  }

  if (expr.includes('aria-current')) {
    return ctx.link.key === 'kitchen' ? raw('aria-current="page"') : '';
  }

  if (expr.startsWith('$isActive($link[\'url\']) ?')) {
    return ctx.link.key === 'kitchen' ? ' is-active' : '';
  }

  if (expr.startsWith('str_pad(')) {
    return String(Number(ctx.position) + 1).padStart(2, '0');
  }

  if (expr === '$view->component(\'flag\', [\'code\' => $currentLocale])') {
    return raw(flagSvg(currentLocale));
  }

    if (expr.startsWith('$getSectionIcon(')) {
    const match = expr.match(/\$getSectionIcon\(['"]([^'"]+)['"]\)/);
    const key = match ? match[1] : (ctx.link ? ctx.link.key : 'collections');
    const icons = {
      bathroom: '<svg class="icon mnav-cat-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6h6a2 2 0 0 1 2 2v2H7V8a2 2 0 0 1 2-2z"></path><path d="M5 10h14a2 2 0 0 1 2 2v2a6 6 0 0 1-6 6H9a6 6 0 0 1-6-6v-2a2 2 0 0 1 2-2z"></path><line x1="7" y1="20" x2="7" y2="22"></line><line x1="17" y1="20" x2="17" y2="22"></line></svg>',
      kitchen: '<svg class="icon mnav-cat-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 14h18"></path><path d="M5 14V6a3 3 0 0 1 6 0v2"></path><path d="M19 14v4a3 3 0 0 1-3 3H8a3 3 0 0 1-3-3v-4"></path><circle cx="8" cy="8" r="1"></circle></svg>',
      latest: '<svg class="icon mnav-cat-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>',
      collections: '<svg class="icon mnav-cat-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>',
      finishes: '<svg class="icon mnav-cat-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 3a9 9 0 0 1 9 9c0 2.5-2 4.5-4.5 4.5s-2.5-2-2.5-2H10"></path></svg>',
      rituals: '<svg class="icon mnav-cat-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"></path></svg>',
      inspirations: '<svg class="icon mnav-cat-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"></polygon></svg>',
      news: '<svg class="icon mnav-cat-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"></path><path d="M18 14h-8"></path><path d="M15 18h-5"></path><path d="M10 6h8v4h-8V6Z"></path></svg>',
      contact: '<svg class="icon mnav-cat-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>'
    };
    return raw(icons[key] || '<svg class="icon mnav-cat-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"></circle></svg>');
  }

  if (expr === '$view->component(\'flag\', [\'code\' => $code])') {
    return raw(flagSvg(ctx.code));
  }

  return `<!-- unhandled: ${escape(expr)} -->`;
}

function interpolate(template, ctx = {}) {
  let out = template;

  /* if / else / endif */
  out = out.replace(
    /<\?php if \(([^)]*)\): \?>([\s\S]*?)(?:<\?php else: \?>([\s\S]*?))?<\?php endif; \?>/g,
    (match, condition, whenTrue, whenFalse = '') => {
      const value = condition.includes('$code === $currentLocale')
        ? ctx.code === currentLocale
        : false;

      return value ? whenTrue : whenFalse;
    }
  );

  /* echoes */
  out = out.replace(/<\?= ([\s\S]*?) \?>/g, (match, expression) => escape(evaluate(expression, ctx)));

  return out;
}

function expandLoops(template, ctx = {}) {
  const pattern = /<\?php foreach \(\$(\w+) as \$(\w+)(?: => \$(\w+))?\): \?>([\s\S]*?)<\?php endforeach; \?>/g;

  return template.replace(pattern, (match, collection, first, second, body) => {
    const items = ctx[collection] || [];
    const keyName = second ? first : null;
    const valueName = second || first;
    const entries = Array.isArray(items) ? items.map((item, i) => [i, item]) : Object.entries(items);

    return entries.map(([key, item]) => {
      const scope = { ...ctx, [valueName]: item };

      if (keyName) {
        scope[keyName] = key;
      }

      return interpolate(expandLoops(body, scope), scope);
    }).join('');
  });
}

/* ---------- the header itself ---------- */

export function navbarMarkup() {
  const source = read('resources/views/components/navbar.php');
  const start = source.indexOf('<header class="mnav"');
  const end = source.indexOf('</header>', start);
  const header = source.slice(start, end + '</header>'.length);

  const scope = { railLinks, indexLinks, supported, languageLinks, currentLocale };

  return interpolate(expandLoops(header, scope), scope).replace(/<!-- unhandled: [\s\S]*?-->/g, '');
}

/* ---------- demo stage around it ---------- */

function stage() {
  const cards = [
    ['Basin mixers', 'Matte black · gunmetal · brushed gold PVD', 'images/lifestyle/minimal-basin.png'],
    ['Rain showers', 'Concealed thermostatic, 45% water saving', 'images/lifestyle/luxury-shower.png'],
    ['Kitchen', 'Smart sensor faucets, digital temperature', 'images/lifestyle/kitchen-suite.png']
  ];

  return `
  <section class="stage-hero">
    <img class="stage-hero-img" src="images/lifestyle/heroc-1.webp" alt="" width="1600" height="900" fetchpriority="high">
    <div class="stage-hero-copy">
      <span class="stage-kicker">Design review · navbar</span>
      <h1 class="stage-title">Machined glass,<br>two rails, one monolith.</h1>
      <p class="stage-lead">Scroll to see the header condense, the progress line appear and the
        crooked index line unfold. Toggle the theme from the header.</p>
      <div class="stage-actions">
        <span class="stage-chip">Hover the crooked line → drawer</span>
        <span class="stage-chip">Resize &lt; 1024px → bloom button</span>
        <span class="stage-chip">Press / to search</span>
      </div>
    </div>
  </section>

  <section class="section container-narrow" id="finishes">
    <div class="stack">
      <span class="eyebrow">Try the glass</span>
      <h2>Content scrolls under a static-height slab</h2>
      <p class="stage-muted">The slab never changes height: only tint, shadow and the progress
        line react, so scrolling stays on the compositor.</p>
    </div>
    <div class="grid grid-3" style="margin-top: var(--space-8)">
      ${cards.map(([title, text, image]) => `
      <article class="card">
        <img src="${image}" alt="" width="640" height="420" loading="lazy" style="width:100%;display:block">
        <div class="card-body">
          <h3 class="card-title">${title}</h3>
          <p class="card-subtitle">${text}</p>
        </div>
      </article>`).join('')}
    </div>
  </section>

  <section class="band" id="rituals" style="padding: var(--section-gap) 0">
    <div class="container">
      <div class="section-header-left">
        <span class="section-tag">Band surface</span>
        <h2 class="section-title">Dark sections stay dark <em>— the glass reads either way</em></h2>
        <p class="section-subtitle">Both themes resolve the navbar through the same token family.</p>
      </div>
    </div>
  </section>

  <section class="section container-narrow" id="arrows">
    <div class="stack">
      <span class="eyebrow">Hero controls</span>
      <h2>Arrows before the icon font lands</h2>
      <p class="stage-muted">Font Awesome is loaded without blocking the paint, so the
        buttons draw CSS chevrons until its webfont is ready — never an empty glass circle.</p>
    </div>
    <div class="stage-arrows">
      <button type="button" class="lfc-arrow" id="lfc-prev" aria-label="Previous slide">
        <i class="fa-solid fa-chevron-left"></i>
      </button>
      <button type="button" class="lfc-arrow" id="lfc-next" aria-label="Next slide">
        <i class="fa-solid fa-chevron-right"></i>
      </button>
      <span class="stage-arrows-note">fa-ready: <b id="fa-state">no</b></span>
    </div>
  </section>

  <section class="section container-narrow" id="inspiration">
    <div class="stack">
      <span class="eyebrow">Long scroll</span>
      <h2>Progress line</h2>
      <p class="stage-muted">A single scaleX transform, painted inside requestAnimationFrame.</p>
    </div>
    <div class="stage-filler" aria-hidden="true"></div>
  </section>`;
}

export function buildPage() {
  const themeScript = read('resources/views/layouts/frontend.php')
    .match(/<script>\n\/\* theme before first paint \*\/[\s\S]*?<\/script>/)[0];

  return `<!DOCTYPE html>
<html lang="en" dir="ltr" data-theme="light" data-base="">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LUFLY — navbar design review</title>
${themeScript}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
<link rel="stylesheet" href="frontend/design-system/style.css">
<link rel="stylesheet" href="frontend/css/app.css">
<link rel="stylesheet" href="frontend/components/navbar/navbar.css">
<link rel="stylesheet" href="frontend/home/hero-cinema/hero-cinema.css">
<style>
  .stage-hero { position: relative; min-height: 74vh; display: flex; align-items: center; overflow: hidden; background: var(--ds-media-scrim-strong); }
  .stage-hero-img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; opacity: .55; }
  .stage-hero-copy { position: relative; z-index: 1; max-width: 760px; margin: 0 auto; padding: var(--space-16) var(--container-gutter); color: var(--ds-media-text); }
  .stage-kicker { display: block; margin-bottom: var(--space-4); color: var(--ds-media-primary); font-family: var(--font-mono); font-size: var(--text-caption); letter-spacing: var(--tracking-widest); text-transform: uppercase; }
  .stage-title { margin: 0 0 var(--space-4); font-family: var(--font-display); font-size: var(--text-display-lg); font-weight: var(--weight-regular); color: var(--ds-media-text); }
  .stage-lead { max-width: 560px; color: var(--ds-media-text-soft); }
  .stage-actions { display: flex; flex-wrap: wrap; gap: var(--space-2); margin-top: var(--space-6); }
  .stage-chip { padding: var(--space-2) var(--space-3); border: var(--border-hairline) solid var(--ds-media-border); border-radius: var(--radius-xs); color: var(--ds-media-text-soft); font-family: var(--font-mono); font-size: var(--text-caption); letter-spacing: var(--tracking-wide); text-transform: uppercase; }
  .stage-muted { color: var(--ds-text-muted); }
  .stage-arrows { display: flex; align-items: center; gap: var(--space-3); margin-top: var(--space-6); padding: var(--space-6); border-radius: var(--radius-md); background: var(--ds-media-scrim-strong); }
  .stage-arrows-note { margin-inline-start: var(--space-3); color: var(--ds-media-text-soft); font-family: var(--font-mono); font-size: var(--text-caption); letter-spacing: var(--tracking-wide); text-transform: uppercase; }
  .stage-filler { height: 140vh; margin-top: var(--space-8); border: var(--border-hairline) dashed var(--ds-border); border-radius: var(--radius-md); background: repeating-linear-gradient(180deg, var(--ds-primary-tint) 0 2px, transparent 2px 14px); }
</style>
</head>
<body class="ds-app aquatic-stage ld-loading">
<div class="ld-loader is-initial" id="ldLoader" role="status" aria-label="Loading">
    <div class="ld-blob a"></div>
    <div class="ld-blob b"></div>
    <div class="ld-vignette"></div>
    <div class="ld-kicker">Lufly · Premium Sanitary Ware</div>
    <div class="ld-logoBox" id="ldLogoBox">
        <svg class="ld-draw" viewBox="0 0 510 325" aria-hidden="true">
            <path pathLength="1" d="M 30 16 L 30 298 Q 30 306 38 306 L 116 306"/>
            <path pathLength="1" d="M 64 16 L 64 174"/>
            <path pathLength="1" d="M 64 244 L 116 244"/>
            <path pathLength="1" d="M 158 148 L 158 246 Q 158 292 198 292 Q 238 292 238 246 L 238 148"/>
            <path pathLength="1" d="M 288 292 L 288 82 Q 288 42 330 42"/>
            <path pathLength="1" d="M 256 148 L 320 148"/>
            <path pathLength="1" d="M 378 16 L 378 292"/>
            <path pathLength="1" d="M 416 148 L 456 238 L 496 148 L 424 306"/>
        </svg>
        <div class="ld-water" aria-hidden="true">
            <div class="ld-waterBody" id="ldWaterBody">
                <span class="wave"></span>
                <span class="wave w2"></span>
            </div>
        </div>
    </div>
    <div class="ld-tag" id="ldTag">Bathroom Culture · Enjoyed and Shared</div>
    <div class="ld-ui" id="ldUi">
        <div class="ld-row">
            <div class="ld-track"><span class="ld-fill" id="ldFill"></span></div>
            <span class="ld-pct" id="ldPct">0%</span>
        </div>
        <div class="ld-msg" id="ldMsg">Casting the brass bodies</div>
    </div>
    <div class="ld-foot">LUFLY HQ · Gaziantep Manufacturing</div>
</div>
<a class="skip-link" href="#main">Skip to content</a>
${navbarMarkup()}
<main class="ds-main" id="main">
${stage()}
</main>
<script src="frontend/js/app.js"></script>
<script src="frontend/components/loader/loader.js"></script>
<script src="frontend/components/navbar/navbar.js"></script>
<script>
  /* report the icon-font hand-over state instead of depending on the CDN */
  setTimeout(function () {
    document.getElementById('fa-state').textContent =
      document.documentElement.classList.contains('fa-ready') ? 'yes' : 'no (css chevrons)';
  }, 1200);
</script>
</body>
</html>`;
}
