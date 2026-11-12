<?php
/**
 * SPDX-FileCopyrightText: 2026 Vitor Mattos
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

use ChurchCRM\dto\SystemURLs;
require SystemURLs::getDocumentRoot() . '/Include/Header.php';
?>
<div class="card mb-3">
  <div class="card-header"><h3 class="card-title"><i class="fa-brands fa-whatsapp me-2"></i>WhatsApp / GoWA</h3></div>
  <div class="card-body">
    <p>Configure a API GoWA em <a href="<?= htmlspecialchars(SystemURLs::getRootPath(), ENT_QUOTES) ?>/plugins/management">Administração de plugins</a>. Somente administradores podem acessar esta página.</p>
    <div id="wa-connection" class="alert alert-info" role="status" aria-live="polite">Verificando conexão com o serviço...</div>
    <div id="wa-device" class="mb-3"></div>
    <div class="d-flex flex-wrap gap-2 mb-3">
      <button class="btn btn-outline-secondary" id="wa-status" type="button">Verificar conexão</button>
      <button class="btn btn-outline-secondary" id="wa-register" type="button" disabled>Registrar dispositivo</button>
      <button class="btn btn-primary" id="wa-qr" type="button" disabled>Exibir QR Code</button>
    </div>
    <div id="wa-pairing-status" class="mb-3" role="status" aria-live="polite"></div>
    <div id="wa-qr-container" class="d-none text-center mb-3">
      <img id="wa-qr-image" class="img-fluid rounded border p-2 bg-white" alt="QR Code para conectar o WhatsApp da igreja" width="320" height="320">
      <p id="wa-qr-expiry" class="text-body-secondary mt-2 mb-0"></p>
      <p class="mt-2">No celular da igreja, abra <strong>WhatsApp &gt; Dispositivos conectados &gt; Conectar dispositivo</strong> e escaneie o código.</p>
    </div>
  </div>
</div>
<div class="card">
  <div class="card-header"><h3 class="card-title">Enviar mensagem individual</h3></div>
  <div class="card-body">
    <div id="wa-sending-status" class="alert alert-warning" role="status">Envio desativado até a conexão e habilitação nas configurações do plugin.</div>
    <form id="wa-send-form">
      <div class="mb-3">
        <label for="wa-phone" class="form-label">Número com DDI (por exemplo, 5521999999999)</label>
        <input class="form-control" id="wa-phone" required pattern="[+0-9 ()-]+" autocomplete="off" inputmode="tel">
      </div>
      <div class="mb-3">
        <label for="wa-message" class="form-label">Mensagem</label>
        <textarea class="form-control" id="wa-message" required maxlength="2000" rows="5"></textarea>
      </div>
      <label><input type="checkbox" id="wa-consent" required> Confirmo que o destinatário consentiu em receber mensagens pelo WhatsApp.</label>
      <div class="mt-3"><button class="btn btn-primary" id="wa-send" type="submit" disabled>Enviar mensagem</button></div>
      <div id="wa-send-feedback" class="mt-2" role="status" aria-live="polite"></div>
    </form>
  </div>
</div>
<script nonce="<?= SystemURLs::getCSPNonce() ?>">
(() => {
  'use strict';
  const base = window.CRM.root + '/plugins/whatsapp-gowa/api';
  const $ = (id) => document.getElementById(id);
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
    const response = await fetch(base + path, {credentials: 'same-origin', cache: 'no-store', ...options});
    if (!(response.headers.get('Content-Type') || '').includes('application/json')) {
      throw new Error('Resposta inesperada do ChurchCRM. Atualize a página e confirme que a sessão continua ativa.');
    }
    const result = await response.json();
    if (!response.ok || !result.success) {
      throw new Error(result.message || 'Falha na comunicação com o servidor.');
    }
    return result.data;
  }

  async function refreshStatus() {
    statusButton.disabled = true;
    alertState($('wa-connection'), 'Verificando a conexão...', 'info');
    try {
      const state = await api('/status');
      registered = Boolean(state.deviceRegistered);
      connected = Boolean(state.connected && state.loggedIn);
      sendingEnabled = Boolean(state.sendingEnabled);
      const device = registered ? state.deviceId : 'nenhum';
      $('wa-device').textContent = 'Dispositivo registrado: ' + device;
      registerButton.disabled = registered;
      registerButton.textContent = registered ? 'Dispositivo já registrado' : 'Registrar dispositivo';
      qrButton.disabled = !registered || connected;
      sendButton.disabled = !connected || !sendingEnabled;
      if (connected) {
        alertState($('wa-connection'), 'GoWA operacional. WhatsApp conectado e autenticado.', 'success');
        qrContainer.classList.add('d-none');
        $('wa-pairing-status').textContent = 'A conta WhatsApp está conectada; não é necessário escanear um QR Code.';
      } else if (!registered) {
        alertState($('wa-connection'), 'GoWA operacional, mas ainda não há dispositivo registrado.', 'warning');
      } else {
        alertState($('wa-connection'), 'GoWA operacional. WhatsApp aguardando conexão pelo QR Code.', 'warning');
      }
      alertState($('wa-sending-status'), !sendingEnabled
        ? 'O envio está desativado nas configurações do plugin.'
        : connected ? 'Envio de mensagens habilitado.' : 'Conecte o WhatsApp para habilitar o envio.',
      sendingEnabled && connected ? 'success' : 'warning');
    } catch (error) {
      connected = false;
      registered = false;
      registerButton.disabled = true;
      qrButton.disabled = true;
      sendButton.disabled = true;
      alertState($('wa-connection'), 'Não foi possível verificar a conexão: ' + error.message, 'danger');
    } finally {
      statusButton.disabled = false;
    }
  }

  statusButton.addEventListener('click', refreshStatus);

  registerButton.addEventListener('click', async () => {
    registerButton.disabled = true;
    $('wa-pairing-status').textContent = 'Registrando dispositivo...';
    try {
      const state = await api('/register-device', {method: 'POST'});
      $('wa-pairing-status').textContent = state.alreadyRegistered
        ? 'O dispositivo ' + state.deviceId + ' já estava registrado.'
        : 'Dispositivo ' + state.deviceId + ' registrado. Agora exiba o QR Code.';
      await refreshStatus();
    } catch (error) {
      $('wa-pairing-status').textContent = 'Não foi possível registrar o dispositivo: ' + error.message;
      await refreshStatus();
    }
  });

  qrButton.addEventListener('click', async () => {
    qrButton.disabled = true;
    qrContainer.classList.add('d-none');
    $('wa-qr-image').removeAttribute('src');
    $('wa-pairing-status').textContent = 'Gerando QR Code...';
    try {
      const qr = await api('/qr');
      if (typeof qr.image !== 'string' || !/^data:image\/png;base64,[A-Za-z0-9+/=]+$/.test(qr.image)) {
        throw new Error('Imagem QR Code inválida');
      }
      $('wa-qr-image').src = qr.image;
      qrContainer.classList.remove('d-none');
      $('wa-qr-expiry').textContent = 'O código expira em aproximadamente ' + qr.validForSeconds + ' segundos. Use o botão novamente para obter um novo.';
      $('wa-pairing-status').textContent = 'QR Code gerado. Escaneie pelo WhatsApp no celular da igreja.';
    } catch (error) {
      $('wa-pairing-status').textContent = 'Erro ao gerar QR Code: ' + error.message;
    } finally {
      qrButton.disabled = connected || !registered;
    }
  });

  $('wa-send-form').addEventListener('submit', async (event) => {
    event.preventDefault();
    if (!connected || !sendingEnabled) return;
    if (!confirm('Confirma o envio desta mensagem uma única vez?')) return;
    sendButton.disabled = true;
    $('wa-send-feedback').textContent = 'Enviando mensagem...';
    try {
      const form = new URLSearchParams({
        phone: $('wa-phone').value,
        message: $('wa-message').value,
        consent: $('wa-consent').checked ? 'yes' : 'no',
      });
      const response = await fetch(base + '/send', {
        method: 'POST', credentials: 'same-origin', cache: 'no-store',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: form,
      });
      const result = await response.json();
      if (!response.ok || !result.success) throw new Error(result.message || 'Não foi possível confirmar o envio.');
      $('wa-send-feedback').textContent = 'Mensagem aceita pelo gateway. ID: ' + (result.messageId || 'não informado');
    } catch (error) {
      $('wa-send-feedback').textContent = 'Não reenviar sem verificar o status anterior: ' + error.message;
    } finally {
      sendButton.disabled = !connected || !sendingEnabled;
    }
  });

  refreshStatus();
})();
</script>
