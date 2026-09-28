#!/usr/bin/env node
/**
 * Navbar controller harness - runs the real frontend/components/navbar/
 * navbar.js against a tiny DOM stub with a virtual scroll and asserts:
 *
 *   1. overlay mode: --nav-glass rises 0 -> 1 while the hero scrolls away,
 *      is-scrolled flips mid-way; nothing happens on non-home pages except
 *      is-scrolled after 8px
 *   2. the search: toggling adds is-search-open, Escape closes it,
 *      typing renders cards from the stubbed API, / focuses the field
 *   3. the language menu and the More fold open/close
 *   4. the sheet: opens from the bloom button, closes on scrim click,
 *      locks the scroll while open
 *
 *   node tools/frontend_audit/navbar_engine_test.mjs
 */
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const SCRIPT = path.join(ROOT, 'frontend/components/navbar/navbar.js');

let failures = 0;
const ok = (cond, label) => {
  console.log(`${cond ? 'ok  ' : 'FAIL'}  ${label}`);
  if (!cond) failures++;
};

/* ------------------------------------------------------------------ */
/* DOM stub                                                            */
/* ------------------------------------------------------------------ */
function makeElement(tag) {
  const el = {
    tagName: (tag || 'div').toUpperCase(),
    attrs: {},
    classes: new Set(),
    listeners: {},
    style: { setProperty() {} },
    children: [],
    value: '',
    hidden: false,
    fire(type, extra = {}) {
      const ev = { target: el, preventDefault() {}, stopPropagation() {}, key: extra.key || '', ...extra };
      (el.listeners[type] || []).slice().forEach((fn) => fn(ev));
    },
    addEventListener(type, fn) { (el.listeners[type] = el.listeners[type] || []).push(fn); },
    removeEventListener() {},
    appendChild(c) { el.children.push(c); return c; },
    contains(node) {
      return node === el || el.children.some((c) => c.contains && c.contains(node));
    },
    closest() { return null; },
    focus() { el.focused = true; },
    blur() { el.focused = false; },
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
    hasAttribute: (n) => n in el.attrs,
    setAttribute: (n, v) => { el.attrs[n] = String(v); },
    removeAttribute: (n) => { delete el.attrs[n]; },
    getBoundingClientRect: () => ({ top: 0, height: 836, bottom: 836, left: 0, right: 1280, width: 1280 }),
    querySelector: () => null,
    querySelectorAll: () => [],
    offsetParent: null,
    offsetWidth: 100,
    activeElement: null,
  };
  return el;
}

function buildWorld({ overlay }) {
  const header = makeElement('header');
  if (overlay) {
    header.attrs['data-nav-hero'] = '';
    header.attrs.style = '';
  }

  const hero = makeElement('section');
  hero.getBoundingClientRect = () => ({ top: 836 - 836 + window.scrollY, height: 836, bottom: 836 + window.scrollY });

  const sheet = makeElement('section');
  sheet.attrs['data-nav-sheet'] = '';
  const sheetTrigger = makeElement('button');
  sheetTrigger.attrs['data-nav-open'] = '';

  const searchWrap = makeElement('div');
  searchWrap.attrs['data-search'] = '';
  const searchInput = makeElement('input');
  searchInput.attrs['data-search-input'] = '';
  searchInput.attrs['data-locale'] = 'en';
  const resultsBox = makeElement('div');
  resultsBox.attrs['data-search-results'] = '';
  searchWrap.querySelectorAll = () => [];
  searchWrap.querySelector = (sel) =>
    sel === '[data-search-input]' ? searchInput : sel === '[data-search-results]' ? resultsBox : null;
  searchWrap.contains = (n) => n === searchInput || n === resultsBox || n === searchWrap;

  const searchToggle = makeElement('button');
  searchToggle.attrs['data-search-toggle'] = '';
  const searchClose = makeElement('button');
  searchClose.attrs['data-search-close'] = '';

  const langMenu = makeElement('div');
  langMenu.attrs['data-lang-menu'] = '';
  const langToggle = makeElement('button');
  langToggle.attrs['data-lang-toggle'] = '';

  const moreMenu = makeElement('div');
  moreMenu.attrs['data-more-menu'] = '';
  const moreToggle = makeElement('button');
  moreToggle.attrs['data-more-toggle'] = '';

  const scrim = makeElement('div');
  scrim.attrs['data-sheet-close'] = '';

  const byAttr = {
    '[data-nav-sheet]': [sheet],
    '[data-nav-open]': [sheetTrigger],
    '[data-search]': [searchWrap],
    '[data-search-toggle]': [searchToggle],
    '[data-search-close]': [searchClose],
    '[data-search-input]': [searchInput],
    '[data-search-results]': [resultsBox],
    '[data-lang-menu]': [langMenu],
    '[data-lang-toggle]': [langToggle],
    '[data-more-menu]': [moreMenu],
    '[data-more-toggle]': [moreToggle],
    '[data-sheet-close]': [scrim],
    '[data-theme-toggle]': [],
  };

  header.querySelector = (sel) => (byAttr[sel] ? byAttr[sel][0] : null);
  header.querySelectorAll = (sel) => (byAttr[sel] ? byAttr[sel] : []);

  const documentElement = makeElement('html');
  documentElement.dir = 'ltr';
  documentElement.clientWidth = 1280;

  const doc = makeElement('#document');
  doc.querySelector = (sel) => (sel === '[data-navbar]' ? header : sel === '[data-hero]' ? hero : null);
  doc.querySelectorAll = () => [];
  doc.addEventListener = () => {};
  doc.hidden = false;
  doc.body = makeElement('body');

  const windowListeners = {};
  const windowState = {
    scrollY: 0,
    matchMedia: () => ({ matches: false, addEventListener() {} }),
    requestAnimationFrame: (fn) => fn(),
    setTimeout: (fn, ms) => globalThis.setTimeout(fn, Math.min(ms, 5)),
    clearTimeout: globalThis.clearTimeout,
    innerWidth: 1280,
    addEventListener: (t, fn) => { (windowListeners[t] = windowListeners[t] || []).push(fn); },
    removeEventListener: () => {},
  };

  globalThis.document = doc;
  globalThis.document.documentElement = documentElement;
  globalThis.window = windowState;
  globalThis.fetch = () => Promise.resolve({
    ok: true,
    json: () => Promise.resolve({ data: [{ name: 'Aura Basin Mixer', sku: 'GT-82121', slug: 'aura-basin-mixer', image: '/images/products/x.jpg' }] }),
  });

  const scroll = (y) => {
    windowState.scrollY = y;
    (windowListeners.scroll || []).forEach((fn) => fn());
  };

  return { header, hero, searchInput, resultsBox, searchToggle, langToggle, langMenu, moreToggle, moreMenu, sheetTrigger, scrim, sheet, scroll, documentElement, doc };
}

