#!/usr/bin/env node
/**
 * Home band audit - photo or plain, in the order the page really renders them.
 *
 * The home page alternates photographic bands with flat ones. Two neighbouring
 * photo bands read as one long image, and a band that is supposed to be flat
 * but silently borrows a photograph (or the other way round) is invisible in a
 * code review - it only shows up as "the design I made got scrambled".
 *
 * The order is read from resources/views/home/index.php, and each band's
 * background is read from its own CSS (or, for the hero, from its JS), so this
 * check fails when the files and the intent drift apart.
 *
 *   node tools/frontend_audit/band_audit.mjs [--json]
 */

import fs from 'node:fs';
import path from 'node:path';

const root = process.cwd();
const asJson = process.argv.includes('--json');

/* band => how its background is produced.
   `photo`  = a photograph is painted behind the band (file must exist)
   `plain`  = no photograph at all
   `hero`   = the hero artwork, chosen at runtime by hero-cinema.js */
const BANDS = {
  'hero-cinema': { kind: 'hero' },
  'trust-bar': { kind: 'plain' },
  finishes: { kind: 'plain' },
  categories: { kind: 'photo', file: 'images/lifestyle/categories-backdrop.jpg' },
  inspiration: { kind: 'plain' },
  rituals: { kind: 'photo', file: 'images/lifestyle/rituals-backdrop.jpg' },
  masterpieces: { kind: 'plain' },
  corporate: { kind: 'photo', file: 'images/lifestyle/corporate-backdrop.jpg' },
};

const read = (p) => fs.readFileSync(path.join(root, p), 'utf8');

/* ---------- the order the components are rendered in ---------- */

const index = read('resources/views/home/index.php');
const order = [...index.matchAll(/\$component\('([a-z-]+)'/g)].map((m) => m[1]);

/* `images/` is a symlink to `public/images/`, so both the stylesheet paths and
   the checks below see the same file whichever way it is written. */

/* ---------- what each band's stylesheet actually paints ---------- */

const cssFor = (band) => `frontend/home/${band}/${band}.css`;
const urlsIn = (css) => [...css.matchAll(/url\((['"]?)([^'")]+)\1\)/g)].map((m) => m[2]);

const problems = [];
const rows = [];

if (order.length === 0) {
  problems.push('resources/views/home/index.php: no $component(...) calls found - cannot read the band order');
}

for (const band of order) {
  const intent = BANDS[band];
  if (!intent) {
    problems.push(`${band}: not listed in this audit - add it (photo or plain?)`);
    continue;
  }

  const file = cssFor(band);
  const css = fs.existsSync(path.join(root, file)) ? read(file) : '';
  if (css === '') problems.push(`${file}: missing`);

  /* comments describe the artwork, they do not paint it */
  const painted = urlsIn(css.replace(/\/\*[\s\S]*?\*\//g, ''));

  if (intent.kind === 'plain') {
    if (painted.length) {
      problems.push(`${band}: meant to be photo-free but ${file} paints ${painted.join(', ')}`);
    }
    rows.push([band, 'plain', painted.length ? painted.join(' ') : '-']);
    continue;
  }

  if (intent.kind === 'hero') {
    /* the hero picks its artwork in JS from the data-img* attributes the view
       renders, so the template lives in the PHP and the masters on disk */
    const view = read('resources/views/home/hero-cinema/hero-cinema.php');
    const template = /images\/lifestyle\/heroc-/.test(view);
    const masters = ['heroc-1.webp', 'heroc-1-m.webp', 'heroc-1-p.webp']
      .every((f) => fs.existsSync(path.join(root, 'images/lifestyle', f)));
    if (!template) problems.push('hero-cinema: the view no longer references images/lifestyle/heroc-*');
    if (!masters) problems.push('hero-cinema: a heroc-1 artwork master is missing from images/lifestyle/');
    rows.push([band, 'photo', template && masters ? 'images/lifestyle/heroc-*' : 'MISSING']);
    continue;
  }

  const backdrop = painted.find((u) => u.includes('backdrop'));
  if (!backdrop) {
    problems.push(`${band}: meant to be a photo band but ${file} paints no backdrop url()`);
    rows.push([band, 'photo', 'MISSING']);
    continue;
  }
  if (!fs.existsSync(path.join(root, intent.file))) {
    problems.push(`${band}: ${intent.file} does not exist`);
  }
  /* the photo is relative to the stylesheet, and it has to resolve to the file
     this audit knows about */
  const resolved = path.relative(root, path.resolve(path.dirname(path.join(root, file)), backdrop));
  if (resolved !== intent.file) {
    problems.push(`${band}: ${file} points at ${resolved}, the audit expects ${intent.file}`);
  }
  if (!/::before/.test(css)) {
    problems.push(`${band}: the photograph is not on its own ::before layer (the veil cannot be tuned independently)`);
  }
  if (!/z-index:\s*1/.test(css)) {
    problems.push(`${band}: the content is not lifted above the veil (container needs position: relative; z-index: 1)`);
  }
  if (!/@media \(hover: none\)/.test(css)) {
    problems.push(`${band}: no `+"`background-attachment: fixed`"+` fallback for iOS`);
  }
  rows.push([band, 'photo', resolved]);
}

/* ---------- neighbours must not both be photographic ---------- */

const photoBands = order.filter((b) => (BANDS[b]?.kind ?? 'plain') !== 'plain');
for (let i = 1; i < order.length; i++) {
  if (photoBands.includes(order[i]) && photoBands.includes(order[i - 1])) {
    problems.push(`${order[i - 1]} and ${order[i]} are neighbouring photo bands - the page reads as one long image`);
  }
}

if (asJson) {
  console.log(JSON.stringify({ order, rows, photoBands, problems }, null, 2));
} else {
  console.log('home bands, in render order:\n');
  console.log('  band            background  artwork');
  console.log('  ' + '-'.repeat(62));
  for (const [band, kind, artwork] of rows) {
    console.log(`  ${band.padEnd(14)}  ${kind.padEnd(10)}  ${artwork}`);
  }
  console.log(`\n  ${photoBands.length} photo band(s): ${photoBands.join(', ')}`);
  if (problems.length) {
    console.log(`\n${problems.length} problem(s):`);
    for (const p of problems) console.log(`  - ${p}`);
  } else {
    console.log('\nthe bands alternate photo / plain, every backdrop resolves to a real file');
  }
}

process.exit(problems.length ? 1 : 0);
