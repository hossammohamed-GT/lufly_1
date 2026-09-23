#!/usr/bin/env node
/**
 * Home page CSS audit — resolves the stylesheets the home page actually loads
 * for a list of device viewports and prints the declarations that decide
 * layout: grid columns, fixed heights, aspect ratios and type sizes.
 *
 * There is no browser here, so this is a small cascade evaluator: it parses
 * the rules (including nested @media / @supports), keeps the last matching
 * declaration per property, and reports the result per viewport. It is meant
 * to catch the classes of bug that only show up on a small screen:
 *
 *   * a fixed height that is taller than the device viewport
 *   * a grid that still asks for 4–5 columns on a 320–414px screen
 *   * a phone never reaching the responsive rules because a query is wrong
 *   * images taller than the viewport because an aspect ratio was dropped
 *
 * usage:
 *   node tools/frontend_audit/css_audit.mjs                # home page defaults
 *   node tools/frontend_audit/css_audit.mjs --widths 320,390 --heights 568,844
 */
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');

const args = process.argv.slice(2);
const readArg = (name, fallback) => {
  const i = args.indexOf(name);
  return i >= 0 ? args[i + 1] : fallback;
};

const WIDTHS = readArg('--widths', '320,360,390,412,430,480,640,768,1024,1280').split(',').map(Number);
const HEIGHTS = readArg('--heights', '568,740,844,915,932,1024,900').split(',').map(Number);

/* stylesheets the home page pulls in, in load order */
const SHEETS = [
  'frontend/design-system/style.css',
  'frontend/css/app.css',
  'frontend/components/loader/loader.css',
  'frontend/components/announcement/announcement.css',
  'frontend/components/navbar/navbar.css',
  'frontend/home/hero-cinema/hero-cinema.css',
  'frontend/home/trust-bar/trust-bar.css',
  'frontend/home/finishes/finishes.css',
  'frontend/home/categories/categories.css',
  'frontend/home/inspiration/inspiration.css',
  'frontend/home/rituals/rituals.css',
  'frontend/css/product-card.css',
  'frontend/home/masterpieces/masterpieces.css',
  'frontend/home/corporate/corporate.css',
];

/* what to report: selector -> properties that decide its layout */
const TARGETS = [
  ['hero', '.lfc', ['height', 'min-height']],
  ['hero art', '.lfc-scene', ['inset']],
  ['hero panel', '.lfc-panel', ['padding-top', 'padding-bottom']],
  ['hero title', '.lfc-title', ['font-size']],
  ['trust grid', '.trust-grid', ['grid-template-columns']],
  ['finish swatches', '.finishes-swatch-grid', ['grid-template-columns']],
  ['finish showcase', '.finish-showcase-img', ['min-height', 'aspect-ratio']],
  ['category mosaic', '.category-mosaic', ['grid-template-columns']],
  ['category tile', '.category-card-monolith', ['height']],
  ['inspiration grid', '.inspiration-carousel', ['grid-template-columns']],
  ['inspiration art', '.inspiration-img-wrap', ['height']],
  ['rituals grid', '.rituals-grid', ['grid-template-columns']],
  ['ritual art', '.ritual-figure', ['height']],
  ['product grid', '.catalog-grid', ['grid-template-columns']],
  ['product card art', '.pcard-media', ['aspect-ratio']],
  ['masterpieces grid', '.masterpieces-grid', ['grid-template-columns']],
  ['footer grid', '.footer-grid', ['grid-template-columns']],
  ['main region', '.ds-main', ['margin-inline-start', 'padding-inline-start']],
  ['hero panel', '.lfc-panel', ['padding-top', 'padding-bottom']],
  ['hero logo box', '.lfc-logoBox', ['width', 'margin-bottom', 'display']],
  ['hero kicker', '.lfc-kicker', ['display']],
  ['hero tag', '.lfc-tag', ['display']],
  ['hero CTAs', '.lfc-ctas', ['margin-top', 'flex-wrap']],
  ['hero CTA', '.lfc-cta', ['padding-top', 'font-size']],
  ['nav drawer', '.mnav-drawer', ['height', 'inline-size']],
  ['nav sheet', '.mnav-sheet', ['max-height', 'height']],
  ['nav results', '.mnav-results', ['max-height']],
];

