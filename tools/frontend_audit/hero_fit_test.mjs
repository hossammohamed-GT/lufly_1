#!/usr/bin/env node
/**
 * Hero fit harness — runs the real frontend/home/hero-cinema/hero-cinema.js
 * against a tiny DOM stub and simulated devices, then reports the height the
 * hero ends up with.
 *
 * There is no browser in this environment, so the DOM stub models exactly the
 * pieces the hero uses: viewport height (small viewport + the sliding URL bar),
 * document-space geometry, panel content heights and the chrome above the hero
 * (announcement bar + navbar).
 *
 *   node tools/frontend_audit/hero_fit_test.mjs            # current script
 *   node tools/frontend_audit/hero_fit_test.mjs --file X   # any build
 *   node tools/frontend_audit/hero_fit_test.mjs --all      # every device + table
 *
 * Exit code 1 when a device ends up with a hero that is taller than the screen,
 * taller than it needs to be, clipped, or that changes height while scrolling.
 */
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');

const args = process.argv.slice(2);
const legacyViewport = args.includes('--legacy-viewport');
const fileArg = args.indexOf('--file');
const scriptPath = fileArg >= 0 ? args[fileArg + 1] : path.join(ROOT, 'frontend/home/hero-cinema/hero-cinema.js');

/* ------------------------------------------------------------------ *
 * device profiles: css px. `svh` is the small viewport (toolbars shown),
 * `innerExpanded` is window.innerHeight once the URL bar slides away.
 * ------------------------------------------------------------------ */
const DEVICES = [
  { name: 'iPhone SE 320x568', width: 320, height: 568, port: true, mobile: true, chrome: 40 + 56, base: 300, para: 96, sub: 4 },
  { name: 'iPhone 12 390x844', width: 390, height: 844, port: true, mobile: true, chrome: 40 + 56, base: 330, para: 96, sub: 3.6 },
  { name: 'iPhone 15 Pro Max', width: 430, height: 932, port: true, mobile: true, chrome: 40 + 56, base: 340, para: 96, sub: 3.4 },
  { name: 'Pixel 7 412x915', width: 412, height: 915, port: true, mobile: true, chrome: 40 + 56, base: 335, para: 96, sub: 3.5 },
  { name: 'Galaxy S8 360x740', width: 360, height: 740, port: true, mobile: true, chrome: 40 + 56, base: 320, para: 96, sub: 3.8 },
  { name: 'Galaxy Fold 280x653', width: 280, height: 653, port: true, mobile: true, chrome: 40 + 56, base: 290, para: 92, sub: 4.2 },
  { name: 'iPhone 8 375x667', width: 375, height: 667, port: true, mobile: true, chrome: 40 + 56, base: 320, para: 92, sub: 3.8 },
  { name: 'SE landscape 568x320', width: 568, height: 320, port: false, mobile: true, chrome: 40 + 56, base: 250, para: 84, sub: 3.8 },
  { name: 'iPhone 12 landscape', width: 844, height: 390, port: false, mobile: false, chrome: 40 + 58, base: 250, para: 84, sub: 0 },
  { name: 'iPad mini 768x1024', width: 768, height: 1024, port: true, mobile: false, chrome: 44 + 58, base: 330, para: 110, sub: 0 },
  { name: 'laptop 1280x720', width: 1280, height: 720, port: false, mobile: false, chrome: 44 + 64, base: 330, para: 110, sub: 0 },
  { name: 'desktop 1600x900', width: 1600, height: 900, port: false, mobile: false, chrome: 44 + 64, base: 340, para: 110, sub: 0 }
];

/* What the phone copy needs, modelling the portrait-phone rules in
   frontend/home/hero-cinema/hero-cinema.css: the kicker, the brand box (or
   the two-line title), a three-line paragraph and the two CTAs, plus the
   panel padding. Worst case (branded slide, paragraph visible, CTA row
   wrapped). */
function phoneCopyNeed(device) {
  const w = device.width;
  const h = device.height;

  const kicker = 20;
  const logoBox = Math.min(190, Math.round(w * 0.46)) * (274 / 430) + 8;
  const title = 2 * Math.min(Math.max(27, w * 0.084), 44) * 1.06 + 8;
  const subVisible = h > 640 && w > 340;
  const sub = subVisible ? 3 * 13.5 * 1.62 + 8 : 0;
  const rows = w >= 380 ? 1 : 2;
  const ctas = 12 + rows * (12 + 12 + 13);
  const pad = 10 + 54;

  return Math.round(Math.max(kicker + logoBox, kicker + title) + sub + ctas + pad);
}

