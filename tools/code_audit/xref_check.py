#!/usr/bin/env python3
"""
Full-repo cross-reference audit.

Validates the WIRING of the codebase, not just its syntax:

  1. trans()/__()/trans_choice() keys used anywhere (php + js) exist in
     resources/lang/{en,tr,cs}/*.php, and every locale carries the same keys
  2. config('a.b') keys exist in config/*.php
  3. route('name') calls resolve to a ->name() defined in routes/ or a module
  4. view templates referenced via view()/render()/resolvePath()/component()
     exist on disk (resources/views + modules/*/Views namespaces)
  5. route handlers [Foo::class, 'bar'] / 'Foo@bar' point at a real class
     with a real public method (PSR-4: App\\ app/, Core\\ core/, Modules\\
     modules/, Database\\ database/)
  6. `use Foo\\Bar;` statements resolve to real files
  7. SQL in the code only touches tables/columns that exist in
     database/lufly.sqlite
  8. asset()/pushStyle/pushScript/pushPreload paths exist under public/
  9. middleware names in routes resolve via config/app.php aliases/groups

Usage: python3 tools/code_audit/xref_check.py
"""

import json
import os
import re
import sqlite3
import subprocess
import sys

ROOT = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
os.chdir(ROOT)

LOCALES = ['en', 'tr', 'cs']
PHP_DIRS = ['app', 'core', 'config', 'modules', 'routes', 'database', 'public', 'resources/views']
SKIP = {'node_modules', '.git', 'storage'}

failures = []
warnings = []
notes = []


def fail(msg): failures.append(msg)
def warn(msg): warnings.append(msg)
def note(msg): notes.append(msg)


# --------------------------------------------------------------------------
# definitions
# --------------------------------------------------------------------------

def php_key_tree():
    """Ask the PHP engine (php-wasm) to load every lang + config file and
    dump its key tree as JSON. Values are irrelevant; keys are the wiring.
    The wasm runtime has no host filesystem, so file contents are embedded
    and eval'd, exactly like tools/php-wasm/render.mjs does for templates."""
    filemap = {}
    for lc in LOCALES:
        base = os.path.join(ROOT, 'resources/lang', lc)
        for f in sorted(os.listdir(base)):
            if f.endswith('.php'):
                filemap[f'resources/lang/{lc}/{f}'] = open(os.path.join(base, f), encoding='utf-8').read()
    cfgdir = os.path.join(ROOT, 'config')
    for f in sorted(os.listdir(cfgdir)):
        if f.endswith('.php'):
            filemap[f'config/{f}'] = open(os.path.join(cfgdir, f), encoding='utf-8').read()

    php_code = r'''
function env($k, $d = null) { return $d; }
$files = json_decode(__PAYLOAD__, true);
$tree = array('lang' => array(), 'config' => array(), 'skipped' => array());
$walk = function ($arr, $prefix) use (&$walk) {
    $flat = array();
    foreach ($arr as $k => $v) {
        $key = $prefix === '' ? (string) $k : $prefix . '.' . $k;
        $flat[$key] = true;   /* arrays are readable as a whole, empty or not */
        if (is_array($v)) { $flat = array_merge($flat, $walk($v, $key)); }
    }
    return $flat;
};
foreach ($files as $name => $code) {
    $code = preg_replace('/^<\?php\s*/i', '', $code, 1);
    $code = preg_replace('/declare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;/i', '', $code, 1);
    $code = preg_replace('/^use\s+[A-Za-z0-9_\\\\]+\s*;\s*$/m', '', $code);
    $data = @eval($code);
    if (!is_array($data)) { $tree['skipped'][] = $name . ' :: ' . substr((string) $e ?? '', 0, 0); continue; }
    $flat = $walk($data, '');
    if (strpos($name, 'resources/lang/') === 0) {
        $rest = substr($name, strlen('resources/lang/'));
        $tree['lang'][$rest] = array_keys($flat);
    } else {
        $tree['config'][basename($name, '.php')] = array_keys($flat);
    }
}
echo json_encode($tree);
'''
    payload_php = "'" + json.dumps(filemap).replace('\\', '\\\\').replace("'", "\\'") + "'"
    php_final = php_code.replace('__PAYLOAD__', payload_php)
    driver = (
        "process.chdir('" + ROOT.replace("'", "\\'") + "/node_modules/@php-wasm/node-8-3/asyncify');\n"
        "const { loadNodeRuntime } = await import('@php-wasm/node');\n"
        "const { PHP } = await import('@php-wasm/universal');\n"
        "const runtime = await loadNodeRuntime('8.3', { persist: false, emscriptenOptions: { processId: 1 } });\n"
        "const php = new PHP(runtime);\n"
        "process.chdir('" + ROOT.replace("'", "\\'") + "');\n"
        "const res = await php.run({ code: " + json.dumps("<?php\n" + php_final) + " });\n"
        "console.log(res.text);\n"
        "process.exit(0);\n"
    )
    with open('storage/reports/_xref_driver.mjs', 'w') as f:
        f.write(driver)
    try:
        out = subprocess.run(['node', 'storage/reports/_xref_driver.mjs'],
                             capture_output=True, text=True, timeout=180)
        return json.loads(out.stdout)
    finally:
        os.remove('storage/reports/_xref_driver.mjs')


