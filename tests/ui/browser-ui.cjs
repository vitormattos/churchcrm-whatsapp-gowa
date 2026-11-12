// SPDX-FileCopyrightText: 2026 Vitor Mattos
// SPDX-License-Identifier: AGPL-3.0-or-later

'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { chromium } = require('playwright');

async function main() {
  const viewPath = path.join(__dirname, '../../views/manage.php');
  const markup = fs.readFileSync(viewPath, 'utf8').replace(/<\?php[\s\S]*?\?>/g, '');
  const browser = await chromium.launch(process.env.PLAYWRIGHT_USE_SYSTEM_CHROME === '1'
    ? { channel: 'chrome', headless: true }
    : { headless: true });
  try {
    const page = await browser.newPage();
    const runtimeErrors = [];
    page.on('pageerror', (error) => runtimeErrors.push(error.message));
    const mock = `<script>
      window.CRM = { root: '' };
      window.__test = { registered: true, connected: false, sendingEnabled: false, calls: [] };
      window.fetch = async (url, options = {}) => {
        const state = window.__test;
        const path = String(url).split('/api')[1];
        state.calls.push({ path, method: options.method || 'GET' });
        let result;
        if (path === '/status') {
          result = { success: true, data: {
            deviceId: state.registered ? 'churchcrm' : '',
            deviceRegistered: state.registered,
            connected: state.connected,
            loggedIn: state.connected,
            sendingEnabled: state.sendingEnabled
          }};
        } else if (path === '/qr') {
          result = { success: true, data: {
            image: 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jkeUAAAAASUVORK5CYII=',
            validForSeconds: 30
          }};
        } else if (path === '/register-device') {
          const alreadyRegistered = state.registered;
          state.registered = true;
          result = { success: true, data: { deviceId: 'churchcrm', alreadyRegistered } };
        } else throw new Error('Unexpected API call: ' + path);
        return new Response(JSON.stringify(result), { headers: { 'Content-Type': 'application/json' } });
      };
    </script>`;
    await page.setContent('<!doctype html><html><head><style>.d-none{display:none!important}</style></head><body>' + mock + markup + '</body></html>');

    await page.waitForFunction(() => document.getElementById('wa-connection').textContent.includes('aguardando conexão'));
    assert.match(await page.locator('#wa-connection').innerText(), /GoWA operacional/);
    assert.equal(await page.locator('#wa-register').isDisabled(), true);
    assert.match(await page.locator('#wa-register').innerText(), /já registrado/);
    assert.equal(await page.locator('#wa-qr').isEnabled(), true);
    assert.equal(await page.locator('#wa-send').isDisabled(), true);
    assert.doesNotMatch(await page.locator('#wa-connection').innerText(), /\"code\"|\"results\"/);

    await page.locator('#wa-qr').click();
    await page.waitForFunction(() => !document.getElementById('wa-qr-container').classList.contains('d-none'));
    assert.match(await page.locator('#wa-qr-image').getAttribute('src'), /^data:image\/png;base64,/);
    assert.match(await page.locator('#wa-pairing-status').innerText(), /QR Code gerado/);

    await page.evaluate(() => { window.__test.connected = true; });
    await page.locator('#wa-status').click();
    await page.waitForFunction(() => document.getElementById('wa-connection').textContent.includes('conectado e autenticado'));
    assert.equal(await page.locator('#wa-qr').isDisabled(), true);
    assert.equal(await page.locator('#wa-qr-container').isVisible(), false);

    await page.evaluate(() => { window.__test.registered = false; window.__test.connected = false; });
    await page.locator('#wa-status').click();
    await page.waitForFunction(() => document.getElementById('wa-register').textContent.includes('Registrar dispositivo'));
    assert.equal(await page.locator('#wa-register').isEnabled(), true);
    await page.locator('#wa-register').click();
    await page.waitForFunction(() => document.getElementById('wa-register').textContent.includes('já registrado'));
    assert.equal(await page.locator('#wa-qr').isEnabled(), true);
    assert.equal((await page.evaluate(() => window.__test.calls)).filter((call) => call.path === '/register-device').length, 1);
    assert.deepEqual(runtimeErrors, []);
    console.log('Browser UI tests passed: connection status, already registered, QR image and new device flow.');
  } finally {
    await browser.close();
  }
}

main().catch((error) => { console.error(error); process.exitCode = 1; });