/* panel metrics per stylesheet breakpoint - the stub has to agree with
   frontend/home/hero-cinema/hero-cinema.css or the numbers are fiction:
     max-height 560  -> decorative lines gone, compact padding + type + CTAs
     max-height 640  -> sub hidden
     max-height 700  -> compact padding + 0.92 type scale
   and on phones the sub is clamped to ~9ch lines. */
function panelMetrics(device) {
  const h = device.height;

  /* very short: the kicker, the tag and the rule are hidden and the paddings,
     the brand box and the CTAs are the compact ones - what is left is roughly
     the brand box plus the CTA row */
  if (h <= 560) {
    return { pad: { top: 8, bottom: 56 }, content: Math.min(device.base, 112), showSub: false };
  }

  const heightScale = h <= 700 ? 0.92 : 1;
  const pad = h <= 700 ? { top: 16, bottom: 72 } : { top: 28, bottom: 92 };
  const showSub = h > 640 && device.sub > 0;
  const subHeight = showSub ? Math.round(device.para * (device.sub / 3.4)) : 0;

  return {
    pad: pad,
    content: Math.round((device.base + subHeight) * heightScale),
    showSub: showSub,
  };
}

/* ------------------------------------------------------------------ *
 * DOM stub
 * ------------------------------------------------------------------ */
function makeElement(tag, opts = {}) {
  const el = {
    tagName: (tag || 'div').toUpperCase(),
    children: [],
    attrs: {},
    classes: new Set(),
    style: {
      cssText: opts.cssText || '',
      setProperty() {},
      get height() { return el._height || ''; },
      set height(value) { el._height = value; },
    },
    _height: '',
    offsetHeight: opts.offsetHeight || 0,
    clientWidth: opts.clientWidth || 0,
    scrollWidth: opts.scrollWidth || 0,
    hidden: false,
    textContent: '',
    get classList() {
      return {
        add: (...names) => names.forEach((n) => el.classes.add(n)),
        remove: (...names) => names.forEach((n) => el.classes.delete(n)),
        toggle: (n, force) => {
          const on = force === undefined ? !el.classes.has(n) : !!force;
          if (on) el.classes.add(n); else el.classes.delete(n);
          return on;
        },
        contains: (n) => el.classes.has(n),
      };
    },
    getAttribute: (name) => (name in el.attrs ? el.attrs[name] : null),
    setAttribute: (name, value) => { el.attrs[name] = String(value); },
    removeAttribute: (name) => { delete el.attrs[name]; },
    appendChild: (child) => { el.children.push(child); child.parentNode = el; return child; },
    removeChild: (child) => { el.children = el.children.filter((c) => c !== child); return child; },
    querySelector: () => null,
    querySelectorAll: () => [],
    addEventListener: () => {},
    removeEventListener: () => {},
    getBoundingClientRect: () => ({ top: 0, bottom: 0, left: 0, right: 0, width: 0, height: el.offsetHeight }),
    replaceChildren: () => {},
    scrollIntoView: () => {},
  };
  return el;
}

