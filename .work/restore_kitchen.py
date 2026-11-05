"""Roll every Kitchen product photo (and its media row) back to the state it had
in commit 82ca5ee -- i.e. before any of the re-render/engrave work."""
import json, re, subprocess, sqlite3
from pathlib import Path

BASE = "82ca5ee"
paths = [l.strip() for l in open('/tmp/kitchen_media.txt') if l.strip()]
files = ["public" + p for p in paths]

# 1. photos straight out of the base commit
subprocess.run(["git", "checkout", BASE, "--"] + files, check=True)
print(f"restored {len(files)} image files from {BASE}")

# 2. media rows: copy the base values over the live sqlite
subprocess.run(["git", "show", f"{BASE}:database/lufly.sqlite"], stdout=open('/tmp/base.sqlite','wb'), check=True)
base = sqlite3.connect('/tmp/base.sqlite')
cur = sqlite3.connect('database/lufly.sqlite')
cols = [r[1] for r in base.execute("pragma table_info(media)")]
for p in paths:
    row = base.execute("select * from media where path=?", (p,)).fetchone()
    if row is None:
        continue
    vals = dict(zip(cols, row))
    sets = ", ".join(f"{c}=?" for c in cols if c != 'path')
    data = [vals[c] for c in cols if c != 'path'] + [p]
    cur.execute(f"update media set {sets} where path=?", data)
    # keep the json meta in sync with the restored byte size
    if vals.get('meta'):
        try:
            meta = json.loads(vals['meta'])
            meta['sha256'] = meta.get('sha256')
        except Exception:
            pass
cur.commit()
print("media rows restored:", cur.total_changes)
