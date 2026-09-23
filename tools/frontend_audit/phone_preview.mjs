#!/usr/bin/env node
/**
 * Device preview of the home page, built from the real render.
 *
 *   node run/render_home.php > storage/reports/_home-render.html   (php-wasm, sandbox only)
 *   node tools/frontend_audit/phone_preview.mjs --render storage/reports/_home-render.html \
 *        --out storage/reports/hero-phones.html
 *
 * Each frame is a real <iframe> sized to a device viewport, holding the real
 * page markup with the real stylesheets inlined, so the media queries, the hero
 * script and the sticky chrome all behave exactly as they do on the device.
 * The captions come from hero_fit_test.mjs --json, so they describe the same
 * numbers the checks assert on.
 */
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { execFileSync } from 'node:child_process';

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const args = process.argv.slice(2);
const argValue = (flag, fallback) => (args.indexOf(flag) >= 0 ? args[args.indexOf(flag) + 1] : fallback);

const renderPath = path.resolve(ROOT, argValue('--render', 'storage/reports/_home-render.html'));
const outPath = path.resolve(ROOT, argValue('--out', 'storage/reports/hero-phones.html'));

const FRAMES = [
  { name: 'Galaxy Fold (closed)', width: 280, height: 653, scale: 0.62 },
  { name: 'iPhone SE', width: 320, height: 568, scale: 0.62 },
  { name: 'iPhone 12', width: 390, height: 844, scale: 0.62 },
  { name: 'iPhone 15 Pro Max', width: 430, height: 932, scale: 0.62 },
  { name: 'iPhone 12 landscape', width: 844, height: 390, scale: 0.62 },
];

const page = fs.readFileSync(renderPath, 'utf8');

/* ---------- pull the page apart ---------- */
const htmlAttrs = (page.match(/<html([^>]*)>/i) || [, ''])[1];
const headScripts = [...page.matchAll(/<script>([\s\S]*?)<\/script>/gi)]
  .map((m) => m[1])
  .filter((code) => code.includes('data-theme')); /* the before-first-paint theme pick */