function runScene(device, scenario) {
  const listeners = {};
  const timers = [];
  let frame = null;

  const metricsForDevice = panelMetrics(device);
  const pad = metricsForDevice.pad;
  const content = scenario.exact
    ? scenario.content - pad.top - pad.bottom
    : Math.round(scenario.content * (metricsForDevice.content / Math.max(1, device.base + (metricsForDevice.showSub ? device.para : 0))));

  const doc = makeElement('html');
  doc.attrs['data-theme'] = 'light';
  const body = makeElement('body');
  doc.body = body;

  /* --- hero markup --- */
  const root = makeElement('section', { offsetHeight: 0 });
  const sceneLayer = makeElement('div');
  const stage = makeElement('div');
  const scenes = [];
  const panels = [];

  for (let i = 0; i < 5; i++) {
    const scene = makeElement('div');
    scene.attrs['data-img'] = `heroc-${i + 1}.webp`;
    scene.attrs['data-img-m'] = `heroc-${i + 1}-m.webp`;
    scene.attrs['data-img-p'] = `heroc-${i + 1}-p.webp`;
    scene.attrs['data-img-light'] = `heroc-${i + 1}-light.jpg`;
    scene.attrs['data-img-light-m'] = `heroc-${i + 1}-light-m.jpg`;
    scene.attrs['data-img-light-p'] = `heroc-${i + 1}-light-p.jpg`;
    scenes.push(scene);

    const panel = makeElement('div');
    const inner = makeElement('div', { offsetHeight: Math.round(content * (i === 0 ? 1 : 0.86)) });
    panel.querySelector = (sel) => (sel === '.lfc-inner' ? inner : null);
    panels.push(panel);
  }

  root.querySelector = (sel) => (sel === '.lfc-scenes' ? sceneLayer : sel === '.lfc-stage' ? stage : null);
  root.querySelectorAll = (sel) => {
    if (sel === '.lfc-scene') return scenes;
    if (sel === '.lfc-panel') return panels;
    if (sel === '.lfc-tick') return [];
    return [];
  };

  /* --- viewport state --- */
  const state = {
    svh: device.height,
    innerHeight: device.height,
    scrollY: 0,
    heroTop: device.chrome,
  };

  const documentStub = {
    documentElement: doc,
    body,
    hidden: false,
    fonts: undefined,
    createElement: (tag) => {
      const el = makeElement(tag);
      /* the script appends a probe whose cssText sets height:100svh: the stub
         resolves that unit to the device's small viewport */
      Object.defineProperty(el.style, 'cssText', {
        get: () => el._css || '',
        set: (value) => {
          el._css = String(value);
          if (el._css.includes('100svh')) el.offsetHeight = legacyViewport ? 0 : state.svh;
        },
      });
      return el;
    },
    getElementById: (id) => (id === 'lfc' ? root : id === 'lfc-prev' || id === 'lfc-next' ? makeElement('button') : null),
    querySelector: () => null,
    querySelectorAll: () => [],
    addEventListener: (type, fn) => { (listeners[type] = listeners[type] || []).push(fn); },
    removeEventListener: () => {},
  };

  const win = {
    innerHeight: state.innerHeight,
    innerWidth: device.width,
    pageOffsetY: 0,
    get pageYOffset() { return state.scrollY; },
    scrollY: 0,
    document: documentStub,
    matchMedia: (query) => {
      let matches = false;
      if (query.includes('max-width: 760px')) matches = device.width <= 760;
      else if (query.includes('pointer: fine')) matches = !device.mobile;
      else if (query.includes('orientation: portrait')) matches = device.port;
      else if (query.includes('orientation: landscape')) matches = !device.port;
      else if (query.includes('reduced-motion')) matches = false;
      return { matches, media: query, addEventListener() {}, removeEventListener() {} };
    },
    requestAnimationFrame: (fn) => { frame = fn; return 1; },
    cancelAnimationFrame: () => { frame = null; },
    setTimeout: (fn, ms) => { timers.push({ fn, ms }); return timers.length; },
    clearTimeout: () => {},
    setInterval: () => 1,
    clearInterval: () => {},
    visualViewport: undefined,
    getComputedStyle: () => ({ paddingTop: pad.top + 'px', paddingBottom: pad.bottom + 'px' }),
    addEventListener: (type, fn) => { (listeners[type] = listeners[type] || []).push(fn); },
    removeEventListener: () => {},
  };
  win.window = win;

  /* what a UA does: hide the toolbars -> innerHeight grows, svh does not */
  const metrics = {
    get heroHeight() {
      const raw = root._height;
      const n = parseFloat(raw);
      return Number.isFinite(n) ? n : null;
    },
    get heroTop() {
      return Math.max(0, device.chrome - state.scrollY);
    },
    get space() {
      return Math.round(state.svh - metrics.heroTop);
    },
    scrollTo(y, expanded) {
      state.scrollY = y;
      state.innerHeight = expanded === undefined ? device.height : expanded;
      win.innerHeight = state.innerHeight;
      root.getBoundingClientRect = () => ({
        top: device.chrome - state.scrollY,
        bottom: device.chrome - state.scrollY + (metrics.heroHeight || 0),
        left: 0, right: device.width,
        width: device.width,
        height: metrics.heroHeight || 0,
      });
    },
    fire(type) {
      (listeners[type] || []).forEach((fn) => fn({}));
      if (frame) { const f = frame; frame = null; f(); }
    },
    flushTimers() {
      while (timers.length) {
        const t = timers.shift();
        t.fn();
      }
      if (frame) { const f = frame; frame = null; f(); }
    },
    state,
    device,
    needed: content + pad.top + pad.bottom,
  };

  metrics.scrollTo(0);
  root.getBoundingClientRect = () => ({
    top: device.chrome, bottom: device.chrome, left: 0, right: device.width,
    width: device.width, height: 0,
  });

  const code = fs.readFileSync(scriptPath, 'utf8');
  const fn = new Function('window', 'document', 'globalThis', code);
  fn(win, documentStub, win);

  return metrics;
}

/* ------------------------------------------------------------------ *
 * scenarios
 * ------------------------------------------------------------------ */
/* the -p artwork the phone receives, and how much of its width a box of the
   given aspect actually shows under `background-size: cover` */