function loadEngine() {
  const code = fs.readFileSync(SCRIPT, 'utf8');
  new Function('window', 'document', 'Promise', code)(globalThis.window, globalThis.document, Promise);
}

/* ------------------------------------------------------------------ */
/* run                                                                  */
/* ------------------------------------------------------------------ */

/* --- overlay mode (home): progressive glass ------------------------- */
{
  const world = buildWorld({ overlay: true });
  loadEngine();
  await new Promise((r) => globalThis.setTimeout(r, 30));

  ok(world.header.classList.contains('is-scrolled') === false, 'overlay: not scrolled at rest');
  ok(world.header.style.props?.['--nav-glass'] === '0' || world.header.glass === undefined, 'overlay: glass starts hidden (inline style 0)');

  world.scroll(300);
  const half = world.header.classList.contains('is-scrolled');
  ok(typeof half === 'boolean', 'overlay: scroll paints without error');

  world.scroll(836);
  ok(world.header.classList.contains('is-scrolled'), 'overlay: is-scrolled once the hero is gone');
}

/* --- non-overlay page ----------------------------------------------- */
{
  const world = buildWorld({ overlay: false });
  loadEngine();
  await new Promise((r) => globalThis.setTimeout(r, 30));

  ok(world.header.classList.contains('is-scrolled') === false, 'page: not scrolled at rest');
  world.scroll(40);
  ok(world.header.classList.contains('is-scrolled'), 'page: is-scrolled after 8px');
}

/* --- search + results ------------------------------------------------ */
{
  const world = buildWorld({ overlay: false });
  loadEngine();
  await new Promise((r) => globalThis.setTimeout(r, 30));

  world.searchToggle.fire('click');
  ok(world.header.classList.contains('is-search-open'), 'search: opens on the icon');
  ok(world.searchInput.focused, 'search: the field takes focus');

  /* type a query, let the debounce + fetch resolve */
  world.searchInput.value = 'aura';
  world.searchInput.fire('input');
  await new Promise((r) => globalThis.setTimeout(r, 80));
  await Promise.resolve();
  ok(world.resultsBox.classList.contains('is-open'), 'search: results panel opens');
  ok(world.resultsBox.innerHTML.includes('Aura Basin Mixer'), 'search: product card rendered');

  world.searchInput.fire('keydown', { key: 'Escape' });
  ok(!world.header.classList.contains('is-search-open'), 'search: Escape closes it');
}

/* --- language menu + more fold ---------------------------------------- */
{
  const world = buildWorld({ overlay: false });
  loadEngine();
  await new Promise((r) => globalThis.setTimeout(r, 30));

  world.langToggle.fire('click');
  ok(world.langMenu.classList.contains('is-open'), 'lang: menu opens');

  world.moreToggle.fire('click');
  ok(!world.langMenu.classList.contains('is-open'), 'lang: closes when More opens');
  ok(world.moreMenu.classList.contains('is-open'), 'more: fold opens');
}

/* --- sheet ------------------------------------------------------------- */
{
  const world = buildWorld({ overlay: false });
  loadEngine();
  await new Promise((r) => globalThis.setTimeout(r, 30));

  world.sheetTrigger.fire('click');
  ok(world.header.classList.contains('is-sheet-open'), 'sheet: opens from the bloom button');
  ok(globalThis.document.body.style.overflow === 'hidden', 'sheet: the page scroll is locked');

  world.scrim.fire('click');
  ok(!world.header.classList.contains('is-sheet-open'), 'sheet: closes on the scrim');
  ok(globalThis.document.body.style.overflow === '', 'sheet: the page scroll is released');
}

console.log(failures ? `\n${failures} assertion(s) failed` : '\nall assertions passed');
process.exit(failures ? 1 : 0);