/* ------------------------------------------------------------------ *
 * a very small CSS parser: handles one level of nested at-rules, which is
 * all the project uses
 * ------------------------------------------------------------------ */
function stripComments(css) {
  return css.replace(/\/\*[\s\S]*?\*\//g, '');
}

function parseRules(css, conditions = [], rules = []) {
  let i = 0;
  const text = stripComments(css);

  while (i < text.length) {
    const open = text.indexOf('{', i);
    if (open === -1) break;
    const prelude = text.slice(i, open).trim();
    const close = matchingBrace(text, open);
    if (close === -1) break;
    const body = text.slice(open + 1, close);

    if (prelude.startsWith('@')) {
      const name = prelude.slice(1).split(/\s+/)[0].toLowerCase();
      if (['media', 'supports', 'container', 'layer'].includes(name)) {
        const condition = prelude.slice(1 + name.length).trim();
        parseRules(body, name === 'media' ? conditions.concat(condition) : conditions, rules);
      }
    } else if (prelude && !prelude.startsWith('@keyframes') && !prelude.includes('%')) {
      rules.push({ conditions, selector: prelude, body });
    }

    i = close + 1;
  }

  return rules;
}

function matchingBrace(text, open) {
  let depth = 0;
  for (let i = open; i < text.length; i++) {
    if (text[i] === '{') depth++;
    else if (text[i] === '}') {
      depth--;
      if (depth === 0) return i;
    }
  }
  return -1;
}

function declarations(body) {
  const out = new Map();
  for (const chunk of body.split(';')) {
    const colon = chunk.indexOf(':');
    if (colon === -1) continue;
    const prop = chunk.slice(0, colon).trim().toLowerCase();
    const value = chunk.slice(colon + 1).trim();
    if (!prop || !value || prop.startsWith('@')) continue;
    const important = /!important$/i.test(value);
    out.set(prop, { value: value.replace(/!important$/i, '').trim(), important });
  }
  return out;
}

function mediaMatches(condition, ctx) {
  if (!condition) return true;

  return condition.split(/\s+and\s+/i).every((part) => {
    const clause = part.trim().replace(/^\(/, '').replace(/\)$/, '');
    const colon = clause.indexOf(':');
    if (colon === -1) return true;
    const feature = clause.slice(0, colon).trim().toLowerCase();
    const value = clause.slice(colon + 1).trim().toLowerCase();

    if (feature === 'max-width') return ctx.width <= px(value);
    if (feature === 'min-width') return ctx.width >= px(value);
    if (feature === 'max-height') return ctx.height <= px(value);
    if (feature === 'min-height') return ctx.height >= px(value);
    if (feature === 'orientation') return value === 'portrait' ? ctx.height >= ctx.width : ctx.width > ctx.height;
    if (feature === 'hover') return value === 'none' ? ctx.touch : !ctx.touch;
    if (feature === 'pointer') return ctx.touch ? value === 'coarse' : value === 'fine';
    if (feature === 'prefers-reduced-motion') return false;
    return true;
  });
}

const px = (value) => parseFloat(String(value).replace(/px$/, '')) || 0;

/* resolve length functions the checks care about: min(), max(), clamp()
   with vh/vw/svh/lvh/dvh against the simulated viewport */
function resolveLength(value, ctx) {
  if (!value) return null;
  let expr = String(value);

  expr = expr.replace(/([0-9.]+)s?[dl]?vh/g, (_, n) => String((parseFloat(n) / 100) * ctx.height));
  expr = expr.replace(/([0-9.]+)(?:s?[dl]?vw|%)/g, (_, n) => String((parseFloat(n) / 100) * ctx.width));

  const min = expr.match(/^min\(([^)]+)\)$/);
  if (min) return Math.min(...min[1].split(',').map((part) => parseFloat(part)));

  const max = expr.match(/^max\(([^)]+)\)$/);
  if (max) return Math.max(...max[1].split(',').map((part) => parseFloat(part)));

  const clamp = expr.match(/^clamp\(([^)]+)\)$/);
  if (clamp) {
    const [lo, mid, hi] = clamp[1].split(',').map((part) => parseFloat(part));
    return Math.min(Math.max(mid, lo), hi);
  }

  const plain = parseFloat(expr);
  return Number.isFinite(plain) ? plain : null;
}

