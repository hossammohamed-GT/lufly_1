#!/usr/bin/env node
/**
 * Hero engine harness - runs the real frontend/home/hero/hero.js against a
 * tiny DOM stub with a virtual clock and asserts the behaviour the design
 * depends on:
 *
 *   1. boot          - frame 1 active, tab 1 pressed, others not
 *   2. image queue   - frames 2..5 are promoted strictly one after another
 *                       (never a burst), frame 1 stays untouched
 *   3. autoplay      - every DWELL ms the next scene fades in (bathroom ->
 *                       kitchen -> shower -> accessories -> smart -> bathroom)
 *   4. click         - a tab click switches at once and restarts the timer
 *   5. hover         - hovering the selector pauses autoplay, leaving resumes
 *   6. reduced motion- no autoplay at all, still switches on click
 *   7. theme        - the light artwork set swaps in (active frame crossfades
 *                     through a ghost, thumbnails follow, queue promotes the
 *                     new variant) and back to dark
 *   8. launch       - the Explore Collections spiral: a plain click is
 *                     intercepted, the section enters is-launching,
 *                     navigation is deferred ~860ms then fires; mid-flight
 *                     clicks, bfcache returns, modified clicks and
 *                     reduced-motion visitors all behave
 *
 *   node tools/frontend_audit/hero_engine_test.mjs
 *
 * The virtual clock advances in 25ms slices and drains the microtask queue
 * between slices, the way a real event loop interleaves tasks and microtasks.
 * Exit code 1 on the first failed assertion.
 */
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const SCRIPT = path.join(ROOT, 'frontend/home/hero/hero.js');

const DWELL = 2000;
const FADE = 1050;

let failures = 0;
const ok = (cond, label) => {
  console.log(`${cond ? 'ok  ' : 'FAIL'}  ${label}`);
  if (!cond) failures++;
};

/* ------------------------------------------------------------------ *
 * virtual clock: advance() runs due timers, tick() also drains
 * microtasks between small slices like a real event loop
 * ------------------------------------------------------------------ */
const timers = new Map();
let clock = 0;
let id = 1;

globalThis.setTimeout = (fn, ms) => {
  timers.set(id, { at: clock + (ms || 0), fn });
  return id++;
};
globalThis.clearTimeout = (tid) => timers.delete(tid);

function runDue(until) {
  for (;;) {
    const next = [...timers.entries()].filter(([, t]) => t.at <= until).sort((a, b) => a[1].at - b[1].at)[0];
    if (!next) break;
    timers.delete(next[0]);
    clock = next[1].at;
    next[1].fn();
  }
  clock = until;
}

async function tick(ms) {
  const until = clock + ms;
  while (clock < until) {
    runDue(Math.min(clock + 25, until));
    for (let i = 0; i < 15; i++) await Promise.resolve();
  }
}

/* ------------------------------------------------------------------ *
 * DOM stub
 * ------------------------------------------------------------------ */
function makeElement(tag) {
  const el = {
    tagName: (tag || 'div').toUpperCase(),
    attrs: {},
    classes: new Set(),
    listeners: {},
    style: { setProperty() {} },
    children: [],
    hidden: false,
    fire(type, extra = {}) {
      (el.listeners[type] || []).slice().forEach((fn) => fn({ target: el, ...extra }));
    },
    addEventListener(type, fn) { (el.listeners[type] = el.listeners[type] || []).push(fn); },
    removeEventListener() {},
    get classList() {
      return {
        add: (...n) => n.forEach((x) => el.classes.add(x)),
        remove: (...n) => n.forEach((x) => el.classes.delete(x)),
        toggle: (n, f) => {
          const on = f === undefined ? !el.classes.has(n) : !!f;
          if (on) el.classes.add(n); else el.classes.delete(n);
          return on;
        },
        contains: (n) => el.classes.has(n),
      };
    },
    get dataset() {
      return new Proxy({}, {
        get: (_, k) => el.attrs['data-' + k],
        set: (_, k, v) => { el.attrs['data-' + String(k)] = String(v); return true; },
      });
    },
    getAttribute: (n) => (n in el.attrs ? el.attrs[n] : null),
    setAttribute: (n, v) => { el.attrs[n] = String(v); },
    removeAttribute: (n) => { delete el.attrs[n]; },
    className: '',
    parentNode: null,
    appendChild(child) { child.parentNode = el; el.children.push(child); return child; },
    removeChild(child) {
      const i = el.children.indexOf(child);
      if (i >= 0) el.children.splice(i, 1);
      child.parentNode = null;
      return child;
    },
    getBoundingClientRect: () => ({ top: 64, height: 836, bottom: 900 }),
    querySelector: () => null,
    querySelectorAll: () => [],
    offsetWidth: 100,
  };
  return el;
}

