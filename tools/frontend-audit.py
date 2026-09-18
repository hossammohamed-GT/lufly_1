"""Frontend gate: no raw colors, all tokens defined, no orphan classes.

Run from the project root:  python3 tools/frontend-audit.py
"""
import re, os, glob, subprocess

print('=' * 60)
print('LUFLY frontend + docs audit')
print('=' * 60)

style = open('frontend/design-system/style.css', encoding='utf-8').read()
defined_tokens = set(re.findall(r'(--[\w-]+)\s*:', style))

# 1 raw colors outside style.css
comp = [p for p in glob.glob('frontend/**/*.css', recursive=True) if 'design-system/style.css' not in p]
raw = []
for p in comp:
    src = re.sub(r'white-space|nowrap|--weight-black|--ds-band-|font-black', '', open(p, encoding='utf-8').read())
    for m in re.finditer(r'#[0-9a-fA-F]{3,8}\b|rgba?\(|hsla?\(|(?<![-\w])(?:white|black|red|blue|green|gray|grey)(?![-\w])', src):
        raw.append((p, m.group(0)))
print(f'1. raw colors outside style.css: {len(raw)} {raw[:5]}')

# 2 undefined tokens
# A component may declare its own local (non-colour) custom properties; they count
# as defined for the file that declares them.
undef = set()
for p in glob.glob('frontend/**/*.css', recursive=True):
    src = open(p, encoding='utf-8').read()
    local_tokens = set(re.findall(r'(--[\w-]+)\s*:', src))
    for m in re.finditer(r'var\((--[\w-]+)', src):
        if m.group(1) not in defined_tokens and m.group(1) not in local_tokens:
            undef.add((p, m.group(1)))
print(f'2. undefined css variables: {len(undef)} {sorted(undef)[:5]}')

# 3 classes used without css
css = ''
for p in glob.glob('frontend/**/*.css', recursive=True):
    css += re.sub(r'/\*.*?\*/', '', open(p, encoding='utf-8').read(), flags=re.S)
defined = {m.group(1) for m in re.finditer(r'\.(-?[_a-zA-Z][\w-]*)', css)}
used = {}
for root in ('resources', 'modules', 'frontend/js'):
    for dp, dn, fn in os.walk(root):
        for f in fn:
            if f.endswith(('.php', '.js')):
                path = os.path.join(dp, f)
                src = open(path, encoding='utf-8', errors='ignore').read()
                for m in re.finditer(r'class="([^"<>]*)"', src):
                    for token in m.group(1).split():
                        if re.match(r'^[a-z][\w-]*$', token):
                            used.setdefault(token, set()).add(os.path.basename(path))
print(f'3. classes without css: {sorted((c, sorted(v)) for c, v in used.items() if c not in defined)}')

# 4 inline styles in component views
inline = []
for root in ('resources/views', 'modules'):
    for dp, dn, fn in os.walk(root):
        for f in fn:
            if f.endswith('.php'):
                path = os.path.join(dp, f)
                hit = re.findall(r'style="[^"]*"', open(path, encoding='utf-8').read())
                if hit:
                    inline.append((path, len(hit)))
print(f'4. views with inline style attributes: {len(inline)} {sorted(inline)[:6]}')

# 5 lang keys
langs = {}
for loc in ('en', 'tr', 'cs'):
    keys = set()
    for f in glob.glob(f'resources/lang/{loc}/*.php'):
        ns = os.path.basename(f)[:-4]
        for m in re.finditer(r"'([\w.-]+)'\s*=>", open(f, encoding='utf-8').read()):
            keys.add(f'{ns}.{m.group(1)}')
    langs[loc] = keys
used_keys = set()
for root in ('resources', 'modules'):
    for dp, dn, fn in os.walk(root):
        for f in fn:
            if f.endswith('.php'):
                for m in re.finditer(r"trans\('([\w.]+)'", open(os.path.join(dp, f), encoding='utf-8').read()):
                    used_keys.add(m.group(1))
for loc in ('en', 'tr', 'cs'):
    miss = sorted(k for k in used_keys if k not in langs[loc] and not k.endswith('.'))
    print(f'5.{loc} missing translation keys: {miss}')

# 6 assets on disk
bad = []
for root in ('resources', 'modules'):
    for dp, dn, fn in os.walk(root):
        for f in fn:
            if f.endswith('.php'):
                path = os.path.join(dp, f)
                for m in re.finditer(r"asset\('([^']+)'\)", open(path, encoding='utf-8').read()):
                    target = m.group(1)
                    if target.startswith('http'):
                        continue
                    if not (os.path.exists(target) or os.path.exists(os.path.join('public', target))):
                        bad.append((path, target))
print(f'6. missing asset targets: {len(bad)} {bad[:5]}')

# 7 stale references to deleted paths
stale = []
for root in ('resources', 'modules', 'frontend', 'docs', 'README.md'):
    paths = [root] if os.path.isfile(root) else [os.path.join(dp, f) for dp, dn, fn in os.walk(root) for f in fn]
    for path in paths:
        if path.endswith(('.php', '.css', '.js', '.md', '.htaccess')) or path == 'README.md':
            if 'docs/adr/' in path.replace('\\', '/'):
                continue  # decision records keep their original wording
            src = open(path, encoding='utf-8', errors='ignore').read()
            for needle in ('public/frontend', 'admin/assets', 'tidal-monolith', 'atlas.html', 'hero-cinematic', 'hero-scenes', 'theme-switcher.js'):
                if needle in src:
                    stale.append((path, needle))
print(f'7. stale references: {stale}')

# 8 js syntax
js = subprocess.run(['bash', '-c', 'for f in $(find frontend -name "*.js"); do node --check "$f" || echo "FAIL $f"; done'], capture_output=True, text=True)
print('8. js syntax:', 'clean' if js.returncode == 0 and not js.stdout.strip() else js.stdout.strip()[:200])

# 9 size summary
tot = sum(open(p, encoding='utf-8').read().count('\n') for p in glob.glob('frontend/**/*.css', recursive=True))
print(f'9. css lines total: {tot}; component files: {len(comp)}; style.css: {style.count(chr(10))}')