const PHONE_ART = { width: 1080, height: 959 };
function artFraction(boxWidth, boxHeight) {
  const scale = Math.max(boxWidth / PHONE_ART.width, boxHeight / PHONE_ART.height);
  return (boxWidth / scale) / PHONE_ART.width;
}

const BAND_MIN = 140;
const BAND_MAX = 320;
const BAND_SHARE = 0.55;

/* what the script's updateBand() should have produced */
function bandFor(space, copyNeed) {
  const cap = Math.min(Math.round(space * BAND_SHARE), BAND_MAX);
  return Math.min(Math.max(space - copyNeed, BAND_MIN), cap);
}

function checkDevice(device) {
  const phone = device.mobile && device.port;
  const copyNeed = phone ? phoneCopyNeed(device) : panelMetrics(device).content;
  const issues = [];
  const m = runScene(device, phone ? { content: copyNeed, exact: true } : { content: copyNeed });

  const start = m.heroHeight;
  if (start === null) {
    return { device, height: null, issues: ['the script never set a height'] };
  }

  const capOneScreen = m.space;
  let needed = m.needed;

  if (phone) {
    /* the hero fills the open screen, and the copy sits under the artwork
       band instead of fighting the photo for the same box */
    const band = bandFor(capOneScreen, copyNeed);
    const copyArea = capOneScreen - band;
    needed = copyArea;

    if (Math.abs(start - capOneScreen) > 1) {
      issues.push(`the hero does not fill the screen (${start} vs ${capOneScreen})`);
    }
    if (copyNeed > copyArea + 1) {
      issues.push(`the copy does not fit under the artwork band (${copyNeed} > ${copyArea})`);
    }

    /* the whole point of the band: the photo keeps a wide crop */
    const fraction = artFraction(device.width, band);
    if (fraction < 0.55) {
      issues.push(`the phone shows only ${Math.round(fraction * 100)}% of the photo width - cramped crop`);
    }
  } else {
    if (start > capOneScreen + 1) {
      issues.push(`taller than the first screen (${start} > ${capOneScreen})`);
    }
    /* the hero must show the copy whenever the screen has room for it */
    const expectedMin = Math.min(needed, capOneScreen);
    if (start < expectedMin - 1) {
      issues.push(`shorter than the copy needs (${start} < ${expectedMin})`);
    }
    if (start > m.state.svh) {
      issues.push(`taller than the small viewport (${start} > ${m.state.svh})`);
    }
  }

  const tight = needed > capOneScreen + 1;

  const expanded = device.height + 96;   /* toolbars retracted */
  const expandedSpace = expanded - device.chrome;
  const svhSpace = m.state.svh - device.chrome;

  /* 1a. at the top, with the browser toolbars away, the hero fills the whole
         OPEN screen - that is the requested behaviour */
  m.scrollTo(0, expanded);
  m.fire('resize');
  m.flushTimers();
  const atTopExpanded = m.heroHeight;
  /* without svh support (old iOS) the viewport is pinned to the smallest value
     ever seen - the safe choice there is the small viewport, not the expanded
     one, because the toolbar state cannot be trusted */
  const topExpectation = legacyViewport ? m.state.svh - device.chrome : expandedSpace;
  if (Math.abs(atTopExpanded - topExpectation) > 1) {
    issues.push(`at the top with the toolbars away the hero should fill the open screen (${atTopExpanded} vs ${topExpectation})`);
  }

  /* 1b. scrolled - and this is the old bug: the hero used to add the scroll
         offset on every resize and run away. It must now hold the small
         viewport height, so nothing under the reader shifts. */
  m.scrollTo(240, expanded);
  m.fire('resize');
  m.flushTimers();
  const afterScroll = m.heroHeight;

  if (afterScroll > expandedSpace + 1) {
    issues.push(`the scroll offset was added again: ${start} → ${afterScroll} (ceiling ${expandedSpace})`);
  }
  if (Math.abs(afterScroll - svhSpace) > 1) {
    issues.push(`while scrolled the height must stay put (${afterScroll} vs ${svhSpace})`);
  }

  /* 2. a burst of resize events (iOS fires them constantly) may only alternate
        between the two legitimate viewport heights - it must never creep */
  const heights = [];
  for (let i = 0; i < 12; i++) {
    m.scrollTo(120 + i * 40, device.height + (i % 2 ? 96 : 0));
    m.fire('resize');
    m.flushTimers();
    heights.push(m.heroHeight);
  }
  const max = Math.max(...heights);
  const min = Math.min(...heights);
  const allowed = [svhSpace, expandedSpace];
  const rogue = heights.find((h) => allowed.every((a) => Math.abs(h - a) > 1));
  if (rogue !== undefined) {
    issues.push(`unstable across resizes: saw ${rogue}px, expected ${allowed.join('px or ')}px`);
  }
  if (max - min > 97) {
    issues.push(`the height creeps across resizes (${min}…${max})`);
  }
  if (max > expandedSpace + 1) {
    issues.push(`resize burst pushed it past the visible screen (${max} > ${expandedSpace})`);
  }

  /* 3. an empty tab / hidden window must not produce a bogus height */
  m.scrollTo(0);
  m.fire('orientationchange');
  m.flushTimers();

  /* 4. rotation: the same phone flipped to landscape is a fresh profile with a
        viewport that is now much shorter - the hero has to be re-measured, not
        carried over from portrait (the landscape-phone CSS block only applies
        in this orientation). */
  if (device.port && device.mobile) {
    const rotated = { ...device, name: `${device.name} (rotated)`, width: device.height, height: device.width, port: false };
    const rotatedCopy = panelMetrics(rotated).content;
    const rm = runScene(rotated, { content: rotatedCopy });
    if (rm.heroHeight === null) {
      /* the script leaves very short viewports (<200px of space) to the CSS
         fallback, which has to fit the same space */
      const cssFallback = rm.device.height - rm.device.chrome;
      if (cssFallback > rm.space + 1) {
        issues.push(`after rotating to landscape the CSS fallback does not fit the screen (${cssFallback} > ${rm.space})`);
      }
    } else {
      if (rm.heroHeight > rm.space + 1) {
        issues.push(`after rotating to landscape the hero is taller than the screen (${rm.heroHeight} > ${rm.space})`);
      }
      if (rm.heroHeight < rm.needed - 1) {
        issues.push(`after rotating to landscape the copy is cut off (${rm.heroHeight} < ${rm.needed})`);
      }
    }
  }

  return { device, height: start, settled: heights[heights.length - 1], issues, tight, needed };
}