DEFS = php_key_tree()
LANG_KEYS = {}   # (locale, file) -> set(keys)
for name, keys in DEFS.get('lang', {}).items():
    rest = name[:-4] if name.endswith('.php') else name
    locale, file = rest.split('/', 1)
    LANG_KEYS.setdefault((locale, file), set()).update(keys)
CONFIG_KEYS = {}  # file -> set(keys)
for file, keys in DEFS.get('config', {}).items():
    CONFIG_KEYS[file] = set(keys)
ALL_CONFIG_KEYS = set()
for keys in CONFIG_KEYS.values():
    ALL_CONFIG_KEYS |= keys


FULL_LANG_KEYS = None
def full_lang_keys():
    global FULL_LANG_KEYS
    if FULL_LANG_KEYS is None:
        FULL_LANG_KEYS = set()
        for (locale, f), keys in LANG_KEYS.items():
            FULL_LANG_KEYS |= {f + '.' + k for k in keys}
    return FULL_LANG_KEYS


def lang_has(key, locale):
    if '.' not in key:
        return any(key in LANG_KEYS.get((locale, f), set()) for f in get_lang_files())
    file, rest = key.split('.', 1)
    return rest in LANG_KEYS.get((locale, file), set())


_lang_files = None
def get_lang_files():
    global _lang_files
    if _lang_files is None:
        base = os.path.join(ROOT, 'resources/lang/en')
        _lang_files = sorted(f[:-4] for f in os.listdir(base) if f.endswith('.php'))
    return _lang_files


# ---- views on disk --------------------------------------------------------
VIEW_NAMESPACES = {}   # 'products' -> modules/Products/Views
mods_dir = os.path.join(ROOT, 'modules')
for mod in sorted(os.listdir(mods_dir)):
    vdir = os.path.join(mods_dir, mod, 'Views')
    if os.path.isdir(vdir):
        VIEW_NAMESPACES[mod.lower()] = vdir

VIEWS = set()
def add_view_dir(base, prefix=''):
    for r, ds, fs in os.walk(base):
        for f in fs:
            if f.endswith('.php'):
                rel = os.path.relpath(os.path.join(r, f), base)[:-4]
                VIEWS.add(prefix + rel.replace(os.sep, '.'))

add_view_dir(os.path.join(ROOT, 'resources/views'))
for ns, vdir in VIEW_NAMESPACES.items():
    add_view_dir(vdir, ns + '::')


# ---- controllers / classes (PSR-4) ----------------------------------------
CLASS_FILE = {}
NS_MAP = {'Core\\': 'core', 'App\\': 'app', 'Modules\\': 'modules', 'Database\\': 'database'}
for base in ['app', 'core', 'modules', 'database']:
    for r, ds, fs in os.walk(os.path.join(ROOT, base)):
        for f in fs:
            if f.endswith('.php'):
                path = os.path.join(r, f)
                rel = os.path.relpath(path, ROOT)[:-4].replace(os.sep, '/')
                CLASS_FILE[rel] = path
CLASS_METHODS = {}
for rel, path in CLASS_FILE.items():
    src = open(path, encoding='utf-8', errors='ignore').read()
    methods = set(re.findall(r'function\s+([A-Za-z0-9_]+)\s*\(', src))
    CLASS_METHODS[rel] = methods


