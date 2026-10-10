# JavaScript and Blade hook checklist

This baseline was scanned from `resources/js` and `resources/views` in the fresh `landing-design-revamp` worktree. The scanner reads all Blade views so it also captures markup hooks used by inline Blade scripts.

## Baseline count

The current scan found **2,244 distinct entries**. An entry is one unique combination of hook kind, normalized hook value, and source file; it is **not every occurrence**. Repeated occurrences of the same hook in the same file count once, and the reported line is the first occurrence. The same hook in another file counts as another entry. The previous checklist recorded 2,213 entries; rerunning its exact scanner against the committed pre-Batch 6b `HEAD` returned 2,217, so 2,217 is the reproducible baseline for this batch.

This scanner covers Blade markup, JavaScript selectors and states, data attributes, and inline display/color states. Earlier 593/598 counts came from a narrower scanner and should not be compared as if they counted the same categories.

| Entry kind | Count |
|---|---:|
| Class attributes | 952 |
| `classList` states | 66 |
| `className` states | 32 |
| Data attributes | 50 |
| Data attribute accessors | 3 |
| `dataset` accessors | 33 |
| Generated or Blade-interpolated classes | 47 |
| Generated selectors | 3 |
| IDs in markup | 387 |
| `getElementById` hooks | 361 |
| Direct inline states in JS | 60 |
| Inline style state attributes | 168 |
| Selector hooks | 82 |
| **Total** | **2,244** |

## Batch 6a hook review

No functional ID, selector, data-attribute, or existing class hooks were removed. Eleven visual inline-state entries were intentionally replaced by scoped CSS classes: nine direct calendar color/clear assignments and two Blade inline styles. The new classes are `vs-admin-dashboard`, `vs-admin-dashboard__banner`, `vs-admin-dashboard__packages-grid`, `vs-admin-dashboard__edit-image`, `vs-admin-dashboard__edit-image--optional`, `vs-admin-reservation`, `vs-admin-reservation__layout`, `vs-admin-reservation__notice`, `vs-admin-reservation__legend-box`, `vs-admin-reservation__legend-box--success`, `vs-admin-reservation__legend-box--warning`, `vs-admin-reservation__legend-box--danger`, `vs-admin-reservation__legend-box--neutral`, `vs-calendar-day--success`, `vs-calendar-day--warning`, `vs-calendar-day--danger`, and `vs-calendar-day--neutral`.

## Batch 6b Commit 1 hook review

The exact scanner returned **2,236 entries**, up from a reproducible pre-batch scan of 2,217. It found **zero removed functional hooks**: IDs, selectors, data attributes, and existing classes remain available. Two generated audit category expressions changed to include shared badge classes, and three inline visual states in reservation logs were replaced with the scoped load-error/hidden styling. Added classes are `vs-admin-audit-logs`, `vs-admin-audit-logs__detail-modal`, `vs-admin-reservation-logs`, `vs-admin-reservation-logs__confirm-message`, `vs-admin-reservation-logs__confirm-modal`, `vs-admin-reservation-logs__detail-grid`, `vs-admin-reservation-logs__load-error`, `vs-admin-reservation-logs__pagination`, `vs-admin-reservation-logs__view-modal`, `vs-badge`, `vs-badge--danger`, `vs-badge--info`, `vs-badge--neutral`, `vs-table`, `vs-table__actions`, `vs-table__row`, and `vs-table-wrap`.

## Batch 6b Commit 2 hook review

The exact scanner returned **2,244 entries**, up from 2,236 before payment logs. It found **zero removed functional hooks**: payment IDs, selectors, data attributes, and existing classes remain available. Nine inline display states were replaced with `hidden` attributes or state toggles while retaining the same DOM IDs and interaction hooks. New page classes are `vs-admin-payment-logs`, `vs-admin-payment-logs__info-modal`, `vs-admin-payment-logs__modal`, `vs-admin-payment-logs__receipt-content`, `vs-admin-payment-logs__receipt-frame`, `vs-admin-payment-logs__receipt-modal`, `vs-admin-payment-logs__status-message`, `vs-admin-payment-logs__status-modal`, `vs-admin-payment-logs__status-note`, `vs-admin-payment-logs__status-note-input`, and `vs-admin-payment-logs__status-note-label`; shared classes added are `vs-badge`, `vs-form-control`, `vs-table`, and `vs-table-wrap`.

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
