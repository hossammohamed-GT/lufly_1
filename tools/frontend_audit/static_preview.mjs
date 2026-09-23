#!/usr/bin/env node
/**
 * Static home-page preview.
 *
 * Serves a server-rendered home snapshot (see render_home.mjs in the php-wasm
 * toolchain, or `storage/reports/_home-render.html`) together with the real
 * `frontend/` CSS + JS and `images/`, so the page can be reviewed in a browser
 * without a PHP runtime in the sandbox. Nothing is rewritten except the host
 * of the snapshot's absolute URLs, and no page routes are emulated except the
 * home page itself: this is a viewer, not a server.
 *
 *   node tools/frontend_audit/static_preview.mjs --render storage/reports/_home-render.html --port 4173
 *
 * Then open http://localhost:4173/ (in the sandbox this is exposed as a live
 * preview). Everything the snapshot asks for is logged; missing files are
 * reported as 404 so a broken asset path is visible immediately.
 */

import fs from 'node:fs';
import http from 'node:http';
import path from 'node:path';

const args = process.argv.slice(2);
const arg = (name, fallback) => {
  const i = args.indexOf(name);
  return i !== -1 && args[i + 1] ? args[i + 1] : fallback;
};

const root = path.resolve(arg('--root', process.cwd()));
const renderPath = path.resolve(arg('--render', 'storage/reports/_home-render.html'));
const port = Number(arg('--port', '4173'));

if (!fs.existsSync(renderPath)) {
  console.error(`snapshot not found: ${renderPath}\n` +
    'Render it first (php-wasm): node /tmp/phpwasm/run/render_home.mjs');
  process.exit(1);
}

/* the snapshot is generated with APP_URL=http://localhost/, so its asset URLs
   are absolute. Dropping the host turns them into root-relative paths that the
   preview host serves directly - the same thing the real document does. It is
   re-read per request, so a fresh render shows up on the next reload. */
const snapshot = () => fs
  .readFileSync(renderPath, 'utf8')
  .replaceAll('http://localhost', '')
  .replaceAll('https://localhost', '');

const TYPES = {
  '.html': 'text/html; charset=utf-8',
  '.css': 'text/css; charset=utf-8',
  '.js': 'text/javascript; charset=utf-8',
  '.mjs': 'text/javascript; charset=utf-8',
  '.json': 'application/json; charset=utf-8',
  '.svg': 'image/svg+xml',
  '.png': 'image/png',
  '.jpg': 'image/jpeg',
  '.jpeg': 'image/jpeg',
  '.webp': 'image/webp',
  '.avif': 'image/avif',
  '.ico': 'image/x-icon',
  '.woff': 'font/woff',
  '.woff2': 'font/woff2',
  '.ttf': 'font/ttf',
  '.otf': 'font/otf',
  '.txt': 'text/plain; charset=utf-8',
  '.map': 'application/json; charset=utf-8',
};

const HOME_ROUTES = new Set(['/', '/en', '/en/', '/index.php']);

const missing = new Set();

const server = http.createServer((req, res) => {
  const url = new URL(req.url, 'http://preview');
  const route = decodeURIComponent(url.pathname);

  if (HOME_ROUTES.has(route)) {
    res.writeHead(200, {
      'content-type': TYPES['.html'],
      'cache-control': 'no-store',
    });
    res.end(snapshot());
    return;
  }

  const file = path.resolve(root, '.' + route);
  if (!file.startsWith(root + path.sep) || !fs.existsSync(file) || fs.statSync(file).isDirectory()) {
    if (!missing.has(route)) {
      missing.add(route);
      console.log(`404  ${route}`);
    }
    /* page-like routes (no extension) get a friendly note instead of nothing */
    const pageLike = path.extname(route) === '';
    res.writeHead(404, { 'content-type': TYPES['.html'], 'cache-control': 'no-store' });
    res.end(pageLike
      ? `<!doctype html><meta charset="utf-8"><title>Static snapshot</title>
         <body style="font:15px/1.6 system-ui;padding:40px;background:#15181a;color:#e8eeea">
         <h1 style="font-size:20px">Static home snapshot</h1>
         <p><code>${route}</code> is a PHP page, and this preview only holds the
         rendered home page.</p><p><a style="color:#7fe2d0" href="/">back to the home snapshot</a></p>`
      : 'not found');
    return;
  }

  const type = TYPES[path.extname(file).toLowerCase()] ?? 'application/octet-stream';
  res.writeHead(200, { 'content-type': type, 'cache-control': 'no-store' });
  fs.createReadStream(file).pipe(res);
});

server.listen(port, '0.0.0.0', () => {
  console.log(`static home snapshot on http://0.0.0.0:${port}/  (root ${root})`);
  console.log(`serving ${renderPath}`);
  console.log('page routes other than the home page answer 404 on purpose; assets are served from the repo');
});