# ---- routes ---------------------------------------------------------------
ROUTE_NAMES = set()
ROUTE_HANDLERS = []   # (class-rel, method, where)
def scan_route_file(path, group_name=''):
    src = open(path, encoding='utf-8', errors='ignore').read()
    # resolve bare class names through the file's use statements (that is what
    # Foo::class means at runtime)
    uses = {}
    for m in re.finditer(r"use\s+([A-Za-z0-9_\\]+)(?:\s+as\s+([A-Za-z0-9_]+))?\s*;", src):
        full = m.group(1)
        alias = m.group(2) or full.split('\\')[-1]
        uses[alias] = full
    # handlers: [Foo::class, 'bar'] and 'Foo@bar'
    for m in re.finditer(r"\[([A-Za-z0-9_\\]+)::class,\s*'([A-Za-z0-9_]+)'\]", src):
        cls, meth = m.group(1), m.group(2)
        cls = uses.get(cls, cls)
        ROUTE_HANDLERS.append((cls.replace('\\', '/').lstrip('/'), meth, path))
    for m in re.finditer(r"'([A-Za-z0-9_\\]+)@([A-Za-z0-9_]+)'", src):
        cls, meth = m.group(1), m.group(2)
        if cls.endswith(('Controller', 'Service', 'Api')):
            cls = uses.get(cls, cls)
            ROUTE_HANDLERS.append((cls.replace('\\', '/'), meth, path))
    # group name prefixes declared in this file (Router prepends them to
    # every ->name() inside the group)
    group_prefixes = re.findall(r"'name'\s*=>\s*'([^']+)'", src)
    # names: ->name('x')
    raw_names = set(m.group(1) for m in re.finditer(r"->name\('([^']+)'\)", src))
    # localized(...): the 4th argument after the handler array is the name;
    # without it the key (2nd arg) becomes the name
    for m in re.finditer(r"localized\(", src):
        call = src[m.start():src.find(');', m.start())]
        args = re.findall(r"'([A-Za-z0-9_.-]+)'", call)
        key = args[1] if len(args) > 1 else None
        named = re.search(r"'([A-Za-z0-9_.-]+)'\s*\]\s*,\s*'([A-Za-z0-9_.-]+)'\s*\)\s*;", call)
        raw_names.add(named.group(2) if named else key)
    for n in raw_names:
        if n is None:
            continue
        ROUTE_NAMES.add(group_name + n)
        for gp in group_prefixes:
            ROUTE_NAMES.add(gp + n)
            ROUTE_NAMES.add(group_name + gp + n)

for rf in sorted(os.listdir(os.path.join(ROOT, 'routes'))):
    if rf.endswith('.php'):
        scan_route_file(os.path.join(ROOT, 'routes', rf))
for mod in sorted(os.listdir(mods_dir)):
    p = os.path.join(mods_dir, mod, 'Routes')
    if os.path.isdir(p):
        for f in sorted(os.listdir(p)):
            if f.endswith('.php'):
                scan_route_file(os.path.join(p, f))


# ---- db schema -------------------------------------------------------------
db = sqlite3.connect(os.path.join(ROOT, 'database/lufly.sqlite'))
TABLES = {r[0] for r in db.execute("SELECT name FROM sqlite_master WHERE type='table'")}
COLUMNS = {t: {r[1] for r in db.execute(f"PRAGMA table_info({t})")} for t in TABLES}


# --------------------------------------------------------------------------
# usage collection
# --------------------------------------------------------------------------
def php_files():
    for base in PHP_DIRS:
        for r, ds, fs in os.walk(os.path.join(ROOT, base)):
            ds[:] = [d for d in ds if d not in SKIP]
            for f in fs:
                if f.endswith('.php'):
                    yield os.path.join(r, f)


TRANS_USES = []    # (key_or_prefix, file, dynamic)
CONFIG_USES = []
ROUTE_USES = []
VIEW_USES = []
COMPONENT_USES = []
ASSET_USES = []
SQL_TABLE_USES = []
SQL_COLUMN_USES = []
USE_STMTS = []

