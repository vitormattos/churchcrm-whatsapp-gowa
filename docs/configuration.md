<!--
SPDX-FileCopyrightText: 2026 Vitor Mattos
SPDX-License-Identifier: AGPL-3.0-or-later
-->

# Configure WhatsApp in ChurchCRM

After [installing the plugin](installation.md) and [starting GoWA](gateway.md):

1. Open **Administration → Plugins → WhatsApp (GoWA)**.
2. Enter the GoWA API URL (`http://whatsapp-gowa:3000` for the example private Docker network), username and password from the gateway's `.env`.
3. Optionally configure the device identifier (`churchcrm` when registered). Supply a full country calling code with each recipient number (for example, `55` for Brazil); the plugin does not guess the country from ChurchCRM contacts.
4. Save and use the plugin's **connection test**. It verifies API reachability, not WhatsApp pairing.
5. Open **WhatsApp** in the administration menu. The page displays the gateway's current status.

## Connect WhatsApp

1. If the page says **Register device**, click it once. If it says **Device already registered**, **do not create another device**.
2. Click **Show pairing QR**. A time-limited QR image appears inside ChurchCRM.
3. On the phone that owns the church's WhatsApp account, open **WhatsApp → Linked devices → Link a device** and scan the QR.
4. Click **Check connection** after pairing; confirm that both the API and WhatsApp are connected.
5. If the QR expires, generate a new one. Do not reuse older QR images.

## Enable sending

Sending is **off by default**. Only after a successful pairing, return to **Administration → Plugins** and enable `sendingEnabled` for this plugin. The sending form remains disabled if WhatsApp is disconnected.

Send a single test to a **controlled, consented number** that includes the country calling code (for example, Brazil `55` followed by DDD and number). Review the message and confirm the one-time send. A transport timeout may mean the message was sent; do not retry without checking the gateway and the recipient.

## Scope

Current support is one-at-a-time administrator sending. There is no scheduling, audience selection, opt-out registry or mass mailing. Obtain and document consent externally before any messages. Do not infer consent simply because a contact is stored in ChurchCRM.
