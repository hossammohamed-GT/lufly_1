import re, sys, json

def parse_dump(path, tables=None):
    """Yield (table, [row tuples]) for INSERT statements."""
    data = {}
    buf = open(path, encoding='utf-8', errors='replace').read()
    cols_re = re.compile(r"INSERT INTO [`]?(\w+)[`]?\s*\(([^)]*)\)\s*VALUES", re.I)
    pos = 0
    while True:
        m = cols_re.search(buf, pos)
        if not m: break
        table = m.group(1)
        cols = [c.strip().strip('`') for c in m.group(2).split(',')]
        i = m.end()
        rows = []
        # parse tuples until statement-ending ';'
        n = len(buf)
        while i < n:
            while i < n and buf[i] in ' \n\r\t,': i += 1
            if i >= n or buf[i] != '(': break
            i += 1
            vals = []
            cur = ''
            while True:
                ch = buf[i]
                if ch == "'":
                    i += 1
                    s = []
                    while True:
                        c = buf[i]
                        if c == '\\':
                            nxt = buf[i+1]
                            s.append({'n':'\n','t':'\t','r':'\r','0':'\0'}.get(nxt, nxt))
                            i += 2
                        elif c == "'":
                            if buf[i+1] == "'":
                                s.append("'"); i += 2
                            else:
                                i += 1; break
                        else:
                            s.append(c); i += 1
                    vals.append(''.join(s))
                    # skip to , or )
                    while buf[i] in ' \n\r\t': i += 1
                    if buf[i] == ',': i += 1
                    continue
                if ch in ',)':
                    tok = cur.strip()
                    if tok != '':
                        vals.append(None if tok.upper()=='NULL' else tok)
                    cur = ''
                    i += 1
                    if ch == ')': break
                    continue
                cur += ch; i += 1
            rows.append(dict(zip(cols, vals)))
            while i < n and buf[i] in ' \n\r\t': i += 1
            if i < n and buf[i] == ';':
                i += 1; break
        if tables is None or table in tables:
            data.setdefault(table, []).extend(rows)
        pos = i
    return data

if __name__ == '__main__':
    d = parse_dump(sys.argv[1])
    for t, rows in d.items():
        print(t, len(rows))
