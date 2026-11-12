// SPDX-FileCopyrightText: 2026 Vitor Mattos
// SPDX-License-Identifier: AGPL-3.0-or-later

'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const root = path.join(__dirname, '../..');
const en = JSON.parse(fs.readFileSync(path.join(root, 'locale/i18n/en_US.json'), 'utf8'));
const pt = JSON.parse(fs.readFileSync(path.join(root, 'locale/i18n/pt_BR.json'), 'utf8'));
const view = fs.readFileSync(path.join(root, 'views/manage.php'), 'utf8');
const keys = new Set([
  ...[...view.matchAll(/data-i18n(?:-alt)?="([^"]+)"/g)].map((match) => match[1]),
  ...[...view.matchAll(/\bt\('([a-z][a-zA-Z]+\.[a-zA-Z]+)'/g)].map((match) => match[1]),
]);
assert.deepEqual(Object.keys(pt).sort(), Object.keys(en).sort(), 'Both locales must define the same keys');
for (const key of keys) {
  assert.equal(typeof en[key], 'string', `Missing en_US key: ${key}`);
  assert.equal(typeof pt[key], 'string', `Missing pt_BR key: ${key}`);
  assert.ok(en[key].trim() && pt[key].trim(), `Empty translation: ${key}`);
  const vars = (text) => [...text.matchAll(/\{([a-zA-Z]+)\}/g)].map((match) => match[1]).sort();
  assert.deepEqual(vars(en[key]), vars(pt[key]), `Interpolation mismatch: ${key}`);
}
const fallbackMatch = view.match(/const fallback = (\{[\s\S]+?\});\n  const messages/);
assert.ok(fallbackMatch, 'The browser must have an English fallback');
assert.deepEqual(JSON.parse(fallbackMatch[1]), en, 'Browser fallback and en_US dictionary must match');
console.log(`Translations passed: ${keys.size} used keys; ${Object.keys(en).length} English/Portuguese entries.`);
