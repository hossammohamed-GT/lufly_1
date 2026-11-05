"""Restore the Kitchen `media` rows in both SQL dumps to their base-commit values.

Only the fields the batch work touched (size / width / height) are rewritten;
everything else must already match the base row once escapes are decoded."""
import re, sqlite3
from pathlib import Path

BASE = "/tmp/base.sqlite"
paths = [l.strip() for l in open('/tmp/kitchen_media.txt') if l.strip()]
base = sqlite3.connect(BASE)
cols = [r[1] for r in base.execute("pragma table_info(media)")]
want = {}
for p in paths:
    row = base.execute("select * from media where path=?", (p,)).fetchone()
    if row:
        want[row[cols.index('id')]] = dict(zip(cols, row))
print('rows to restore:', len(want))


def split(tup: str):
    body, out, i = tup[1:-1], [], 0
    while i < len(body):
        while i < len(body) and body[i] in ' ,':
            i += 1
        if i >= len(body):
            break
        if body[i] == "'":
            j, buf = i + 1, "'"
            while j < len(body):
                c = body[j]
                if c == '\\':
                    buf += body[j:j+2]; j += 2; continue
                buf += c; j += 1
                if c == "'":
                    break
            out.append(buf); i = j
        else:
            j = body.find(',', i)
            if j < 0: j = len(body)
            out.append(body[i:j].strip()); i = j
    return out


def unescape(lit: str) -> str:
    if lit == 'NULL':
        return None
    s = lit[1:-1]
    return re.sub(r'\\(.)', r'\1', s)


for name in ('lufly-database.sql', 'lufly_db_new.sql'):
    path = Path(name)
    lines = path.read_text(encoding='utf-8').split('\n')
    in_media = False
    rewritten = 0
    for i, line in enumerate(lines):
        if not in_media:
            if "INSERT INTO `media`" not in line:
                continue
            in_media = True
        m = re.search(r"(\((?:[^()']|'(?:[^'\\]|\\.)*')*\))", line[line.find('VALUES') + 6:] if 'VALUES' in line else line)
        off = line.find('VALUES') + 6 if 'VALUES' in line else 0
        if m:
            tup = m.group(1)
            fields = split(tup)
            if len(fields) == len(cols) and re.fullmatch(r'\d+', fields[0]):
                d = dict(zip(cols, fields))
                rid = int(d['id'])
                if rid in want:
                    ref = want[rid]
                    # everything except the three geometry fields must already agree
                    for c in cols:
                        if c in ('size', 'width', 'height', 'path', 'id'):
                            continue
                        if unescape(d[c]) != unescape("'" + str(ref[c]).replace("\\", "\\\\").replace("'", "\\'") + "'" if ref[c] is not None else 'NULL'):
                            raise SystemExit(f"{name} row {rid}: unexpected change in {c}")
                    new = tup
                    for c in ('size', 'width', 'height'):
                        fld = d[c]
                        new = new.replace(fld, str(ref[c]), 1) if False else new
                    d['size'], d['width'], d['height'] = (str(ref['size']), str(ref['width']), str(ref['height']))
                    rebuilt = '(' + ', '.join(d[c] for c in cols) + ')'
                    if rebuilt != tup:
                        lines[i] = line[:off + m.start(1)] + rebuilt + line[off + m.end(1):]
                        rewritten += 1
        if line.rstrip().endswith(';'):
            in_media = False
    path.write_text('\n'.join(lines), encoding='utf-8')
    print(name, 'rows rewritten:', rewritten)