function makeImage(src, onPromote) {
  const el = makeElement('img');
  if (src) el.attrs.src = src;
  el.complete = !!src;
  el.naturalWidth = src ? 1376 : 0;
  el.decode = () => Promise.resolve();

  const arrive = () => {
    el.complete = true;
    el.naturalWidth = 1376;
    queueMicrotask(() => el.fire('load'));
  };
  Object.defineProperty(el, 'src', {
    get: () => el.attrs.src || '',
    set: (v) => { el.attrs.src = v; if (v) { onPromote && onPromote(); arrive(); } },
  });
  Object.defineProperty(el, 'srcset', {
    get: () => el.attrs.srcset || '',
    set: (v) => { el.attrs.srcset = v; if (v) arrive(); },
  });
  el.cloneNode = function () {
    const twin = makeImage(el.attrs.src || '');
    twin.attrs = { ...el.attrs };
    twin.classes = new Set(el.classes);
    twin.complete = el.complete;
    twin.naturalWidth = el.naturalWidth;
    return twin;
  };
  return el;
}

function buildWorld({ reducedMotion = false } = {}) {
  const root = makeElement('section');
  const frames = [];
  const tabs = [];
  const panels = [];
  const promotionOrder = [];

  const media = makeElement('div');
  const thumbs = [];

  for (let i = 0; i < 5; i++) {
    const frame = i === 0
      ? makeImage('/hero-bathroom.webp')
      : makeImage('', () => promotionOrder.push(i));
    frame.attrs['data-hero-src'] = `/hero-${i}.webp`;
    frame.attrs['data-hero-src-light'] = `/hero-${i}-light.webp`;
    frame.classes.add('hero-frame');
    if (i === 0) frame.classes.add('is-active');
    media.appendChild(frame);
    frames.push(frame);

    const panel = makeElement('div');
    panel.attrs['data-hero-panel'] = String(i);
    if (i === 0) panel.classes.add('is-active');
    panels.push(panel);

    const tab = makeElement('button');
    tab.attrs['data-hero-tab'] = String(i);
    tab.setAttribute('aria-pressed', i === 0 ? 'true' : 'false');
    tabs.push(tab);

    const thumb = makeImage(`/hero-${i}-thumb.webp`);
    thumb.attrs['data-thumb-light'] = `/hero-${i}-light-thumb.webp`;
    thumbs.push(thumb);
  }

  const tabsEl = makeElement('nav');
  tabsEl.attrs['data-hero-tabs'] = '';

  /* the Explore Collections CTA (the launch sequence target) */
  const cta = makeElement('a');
  cta.classes.add('hero-cta--primary');
  cta.attrs.href = '/products';
  root.appendChild(cta);

  root.querySelectorAll = (sel) => {
    if (sel === '.hero-frame') return frames;
    if (sel === '[data-hero-panel]') return panels;
    if (sel === '[data-hero-tab]') return tabs;
    if (sel === '[data-hero-tabs]') return [tabsEl];
    if (sel === '.hero-tab-thumb img') return thumbs;
    return [];
  };
  root.querySelector = (sel) => (sel === '[data-hero-tabs]' ? tabsEl : null)
    || (sel === '.hero-cta--primary' ? cta : null);
  root.addEventListener = () => {};

  const documentElement = makeElement('html');
  documentElement.dir = 'ltr';

  const doc = makeElement('#document');
  doc.querySelector = (sel) => (sel === '[data-hero]' ? root : null);
  doc.querySelectorAll = () => [];
  doc.hidden = false;

  globalThis.MutationObserver = class {
    constructor() {}
    observe() {}
    disconnect() {}
  };

  const winListeners = {};

  globalThis.document = doc;
  globalThis.document.documentElement = documentElement;
  globalThis.window = {
    matchMedia: (q) => ({ matches: q.includes('prefers-reduced-motion') ? reducedMotion : false }),
    requestIdleCallback: (fn) => fn(),
    setTimeout: globalThis.setTimeout,
    clearTimeout: globalThis.clearTimeout,
    location: { href: 'about:blank' },
    addEventListener: (type, fn) => {
      (winListeners[type] = winListeners[type] || []).push(fn);
    },
    IntersectionObserver: class {
      constructor(cb) { this.cb = cb; }
      observe() { this.cb([{ isIntersecting: true }]); }
    },
  };

  return { root, media, frames, tabs, panels, tabsEl, thumbs, promotionOrder, doc, documentElement, cta, winListeners, window: globalThis.window };
}

