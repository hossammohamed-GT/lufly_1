#!/usr/bin/env node
/**
 * Render the storefront home page (or lint templates) with php-wasm, for
 * sandboxes that have no PHP runtime.
 *
 *   node tools/php-wasm/render.mjs lint   [file...]
 *       eval-parse every changed .php file: ParseError = syntax error.
 *
 *   node tools/php-wasm/render.mjs home   [locale]
 *       render layouts.frontend + components.navbar + home.index with the
 *       real lang files and stubbed helpers, then write
 *       storage/reports/_home-render.html for static_preview.mjs.
 *
 * The framework helpers (route/trans/asset/...) are stubbed with values that
 * match their real shape; the View class is a faithful re-implementation of
 * Core\View\View (same asset push system, same renderFile semantics), so the
 * templates execute the same code paths they do in production.
 */
import { execSync } from 'node:child_process';
import { existsSync, readFileSync, writeFileSync, mkdirSync } from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
process.chdir(path.join(ROOT, 'node_modules/@php-wasm/node/asyncify'));

const { loadNodeRuntime } = await import('@php-wasm/node');
const { PHP } = await import('@php-wasm/universal');
const runtime = await loadNodeRuntime('8.3', { persist: false });
const php = new PHP(runtime);

process.chdir(ROOT);