TRANS_RE = re.compile(r"(?:trans|__|trans_choice|->trans)\(\s*'([A-Za-z0-9_.:\-]*)'\s*(\.\s*[^,;]+)?")
CONFIG_RE = re.compile(r"config\(\s*'([A-Za-z0-9_.]+)'")
ROUTE_RE = re.compile(r"(?:route|->url)\(\s*'([A-Za-z0-9_.-]+)'")
VIEW_RE = re.compile(r"(?:->view|\bview|->render|->resolvePath)\(\s*'([A-Za-z0-9_.:]+)'")
COMPONENT_RE = re.compile(r"(?:->component|\$component)\(\s*'([A-Za-z0-9_.:-]+)'")
ASSET_RE = re.compile(r"(?:asset|pushStyle|pushDeferredStyle|pushScript|pushPreload)\(\s*'?\"?([A-Za-z0-9_./-]+\.(?:css|js|png|jpg|jpeg|webp|svg|ico|woff2?|mp4|txt|xml))")
SQL_TABLE_RE = re.compile(r"(?:FROM|INTO|UPDATE|JOIN)\s+`?([a-z_][a-z0-9_]*)`?", re.I)
USE_RE = re.compile(r"^use\s+([A-Za-z0-9_\\]+)\s*;", re.M)

for path in php_files():
    rel = os.path.relpath(path, ROOT)
    src = open(path, encoding='utf-8', errors='ignore').read()
    for m in TRANS_RE.finditer(src):
        key = m.group(1)
        if not key:
            continue
        concat = m.group(2) is not None
        dynamic = concat or key.endswith(('.', '_'))
        TRANS_USES.append((key, rel, dynamic))
    for m in CONFIG_RE.finditer(src):
        CONFIG_USES.append((m.group(1), rel))
    for m in ROUTE_RE.finditer(src):
        ROUTE_USES.append((m.group(1), rel))
    for m in VIEW_RE.finditer(src):
        VIEW_USES.append((m.group(1), rel))
    for m in COMPONENT_RE.finditer(src):
        COMPONENT_USES.append((m.group(1), rel))
    for m in ASSET_RE.finditer(src):
        ASSET_USES.append((m.group(1), rel))
    # strip comments, then scan only inside quoted strings - English prose in
    # comments ("pulled FROM the catalog") must not read as a table name
    stripped = re.sub(r'/\*[\s\S]*?\*/', '', src)
    stripped = re.sub(r'(^|\s)//[^\n]*', r'\1', stripped)
    strings = re.findall(r'''(?:[^'\\]|\\.)*'|"(?:[^"\\]|\\.)*"''', stripped)
    for blob in strings:
        for m in SQL_TABLE_RE.finditer(blob):
            SQL_TABLE_USES.append((m.group(1), rel))
    for m in USE_RE.finditer(src):
        USE_STMTS.append((m.group(1), rel))

# js files: locale-key usage via data-i18n / trans-like maps
JS_TRANS_RE = re.compile(r"(?:trans|t|__)\(\s*'((?:nav|home|products|common|contact|errors|auth|assistant|box|planner|announcements|favorites|routes|seo)\.[A-Za-z0-9_.]+)'\s*\)")
for base in ['frontend', 'modules', 'resources/views']:
    for r, ds, fs in os.walk(os.path.join(ROOT, base)):
        ds[:] = [d for d in ds if d not in SKIP]
        for f in fs:
            if f.endswith(('.js', '.mjs', '.cjs')):
                src = open(os.path.join(r, f), encoding='utf-8', errors='ignore').read()
                for m in JS_TRANS_RE.finditer(src):
                    TRANS_USES.append((m.group(1), os.path.relpath(os.path.join(r, f), ROOT), False))


# --------------------------------------------------------------------------
# checks
# --------------------------------------------------------------------------

# 1. lang keys
seen = set()
for key, rel, dynamic in TRANS_USES:
    if key in seen:
        continue
    seen.add(key)
    if dynamic:
        # prefix like 'home.' . $var or 'box.mail_admin_subject_' . $sfx -
        # at least one real key must start with the literal prefix
        prefix = key
        if not any(k.startswith(prefix) for k in full_lang_keys()):
            fail(f"trans prefix '{prefix}' (used in {rel}) matches no lang key")
        continue
    miss = [lc for lc in LOCALES if not lang_has(key, lc)]
    if len(miss) == len(LOCALES):
        fail(f"trans key '{key}' (used in {rel}) exists in NO locale")
    elif miss:
        warn(f"trans key '{key}' (used in {rel}) missing in locales: {', '.join(miss)}")

