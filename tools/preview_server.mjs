/**
 * Serve the whole LUFLY project over HTTP using PHP 8.3 compiled to WebAssembly.
 *
 *     node tools/preview_server.mjs            # http://0.0.0.0:8080
 *
 * Needs @php-wasm/node + @php-wasm/universal in node_modules and a .env in the
 * repo root. Every non-static request goes through public/index.php, the same
 * entry point Apache uses, so routing, sessions, CSRF, sqlite and uploads all
 * behave exactly as they do in production.
 *
 * The repo is mounted read-write into the PHP virtual filesystem, so sqlite,
 * the uploaded photos, caches and logs behave exactly as they do under
 * Apache + PHP: every request goes through public/index.php, the same entry
 * point the real server uses. Static files are answered by node directly to
 * keep image-heavy catalogue pages quick.
 */
import { PHP } from '@php-wasm/universal';
import { loadNodeRuntime, createNodeFsMountHandler } from '@php-wasm/node';
import http from 'node:http';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

// repo root = the parent of tools/, so the runner works from any checkout
const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const DOCROOT = path.join(ROOT, 'public');
const PORT = Number(process.env.PORT || 8080);
const HOST = '0.0.0.0';

const MIME = {
  '.html': 'text/html; charset=utf-8', '.css': 'text/css; charset=utf-8',
  '.js': 'application/javascript; charset=utf-8', '.mjs': 'application/javascript; charset=utf-8',
  '.json': 'application/json', '.webp': 'image/webp', '.png': 'image/png',
  '.jpg': 'image/jpeg', '.jpeg': 'image/jpeg', '.gif': 'image/gif',
  '.svg': 'image/svg+xml', '.ico': 'image/x-icon', '.woff': 'font/woff',
  '.woff2': 'font/woff2', '.ttf': 'font/ttf', '.otf': 'font/otf',
  '.pdf': 'application/pdf', '.txt': 'text/plain; charset=utf-8',
  '.xml': 'application/xml', '.webmanifest': 'application/manifest+json',
  '.map': 'application/json', '.mp4': 'video/mp4', '.webm': 'video/webm',
};

console.log('booting PHP 8.3 (wasm) …');
const runtime = await loadNodeRuntime('8.3', { emscriptenOptions: { processId: 4242 } });
const php = new PHP(runtime, { documentRoot: DOCROOT });
await php.mount(ROOT, createNodeFsMountHandler(ROOT));

// never hand out a dotfile, a database, a dump or a log: match the segment
// AND the extension, so `lufly.sqlite` and `lufly-database.sql` are covered
// as well as a literal `/database/` directory.
const BLOCKED = /(^|\/)(\.env[^/]*|\.git(\/|$)|\.sqlite|\.db|\.sql|\.log|\.ini|\.bak)/i;
const BLOCKED_EXT = /\.(sqlite|sqlite3|db|db3|sql|log|env|ini|bak|sh|py|php|mjs)$/i;

const EXT_DENY = /\.(php|phtml|phar|cgi|pl|py|sh|bash|exe|dll|bat|cmd)$/i;

function staticFile(urlPath) {
  if (urlPath === '/') return null;
  if (EXT_DENY.test(urlPath)) return null;
  let rel = decodeURIComponent(urlPath).replace(/^\/+/, '');
  if (rel.endsWith('/')) rel += 'index.html';
  if (!rel || BLOCKED.test('/' + rel) || BLOCKED_EXT.test(rel)) return null;
  // only public/ is web-served; the repo root exists for the few shared
  // static files (stylesheets, scripts) and nothing under tools/ or storage/
  const allowed = /^(frontend\/|lufly-central\.|images\/|uploads\/|favicon)/;
  const bases = [DOCROOT];
  if (allowed.test(rel)) bases.push(ROOT);
  for (const base of bases) {
    const file = path.join(base, rel);
    if (!file.startsWith(base)) continue;
    try {
      const st = fs.statSync(file);
      if (st.isFile()) return { file, st };
    } catch { /* keep looking */ }
  }
  return null;
}

function readBody(req) {
  return new Promise((resolve) => {
    const chunks = [];
    req.on('data', (c) => chunks.push(c));
    req.on('end', () => resolve(Buffer.concat(chunks)));
  });
}

