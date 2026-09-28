/* Behavioral test for the finishes lab (staged-scene edition).
 *
 * Renders resources/views/home/finishes/finishes.php with a tiny PHP-subset
 * renderer (trans/e/asset/route/component + the two foreach loops), loads
 * frontend/home/finishes/finishes.js into jsdom, and asserts the rail, the
 * scene cross-fade, the callout, the spec sheet, the deep link, the compare
 * drawer and the hotspots.
 *
 * Run: node tools/frontend_audit/finishes_lab_test.cjs  (needs jsdom: npm i jsdom)
 */
'use strict';

const fs = require('fs');
const path = require('path');
let JSDOM, VirtualConsole;
try { ({ JSDOM, VirtualConsole } = require('jsdom')); }
catch (e1) {
  /* dev-machine fallback: the sandbox keeps jsdom under /tmp/fsuite */
  try { ({ JSDOM, VirtualConsole } = require('/tmp/fsuite/node_modules/jsdom')); }
  catch (e2) { console.error('jsdom is required: npm install jsdom, then re-run'); process.exit(2); }
}

const REPO = path.resolve(__dirname, '..', '..');

/* ------------------------------------------------------------------ lang -- */
function loadLang(file) {
  const src = fs.readFileSync(path.join(REPO, 'resources/lang', file, 'home.php'), 'utf8');
  const map = {};
  const re = /'([A-Za-z0-9_]+)'\s*=>\s*'((?:[^'\\]|\\.)*)'/g;
  let m;
  while ((m = re.exec(src)) !== null) {
    map[m[1]] = m[2].replace(/\\'/g, "'").replace(/\\\\/g, '\\');
  }
  return map;
}
const LANG = loadLang('en');

function trans(key, params) {
  if (!(key in LANG)) { throw new Error('missing lang key: ' + key); }
  let out = LANG[key];
  if (params) {
    Object.keys(params).forEach((p) => { out = out.replace(':' + p, String(params[p])); });
  }
  return out;
}
const esc = (s) => String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');

/* ------------------------------------------------------------- repo data -- */
const FINISHES = [
  { key: 'brushed-rose-gold', sphere: 'images/finishes/sphere-brushed-rose-gold.jpg', scene: 'images/finishes/scene-brushed-rose-gold.jpg', label: 'Rose Gold', tech: 'PVD', phrase: 'finishes_phrase_brushed_rose_gold', story: 'finishes_story_brushed_rose_gold', rates: ['high', 'low', 'high'] },
  { key: 'chrome', sphere: 'images/finishes/sphere-chrome.jpg', scene: 'images/finishes/scene-chrome.jpg', label: 'Chrome', tech: 'Electroplated', phrase: 'finishes_phrase_chrome', story: 'finishes_story_chrome', rates: ['medium', 'high', 'medium'] },
  { key: 'brushed-gold', sphere: 'images/finishes/sphere-brushed-gold.jpg', scene: 'images/finishes/scene-brushed-gold.jpg', label: 'Brushed Gold', tech: 'PVD', phrase: 'finishes_phrase_brushed_gold', story: 'finishes_story_brushed_gold', rates: ['high', 'low', 'high'] },
  { key: 'mirror-gold', sphere: 'images/finishes/sphere-mirror-gold.jpg', scene: 'images/finishes/scene-mirror-gold.jpg', label: 'Mirror Gold', tech: 'PVD', phrase: 'finishes_phrase_mirror_gold', story: 'finishes_story_mirror_gold', rates: ['medium', 'high', 'high'] },
  { key: 'matte-black', sphere: 'images/finishes/sphere-matte-black.jpg', scene: 'images/finishes/scene-matte-black.jpg', label: 'Matte Black', tech: 'Powder Coat', phrase: 'finishes_phrase_matte_black', story: 'finishes_story_matte_black', rates: ['high', 'medium', 'high'] },
  { key: 'brushed-nickel', sphere: 'images/finishes/sphere-brushed-nickel.jpg', scene: 'images/finishes/scene-brushed-nickel.jpg', label: 'Brushed Nickel', tech: 'PVD', phrase: 'finishes_phrase_brushed_nickel', story: 'finishes_story_brushed_nickel', rates: ['high', 'low', 'medium'] },
  { key: 'gunmetal', sphere: 'images/finishes/sphere-gunmetal.jpg', scene: 'images/finishes/scene-gunmetal.jpg', label: 'Gunmetal', tech: 'PVD', phrase: 'finishes_phrase_gunmetal', story: 'finishes_story_gunmetal', rates: ['high', 'medium', 'high'] },
  { key: 'brushed-gunmetal', sphere: 'images/finishes/sphere-brushed-gunmetal.jpg', scene: 'images/finishes/scene-brushed-gunmetal.jpg', label: 'Brushed Gunmetal', tech: 'PVD', phrase: 'finishes_phrase_brushed_gunmetal', story: 'finishes_story_brushed_gunmetal', rates: ['high', 'low', 'high'] },
  { key: 'gun-gray', sphere: 'images/finishes/sphere-gun-gray.jpg', scene: 'images/finishes/scene-gun-gray.jpg', label: 'Gun Gray', tech: 'PVD', phrase: 'finishes_phrase_gun_gray', story: 'finishes_story_gun_gray', rates: ['high', 'medium', 'medium'] },
];