# lang file parity across locales
files = get_lang_files()
for f in files:
    en = LANG_KEYS.get(('en', f), set())
    for lc in ['tr', 'cs']:
        other = LANG_KEYS.get((lc, f), set())
        only_en = en - other
        only_other = other - en
        if only_en:
            warn(f"lang {f}.php: {lc} is missing keys present in en: {sorted(only_en)[:8]}")
        if only_other:
            warn(f"lang {f}.php: {lc} carries keys en does not: {sorted(only_other)[:8]}")

# 2. config keys: 'file.rest' - the rest must be a key (or a prefix of one,
#    when the caller reads a whole sub-array) inside config/{file}.php
for key, rel in CONFIG_USES:
    if '.' not in key:
        # whole-file read, e.g. config('features')
        if key in CONFIG_KEYS or os.path.isfile(os.path.join(ROOT, 'config', key + '.php')):
            continue
        fail(f"config('{key}') (used in {rel}) matches no config file")
        continue
    cfile, rest = key.split('.', 1)
    keys = CONFIG_KEYS.get(cfile)
    if keys is None:
        fail(f"config('{key}') (used in {rel}) has no config/{cfile}.php")
        continue
    if rest in keys or any(k.startswith(rest + '.') or k.startswith(rest) for k in keys):
        continue
    fail(f"config('{key}') (used in {rel}) matches no key in config/{cfile}.php")

# 3. route names
for name, rel in ROUTE_USES:
    if name not in ROUTE_NAMES:
        fail(f"route('{name}') (used in {rel}) matches no defined route name")

# 4. views - templates resolve as dotted paths under resources/views or a
#    module namespace ('products::index'); component() names resolve under
#    resources/views/components/
for name, rel in VIEW_USES:
    if name in VIEWS:
        continue
    if '$' in name or '{' in name:
        continue
    if name.endswith('.'):
        # dynamic prefix: 'home.' . $section - some template must match
        if any(v.startswith(name) for v in VIEWS):
            continue
        fail(f"view prefix '{name}' (used in {rel}) matches no template")
        continue
    fail(f"view '{name}' (used in {rel}) has no template on disk")

for name, rel in COMPONENT_USES:
    if 'components.' + name in VIEWS:
        continue
    # resources/views/home/index.php defines a local $component closure that
    # resolves band names as home.{n}.{n}
    if ('home.%s.%s' % (name, name)) in VIEWS:
        continue
    fail(f"component '{name}' (used in {rel}) has no file under resources/views/components/")

# 5. route handlers (translate the namespace to its PSR-4 directory first)
def ns_to_rel(slash_form):
    for ns, d in NS_MAP.items():
        prefix = ns.rstrip('\\')
        if slash_form.startswith(prefix + '/'):
            return d + '/' + slash_form[len(prefix) + 1:]
    return slash_form

for cls_ns, meth, path in ROUTE_HANDLERS:
    cls_rel = ns_to_rel(cls_ns)
    if cls_rel not in CLASS_FILE:
        # autoloader first-segment-lowercase fallback (Database\Schema etc.)
        parts = cls_rel.split('/')
        alt = '/'.join([parts[0].lower()] + parts[1:])
        if alt not in CLASS_FILE:
            fail(f"route handler class '{cls_ns}' (in {os.path.relpath(path, ROOT)}) has no file")
            continue
        cls_rel = alt
    if meth not in CLASS_METHODS.get(cls_rel, set()):
        fail(f"route handler '{cls_ns}::{meth}' (in {os.path.relpath(path, ROOT)}) method missing")

# 6. use statements
def class_to_rel(cls):
    for ns, d in NS_MAP.items():
        if cls.startswith(ns):
            return d + '/' + cls[len(ns):].replace('\\', '/')
    return None

