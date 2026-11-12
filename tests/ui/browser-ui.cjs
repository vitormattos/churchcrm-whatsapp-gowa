// SPDX-FileCopyrightText: 2026 Vitor Mattos
// SPDX-License-Identifier: AGPL-3.0-or-later

'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { chromium } = require('playwright');

const root = path.join(__dirname, '../..');
const dictionaries = Object.fromEntries(['en_US', 'pt_BR'].map((locale) => [
  locale,
  JSON.parse(fs.readFileSync(path.join(root, 'locale/i18n', `${locale}.json`), 'utf8')),
]));

async function testLocale(browser, locale) {
  const page = await browser.newPage();
  const errors = [];
  page.on('pageerror', (error) => errors.push(error.message));
  page.on('dialog', (dialog) => dialog.accept());
  const markup = fs.readFileSync(path.join(root, 'views/manage.php'), 'utf8')
    .replace(/data-csrf-token="<\?=[\s\S]*?\?>"/, 'data-csrf-token="test-csrf-token"')
    .replace(/<\?php[\s\S]*?\?>/g, '').replace(/<\?=[\s\S]*?\?>/g, '');
  const setup = `<script>
    window.CRM = {root: '', plugins: {'whatsapp-gowa': {i18n: ${JSON.stringify(dictionaries[locale])}}}};
    window.__test = {registered: true, connected: false, sendingEnabled: false, calls: []};
    window.fetch = async (url, options = {}) => {
      const state = window.__test;
      const route = String(url).split('/api')[1];
      state.calls.push({route, method: options.method || 'GET', headers: options.headers || {}});
      if (options.method === 'POST' && options.headers['X-CSRF-Token'] !== 'test-csrf-token') {
        throw Error('CSRF header missing');
      }
      let result;
      if (route === '/status') {
        result = {success: true, data: {
          deviceId: state.registered ? 'churchcrm' : '', deviceRegistered: state.registered,
          connected: state.connected, loggedIn: state.connected, sendingEnabled: state.sendingEnabled,
        }};
      } else if (route === '/qr') {
        if (options.method !== 'POST') throw Error('QR must be POST');
        result = {success: true, data: {
          image: 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jkeUAAAAASUVORK5CYII=',
          validForSeconds: 30,
        }};
      } else if (route === '/register-device') {
        const alreadyRegistered = state.registered;
        state.registered = true;
        result = {success: true, data: {deviceId: 'churchcrm', alreadyRegistered}};
      } else if (route === '/send') {
        if (!state.connected || !state.sendingEnabled) throw Error('Unauthorized test send');
        result = {success: true, messageId: 'test-message-id'};
      } else throw Error('Unexpected API route: ' + route);
      return new Response(JSON.stringify(result), {headers: {'Content-Type': 'application/json'}});
    };
  </script>`;
  await page.setContent(`<!doctype html><html><head><style>.d-none{display:none!important}</style></head><body>${setup}${markup}</body></html>`);
  const lang = dictionaries[locale];
  await page.waitForFunction((text) => document.getElementById('wa-connection')?.textContent === text, lang['connection.waiting']);
  assert.equal(await page.locator('#wa-register').isDisabled(), true);
  assert.equal(await page.locator('#wa-register').innerText(), lang['button.registered']);
  assert.equal(await page.locator('#wa-qr').innerText(), lang['button.qr']);
  assert.equal(await page.locator('#wa-send').isDisabled(), true);
  assert.equal(await page.locator('#wa-send-form label[for="wa-message"]').innerText(), lang['send.message']);
  await page.locator('#wa-qr').click();
  await page.waitForFunction(() => !document.getElementById('wa-qr-container').classList.contains('d-none'));
  assert.match(await page.locator('#wa-qr-image').getAttribute('src'), /^data:image\/png;base64,/);
  assert.equal(await page.locator('#wa-pairing-status').innerText(), lang['qr.ready']);
  await page.evaluate(() => {window.__test.connected = true;});
  await page.locator('#wa-status').click();
  await page.waitForFunction((text) => document.getElementById('wa-connection').textContent === text, lang['connection.connected']);
  assert.equal(await page.locator('#wa-qr-container').isVisible(), false);
  await page.evaluate(() => {window.__test.registered = false; window.__test.connected = false;});
  await page.locator('#wa-status').click();
  await page.waitForFunction((text) => document.getElementById('wa-register').textContent === text, lang['button.register']);
  await page.locator('#wa-register').click();
  await page.waitForFunction((text) => document.getElementById('wa-register').textContent === text, lang['button.registered']);
  await page.evaluate(() => {window.__test.connected = true; window.__test.sendingEnabled = true;});
  await page.locator('#wa-status').click();
  await page.waitForFunction(() => !document.getElementById('wa-send').disabled);
  await page.locator('#wa-phone').fill('5521999999999');
  await page.locator('#wa-message').fill('Consent-based test message');
  await page.locator('#wa-consent').check();
  await page.locator('#wa-send').click();
  await page.waitForFunction((text) => document.getElementById('wa-send-feedback').textContent.includes(text), 'test-message-id');
  const calls = await page.evaluate(() => window.__test.calls);
  assert.equal(calls.filter((call) => call.route === '/qr').length, 1);
  for (const call of calls.filter((call) => call.method === 'POST')) {
    assert.equal(call.headers['X-CSRF-Token'], 'test-csrf-token');
  }
  assert.deepEqual(errors, []);
  await page.close();
}

async function main() {
  const browser = await chromium.launch(process.env.PLAYWRIGHT_USE_SYSTEM_CHROME === '1'
    ? {channel: 'chrome', headless: true}
    : {headless: true});
  try {
    for (const locale of ['en_US', 'pt_BR']) await testLocale(browser, locale);
    console.log('Browser UI passed: en_US, pt_BR, QR POST, CSRF headers, device states and opted-in send.');
  } finally {
    await browser.close();
  }
}

main().catch((error) => {console.error(error); process.exitCode = 1;});
