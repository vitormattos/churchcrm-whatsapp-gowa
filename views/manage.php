<?php
/**
 * SPDX-FileCopyrightText: 2026 Vitor Mattos
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

use ChurchCRM\dto\SystemURLs;
use ChurchCRM\Utils\CSRFUtils;

require SystemURLs::getDocumentRoot() . '/Include/Header.php';
?>
<div id="wa-admin-root" data-csrf-token="<?= htmlspecialchars(CSRFUtils::generateToken(), ENT_QUOTES, 'UTF-8') ?>">
  <div class="card mb-3">
    <div class="card-header"><h3 class="card-title"><i class="fa-brands fa-whatsapp me-2"></i>WhatsApp / GoWA</h3></div>
    <div class="card-body">
      <p><span data-i18n="setup.configure">Configure your GoWA API in</span> <a href="<?= htmlspecialchars(SystemURLs::getRootPath(), ENT_QUOTES, 'UTF-8') ?>/plugins/management" data-i18n="setup.plugins">Plugin Management</a>. <span data-i18n="setup.admin">Only administrators can access this page.</span></p>
      <div id="wa-connection" class="alert alert-info" role="status" aria-live="polite" data-i18n="connection.checking">Checking connection...</div>
      <div id="wa-device" class="mb-3"></div>
      <div class="d-flex flex-wrap gap-2 mb-3">
        <button class="btn btn-outline-secondary" id="wa-status" type="button" data-i18n="button.check">Check connection</button>
        <button class="btn btn-outline-secondary" id="wa-register" type="button" disabled data-i18n="button.register">Register device</button>
        <button class="btn btn-primary" id="wa-qr" type="button" disabled data-i18n="button.qr">Show pairing QR</button>
      </div>
      <div id="wa-pairing-status" class="mb-3" role="status" aria-live="polite"></div>
      <div id="wa-qr-container" class="d-none text-center mb-3">
        <img id="wa-qr-image" class="img-fluid rounded border p-2 bg-white" data-i18n-alt="qr.alt" alt="WhatsApp pairing QR code" width="320" height="320">
        <p id="wa-qr-expiry" class="text-secondary mt-2 mb-0"></p>
        <p class="mt-2" data-i18n="qr.instructions">On your phone, open WhatsApp &gt; Linked devices &gt; Link a device and scan the QR code.</p>
      </div>
    </div>
  </div>
  <div class="card">
    <div class="card-header"><h3 class="card-title" data-i18n="send.heading">Send an individual message</h3></div>
    <div class="card-body">
      <div id="wa-sending-status" class="alert alert-warning" role="status" data-i18n="send.disabled">Sending is disabled until your WhatsApp account is connected and sending is enabled in the plugin settings.</div>
      <form id="wa-send-form">
        <div class="mb-3"><label for="wa-phone" class="form-label" data-i18n="send.phone">Phone number with country code (e.g. 5521999999999)</label><input class="form-control" id="wa-phone" required pattern="[+0-9 ()-]+" autocomplete="off" inputmode="tel"></div>
        <div class="mb-3"><label for="wa-message" class="form-label" data-i18n="send.message">Message</label><textarea class="form-control" id="wa-message" required maxlength="2000" rows="5"></textarea></div>
        <label><input type="checkbox" id="wa-consent" required> <span data-i18n="send.consent">I confirm that this recipient agreed to receive WhatsApp messages.</span></label>
        <div class="mt-3"><button class="btn btn-primary" id="wa-send" type="submit" disabled data-i18n="button.send">Send message</button></div>
        <div id="wa-send-feedback" class="mt-2" role="status" aria-live="polite"></div>
      </form>
    </div>
  </div>
</div>
<script nonce="<?= SystemURLs::getCSPNonce() ?>">
(() => {
  'use strict';
  const base = window.CRM.root + '/plugins/whatsapp-gowa/api';
  const root = document.getElementById('wa-admin-root');
  const csrf = root.dataset.csrfToken;
  const fallback = {
    "setup.configure": "Configure your GoWA API in",
    "setup.plugins": "Plugin Management",
    "setup.admin": "Only administrators can access this page.",
    "connection.checking": "Checking connection...",
    "button.check": "Check connection",
    "button.register": "Register device",
    "button.registered": "Device already registered",
    "button.qr": "Show pairing QR",
    "qr.alt": "WhatsApp pairing QR code",
    "qr.instructions": "On your phone, open WhatsApp > Linked devices > Link a device and scan the QR code.",
    "send.heading": "Send an individual message",
    "send.disabled": "Sending is disabled until your WhatsApp account is connected and sending is enabled in the plugin settings.",
    "send.phone": "Phone number with country code (e.g. 5521999999999)",
    "send.message": "Message",
    "send.consent": "I confirm that this recipient agreed to receive WhatsApp messages.",
    "button.send": "Send message",
    "error.session": "Unexpected response from ChurchCRM. Refresh the page and check that you are still signed in.",
    "error.request": "Server request failed.",
    "device.label": "Registered device: {device}",
    "device.none": "none",
    "connection.connected": "GoWA is online. WhatsApp is connected and authenticated.",
    "qr.alreadyConnected": "The WhatsApp account is already connected; no QR code is needed.",
    "connection.waiting": "GoWA is online. WhatsApp is waiting for QR pairing.",
    "connection.unregistered": "GoWA is online, but no device has been registered.",
    "send.disabledSetting": "Sending is disabled in the plugin settings.",
    "send.enabled": "Message sending is enabled.",
    "send.disconnected": "Connect WhatsApp before sending messages.",
    "connection.error": "Could not check the connection: {message}",
    "device.registering": "Registering the device...",
    "device.alreadyRegistered": "Device {device} was already registered.",
    "device.registered": "Device {device} registered. You can now show the pairing QR.",
    "device.error": "Could not register the device: {message}",
    "qr.generating": "Generating a QR code...",
    "qr.invalid": "Invalid QR code image.",
    "qr.expiry": "This code expires in about {seconds} seconds. Click the button again for a new one.",
    "qr.ready": "QR code ready. Scan it with WhatsApp on your phone.",
    "qr.error": "Could not generate the QR code: {message}",
    "send.confirm": "Send this message once?",
    "send.sending": "Sending message...",
    "send.accepted": "The gateway accepted the message. ID: {messageId}",
    "send.noId": "not provided",
    "send.error": "Check the delivery status before retrying: {message}"
};
  const messages = {...fallback, ...(window.CRM.plugins?.['whatsapp-gowa']?.i18n || {})};
  const $ = (id) => document.getElementById(id);
  const t = (key, vars = {}) => {
    const template = messages[key] || key;
    return template.replace(/\{([a-zA-Z]+)\}/g, (match, variable) =>
      Object.hasOwn(vars, variable) ? String(vars[variable]) : match);
  };
  root.querySelectorAll('[data-i18n]').forEach((element) => {
    const key = element.dataset.i18n;
    if (messages[key]) element.textContent = t(key);
  });
  root.querySelectorAll('[data-i18n-alt]').forEach((element) => {
    const key = element.dataset.i18nAlt;
    if (messages[key]) element.setAttribute('alt', t(key));
  });

  const statusButton = $('wa-status');
  const registerButton = $('wa-register');
  const qrButton = $('wa-qr');
  const sendButton = $('wa-send');
  const qrContainer = $('wa-qr-container');
  let connected = false;
  let registered = false;
  let sendingEnabled = false;

  function alertState(element, message, severity) {
    element.textContent = message;
    element.className = 'alert alert-' + severity;
  }

  async function api(path, options = {}) {
    const method = (options.method || 'GET').toUpperCase();
    const headers = {...options.headers};
    if (method !== 'GET' && method !== 'HEAD') headers['X-CSRF-Token'] = csrf;
    const response = await fetch(base + path, {
      credentials: 'same-origin', cache: 'no-store', ...options, headers,
    });
    if (!(response.headers.get('Content-Type') || '').includes('application/json')) {
      throw new Error(t('error.session'));
    }
    const result = await response.json();
    if (!response.ok || !result.success) throw new Error(result.message || t('error.request'));
    return result.data || result;
  }

  async function refreshStatus() {
    statusButton.disabled = true;
    alertState($('wa-connection'), t('connection.checking'), 'info');
    try {
      const state = await api('/status');
      registered = Boolean(state.deviceRegistered);
      connected = Boolean(state.connected && state.loggedIn);
      sendingEnabled = Boolean(state.sendingEnabled);
      $('wa-device').textContent = t('device.label', {device: registered ? state.deviceId : t('device.none')});
      registerButton.disabled = registered;
      registerButton.textContent = t(registered ? 'button.registered' : 'button.register');
      qrButton.disabled = !registered || connected;
      sendButton.disabled = !connected || !sendingEnabled;
      if (connected) {
        alertState($('wa-connection'), t('connection.connected'), 'success');
        qrContainer.classList.add('d-none');
        $('wa-pairing-status').textContent = t('qr.alreadyConnected');
      } else {
        alertState($('wa-connection'), t(registered ? 'connection.waiting' : 'connection.unregistered'), 'warning');
      }
      alertState($('wa-sending-status'), !sendingEnabled
        ? t('send.disabledSetting')
        : connected ? t('send.enabled') : t('send.disconnected'),
      sendingEnabled && connected ? 'success' : 'warning');
    } catch (error) {
      connected = false;
      registered = false;
      registerButton.disabled = true;
      qrButton.disabled = true;
      sendButton.disabled = true;
      alertState($('wa-connection'), t('connection.error', {message: error.message}), 'danger');
    } finally {
      statusButton.disabled = false;
    }
  }

  statusButton.addEventListener('click', refreshStatus);
  registerButton.addEventListener('click', async () => {
    registerButton.disabled = true;
    $('wa-pairing-status').textContent = t('device.registering');
    try {
      const state = await api('/register-device', {method: 'POST'});
      $('wa-pairing-status').textContent = t(state.alreadyRegistered ? 'device.alreadyRegistered' : 'device.registered', {device: state.deviceId});
    } catch (error) {
      $('wa-pairing-status').textContent = t('device.error', {message: error.message});
    } finally {
      await refreshStatus();
    }
  });

  qrButton.addEventListener('click', async () => {
    qrButton.disabled = true;
    qrContainer.classList.add('d-none');
    $('wa-qr-image').removeAttribute('src');
    $('wa-pairing-status').textContent = t('qr.generating');
    try {
      const qr = await api('/qr', {method: 'POST'});
      if (typeof qr.image !== 'string' || !/^data:image\/png;base64,[A-Za-z0-9+/=]+$/.test(qr.image)) {
        throw new Error(t('qr.invalid'));
      }
      $('wa-qr-image').src = qr.image;
      qrContainer.classList.remove('d-none');
      $('wa-qr-expiry').textContent = t('qr.expiry', {seconds: qr.validForSeconds});
      $('wa-pairing-status').textContent = t('qr.ready');
    } catch (error) {
      $('wa-pairing-status').textContent = t('qr.error', {message: error.message});
    } finally {
      qrButton.disabled = connected || !registered;
    }
  });

  $('wa-send-form').addEventListener('submit', async (event) => {
    event.preventDefault();
    if (!connected || !sendingEnabled) return;
    if (!confirm(t('send.confirm'))) return;
    sendButton.disabled = true;
    $('wa-send-feedback').textContent = t('send.sending');
    try {
      const body = new URLSearchParams({
        phone: $('wa-phone').value,
        message: $('wa-message').value,
        consent: $('wa-consent').checked ? 'yes' : 'no',
      });
      const result = await api('/send', {method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body});
      $('wa-send-feedback').textContent = t('send.accepted', {messageId: result.messageId || t('send.noId')});
    } catch (error) {
      $('wa-send-feedback').textContent = t('send.error', {message: error.message});
    } finally {
      sendButton.disabled = !connected || !sendingEnabled;
    }
  });
  refreshStatus();
})();
</script>