const server = http.createServer(async (req, res) => {
  const started = Date.now();
  const host = req.headers.host || `127.0.0.1:${PORT}`;
  const url = new URL(req.url, `http://${host}`);
  const urlPath = url.pathname;

  if (BLOCKED.test(urlPath) || BLOCKED_EXT.test(urlPath)) {
    res.writeHead(403, { 'Content-Type': 'text/plain; charset=utf-8' });
    res.end('403 Forbidden');
    return;
  }

  const hit = staticFile(urlPath);
  if (hit) {
    const type = MIME[path.extname(hit.file).toLowerCase()] || 'application/octet-stream';
    if (req.method === 'HEAD') {
      res.writeHead(200, { 'Content-Type': type, 'Content-Length': hit.st.size });
      res.end();
      return;
    }
    if (req.method === 'GET') {
      res.writeHead(200, {
        'Content-Type': type,
        'Content-Length': hit.st.size,
        'Cache-Control': 'public, max-age=3600',
        'Last-Modified': hit.st.mtime.toUTCString(),
      });
      fs.createReadStream(hit.file).pipe(res);
      return;
    }
  }

  const body = req.method === 'GET' || req.method === 'HEAD' ? Buffer.alloc(0) : await readBody(req);
  const headers = {};
  for (const [k, v] of Object.entries(req.headers)) headers[k] = Array.isArray(v) ? v.join('; ') : String(v);

  const remote = (req.socket.remoteAddress || '127.0.0.1').replace(/^::ffff:/, '');
  const forwardedProto = req.headers['x-forwarded-proto'];
  const serverVars = {};
  if (forwardedProto) {
    serverVars.HTTPS = String(forwardedProto) === 'https' ? 'on' : 'off';
    serverVars.HTTP_X_FORWARDED_PROTO = String(forwardedProto);
  }

  try {
    const response = await php.run({
      scriptPath: DOCROOT + '/index.php',
      relativeUri: req.url,
      method: req.method,
      headers,
      body,
      $_SERVER: { ...serverVars,
        DOCUMENT_ROOT: DOCROOT,
        // The preview proxy terminates TLS, but curl and local checks come in
        // over plain http: report https only when the proxy says so, otherwise
        // a Secure session cookie would be dropped by a plain-http client.

        SCRIPT_FILENAME: DOCROOT + '/index.php',
        SCRIPT_NAME: '/index.php',
        PHP_SELF: '/index.php',
        REQUEST_URI: req.url,
        QUERY_STRING: url.search.replace(/^\?/, ''),
        REQUEST_METHOD: req.method,
        HTTP_HOST: host,
        SERVER_NAME: host.split(':')[0],
        SERVER_PORT: host.split(':')[1] || String(PORT),
        SERVER_PROTOCOL: 'HTTP/' + (req.httpVersion || '1.1'),
        REMOTE_ADDR: remote,
        GATEWAY_INTERFACE: 'CGI/1.1',
        SERVER_SOFTWARE: 'php-wasm',
        HTTP_X_FORWARDED_HOST: host,
      },
    });

    const h = response.headers || {};
    const status = Number(response.httpStatusCode ?? 200) || 200;
    const out = Buffer.from(response.text ?? '', 'utf8');

    for (const [k, v] of Object.entries(h)) {
      if (['status', 'transfer-encoding', 'connection', 'content-length', 'content-encoding', 'x-powered-by'].includes(k)) continue;
      res.setHeader(k, Array.isArray(v) ? v.join(', ') : String(v));
    }
    if (!res.getHeader('content-type')) res.setHeader('content-type', 'text/html; charset=utf-8');
    res.writeHead(status, { 'Content-Length': out.length });
    res.end(req.method === 'HEAD' ? undefined : out);
    console.log(`${status} ${req.method} ${url.pathname}${url.search}  ${Date.now() - started}ms`);
  } catch (err) {
    console.error('PHP error:', err?.message || err);
    const msg = '500 — PHP runtime error:\n\n' + (err?.stack || String(err));
    res.writeHead(500, { 'Content-Type': 'text/plain; charset=utf-8', 'Content-Length': Buffer.byteLength(msg) });
    res.end(msg);
  }
});

server.listen(PORT, HOST, () => console.log(`LUFLY running on http://${HOST}:${PORT}`));
