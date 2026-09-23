#!/usr/bin/env node
/**
 * Renders the hero measurement for every simulated device into one standalone
 * HTML sheet: no browser, no server, no build step — open the file.
 *
 *   node tools/frontend_audit/hero_report.mjs [--out storage/reports/hero-fit.html]
 *
 * Bars are drawn to scale: the frame is the small viewport, the teal block is
 * the hero the script ends up with, the marker is what the copy needs and the
 * dashed line is the portrait artwork cap (the zoom guard).
 */
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { execFileSync } from 'node:child_process';

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const args = process.argv.slice(2);
const outArg = args.indexOf('--out');

/* the harness owns the measurements: ask it for JSON so this sheet can never
   drift from the numbers the checks assert on */
const measured = JSON.parse(
  execFileSync(process.execPath, [path.join(ROOT, 'tools/frontend_audit/hero_fit_test.mjs'), '--json'], {
    encoding: 'utf8',
    maxBuffer: 32 * 1024 * 1024,
  }),
);
const rows = measured.rows;

const scale = 220; /* px per device height in the drawing */

const cards = rows.map((row) => {
  const px = (value) => `${((value / row.height) * scale).toFixed(1)}px`;
  const artworkCap = row.portraitPhone ? row.max : 0;
  const failed = row.issues.length > 0;

  return `<figure class="device${failed ? ' is-bad' : ''}">
  <figcaption><b>${row.name}</b><span>${row.width}x${row.height} css px</span></figcaption>
  <div class="screen" style="height:${scale}px;width:${Math.round((row.width / row.height) * scale)}px">
    <div class="hero" style="height:${px(row.hero)}"></div>
    <div class="needs" style="bottom:${px(row.needs)}"><span>copy needs ${row.needs}px</span></div>
    ${artworkCap ? `<div class="cap" style="bottom:${px(artworkCap)}"><span>artwork cap ${artworkCap}px</span></div>` : ''}
  </div>
  <p class="nums">hero <b>${row.hero}px</b> · max ${row.max}px · ${failed ? row.issues.join('; ') : 'fits the first screen'}</p>
</figure>`;
}).join('\n');

const heroArt = ['1', '2', '3', '4', '5'].map((n) => `
  <div class="pair">
    <figure><img src="../../public/images/lifestyle/heroc-${n}-m.webp" alt=""><figcaption>dark -m (1280x714)</figcaption></figure>
    <figure><img src="../../public/images/lifestyle/heroc-${n}-p.webp" alt=""><figcaption>dark -p (1080x959, new)</figcaption></figure>
    <figure><img src="../../public/images/lifestyle/heroc-${n}-light-p.jpg" alt=""><figcaption>light -p (1080x799, new)</figcaption></figure>
  </div>`).join('');