const sheets = [...page.matchAll(/<link[^>]+rel="stylesheet"[^>]*>/gi)].map((tag) => {
  const href = (tag[0].match(/href="([^"]+)"/) || [, ''])[1];
  const local = href.replace(/^https?:\/\/[^/]+/, '');
  return /^\/?(frontend|css)\//.test(local)
    ? { inline: fs.readFileSync(path.join(ROOT, local.replace(/^\//, '')), 'utf8') }
    : { href };
});

const bodyStart = page.search(/<body[^>]*>/i);
const scripts = [...page.matchAll(/<script\s+defer\s+src="([^"]+)"><\/script>/gi)].map((m) =>
  m[1].replace(/^https?:\/\/[^/]+/, ''),
);

/* ---------- make every URL relative to this preview file (storage/reports/) ---------- */
const rewrite = (text) =>
  text
    .replace(/https?:\/\/localhost\/images\//g, '../../public/images/')
    .replace(/https?:\/\/localhost\//g, '../../')
    .replace(/url\((["']?)\/images\//g, 'url($1../../public/images/');

const css = sheets
  .map((sheet) => (sheet.inline ? `<style>\n${rewrite(sheet.inline)}\n</style>` : `<link rel="stylesheet" href="${sheet.href}">`))
  .join('\n');

const body = rewrite(page.slice(bodyStart));
const scriptTags = scripts.map((src) => `<script defer src="${src}"></script>`).join('\n');

const srcdoc = `<!doctype html>
<html${htmlAttrs}>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
${headScripts.map((code) => `<script>${code}</script>`).join('\n')}
${css}
<style>html, body { margin: 0; }</style>
</head>
${body}
${scriptTags}
</html>`;

/* ---------- the numbers, straight from the harness ---------- */
const measured = JSON.parse(
  execFileSync(process.execPath, [path.join(ROOT, 'tools/frontend_audit/hero_fit_test.mjs'), '--json'], {
    encoding: 'utf8',
    maxBuffer: 32 * 1024 * 1024,
  }),
);

const bySize = new Map(measured.rows.map((row) => [`${row.width}x${row.height}`, row]));

const frames = FRAMES.map((frame) => {
  const row = bySize.get(`${frame.width}x${frame.height}`) || {};
  const caption = [
    `hero <b>${row.hero}px</b> of a ${frame.height}px screen`,
    row.band ? `artwork band <b>${row.band}px</b>` : null,
    row.band ? `copy <b>${row.needs}px</b>` : `copy <b>${row.needs}px</b>`,
    row.cropFraction ? `photo shows <b>${Math.round(row.cropFraction * 100)}%</b> of its width` : null,
  ].filter(Boolean).join(' · ');

  return `<figure class="device">
  <figcaption><b>${frame.name}</b><span>${frame.width} × ${frame.height} css px</span></figcaption>
  <div class="shell" style="width:${(frame.width * frame.scale).toFixed(0)}px;height:${(frame.height * frame.scale).toFixed(0)}px">
    <iframe title="${frame.name}" width="${frame.width}" height="${frame.height}"
            style="transform:scale(${frame.scale})" srcdoc="${srcdoc.replace(/&/g, '&amp;').replace(/"/g, '&quot;')}"></iframe>
  </div>
  <p class="nums">${caption}</p>
</figure>`;
}).join('\n');

const html = `<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>LUFLY home — phone / device preview</title>
<style>
  body { margin: 0; padding: 24px; font: 14px/1.55 -apple-system, "Segoe UI", Roboto, sans-serif; background: #f6f7f9; color: #1f2933; }
  h1 { font-size: 21px; margin: 0 0 6px; }
  h2 { font-size: 16px; margin: 28px 0 8px; }
  .lede { max-width: 900px; color: #52606d; }
  .devices { display: flex; flex-wrap: wrap; gap: 22px; align-items: flex-start; margin-top: 18px; }
  .device { margin: 0; }
  .device figcaption { display: flex; flex-direction: column; font-size: 12px; margin-bottom: 8px; }
  .device figcaption span { color: #7b8794; font-size: 11px; }
  .shell { border: 1px solid #c3ccd5; border-radius: 14px; overflow: hidden; background: #fff; box-shadow: 0 6px 18px rgba(31, 41, 51, 0.08); }
  .shell iframe { border: 0; transform-origin: top left; display: block; }
  .nums { font-size: 11px; color: #52606d; margin: 6px 0 0; max-width: 260px; }
  .note { max-width: 900px; background: #fff; border: 1px solid #d9dee5; border-radius: 10px; padding: 12px 16px; }
  .note li { margin: 4px 0; }
  code { background: #eef1f5; border-radius: 4px; padding: 1px 5px; }
</style>
</head>
<body>
<h1>Home page on real device viewports</h1>
<p class="lede">Every frame is a real <code>&lt;iframe&gt;</code> at the device size, running the real
stylesheets and the real scripts — the hero is measured live inside each frame, so what you see is the
behaviour, not a mock. Scroll inside a frame to walk the rest of the home page.</p>

<div class="devices">
${frames}
</div>

<h2>What to look for</h2>
<div class="note">
<ul>
  <li>The hero fills the screen edge to edge below the announcement bar and the navbar — no strip of the next section.</li>
  <li>On a portrait phone the photo keeps a wide band at the top instead of being cropped into a close-up, and the copy sits under it.</li>
  <li>Scroll a frame down a little and back: the browser retracts its toolbar, the hero grows to fill the newly visible strip and comes back — it never keeps growing.</li>
  <li>Landscape phone: the hero takes the whole short screen, with the decorative lines dropped so the two buttons stay visible.</li>
</ul>
</div>
</body>
</html>
`;

fs.mkdirSync(path.dirname(outPath), { recursive: true });
fs.writeFileSync(outPath, html);
console.log(`wrote ${path.relative(ROOT, outPath)} (${FRAMES.length} frames, ${Math.round(fs.statSync(outPath).size / 1024)} KB)`);