function loadEngine() {
  const code = fs.readFileSync(SCRIPT, 'utf8');
  new Function('window', 'document', 'Promise', code)(globalThis.window, globalThis.document, Promise);
}

const active = (world) => world.frames.findIndex((f) => f.classList.contains('is-active'));

/* ------------------------------------------------------------------ *
 * run
 * ------------------------------------------------------------------ */

/* --- boot + queue + autoplay + click + hover ------------------------- */
{
  const world = buildWorld();
  loadEngine();
  await tick(50);

  ok(active(world) === 0, 'boot: frame 1 is active');
  ok(world.tabs[0].getAttribute('aria-pressed') === 'true', 'boot: tab 1 pressed');
  ok(world.tabs.slice(1).every((t) => t.getAttribute('aria-pressed') === 'false'), 'boot: other tabs released');

  ok(world.promotionOrder.length >= 1, 'queue: promotion starts right after first paint');
  /* the stubbed loads resolve in microtasks, so the whole chain settles
     long before the first autoplay timer (DWELL) fires */
  await tick(100);
  ok(JSON.stringify(world.promotionOrder) === '[1,2,3,4]',
    'queue: frames promoted strictly one after another (got ' + JSON.stringify(world.promotionOrder) + ')');
  ok(!('src' in world.frames[0].attrs) || world.frames[0].getAttribute('src') === '/hero-bathroom.webp',
    'queue: the eager first frame is never re-fetched');
  ok(world.frames.every((f) => f.getAttribute('src') === `/hero-${world.frames.indexOf(f)}.webp`
      || world.frames.indexOf(f) === 0),
    'queue: every promoted frame gets the full master-quality src');
  ok(world.frames.every((f) => !f.attrs.srcset),
    'policy: no srcset ladder is ever written - one master file per scene');

  /* autoplay order + timing (clock is now ~t+150ms) */
  await tick(DWELL - 100 + 60);
  ok(active(world) === 1, 'autoplay: kitchen fades in after the dwell');
  await tick(DWELL - 200);
  ok(active(world) === 1, 'autoplay: the scene holds through its dwell');
  await tick(FADE + DWELL + 200);
  ok(active(world) === 2, 'autoplay: shower after dwell + fade');
  ok(world.tabs[2].getAttribute('aria-pressed') === 'true', 'autoplay: tab state follows the scene');

  /* click: jumps immediately and restarts the timer */
  world.tabs[4].fire('click');
  await tick(40);
  ok(active(world) === 4, 'click: smart scene shows at once');
  await tick(DWELL - 300);
  ok(active(world) === 4, 'click: the timer restarted from the clicked scene');
  await tick(FADE + 400);
  ok(active(world) === 0, 'click: wraps back to bathroom');

  /* hover pause / resume */
  world.tabsEl.fire('mouseenter');
  await tick(DWELL * 3 + FADE * 2);
  ok(active(world) === 0, 'hover: autoplay paused while aiming');
  world.tabsEl.fire('mouseleave');
  await tick(DWELL + FADE + 200);
  ok(active(world) === 1, 'hover: autoplay resumes after leaving');
}

/* --- reduced motion --------------------------------------------------- */
{
  const world = buildWorld({ reducedMotion: true });
  loadEngine();
  await tick(DWELL * 6);

  ok(active(world) === 0, 'reduced motion: no autoplay');

  world.tabs[3].fire('click');
  await tick(400);
  ok(active(world) === 3, 'reduced motion: click still switches');
}

