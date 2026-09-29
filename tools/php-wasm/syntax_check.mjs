#!/usr/bin/env node
/**
 * Full-repo PHP syntax check.
 *
 * lint.mjs tokenizes the files that changed; this one walks EVERY tracked
 * .php file and asks the PHP engine itself to parse the token stream
 * (token_get_all with TOKEN_PARSE throws a ParseError on malformed code),
 * then verifies brace/paren/bracket balance across the token stream. It
 * catches unterminated strings and heredocs, stray characters, unbalanced
 * constructs and context-level syntax errors in files no route ever
 * executes (dead code still has to parse).
 *
 *   node tools/php-wasm/syntax_check.mjs            # every tracked .php file
 *   node tools/php-wasm/syntax_check.mjs app core   # just these subtrees
 */

import { execSync } from 'node:child_process';
import { readFileSync } from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');

/* the wasm loader resolves its binary relative to cwd */
process.chdir(path.join(ROOT, 'node_modules/@php-wasm/node-8-3/asyncify'));
const { loadNodeRuntime } = await import('@php-wasm/node');
const { PHP } = await import('@php-wasm/universal');
const runtime = await loadNodeRuntime('8.3', {
  persist: false,
  emscriptenOptions: { processId: 1 },
});
const php = new PHP(runtime);
process.chdir(ROOT);

/* ------------------------------------------------------------------ files */
let filter = process.argv.slice(2);
if (filter.length === 0) filter = [''];
const all = execSync('git ls-files -- "*.php"; git ls-files --others --exclude-standard -- "*.php"', { cwd: ROOT, shell: '/bin/bash' })
  .toString().split('\n').map((s) => s.trim()).filter(Boolean);
const files = [...new Set(all)].filter((f) => filter.some((p) => f.startsWith(p)));
if (files.length === 0) {
  console.log('no php files to check');
  process.exit(0);
}

const fileMap = {};
for (const f of files) fileMap[f] = readFileSync(path.join(ROOT, f), 'utf8');

/* ---------------------------------------------------------------- harness */
const phpString = (s) => "'" + s.replace(/\\/g, '\\\\').replace(/'/g, "\\'") + "'";

const CHECK = `
$files = json_decode(${phpString(JSON.stringify(fileMap))}, true);
$problems = array();
foreach ($files as $name => $code) {
    $errs = array();
    /* eval() cannot host a declare(strict_types=1) that is not first, but
       token_get_all does not care; strip nothing. TOKEN_PARSE asks the
       real parser to validate the token stream. */
    try {
        $tokens = token_get_all($code, TOKEN_PARSE);
        $depth = array('brace' => 0, 'paren' => 0, 'bracket' => 0);
        $openers = array();
        foreach ($tokens as $t) {
            if (is_array($t)) {
                if ($t[0] === T_CURLY_OPEN || $t[0] === T_DOLLAR_OPEN_CURLY_BRACES) { $depth['brace']++; $openers[] = 'curly-interp'; }
                continue;
            }
            if ($t === '{' || $t[0] === '{') { $depth['brace']++; $openers[] = 'brace'; }
            elseif ($t === '}') { $depth['brace']--; if ($depth['brace'] < 0) { $errs[] = 'unexpected }'; $depth['brace'] = 0; } elseif (!empty($openers)) { array_pop($openers); } }
            elseif ($t === '(') { $depth['paren']++; }
            elseif ($t === ')') { $depth['paren']--; if ($depth['paren'] < 0) { $errs[] = 'unexpected )'; $depth['paren'] = 0; } }
            elseif ($t === '[') { $depth['bracket']++; }
            elseif ($t === ']') { $depth['bracket']--; if ($depth['bracket'] < 0) { $errs[] = 'unexpected ]'; $depth['bracket'] = 0; } }
        }
        if ($depth['brace'] !== 0) { $errs[] = 'brace balance ' . $depth['brace'] . ($depth['brace'] > 0 ? ' (missing } or heredoc left open?)' : ''); }
        if ($depth['paren'] !== 0) { $errs[] = 'paren balance ' . $depth['paren']; }
        if ($depth['bracket'] !== 0) { $errs[] = 'bracket balance ' . $depth['bracket']; }
    } catch (ParseError $e) {
        $errs[] = 'ParseError: ' . $e->getMessage() . ' @ line ' . $e->getLine();
    } catch (Error $e) {
        $errs[] = get_class($e) . ': ' . $e->getMessage();
    }
    if ($errs) { $problems[$name] = $errs; }
}
echo json_encode($problems);
`;

const result = await php.run({ code: `<?php\n${CHECK}` });
const out = JSON.parse(result.text);
const names = Object.keys(out);
if (names.length === 0) {
  console.log('all %d php files parse clean (TOKEN_PARSE + balance)', files.length);
  process.exit(0);
}
for (const name of names) {
  console.log('FAIL ' + name);
  for (const e of out[name]) console.log('      ' + e);
}
console.log('\n%d of %d files failed', names.length, files.length);
process.exit(1);
