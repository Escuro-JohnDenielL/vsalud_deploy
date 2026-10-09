# JavaScript and Blade hook checklist

This baseline was scanned from `resources/js` and `resources/views` in the fresh `landing-design-revamp` worktree. The scanner reads all Blade views so it also captures markup hooks used by inline Blade scripts.

## Baseline count

The scan found **2,178 distinct entries**. An entry is one unique combination of hook kind, normalized hook value, and source file; it is **not every occurrence**. Repeated occurrences of the same hook in the same file count once, and the reported line is the first occurrence. The same hook in another file counts as another entry.

For a concise, deduplicated view across files and hook kinds, the baseline has **404 unique IDs**, **556 unique literal class names**, and **55 unique data-attribute names**. There are also **44 distinct generated-class expressions**; these are listed separately because their runtime values are not literal class names.

The earlier checklist recorded **593 entries** because its scanner inspected JavaScript plus inline Blade `<script>` bodies only. Re-running that same earlier scanner against the later `railway` source returns **598**: five additional hooks came with subsequent landing-page changes to `resources/js/index.js` and `resources/views/index.blade.php`. This checklist's broader scan also includes Blade markup IDs/classes/data attributes and inline style states, and separates hook kinds such as markup classes, class-state operations, and direct display/color values. Those expanded categories account for the increase from 598 to 2,178; it is a coverage/counting-scope difference, not 1,580 new occurrences of the same selectors.

| Entry kind | Count |
|---|---:|
| Class attributes | 865 |
| `classList` states | 54 |
| `className` states | 30 |
| Data attributes | 50 |
| Data attribute accessors | 3 |
| `dataset` accessors | 33 |
| Generated or Blade-interpolated classes | 46 |
| Generated selectors | 3 |
| IDs in markup | 387 |
| `getElementById` hooks | 361 |
| Direct inline states in JS | 84 |
| Inline style state attributes | 180 |
| Selector hooks | 82 |
| **Total** | **2,178** |

## Exact scan command

Run this PowerShell command from the repository root. It prints one TSV row per distinct hook, including the first source line. Generated class expressions are retained verbatim as well as scanned for their literal class portions.

```powershell
$scan = @'
const fs = require('node:fs');
const path = require('node:path');
const files = [];
function walk(dir, accept) {
  for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
    const full = path.join(dir, entry.name);
    if (entry.isDirectory()) walk(full, accept);
    else if (accept(entry.name)) files.push(full);
  }
}
walk('resources/js', name => name.endsWith('.js'));
walk('resources/views', name => name.endsWith('.blade.php'));
const hooks = new Map();
function add(kind, value, file, source, index) {
  const line = source.slice(0, index).split('\n').length;
  const normalized = String(value).replace(/\s+/g, ' ').trim();
  if (!normalized) return;
  const key = [kind, normalized, file.replaceAll('\\', '/')].join('\t');
  if (!hooks.has(key)) hooks.set(key, [key, line].join('\t'));
}
function addClassTokens(kind, value, file, source, index) {
  const normalized = value.replace(/\$\{[\s\S]*?\}/g, 'DYNAMIC').replace(/{{[\s\S]*?}}/g, 'DYNAMIC');
  for (const token of normalized.split(/\s+/).filter(Boolean)) add(kind, token, file, source, index);
  if (/\$\{|{{/.test(value)) add('generated-class', value, file, source, index);
}
for (const file of files) {
  const source = fs.readFileSync(file, 'utf8');
  for (const match of source.matchAll(/\b(id|class|data-[\w:-]+|style)\s*=\s*(["'])([\s\S]*?)\2/g)) {
    const attr = match[1], value = match[3], index = match.index;
    if (attr === 'class') addClassTokens('class-attribute', value, file, source, index);
    else if (attr === 'style' && /\b(display|visibility|opacity|color|background(?:-color)?|border-color)\s*:/i.test(value)) add('inline-style-state', value, file, source, index);
    else if (attr !== 'style') add(attr === 'id' ? 'id' : 'data-attribute', `${attr}=${value}`, file, source, index);
  }
  for (const match of source.matchAll(/\bgetElementById\(\s*(["'])(.*?)\1\s*\)/g)) add('id-selector', match[2], file, source, match.index);
  for (const match of source.matchAll(/\b(querySelector|querySelectorAll|closest|matches)\(\s*(["'`])([\s\S]*?)\2\s*\)/g)) {
    const selector = match[3];
    for (const token of selector.matchAll(/#[\w-]+|\.[\w-]+|\[data-[\w-]+(?:=[^\]]+)?\]/g)) add('selector', token[0], file, source, match.index);
    if (/\$\{|{{/.test(selector)) add('generated-selector', selector, file, source, match.index);
  }
  for (const match of source.matchAll(/classList\.(add|remove|toggle|contains)\(([^)]*)\)/g)) {
    for (const token of match[2].matchAll(/(["'])([^"']+)\1/g)) addClassTokens('classList-state', token[2], file, source, match.index);
    if (/\$\{|{{/.test(match[2])) add('generated-class', match[2], file, source, match.index);
  }
  for (const match of source.matchAll(/\bclassName\s*=\s*(["'`])([\s\S]*?)\1/g)) addClassTokens('className-state', match[2], file, source, match.index);
  for (const match of source.matchAll(/\.(getAttribute|setAttribute|removeAttribute)\(\s*(["'])(data-[\w-]+)\2/g)) add('data-attribute-access', match[3], file, source, match.index);
  for (const match of source.matchAll(/\.dataset\.([A-Za-z_$][\w$]*)/g)) add('dataset-access', match[1].replace(/[A-Z]/g, c => `-${c.toLowerCase()}`), file, source, match.index);
  for (const match of source.matchAll(/\.style\.(display|visibility|opacity|color|background|backgroundColor|borderColor|width)\s*=\s*([^;\n]+)/g)) add('inline-state', `${match[1]}=${match[2].trim()}`, file, source, match.index);
}
const rows = [...hooks.values()].sort((a,b) => a.localeCompare(b));
console.log(rows.join('\n'));
'@;
$scan | node
```

## Generated and dynamic classes

The scanner records quoted `classList`/`className` values, class attributes, and interpolated class expressions. Blade `{{ ... }}` and JavaScript `${ ... }` expressions are retained as `generated-class` entries instead of being treated as literal class names. Their source file and line identify where to check the set of possible values.

This includes calendar availability states (`Available`, `Half`, `Nearly`, `Full`, `Closed`), audit category classes (`audit-cat-{{ $log->category_key }}` and `audit-cat-${entry.category_key || 'system'}`), and drag states (`dragging`, `drag-over`). State-setting calls such as `classList.add/remove` are recorded even when their corresponding DOM elements are generated at runtime.

## How to verify after each batch

1. Before editing, run the scan and save its TSV rows in a PowerShell variable: `$before = $scan | node`.
2. After the batch, rerun the exact scan command and capture `$after = $scan | node`.
3. Compare the captures with `Compare-Object -ReferenceObject $before -DifferenceObject $after`.
4. Investigate every removed or changed hook. Confirm the behavior still has an equivalent hook before accepting an intentional change; visual-only batches should normally preserve the baseline entries.
5. Confirm the expected entry count and inspect the batch diff before staging the approved file paths.
