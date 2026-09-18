/**
 * Static preview server for the navbar design review (dev only).
 * Serves the repository root, rebuilds the review page on every request so the
 * page always mirrors resources/views/components/navbar.php.
 *
 *   node tools/navbar-preview/serve.mjs        → http://localhost:8099/
 */
import { createServer } from 'node:http';
import { readFileSync, statSync } from 'node:fs';
import path from 'node:path';
import { ROOT, buildPage } from './build.mjs';

const PORT = Number(process.env.PORT || 8099);
const HOST = process.env.HOST || '0.0.0.0';

const TYPES = {
  '.html': 'text/html; charset=utf-8',
  '.css': 'text/css; charset=utf-8',
  '.js': 'text/javascript; charset=utf-8',
  '.mjs': 'text/javascript; charset=utf-8',
  '.json': 'application/json; charset=utf-8',
  '.png': 'image/png',
  '.jpg': 'image/jpeg',
  '.jpeg': 'image/jpeg',
  '.webp': 'image/webp',
  '.svg': 'image/svg+xml',
  '.woff2': 'font/woff2',
  '.ico': 'image/x-icon'
};

function send(response, status, body, type = 'text/plain; charset=utf-8') {
  response.writeHead(status, { 'Content-Type': type, 'Cache-Control': 'no-store' });
  response.end(body);
}

createServer((request, response) => {
  const url = new URL(request.url, `http://${request.headers.host}`);
  const pathname = decodeURIComponent(url.pathname);

  if (pathname === '/' || pathname === '/index.html') {
    try {
      send(response, 200, buildPage(), TYPES['.html']);
    } catch (error) {
      send(response, 500, `build failed: ${error.stack}`);
    }

    return;
  }

  const target = path.normalize(path.join(ROOT, pathname));

  if (!target.startsWith(ROOT)) {
    send(response, 403, 'forbidden');
    return;
  }

  try {
    if (statSync(target).isDirectory()) {
      send(response, 404, 'not found');
      return;
    }

    send(response, 200, readFileSync(target), TYPES[path.extname(target)] || 'application/octet-stream');
  } catch (error) {
    send(response, 404, `not found: ${pathname}`);
  }
}).listen(PORT, HOST, () => {
  console.log(`navbar review → http://${HOST}:${PORT}/`);
});