const read = (p) => readFileSync(path.join(ROOT, p), 'utf8');
const phpString = (s) => "'" + s.replace(/\\/g, '\\\\').replace(/'/g, "\\'") + "'";

/* ---------------------------------------------------------------------- */
/* collect files                                                          */
/* ---------------------------------------------------------------------- */

function collect(dir, out = []) {
  if (!existsSync(path.join(ROOT, dir))) return out;
  const listing = execSync(`find ${dir} -name '*.php' -o -name '*.json' | sort`, { cwd: ROOT, shell: '/bin/bash' })
    .toString().split('\n').map((s) => s.trim()).filter(Boolean);
  out.push(...listing);
  return out;
}

const mode = process.argv[2] || 'home';
const locale = process.argv[3] || 'en';
const fileMap = {};

if (mode === 'lint') {
  let targets = process.argv.slice(3);
  if (targets.length === 0) {
    targets = execSync(
      "bash -c \"git diff --name-only HEAD -- '*.php'; git ls-files --others --exclude-standard -- '*.php'; git diff --name-only --diff-filter=D HEAD -- '*.php'\"",
      { cwd: ROOT, shell: '/bin/bash' }
    ).toString().split('\n').map((s) => s.trim()).filter(Boolean);
  }
  for (const f of targets) {
    try {
      fileMap[f.replace(/^\.\//, '')] = read(f);
    } catch { /* deleted */ }
  }
} else {
  const found = [];
  collect('resources/lang/' + locale, found);
  collect('resources/views', found);
  for (const f of found) fileMap[f] = read(f);
}

/* ---------------------------------------------------------------------- */
/* the PHP harness                                                        */
/* ---------------------------------------------------------------------- */

const HARNESS = () => `
$___files = json_decode(${phpString(JSON.stringify(fileMap))}, true);
$___locale = ${phpString(locale)};

/* eval() cannot host a strict_types declaration that is not the first
   statement of the eval'd string - strip it, it changes nothing here. */
function ___tpl($code) {
    return preg_replace('/^<\\?php\\s+declare\\s*\\(\\s*strict_types\\s*=\\s*1\\s*\\)\\s*;/i', '<?php ', $code, 1);
}

function e($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }
function asset($p) {
    $u = '/' . ltrim($p, '/');
    /* css/js get a render-time stamp, mirroring the filemtime stamp the
       real asset() adds - so a fresh render always means fresh URLs */
    if (preg_match('#\\.(css|js)$#i', $u) !== 1) { return $u; }
    return $u . '?v=' . (int) $GLOBALS['___vstamp'];
}
function url($p = '') { return '/' . ltrim($p, '/'); }
function config($key, $default = null) { return $default; }
function feature($name, $default = false) { return in_array($name, ['ai', 'assistant'], true) ? false : $default; }
function csrf_token() { return 'preview'; }
function external_link_attrs($url) { return ''; }
function app($abstract = null) { return new ___FakeService(); }
function auth() { return new ___FakeAuth(); }
function request() { return $GLOBALS['___request']; }
function now() { return date('Y-m-d H:i:s'); }
function base_path($p = '') { return '/tmp' . ($p !== '' ? '/' . ltrim($p, '/') : ''); }
function storage_path($p = '') { return '/tmp/storage' . ($p !== '' ? '/' . ltrim($p, '/') : ''); }
function resource_path($p = '') { return 'resources' . ($p !== '' ? '/' . ltrim($p, '/') : ''); }
function public_path($p = '') { return 'public' . ($p !== '' ? '/' . ltrim($p, '/') : ''); }
function database_path($p = '') { return 'database' . ($p !== '' ? '/' . ltrim($p, '/') : ''); }
function env($key, $default = null) { return $default; }
function cache_remember($k, $ttl, $fn) { return $fn(); }
function old($key = null, $default = null) { return $default; }
function session($key = null, $default = null) { return $key === null ? new ___FakeFlash() : $default; }
function flash($key = null, $default = null) { return $default; }
function uploads_url($p = '') { return '/uploads/' . ltrim($p, '/'); }

function route($name, $params = [], $absolute = true) {
    $q = '';
    if ($params && $name === 'products.index') {
        $q = '?' . http_build_query($params);
    }
    $map = [
        'home' => '/' . $GLOBALS['___localeCode'],
        'contact' => '/' . $GLOBALS['___localeCode'] . '/contact',
        'products.index' => '/' . $GLOBALS['___localeCode'] . '/products',
        'products.show' => '/' . $GLOBALS['___localeCode'] . '/products/' . ($params['slug'] ?? ($params['id'] ?? 'x')),
        'favorites.index' => '/' . $GLOBALS['___localeCode'] . '/favorites',
        'box.index' => '/' . $GLOBALS['___localeCode'] . '/box',
        'planner.index' => '/' . $GLOBALS['___localeCode'] . '/planner',
        'login' => '/' . $GLOBALS['___localeCode'] . '/login',
        'admin.dashboard' => '/admin',
        'lang.switch' => '/lang/' . ($params['code'] ?? 'en'),
    ];
    return ($map[$name] ?? '/' . $GLOBALS['___localeCode'] . '/' . $name) . $q;
}

$___lang = [];
foreach ($___files as $k => $code) {
    if (strpos($k, 'resources/lang/' . $___locale . '/') === 0 && strpos($k, 'routes.php') === false) {
        $file = basename($k, '.php');
        $val = eval('?>' . ___tpl($code));
        if (is_array($val)) { $___lang[$file] = $val; }
    }
}

function trans($key, $params = [], $locale = null) {
    global $___lang;
    $parts = explode('.', $key, 2);
    if (count($parts) !== 2) { return $key; }
    [$file, $name] = $parts;
    $value = $___lang[$file][$name] ?? null;
    if (!is_string($value)) { return $key; }
    foreach ($params as $k => $v) {
        $value = str_replace(':' . $k, (string) $v, $value);
    }
    return $value;
}

class ___FakeFlash {
    public function getFlash($k, $default = null) { return $default; }
    public function get($k, $default = null) { return $default; }
    public function has($k) { return false; }
}
class ___FakeService {
    public function __call($m, $a) { return $m === 'count' ? 2 : []; }
    public function __get($k) { return new ___FakeService(); }
    public function getLocale() { global $___locale; return $___locale; }
    public function supported() { global $___locale; return ['en' => 'English', 'tr' => 'Türkçe', 'cs' => 'Čeština']; }
    public function trans($k, $p = [], $l = null) { return trans($k, $p, $l); }
}
/* the layout (and any view) picks the page language through
   $translator instanceof \Core\Localization\Translator - alias the fake to
   the real name so a tr/cs render carries lang="tr"/"cs" exactly like
   production, instead of falling back to the default locale */
class_alias('___FakeService', 'Core\\Localization\\Translator');
class ___FakeAuth {
    public function check() { return false; }
    public function user() { return null; }
}
class ___FakeRoute {
    public ?string $name = 'home';
    public bool $localized = true;
    public string $localizedKey = 'home';
}
class ___FakeRequest {
    public ?___FakeRoute $route = null;
    public function __construct() { $this->route = new ___FakeRoute(); }
    public function url() { return 'http://localhost/' . $GLOBALS['___localeCode']; }
    public function route() { return $this->route; }
    public function params() { return ['locale' => $GLOBALS['___localeCode']]; }
}

$GLOBALS['___localeCode'] = ${phpString(locale)};
$GLOBALS['___vstamp'] = ${phpString(String(Date.now()))};
$GLOBALS['___request'] = new ___FakeRequest();

class ___View {
    public array $assets = ['styles' => [], 'deferred-styles' => [], 'scripts' => [], 'preloads' => []];
    private ?string $layout = null;
    private array $shared = [];

    public function resolvePath(string $template): string {
        if (str_contains($template, '::')) {
            return 'MISSING::' . $template;
        }
        return 'resources/views/' . str_replace('.', '/', $template) . '.php';
    }

    public function renderFile(string $path, array $data = []): string {
        global $___files;
        $code = $___files[$path] ?? null;
        if ($code !== null) { $code = ___tpl($code); }
        if ($code === null) {
            throw new RuntimeException('View not found: ' . $path);
        }
        $view = $this;
        $t = fn (string $key, array $params = []): string => trans($key, $params);
        $translator = new ___FakeService();
        extract(array_merge($this->shared, $data), EXTR_SKIP);
        ob_start();
        try {
            eval('?>' . $code);
        } catch (Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        return (string) ob_get_clean();
    }

    public function component(string $name, array $props = [], ?string $slot = null): string {
        $props['slot'] = $slot;
        return $this->renderFile('resources/views/components/' . $name . '.php', ['props' => $props] + $props);
    }

    public function layout(string $template): void { $this->layout = $template; }
    public function share(array $data): void { $this->shared = array_merge($this->shared, $data); }
    public function pushStyle(string $p): void { $this->pushAsset('styles', $p); }
    public function pushDeferredStyle(string $p): void { $this->pushAsset('deferred-styles', $p); }
    public function pushScript(string $p): void { $this->pushAsset('scripts', $p); }
    public function pushPreload(string $href, array $attributes = ['as' => 'image']): void {
        foreach ($this->assets['preloads'] as $pre) { if ($pre['href'] === $href) return; }
        $this->assets['preloads'][] = ['href' => $href, 'attributes' => $attributes];
    }
    public function styles(): array { return $this->assets['styles']; }
    public function deferredStyles(): array { return $this->assets['deferred-styles']; }
    public function scripts(): array { return $this->assets['scripts']; }
    public function preloads(): array { return $this->assets['preloads']; }

    public function render(string $template, array $data = []): string {
        $this->layout = null;
        $content = $this->renderFile($this->resolvePath($template), $data);
        if ($this->layout !== null) {
            $layoutPath = $this->resolvePath($this->layout);
            $this->layout = null;
            return $this->renderFile($layoutPath, array_merge($data, ['content' => $content]));
        }
        return $content;
    }

    public function exists(string $template): bool {
        global $___files;
        return isset($___files[$this->resolvePath($template)]);
    }

    private function pushAsset(string $type, string $p): void {
        if (!in_array($p, $this->assets[$type], true)) {
            $this->assets[$type][] = $p;
        }
    }
}

try {
${mode === 'lint' ? `
    $fail = 0;
    foreach (array_keys($___files) as $file) {
        if (strpos($file, 'resources/lang/') === 0) continue;
        $code = ___tpl($___files[$file]);
        try {
            ob_start();
            eval('?>' . $code);
            ob_end_clean();
            echo "ok    {$file}\\n";
        } catch (ParseError $e) {
            ob_end_clean();
            $fail++;
            echo "FAIL  {$file} :: {$e->getMessage()} @ line {$e->getLine()}\\n";
        } catch (Throwable $e) {
            ob_end_clean();
            echo "ok*   {$file} (runtime only: " . $e->getMessage() . ")\\n";
        }
    }
    exit($fail ? 1 : 0);
` : `
    $view = new ___View();
    $translator = new ___FakeService();
    echo $view->render('home.index', [
        'translator' => $translator,
        'categories' => [],
        'featuredProducts' => [],
        'title' => 'LUFLY - Architectural Sanitary Ware',
        'seo' => null,
    ]);
    exit(0);
`}
} catch (Throwable $e) {
    echo "RENDER ERROR: " . $e->getMessage() . " @ " . $e->getFile() . ":" . $e->getLine() . "\\n";
    echo $e->getTraceAsString() . "\\n";
    exit(1);
}
`;

const response = await php.run({ code: `<?php\n${HARNESS()}` });
const text = response.text;

if (mode === 'lint') {
  console.log(text);
  process.exit(response.exitCode);
} else {
  if (response.exitCode !== 0 || text.startsWith('RENDER ERROR')) {
    console.error(text);
    process.exit(1);
  }
  mkdirSync(path.join(ROOT, 'storage/reports'), { recursive: true });
  const stamped = `<!-- preview build ${new Date().toISOString()} -->\n` + text;
  writeFileSync(path.join(ROOT, 'storage/reports/_home-render.html'), stamped);
  console.log(`rendered ${(text.length / 1024).toFixed(1)} KB -> storage/reports/_home-render.html`);
}
process.exit(0);