const shortProp = (prop) => prop
  .replace('grid-template-columns', 'cols')
  .replace('aspect-ratio', 'ratio')
  .replace('padding-', 'pad-')
  .replace('margin-inline-start', 'margin-start');

function resolve(rules, selector, properties, ctx) {
  const wanted = new Set(properties);
  const out = {};

  for (const rule of rules) {
    if (!selectorMatches(rule.selector, selector)) continue;
    if (!rule.conditions.every((condition) => mediaMatches(condition, ctx))) continue;

    for (const [prop, { value }] of declarations(rule.body)) {
      if (wanted.has(prop)) out[prop] = value;
    }
  }

  return out;
}

/* selectors in this project are single-class or element+class chains; a rule
   applies to the element when its last compound selector carries the class */
function selectorMatches(ruleSelector, target) {
  const targetClass = target.replace(/^\./, '');
  const exact = new RegExp('\\.' + targetClass + '(?![-\\w])'); /* .lfc, not .lfc-arrow */
  return ruleSelector
    .split(',')
    .map((part) => part.trim())
    /* an ::after / ::before rule paints a box the element does not own, and
       hover/state selectors never decide the base layout - skip both */
    .filter((part) => !part.includes('::'))
    .map((part) => part.replace(/:hover|:focus[^\s>+~]*|:active|:first-child|:last-child|:nth-child\([^)]*\)|:not\([^)]*\)|:is\([^)]*\)/gi, '').trim())
    .some((part) => {
      const last = part.split(/[\s>+~]+/).filter(Boolean).pop() || '';
      return exact.test(last);
    });
}

/* ------------------------------------------------------------------ */
const rules = [];
for (const sheet of SHEETS) {
  const file = path.join(ROOT, sheet);
  if (!fs.existsSync(file)) {
    console.log(`!! missing stylesheet ${sheet}`);
    continue;
  }
  parseRules(fs.readFileSync(file, 'utf8'), [], rules);
}

const devices = [];
for (const width of WIDTHS) {
  for (const height of HEIGHTS) {
    if (width >= 768 && height > 900 && height < 1000) continue; /* keep the table short */
    devices.push({ width, height, touch: width <= 1024 });
  }
}

const issues = [];
const problems = [];
const notes = [];
const reported = new Set();

/* `::after` and `::before` boxes are decorative; the scan only cares about
   rules that can widen the element itself */
function selectorIsPseudoOnly(selector) {
  return selector
    .split(',')
    .every((part) => part.includes('::'));
}