/* --------------------------------------------------- responsive <picture> -- */
function renderRespic(args) {
  const found = [];
  const srcset = [];
  args.widths.forEach((w) => {
    const candidate = args.src + '@' + w + 'w.webp';
    if (fs.existsSync(path.join(REPO, 'public', candidate))) {
      srcset.push('/' + candidate + ' ' + w + 'w');
      found.push(w);
    }
  });
  const imgAttrs = ' src="/' + args.src + '" alt="' + esc(args.alt || '') + '"' +
    ' class="' + args.class + '"' +
    ' width="' + args.width + '" height="' + args.height + '"' +
    ' loading="lazy" decoding="async" data-respic-widths="' + found.join(',') + '"';
  if (!srcset.length) { return '<img' + imgAttrs + '>'; }
  return '<picture class="respic"><source type="image/webp" srcset="' + srcset.join(', ') + '" sizes="' + esc(args.sizes || '100vw') + '">' +
    '<img' + imgAttrs + '></picture>';
}

/* --------------------------------------------------------------- renderer -- */
function renderTemplate() {
  const src = fs.readFileSync(path.join(REPO, 'resources/views/home/finishes/finishes.php'), 'utf8');
  let html = src.slice(src.indexOf('<section'));

  /* the two scene <picture>s (src is $finishes[0]['scene']) */
  html = html.replace(/<\?=\s*\$view->component\('responsive-image',\s*\[([\s\S]*?)\]\)\s*\?>/g, (whole, argsBlob) => {
    const pick = (name) => {
      if (name === 'widths') {
        const m = argsBlob.match(/'widths'\s*=>\s*\[([0-9,\s]*)\]/);
        if (!m) { throw new Error('responsive-image arg missing: widths'); }
        return '[' + m[1].replace(/\s/g, '') + ']';
      }
      /* line-anchored: values like $finishes[0]['scene'] contain ] and , */
      const m = argsBlob.match(new RegExp("'" + name + "'\\s*=>\\s*(.+)$", 'm'));
      if (!m) { throw new Error('responsive-image arg missing: ' + name); }
      return m[1].trim().replace(/,$/, '');
    };
    const val = (raw) => {
      if (/^\$finishes\[0\]\['scene'\]$/.test(raw)) { return FINISHES[0].scene; }
      if (/^'/.test(raw)) { return raw.slice(1, -1); }
      if (/^\[/.test(raw)) {
        return raw.slice(1, -1).split(',').map((n) => parseInt(n, 10));
      }
      return parseInt(raw, 10);
    };
    return renderRespic({
      src: val(pick('src')), alt: val(pick('alt')), class: val(pick('class')),
      width: val(pick('width')), height: val(pick('height')),
      sizes: val(pick('sizes')), widths: val(pick('widths')),
    });
  });

  /* the card rail loop */
  html = html.replace(/<\?php\s+foreach \(\$finishes as \$i => \$finish\):[\s\S]*?\?>([\s\S]*?)<\?php endforeach; \?>/, (whole, body) => {
    return FINISHES.map((f, i) => body
      .replace(/<\?= \$i === 0 \? ' is-active' : '' \?>/g, i === 0 ? ' is-active' : '')
      .replace(/<\?= \$i === 0 \? 'true' : 'false' \?>/g, i === 0 ? 'true' : 'false')
      .replace(/<\?= e\(\$finish\['key'\]\) \?>/g, esc(f.key))
      .replace(/<\?= e\(trans\('home\.' \. \$meta\['phrase'\]\)\) \?>/g, esc(trans(f.phrase)))
      .replace(/<\?= e\(trans\('home\.' \. \$meta\['story'\]\)\) \?>/g, esc(trans(f.story)))
      .replace(/<\?= e\(asset\(\$finish\['sphere'\]\)\) \?>/g, esc('/' + f.sphere))
      .replace(/<\?= e\(\$finish\['label'\]\) \?>/g, esc(f.label))
      .replace(/<\?= e\(\$finish\['tech'\]\) \?>/g, esc(f.tech))
      .replace(/<\?= \(int\) \$i \?>/g, String(i))
    ).join('');
  });

  /* the compare drawer rows loop (anchored on </tbody>: the row template
     contains a nested rates loop whose endforeach must not end the match) */
  html = html.replace(/<\?php\s+foreach \(\$finishes as \$finish\):[\s\S]*?\?>([\s\S]*?)<\?php endforeach; \?>\s*<\/tbody>/, (whole, body) => {
    return FINISHES.map((f) => body
      .replace(/<\?php foreach \(\$meta\['rates'\] as \$r\): \?>[\s\S]*?<\?php endforeach; \?>/g, () => f.rates.map((r) =>
        '<td><span class="fs-rate fs-rate-' + r + '">' + esc(trans('finishes_rating_' + r)) + '</span></td>'
      ).join(''))
      .replace(/<\?= e\(\$finish\['key'\]\) \?>/g, esc(f.key))
      .replace(/<\?= e\(asset\(\$finish\['sphere'\]\)\) \?>/g, esc('/' + f.sphere))
      .replace(/<\?= e\(\$finish\['label'\]\) \?>/g, esc(f.label))
    ).join('') + '</tbody>';
  });

  /* the simple echos */
  html = html
    .replace(/<\?= e\(trans\('home\.([A-Za-z0-9_]+)', \['count' => count\(\$finishes\)\]\)\) \?>/g, (w, k) => esc(trans(k, { count: FINISHES.length })))
    .replace(/<\?= e\(trans\('home\.' \. \$firstMeta\['(phrase|story)'\]\)\) \?>/g, (w, which) => esc(trans(FINISHES[0][which])))
    .replace(/<\?= e\(trans\('home\.([A-Za-z0-9_]+)'\)\) \?>/g, (w, k) => esc(trans(k)))
    .replace(/<\?= e\(\$first\) \?>/g, esc(FINISHES[0].key))
    .replace(/<\?= e\(route\('products\.index'\)\) \?>/g, '/products')
    .replace(/<\?= e\(str_pad\(\(string\) count\(\$finishes\), 2, '0', STR_PAD_LEFT\)\) \?>/g, '09');

  if (/<\?=|<\?php/.test(html)) {
    throw new Error('unrendered PHP left in template: ' + html.match(/<\?[\s\S]{0,60}/)[0]);
  }
  return html;
}

/* ------------------------------------------------------------------ suite -- */
const JS = fs.readFileSync(path.join(REPO, 'frontend/home/finishes/finishes.js'), 'utf8');
const CSS = fs.readFileSync(path.join(REPO, 'frontend/home/finishes/finishes.css'), 'utf8');

let pass = 0;
let fail = 0;
function ok(cond, label) {
  if (cond) { pass++; console.log('  ok  ' + label); }
  else { fail++; console.log('FAIL  ' + label); }
}
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

function boot(url) {
  const html = '<!doctype html><html data-base=""><head><style>' + CSS + '</style></head><body>' +
    renderTemplate() + '<script>' + JS + '</scr' + 'ipt></body></html>';
  const vc = new VirtualConsole();
  vc.on('jsdomError', (e) => {
    console.log('JSDOM ERROR: ' + (e.detail && e.detail.stack ? e.detail.stack.split('\n').slice(0, 3).join(' | ') : e.message));
  });
  return new JSDOM(html, { runScripts: 'dangerously', pretendToBeVisual: true, url, virtualConsole: vc });
}

(async function main() {
  console.log('-- structure --');
  const dom = boot('https://lufly.test/');
  const doc = dom.window.document;
  await sleep(150); /* let jsdom fire DOMContentLoaded so finishes.js attaches its listeners */
  const cards = Array.from(doc.querySelectorAll('.fs-card'));
  ok(cards.length === 9, 'rail renders 9 finish cards');
  ok(doc.querySelectorAll('.fs-carousel .fs-cards .fs-card').length === 9, 'cards live inside the carousel');
  ok(!!doc.querySelector('[data-fs-prev]') && !!doc.querySelector('[data-fs-next]'), 'prev/next chevrons flank the rail');
  ok(!doc.querySelector('[data-fs-spaces]') && !doc.querySelector('.fs-modal') && !doc.querySelector('.fs-place'),
    'view-in-spaces is gone (button, modal, places)');
  ok(doc.querySelector('.fs-card').classList.contains('is-active') &&
     doc.querySelector('.fs-card').getAttribute('aria-pressed') === 'true', 'first card starts active + pressed');
  ok(cards.slice(1).every((c) => c.getAttribute('aria-pressed') === 'false' && !c.classList.contains('is-active')),
    'the other eight start inactive');
  ok(cards.every((c) => c.dataset.phrase && c.dataset.story), 'every card carries phrase + story data');
  const imgA = doc.querySelector('.fs-scene-img-a');
  const imgB = doc.querySelector('.fs-scene-img-b');
  ok(!!imgA && !!imgB, 'two stacked scene layers exist');
  ok(imgA.parentNode.tagName === 'PICTURE' && !!imgA.parentNode.querySelector('source[type="image/webp"]'),
    'scene layers are responsive <picture>s with webp sources');
  ok(imgA.getAttribute('data-respic-widths') === '480,960,1280', 'scene publishes its rendition widths');
  ok(imgA.classList.contains('is-on') && !imgB.classList.contains('is-on'), 'layer A is the initially visible photo');
  ok(doc.querySelectorAll('#finish-desc').length === 1 && doc.querySelectorAll('#fs-story').length === 1,
    'no duplicate ids (finish-desc / fs-story)');
  ok(doc.querySelector('.fs-cta-main').getAttribute('href') === '/products', 'explore CTA points at the catalogue');
  ok(doc.querySelectorAll('.fs-drawer tbody tr').length === 9, 'compare drawer tabulates all nine finishes');
  ok(doc.querySelectorAll('.fs-rate-high').length + doc.querySelectorAll('.fs-rate-medium').length +
     doc.querySelectorAll('.fs-rate-low').length === 27, 'drawer rates render (27 cells)');

  console.log('-- selection drives everything --');
  const chromeCard = cards.find((c) => c.dataset.finish === 'chrome');
  chromeCard.click();
  await sleep(350);
  ok(chromeCard.classList.contains('is-active') && !cards[0].classList.contains('is-active'),
    'active state moves to chrome');
  ok(doc.getElementById('finish-index').textContent === '02', 'counter shows 02');
  ok(doc.getElementById('finish-title').textContent === 'Polished Mirror Chrome' &&
     doc.getElementById('fs-caption-title').textContent === 'Polished Mirror Chrome',
    'spec title + scene callout title follow');
  ok(doc.getElementById('fs-phrase').textContent === 'Reflect Perfection', 'headline phrase follows');
  ok(doc.getElementById('fs-story').textContent === 'Pure Reflection. Timeless Design.', 'callout story follows');
  ok(doc.getElementById('finish-desc').textContent.indexOf('The quintessential') === 0, 'description follows');
  ok(doc.getElementById('finish-tag').textContent.indexOf('12-MICRON') === 0, 'process pill follows');
  ok(doc.getElementById('finish-base').textContent === 'Low-Lead Architectural Brass', 'base spec follows');
  ok(imgB.classList.contains('is-on') && !imgA.classList.contains('is-on'), 'scene cross-fades onto layer B');
  ok(imgB.src.endsWith('/images/finishes/scene-chrome.jpg'), 'layer B now wears the chrome scene');
  ok(imgB.parentNode.querySelector('source').srcset.indexOf('scene-chrome.jpg@480w.webp') !== -1,
    'layer B srcset rebuilt for the chrome renditions');
  ok(dom.window.location.search === '?finish=chrome', 'URL carries ?finish=chrome');
  ok(doc.querySelector('.finishes-section').getAttribute('data-finish') === 'chrome', 'section data-finish follows');

  console.log('-- the rail chevrons --');
  const prev = doc.querySelector('[data-fs-prev]');
  const next = doc.querySelector('[data-fs-next]');
  ok(prev.disabled === true && next.disabled === true, 'chevrons report disabled state from scroll metrics');
  let threw = false;
  try { prev.click(); next.click(); } catch (e) { threw = true; }
  ok(!threw, 'chevron clicks never throw');

  console.log('-- hotspots --');
  const spots = Array.from(doc.querySelectorAll('.fs-hotspot'));
  ok(spots.length === 3, 'three hotspots ride the faucet');
  ok(spots.every((s) => s.getAttribute('data-x') && s.getAttribute('data-y')), 'hotspots carry photo-relative coordinates');
  spots[0].click();
  ok(spots[0].classList.contains('is-open'), 'hotspot opens');
  spots[1].click();
  ok(!spots[0].classList.contains('is-open') && spots[1].classList.contains('is-open'), 'one hotspot open at a time');
  dom.window.document.dispatchEvent(new dom.window.KeyboardEvent('keydown', { key: 'Escape', bubbles: true }));
  ok(spots.every((s) => !s.classList.contains('is-open')), 'Escape closes hotspots');

  console.log('-- compare drawer --');
  const drawer = doc.querySelector('[data-fs-drawer]');
  ok(drawer.hidden === true, 'drawer starts hidden');
  doc.querySelector('[data-fs-compare]').click();
  await sleep(60);
  ok(drawer.hidden === false && drawer.classList.contains('is-open'), 'compare opens');
  doc.querySelector('[data-fs-compare-close]').click();
  await sleep(500);
  ok(drawer.hidden === true && !drawer.classList.contains('is-open'), 'compare closes and hides');

  console.log('-- entrance --');
  ok(doc.querySelector('.finishes-section').classList.contains('fs-in'), 'entrance choreography starts (no IO fallback)');

  console.log('-- deep link --');
  const deep = boot('https://lufly.test/?finish=gunmetal');
  await sleep(300);
  const deepActive = deep.window.document.querySelector('.fs-card.is-active');
  ok(deepActive && deepActive.dataset.finish === 'gunmetal', '?finish=gunmetal pre-selects gunmetal');
  ok(deep.window.document.getElementById('finish-index').textContent === '07', 'deep link counter is 07');
  ok(deep.window.document.getElementById('fs-phrase').textContent === 'Engineer Atmosphere', 'deep link phrase follows');

  console.log('-- css guards --');
  ok(/\.fs-drawer\[hidden\]\s*\{\s*display:\s*none/.test(CSS), '[hidden] guard keeps the drawer from covering the page');
  ok(/overflow-x:\s*(auto|scroll)/.test(CSS) && /scrollbar-width:\s*none/.test(CSS),
    'the rail is a horizontal scroller with its scrollbar hidden');
  ok(/scroll-snap/.test(CSS), 'the rail snaps to cards');

  console.log('\n' + pass + ' passed, ' + fail + ' failed');
  process.exit(fail ? 1 : 0);
})().catch((e) => { console.error('SUITE ERROR:', e); process.exit(1); });