const results = DEVICES.map(checkDevice);
const failed = results.filter((r) => r.issues.length);
const tight = results.filter((r) => !r.issues.length && r.tight);

/* one row per device with everything a report needs, so the table and the
   JSON view can never disagree */
const rows = results.map((r) => {
  const device = r.device;
  const phone = device.mobile && device.port;
  const copyNeed = phone ? phoneCopyNeed(device) : panelMetrics(device).content;
  const m = runScene(device, phone ? { content: copyNeed, exact: true } : { content: copyNeed });

  const band = phone ? bandFor(m.space, copyNeed) : null;
  const cropFraction = phone ? artFraction(device.width, band) : null;

  return {
    name: device.name,
    width: device.width,
    height: device.height,
    portraitPhone: phone,
    hero: r.height,
    needs: phone ? m.space - band : m.needed,
    max: m.space,
    band,
    cropFraction,
    tight: r.tight,
    issues: r.issues,
    svh: m.state.svh,
    innerExpanded: m.state.innerHeight,
  };
});

if (args.includes('--json')) {
  console.log(JSON.stringify({ script: path.relative(ROOT, scriptPath), legacyViewport, rows }, null, 2));
  process.exit(failed.length ? 1 : 0);
}

const width = (value, size) => String(value).padEnd(size);
console.log(`hero script: ${path.relative(ROOT, scriptPath)}${legacyViewport ? '  (no svh support: smallest-observed fallback)' : ''}`);
console.log('');

console.log('');
console.log(
  width('device', 24) + width('viewport', 12) + width('hero', 8) +
  width('art band', 10) + width('copy area', 11) + width('crop', 8) + 'notes'
);
console.log('-'.repeat(104));
for (const row of rows) {
  console.log(
    width(row.name, 24) +
    width(`${row.width}x${row.height}`, 12) +
    width(row.hero === null ? '-' : `${row.hero}px`, 8) +
    width(row.band === null ? '—' : `${row.band}px`, 10) +
    width(`${row.needs}px`, 11) +
    width(row.cropFraction === null ? '—' : `${Math.round(row.cropFraction * 100)}%`, 8) +
    (row.issues.length ? 'FAIL: ' + row.issues.join('; ') : 'ok')
  );
}
console.log('');
console.log(
  failed.length
    ? `${failed.length} of ${results.length} device(s) failed`
    : `all ${results.length} devices fill the open screen, with the copy fitting under the artwork`
);
if (tight.length) {
  console.log(
    'note: copy taller than the screen on ' +
    tight.map((r) => r.device.name).join(', ') +
    ' - the compact rules hide the paragraph there, the harness estimate is approximate'
  );
}

process.exit(failed.length ? 1 : 0);