/* --- theme: dark -> light -> dark -------------------------------------- */
{
  const world = buildWorld();
  loadEngine();
  await tick(300);                       /* queue settles, all frames loaded */
  world.frames[0].dataset.heroDone = '1'; /* so the swap takes the ghost path */

  const ghostsBefore = world.media.children.length;
  world.doc.fire('lufly:theme', { detail: { theme: 'light' } });
  await tick(50);

  ok(world.frames[0].attrs.src === '/hero-0-light.webp',
    'theme: the active frame re-points to the light artwork');
  ok(world.thumbs.every((t) => t.attrs.src === t.attrs['data-thumb-light']),
    'theme: every tab thumbnail follows');
  ok(world.media.children.length === ghostsBefore + 1,
    'theme: a ghost carries the outgoing artwork');
  ok(world.frames[1].attrs.src === '/hero-1-light.webp',
    'theme: already-promoted frames re-point quietly');

  /* the ghost fades out and is removed once the new artwork is in */
  await tick(600);
  ok(world.media.children.length === ghostsBefore,
    'theme: the ghost is cleaned up after the crossfade');

  /* and back to dark */
  world.frames[0].dataset.heroDone = '1';
  world.doc.fire('lufly:theme', { detail: { theme: 'dark' } });
  await tick(50);
  ok(world.frames[0].attrs.src === '/hero-0.webp',
    'theme: switching back re-points to the dark artwork');
  ok(world.thumbs.every((t) => t.attrs.src === `/hero-${world.thumbs.indexOf(t)}-thumb.webp`),
    'theme: thumbnails return to the dark set');
}

/* --- theme: a visitor whose theme is light from the start --------------- */
{
  const world = buildWorld();
  world.documentElement.attrs['data-theme'] = 'light';
  loadEngine();
  await tick(50);

  ok(world.frames[0].attrs.src === '/hero-0-light.webp',
    'light boot: the first frame swaps before the first paint');
  ok(world.frames[0].classList.contains('is-active'), 'light boot: scene 1 still active');
}

/* --- launch: the Explore Collections spiral ---------------------------- */
{
  const world = buildWorld();
  loadEngine();
  await tick(50);

  let prevented = 0;
  const plainClick = (over = {}) => ({
    button: 0, metaKey: false, ctrlKey: false, shiftKey: false, altKey: false,
    defaultPrevented: false,
    preventDefault() { prevented++; this.defaultPrevented = true; },
    ...over,
  });

  world.cta.fire('click', plainClick());

  ok(prevented === 1, 'launch: a plain click is swallowed for the show');
  ok(world.root.classes.has('is-launching'),
    'launch: the hero enters the launching state');
  ok(world.window.location.href === 'about:blank',
    'launch: navigation is deferred while the spiral plays');

  /* a mid-flight double click must never race the choreography */
  world.cta.fire('click', plainClick());
  ok(prevented === 2 && world.window.location.href === 'about:blank',
    'launch: a mid-flight click is swallowed, navigation still waits');

  await tick(1000);
  ok(world.window.location.href === '/products',
    'launch: the catalogue takes over after the sequence (~980ms)');

  /* bfcache return: the hero must come back clean for an encore */
  (world.winListeners.pageshow || []).forEach((fn) => fn({ persisted: true }));
  ok(!world.root.classes.has('is-launching'),
    'launch: a bfcache return clears the launching state');

  /* modified clicks keep native behaviour (new tab / new window) */
  const world2 = buildWorld();
  loadEngine();
  await tick(50);
  let modPrevented = 0;
  world2.cta.fire('click', {
    button: 0, metaKey: true, ctrlKey: false, shiftKey: false, altKey: false,
    defaultPrevented: false,
    preventDefault() { modPrevented++; },
  });
  ok(modPrevented === 0 && !world2.root.classes.has('is-launching'),
    'launch: modified clicks (new tab) are left to the browser');

  /* reduced motion: straight navigation, no show at all */
  const world3 = buildWorld({ reducedMotion: true });
  loadEngine();
  await tick(50);
  let rmPrevented = 0;
  world3.cta.fire('click', {
    button: 0, metaKey: false, ctrlKey: false, shiftKey: false, altKey: false,
    defaultPrevented: false,
    preventDefault() { rmPrevented++; },
  });
  ok(rmPrevented === 0 && !world3.root.classes.has('is-launching'),
    'launch: reduced-motion visitors navigate without the show');
}

console.log(failures ? `\n${failures} assertion(s) failed` : '\nall assertions passed');
process.exit(failures ? 1 : 0);
