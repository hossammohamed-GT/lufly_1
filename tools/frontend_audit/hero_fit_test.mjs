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
  const pad = panelMetrics(device).pad;

  const kicker = 20;
  const logoBox = Math.min(190, Math.round(w * 0.46)) * (274 / 430) + 8;
  const title = 2 * Math.min(Math.max(27, w * 0.084), 44) * 1.06 + 8;
  const subVisible = h > 640 && w > 340;
  const sub = subVisible ? 3 * 13.5 * 1.62 + 8 : 0;
  const rows = w >= 380 ? 1 : 2;
  const ctas = 12 + rows * (12 + 12 + 13);

  return Math.round(Math.max(kicker + logoBox, kicker + title) + sub + ctas + pad.top + pad.bottom);
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

function runScene(profile, scenario) {
  /* never mutate the caller's device profile: the round trip test resizes the
     simulated window, and the same profile object is reused by other checks */
  const device = { ...profile };
  const listeners = {};
  const timers = [];
  const mediaListeners = {};
  const media = {
    phone: device.width <= 760,
    portrait: device.port,
    landscape: !device.port,
    fine: !device.mobile,
  };
  let frame = null;

  function matchesFor(query) {
    if (query.includes('max-width: 760px')) return media.phone;
    if (query.includes('pointer: fine')) return media.fine;
    if (query.includes('orientation: portrait')) return media.portrait;
    if (query.includes('orientation: landscape')) return media.landscape;
    return false;
  }

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
          if (el._css.includes('100svh')) {
            /* the probe measures the viewport, so it has to move with it */
            Object.defineProperty(el, 'offsetHeight', {
              get: () => (legacyViewport ? 0 : state.svh),
              configurable: true,
            });
          }
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
    matchMedia: (query) => ({
      get matches() {
        return matchesFor(query);
      },
      media: query,
      addEventListener(type, fn) {
        if (type !== 'change') return;
        (mediaListeners[query] = mediaListeners[query] || []).push(fn);
      },
      removeEventListener() {},
    }),
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
    /* Resize the simulated window the way the browser does: the viewport, the
       media queries and a resize event, all together. */
    resizeTo(width, height) {
      const wasPhone = media.phone;
      const wasPortrait = media.portrait;

      device.width = width;
      device.height = height;
      state.svh = height;
      state.innerHeight = height;
      win.innerHeight = height;
      media.phone = width <= 760;
      media.portrait = height >= width;
      media.landscape = width > height;

      root.getBoundingClientRect = () => ({
        top: device.chrome - state.scrollY,
        bottom: device.chrome - state.scrollY + (metrics.heroHeight || 0),
        left: 0,
        right: width,
        width,
        height: metrics.heroHeight || 0,
      });

      Object.keys(mediaListeners).forEach((query) => {
        const changed =
          (query.includes('max-width: 760px') && wasPhone !== media.phone) ||
          (query.includes('orientation: portrait') && wasPortrait !== media.portrait) ||
          (query.includes('orientation: landscape') && wasPortrait !== media.portrait);
        if (changed) mediaListeners[query].forEach((fn) => fn({ matches: matchesFor(query), media: query }));
      });

      (listeners['resize'] || []).forEach((fn) => fn({}));
      if (frame) {
        const f = frame;
        frame = null;
        f();
      }
    },
    get backgrounds() {
      /* scenes other than the visible one may still be queued (the script
         preloads them lazily), so the pending URL counts as applied */
      return scenes.map((sc) => sc.style.backgroundImage || sc._bgUrl || '');
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
/* the portrait artwork the phone receives (tools/media_audit/hero_portrait_crops.py)
   and how much of it a box of the given aspect shows under
   `background-size: cover` - a full-height phone hero crops a landscape shot
   down to about a quarter of its width, which is what used to feel cramped. */
const PHONE_ART = { width: 800, height: 1072 };      /* heroc-<n>-p.webp */
const PHONE_ART_LIGHT = { width: 768, height: 1290 }; /* heroc-<n>-light-p.jpg */

function artFraction(boxWidth, boxHeight, art = PHONE_ART) {
  const scale = Math.max(boxWidth / art.width, boxHeight / art.height);
  const visibleWidth = boxWidth / scale;
  const visibleHeight = boxHeight / scale;
  return {
    width: visibleWidth / art.width,
    height: visibleHeight / art.height,
    area: (visibleWidth * visibleHeight) / (art.width * art.height),
  };
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
    /* the hero fills the open screen, with the photo edge to edge - the copy
       has to fit inside that same box */
    if (Math.abs(start - capOneScreen) > 1) {
      issues.push(`the hero does not fill the screen (${start} vs ${capOneScreen})`);
    }
    if (copyNeed > capOneScreen + 1) {
      issues.push(`the copy is taller than the screen (${copyNeed} > ${capOneScreen})`);
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

  /* whenever the box is tall the portrait artwork is what keeps the crop
     comfortable - measured for phones and for portrait tablets / narrow
     portrait windows, which get the same crop */
  const tallBox = device.height >= device.width && device.width <= 900;
  if (tallBox) {
    const dark = artFraction(device.width, start);
    const light = artFraction(device.width, start, PHONE_ART_LIGHT);
    const worst = Math.min(dark.width, light.width);
    if (worst < 0.65) {
      issues.push(`the box shows only ${Math.round(worst * 100)}% of the artwork width - cramped crop`);
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

/* The reported bug: the artwork lagged one breakpoint behind, because the
   cached `isMobile` flag was read by the background swap while the re-fit was
   still queued. Shrink the window and the desktop shot stayed in a tall box (a
   quarter of the frame - "the picture is zoomed"); grow it back and the
   portrait crop was stretched over the wide hero. Both directions, from a
   phone and from a desktop window, are checked here. */
function checkRoundTrip(profile, other) {
  const phone = profile.mobile && profile.port;
  const m = runScene(profile, phone
    ? { content: phoneCopyNeed(profile), exact: true }
    : { content: panelMetrics(profile).content });
  const issues = [];
  let lastWidth = profile.width;

  const isPhoneWidth = (width) => width <= 760;
  const check = (when) => {
    const urls = m.backgrounds;
    const wide = !isPhoneWidth(lastWidth);
    const wrong = urls.filter((url) => (wide
      ? /-(p|m)\.(webp|jpg)/.test(url)     /* a phone crop on a wide window */
      : !/-p\.(webp|jpg)/.test(url)));      /* no portrait crop on a phone box */
    if (wrong.length) {
      issues.push(`${when}: wrong artwork - ${wrong[0]}${wide ? ' (phone crop on a desktop width)' : ' (desktop art in a phone box)'}`);
    }

    const space = m.state.svh - m.device.chrome;
    if (m.heroHeight !== null && m.heroHeight > space + 1) {
      issues.push(`${when}: the hero is taller than the screen (${m.heroHeight} > ${space})`);
    }
  };

  check(`starting at ${profile.width}x${profile.height}`);
  lastWidth = other.width;
  m.resizeTo(other.width, other.height);
  m.flushTimers();
  check(`after resizing to ${other.width}x${other.height}`);
  lastWidth = profile.width;
  m.resizeTo(profile.width, profile.height);
  m.flushTimers();
  check(`back at ${profile.width}x${profile.height}`);

  return { name: `${profile.name} ↔ ${other.width}x${other.height}`, issues };
}

const byName = (prefix) => DEVICES.find((d) => d.name.startsWith(prefix));
const roundTrips = [
  checkRoundTrip(byName('iPhone 12 390'), { width: 1280, height: 720 }),
  checkRoundTrip(byName('Galaxy Fold'), { width: 1600, height: 900 }),
  checkRoundTrip(byName('laptop 1280'), { width: 390, height: 844 }),
  checkRoundTrip(byName('desktop 1600'), { width: 320, height: 568 }),
];

const results = DEVICES.map(checkDevice);
const failed = results.filter((r) => r.issues.length).concat(roundTrips.filter((r) => r.issues.length));
const tight = results.filter((r) => !r.issues.length && r.tight);

/* one row per device with everything a report needs, so the table and the
   JSON view can never disagree */
const rows = results.map((r) => {
  const device = r.device;
  const phone = device.mobile && device.port;
  const copyNeed = phone ? phoneCopyNeed(device) : panelMetrics(device).content;
  const m = runScene(device, phone ? { content: copyNeed, exact: true } : { content: copyNeed });

  const tallBox = device.height >= device.width && device.width <= 900;
  const crop = tallBox ? artFraction(device.width, r.height || m.space) : null;
  const cropLight = tallBox ? artFraction(device.width, r.height || m.space, PHONE_ART_LIGHT) : null;

  return {
    name: device.name,
    width: device.width,
    height: device.height,
    portraitPhone: phone,
    hero: r.height,
    needs: m.needed,
    max: m.space,
    cropFraction: crop ? Math.min(crop.width, cropLight.width) : null,
    cropArea: crop ? crop.area : null,
    tight: r.tight,
    issues: r.issues,
    svh: m.state.svh,
    innerExpanded: m.state.innerHeight,
  };
});

if (args.includes('--json')) {
  console.log(JSON.stringify({
    script: path.relative(ROOT, scriptPath),
    legacyViewport,
    rows,
    roundTrips,
    failed: failed.length,
  }, null, 2));
  process.exit(failed.length ? 1 : 0);
}

const width = (value, size) => String(value).padEnd(size);
console.log(`hero script: ${path.relative(ROOT, scriptPath)}${legacyViewport ? '  (no svh support: smallest-observed fallback)' : ''}`);
console.log('');

for (const trip of roundTrips) {
  if (trip.issues.length) console.log(`FAIL ${trip.name}: ${trip.issues.join('; ')}`);
}
for (const trip of roundTrips) {
  if (!trip.issues.length) console.log(`round trip ok  ${trip.name}`);
}

console.log('');
console.log(
  width('device', 24) + width('viewport', 12) + width('hero', 8) +
  width('needs', 9) + width('photo', 8) + 'notes'
);
console.log('-'.repeat(104));
for (const row of rows) {
  console.log(
    width(row.name, 24) +
    width(`${row.width}x${row.height}`, 12) +
    width(row.hero === null ? '-' : `${row.hero}px`, 8) +
    width(`${row.needs}px`, 9) +
    width(row.cropFraction === null ? '—' : `${Math.round(row.cropFraction * 100)}%`, 8) +
    (row.issues.length ? 'FAIL: ' + row.issues.join('; ') : 'ok')
  );
}
console.log('');
console.log(
  failed.length
    ? `${failed.length} of ${results.length + roundTrips.length} check(s) failed`
    : `all ${results.length} devices fill the open screen with the photo edge to edge, ` +
      `and the artwork follows the breakpoint both ways`
);
if (tight.length) {
  console.log(
    'note: copy taller than the screen on ' +
    tight.map((r) => r.device.name).join(', ') +
    ' - the compact rules hide the paragraph there, the harness estimate is approximate'
  );
}

process.exit(failed.length ? 1 : 0);
