<?php
use ChurchCRM\dto\SystemURLs;
require SystemURLs::getDocumentRoot() . '/Include/Header.php';
?>
<div class="card mb-3">
  <div class="card-header"><h3 class="card-title">WhatsApp / GoWA</h3></div>
  <div class="card-body">
    <p>Configure the gateway in <a href="<?= htmlspecialchars(SystemURLs::getRootPath(), ENT_QUOTES) ?>/plugins/management">Plugin Management</a>. Only administrators can access this page.</p>
    <button class="btn btn-secondary" id="wa-status" type="button">Check connection</button>
    <button class="btn btn-secondary" id="wa-qr" type="button">Show pairing QR</button>
    <pre id="wa-feedback" class="mt-3" role="status" aria-live="polite"></pre>
    <img id="wa-qr-image" alt="WhatsApp pairing QR code" style="display:none; max-width:300px" />
  </div>
</div>
<div class="card">
  <div class="card-header"><h3 class="card-title">Send an opt-in message</h3></div>
  <div class="card-body">
    <form id="wa-send-form">
      <div class="mb-3"><label for="wa-phone">Phone with country code</label><input class="form-control" id="wa-phone" required pattern="[+0-9 ()-]+" autocomplete="off"></div>
      <div class="mb-3"><label for="wa-message">Message</label><textarea class="form-control" id="wa-message" required maxlength="2000" rows="5"></textarea></div>
      <label><input type="checkbox" id="wa-consent" required> I confirm this recipient consented to receive WhatsApp messages.</label>
      <div class="mt-3"><button class="btn btn-primary" id="wa-send" type="submit">Send message</button></div>
    </form>
  </div>
</div>
<script nonce="<?= SystemURLs::getCSPNonce() ?>">
(() => {
  const base = window.CRM.root + '/plugins/whatsapp-gowa/api';
  const feedback = document.getElementById('wa-feedback');
  const qrImage = document.getElementById('wa-qr-image');
  async function api(path, options) {
    const response = await fetch(base + path, {credentials: 'same-origin', ...options});
    const data = await response.json();
    if (!response.ok || !data.success) throw new Error(data.message || 'Request failed');
    return data;
  }
  document.getElementById('wa-status').addEventListener('click', async () => {
    try {
      const result = await api('/status');
      feedback.textContent = JSON.stringify(result.data, null, 2);
    } catch (error) { feedback.textContent = error.message; }
  });
  document.getElementById('wa-qr').addEventListener('click', async () => {
    qrImage.style.display = 'none';
    try {
      const result = await api('/qr');
      const payload = result.data.results || result.data;
      const value = payload.qr || payload.qr_code || payload.qrCode || payload.qr_link || payload.image;
      if (typeof value === 'string' && (/^data:image\/png;base64,/.test(value) || /^data:image\/jpeg;base64,/.test(value))) {
        qrImage.src = value;
        qrImage.style.display = 'block';
        feedback.textContent = 'Scan with WhatsApp > Linked devices';
      } else if (typeof value === 'string') {
        try {
          const url = new URL(value);
          const match = url.pathname.match(/^\/statics\/qrcode\/([A-Za-z0-9_-]+\.png)$/);
          if (!match) throw new Error('Unexpected QR path');
          qrImage.src = base + '/qr-image?file=' + encodeURIComponent(match[1]);
          qrImage.style.display = 'block';
          feedback.textContent = 'Scan with WhatsApp > Linked devices';
        } catch (error) {
          feedback.textContent = 'GoWA returned an unsupported QR image location';
        }
      } else {
        feedback.textContent = 'The gateway returned no browser-displayable QR. Check the gateway version and connection status.';
      }
    } catch (error) { feedback.textContent = error.message; }
  });
  document.getElementById('wa-send-form').addEventListener('submit', async (event) => {
    event.preventDefault();
    if (!confirm('Send this message once?')) return;
    const button = document.getElementById('wa-send');
    button.disabled = true;
    try {
      const form = new URLSearchParams({
        phone: document.getElementById('wa-phone').value,
        message: document.getElementById('wa-message').value,
        consent: document.getElementById('wa-consent').checked ? 'yes' : 'no'
      });
      const result = await api('/send', {method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: form});
      feedback.textContent = 'Gateway accepted the message. ID: ' + (result.messageId || '(not supplied)');
    } catch (error) { feedback.textContent = error.message; }
    finally { button.disabled = false; }
  });
})();
</script>