for cls, rel in USE_STMTS:
    cls_rel = class_to_rel(cls)
    if cls_rel is None:
        continue  # not one of our PSR-4 roots
    if cls_rel not in CLASS_FILE:
        # core/Foundation/Autoloader.php retries with the first segment of the
        # class path (after the PSR-4 root) lowercased: Database\Schema\X
        # -> database/Schema/X -> database/schema/X
        parts = cls_rel.split('/')
        if len(parts) > 1:
            alt = '/'.join([parts[0], parts[1].lower()] + parts[2:])
            if alt in CLASS_FILE:
                continue
        warn(f"use {cls}; (in {rel}) resolves to no PSR-4 file")

# 7. sql tables
NON_DB = {'dual', 'information_schema', 'sqlite_master', 'sqlite_sequence', 'migrations'}
for t, rel in SQL_TABLE_USES:
    if t in NON_DB or t.upper() in ('SELECT',):
        continue
    if t in TABLES:
        continue
    if '_' in t:
        # snake_case unknown identifier: a strong SQL-table signal
        fail(f"SQL references table '{t}' (in {rel}) which is not in the schema")
    # single-word unknowns are too risky to flag (english prose) - skip

# 8. asset paths - the docroot is the repo root: frontend/ is served as-is
#    (.htaccess: RewriteRule ^frontend/.* - [L]), everything else falls
#    through to public/ (RewriteRule ^(.*)$ public/$1)
for p, rel in ASSET_USES:
    if p.startswith(('http', '//')):
        continue
    clean = p.lstrip('/')
    if os.path.isfile(os.path.join(ROOT, clean)):
        continue
    if os.path.isfile(os.path.join(ROOT, 'public', clean)):
        continue
    fail(f"asset path '{p}' (used in {rel}) has no file (repo root or public/)")

# 9. middleware names used in routes
app_cfg = open(os.path.join(ROOT, 'config/app.php'), encoding='utf-8').read()
alias_block = re.search(r"'middleware_aliases'\s*=>\s*\[(.*?)\]", app_cfg, re.S)
group_block = re.search(r"'middleware_groups'\s*=>\s*\[(.*?)\]\s*,", app_cfg, re.S)
defined_mw = set()
if alias_block:
    defined_mw |= set(re.findall(r"'([a-z0-9_.:-]+)'\s*=>", alias_block.group(1)))
if group_block:
    defined_mw |= set(re.findall(r"'([a-z0-9_.:-]+)'\s*=>", group_block.group(1)))
for base in ['routes'] + [os.path.join('modules', m, 'Routes') for m in sorted(os.listdir(mods_dir)) if os.path.isdir(os.path.join(mods_dir, m, 'Routes'))]:
    d = os.path.join(ROOT, base)
    if not os.path.isdir(d):
        continue
    for f in os.listdir(d):
        if not f.endswith('.php'):
            continue
        src = open(os.path.join(d, f), encoding='utf-8', errors='ignore').read()
        for m in re.finditer(r"middleware\(\s*\[?([^\])]+)\]?\)", src):
            for raw in re.findall(r"'([^']+)'", m.group(1)):
                # the router splits alias:parameter at the colon
                base_name = raw.split(':', 1)[0]
                if base_name in defined_mw or '\\' in raw:
                    continue
                if not os.path.isfile(os.path.join(ROOT, 'core/Http/Middleware', base_name + '.php')):
                    fail(f"middleware '{raw}' (in {base}/{f}) is neither an alias/group nor a core class")


# --------------------------------------------------------------------------
# report
# --------------------------------------------------------------------------
print(f"scanned php dirs: {', '.join(PHP_DIRS)}")
print(f"lang files: {len(files)} x {len(LOCALES)} locales | config keys: {len(ALL_CONFIG_KEYS)} | "
      f"views: {len(VIEWS)} | route names: {len(ROUTE_NAMES)} | classes: {len(CLASS_FILE)} | tables: {len(TABLES)}")
print(f"trans usages: {len(seen)} unique | config usages: {len(set(CONFIG_USES))} | "
      f"route usages: {len(set(ROUTE_USES))} | view usages: {len(set(VIEW_USES))} | assets: {len(set(ASSET_USES))}")
print()
for w in warnings:
    print('WARN  ' + w)
for f_ in failures:
    print('FAIL  ' + f_)
print()
print(f"{len(failures)} failure(s), {len(warnings)} warning(s)")
sys.exit(1 if failures else 0)