const html = `<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>LUFLY home hero — fit report</title>
<style>
  :root { color-scheme: light; }
  body { margin: 0; padding: 26px; font: 14px/1.55 -apple-system, "Segoe UI", Roboto, sans-serif; background: #f6f7f9; color: #1f2933; }
  h1 { font-size: 21px; margin: 0 0 6px; }
  h2 { font-size: 16px; margin: 30px 0 10px; }
  .lede { max-width: 860px; color: #52606d; }
  .devices { display: flex; flex-wrap: wrap; gap: 14px; margin-top: 16px; }
  .device { margin: 0; padding: 10px 12px 6px; border: 1px solid #d9dee5; border-radius: 10px; background: #fff; }
  .device figcaption { display: flex; flex-direction: column; font-size: 12px; margin-bottom: 8px; }
  .device figcaption span { color: #7b8794; font-size: 11px; }
  .screen { position: relative; border: 1px solid #c3ccd5; border-radius: 8px; background: linear-gradient(180deg, #f0f3f6, #e7ebef); overflow: hidden; margin-inline: auto; }
  .hero { position: absolute; inset-inline: 0; top: 0; background: color-mix(in srgb, #1c8b8b 22%, #fff); border-bottom: 2px solid #1c8b8b; }
  .needs, .cap { position: absolute; inset-inline: 0; border-top: 1px dashed #9b1c1c; font-size: 9.5px; color: #9b1c1c; }
  .needs span, .cap span { position: absolute; inset-inline-start: 4px; top: -12px; background: #fff; padding: 0 3px; white-space: nowrap; }
  .cap { border-top-color: #0f766e; color: #0f766e; }
  .cap span { top: auto; bottom: -12px; }
  .nums { font-size: 11px; color: #52606d; margin: 6px 0 2px; text-align: center; }
  .is-bad { border-color: #9b1c1c; }
  .is-bad .nums { color: #9b1c1c; }
  .pairs { display: flex; flex-direction: column; gap: 12px; }
  .pair { display: flex; gap: 12px; align-items: flex-end; }
  .pair figure { margin: 0; }
  .pair img { height: 120px; width: auto; border: 1px solid #d9dee5; border-radius: 8px; display: block; }
  .pair figcaption { font-size: 10.5px; color: #7b8794; text-align: center; margin-top: 4px; }
  code { background: #eef1f5; border-radius: 4px; padding: 1px 5px; }
  .note { max-width: 900px; background: #fff; border: 1px solid #d9dee5; border-radius: 10px; padding: 12px 16px; }
  .note li { margin: 4px 0; }
</style>
</head>
<body>
<h1>Home hero — responsive fit report</h1>
<p class="lede">Every frame is a simulated device viewport (small viewport height, toolbars showing). The teal block is the height the hero ends up with, the red dashed line is what the copy needs, the teal dashed line is the portrait artwork cap that keeps the photo from being zoomed into a close-up. Generated by <code>tools/frontend_audit/hero_report.mjs</code>, which reads the same numbers the checks assert on.</p>

<div class="devices">
${cards}
</div>

<h2>What changed</h2>
<div class="note">
<ul>
  <li><b>The hero used a client rect.</b> <code>root.getBoundingClientRect().top</code> goes negative as soon as the page scrolls, so every <code>resize</code> event added the scroll offset to the hero height — and mobile browsers fire <code>resize</code> constantly while scrolling (URL bar sliding). Heights are now measured in document space and clamped.</li>
  <li><b>The viewport height came from <code>window.innerHeight</code>.</b> That value grows by ~100px when the phone hides its toolbars, so the hero grew mid-scroll and the <code>cover</code> artwork zoomed with it. The script now reads a <code>100svh</code> probe (with a smallest-observed fallback for old iOS).</li>
  <li><b>Nothing capped the artwork.</b> The scene layer bleeds <code>-3.5%</code> and the phone rules set a fixed floor of <code>420px</code> that could exceed the whole screen on landscape phones; both are bounded now, and portrait phones get a dedicated 1080x959/<wbr>1080x799 crop instead of a landscape shot squeezed into a tall box.</li>
  <li><b>Phone Ken Burns.</b> The zoom drift was 3–11% on a box that already crops hard; the phone keyframes drift 0.5–3.5% instead.</li>
  <li><b>content-visibility placeholders</b> were a flat 500px for every home band, so the page grew section by section while scrolling (which drags the URL bar, which fires resize). Each band now carries a placeholder close to its real height.</li>
  <li><b>The category mosaic never rendered</b> — <code>home/index.php</code> did not hand the controller's <code>$categories</code> to the component, so it returned early. Fixed.</li>
  <li><b>Smaller fixes:</b> the mobile drawer/rail use <code>100dvh</code> (their last entries sat under the browser toolbar), <code>body.ready</code> no longer re-enables horizontal scrolling after the loader exits, and the shared section headings drop to a phone scale.</li>
</ul>
</div>

<h2>New portrait artwork (crop of the same photographs)</h2>
<p class="lede">Landscape originals for reference, then the two new portrait crops the phone now receives. 15 files, ~690 KB total, generated with ImageMagick from the existing shots.</p>
<div class="pairs">
${heroArt}
</div>
</body>
</html>
`;

const out = path.resolve(ROOT, outArg >= 0 ? args[outArg + 1] : 'storage/reports/hero-fit.html');
const bad = rows.filter((row) => row.issues.length).length;
fs.mkdirSync(path.dirname(out), { recursive: true });
fs.writeFileSync(out, html);
console.log(
  `wrote ${path.relative(ROOT, out)} (${rows.length} devices, ${bad} failing, ` +
  `${Math.round(fs.statSync(out).size / 1024)} KB)`,
);
process.exit(bad ? 1 : 0);