for (const device of devices) {
  const ctx = { width: device.width, height: device.height, touch: device.touch };
  const row = { device: `${device.width}x${device.height}`, values: {} };

  for (const [label, selector, props] of TARGETS) {
    const resolved = resolve(rules, selector, props, ctx);
    row.values[label] = props.map((p) => `${shortProp(p)}=${resolved[p] ?? '—'}`).join(' ');
  }

  /* --- checks --- */
  const heroHeight = resolve(rules, '.lfc', ['height', 'min-height'], ctx);
  const heroFloor = resolveLength(heroHeight['min-height'], ctx);
  if (heroFloor !== null && heroFloor > device.height) {
    problems.push(`${row.device}: hero floor ${heroHeight['min-height']} resolves to ${Math.round(heroFloor)}px - taller than the viewport (${device.height}px)`);
  }
  const heroCssHeight = resolveLength(heroHeight.height, ctx);
  if (heroCssHeight !== null && heroCssHeight > device.height) {
    problems.push(`${row.device}: hero CSS height ${heroHeight.height} resolves to ${Math.round(heroCssHeight)}px - taller than the viewport`);
  }

  /* A tall band of art is only a problem where it has to share the screen with
     the hero: below the fold the page is meant to scroll, so those stay notes. */
  for (const [label, selector] of [['category tile', '.category-card-monolith'], ['inspiration art', '.inspiration-img-wrap'], ['ritual art', '.ritual-figure']]) {
    const decls = resolve(rules, selector, ['height'], ctx);
    const value = resolveLength(decls.height, ctx) || 0;
    if (value > device.height * 0.75 && device.width <= 480) {
      notes.push(`${row.device}: ${label} height ${decls.height} is ${Math.round((value / device.height) * 100)}% of the screen - it fills the first screen on its own, the page just scrolls`);
    }
  }

  for (const [label, selector] of [
    ['category mosaic', '.category-mosaic'],
    ['inspiration grid', '.inspiration-carousel'],
    ['rituals grid', '.rituals-grid'],
    ['product grid', '.catalog-grid'],
    ['masterpieces grid', '.masterpieces-grid'],
    ['trust grid', '.trust-grid'],
    ['finish swatches', '.finishes-swatch-grid'],
    ['footer grid', '.footer-grid'],
  ]) {
    const decls = resolve(rules, selector, ['grid-template-columns'], ctx);
    const columns = countColumns(decls['grid-template-columns'] || '');
    if (columns && device.width <= 480 && columns > 2) {
      problems.push(`${row.device}: ${label} still asks for ${columns} columns`);
    }
  }

  /* --- generic horizontal overflow scan ------------------------------------
     Anything that asks for more pixels than the screen has (a fixed width, a
     min-width floor, `100vw` next to horizontal padding) pushes the page
     sideways. The design system clips overflow-x, but that only hides the
     scrollbar - the content still gets cut. */
  for (const rule of rules) {
    if (!rule.conditions.every((condition) => mediaMatches(condition, ctx))) continue;
    if (selectorIsPseudoOnly(rule.selector)) continue;

    const decls = {};
    for (const [prop, { value }] of declarations(rule.body)) decls[prop] = value;

    const fixed = ['width', 'min-width', 'inline-size', 'min-inline-size', 'flex-basis'];
    for (const prop of fixed) {
      if (!(prop in decls)) continue;
      const value = resolveLength(decls[prop], ctx);
      if (value === null || value <= device.width + 1) continue;
      const where = rule.selector.split(',')[0].trim();
      /* only the first report per selector per device - one line is enough */
      const key = `${row.device}|${where}|${prop}`;
      if (reported.has(key)) continue;
      reported.add(key);
      problems.push(`${row.device}: ${where} { ${prop}: ${decls[prop]} } wants ${Math.round(value)}px on a ${device.width}px screen`);
    }

    const padded = (parseFloat(decls['padding-inline'] || decls['padding'] || '0') || 0)
      + (parseFloat(decls['padding-left'] || decls['padding-inline-start'] || '0') || 0)
      + (parseFloat(decls['padding-right'] || decls['padding-inline-end'] || '0') || 0);
    if ((decls.width === '100vw' || decls['inline-size'] === '100vw') && padded > 0) {
      const where = rule.selector.split(',')[0].trim();
      const key = `${row.device}|${where}|100vw+padding`;
      if (!reported.has(key)) {
        reported.add(key);
        problems.push(`${row.device}: ${where} is 100vw wide with ${padded}px of horizontal padding - ${Math.round(padded)}px of horizontal scroll`);
      }
    }
  }

  issues.push(row);
}

function countColumns(value) {
  if (!value || value.includes('—')) return 0;
  const repeat = value.match(/repeat\(\s*([0-9.]+)\s*,/i);
  if (repeat) return Math.floor(parseFloat(repeat[1]));
  /* split on spaces that are not inside parentheses */
  let depth = 0;
  let count = 0;
  let token = false;
  for (const ch of value) {
    if (ch === '(') depth++;
    else if (ch === ')') depth--;
    if (/\s/.test(ch) && depth === 0) {
      if (token) count++;
      token = false;
    } else {
      token = true;
    }
  }
  return token ? count + 1 : count;
}

/* print one table per width so the phone columns stay readable */
const byWidth = new Map();
for (const row of issues) {
  const width = row.device.split('x')[0];
  if (!byWidth.has(width)) byWidth.set(width, []);
  byWidth.get(width).push(row);
}

for (const [width, rows] of byWidth) {
  console.log(`\n=== width ${width}px ===`);
  for (const row of rows) {
    console.log(`  ${row.device}`);
    for (const [label, value] of Object.entries(row.values)) {
      if (value.includes('—')) continue;
      console.log(`      ${label.padEnd(18)} ${value}`);
    }
  }
}

console.log('');
if (problems.length) {
  console.log(`${problems.length} layout problem(s):`);
  for (const problem of problems) console.log('  - ' + problem);
} else {
  console.log('no layout problems detected at any tested viewport');
}

if (notes.length) {
  console.log('');
  console.log(`${notes.length} note(s) - not defects, just things worth knowing:`);
  for (const note of notes) console.log('  - ' + note);
}
