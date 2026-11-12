<!--
SPDX-FileCopyrightText: 2026 Vitor Mattos
SPDX-License-Identifier: AGPL-3.0-or-later
-->

# WhatsApp for ChurchCRM (GoWA)

**Send opted-in WhatsApp messages from ChurchCRM, using your own WhatsApp account and a self-hosted GoWA gateway.** The plugin adds an administrator-only screen for checking the connection, pairing a device with a QR code, and sending an individual message.

[![PHPUnit](https://github.com/vitormattos/churchcrm-whatsapp-gowa/actions/workflows/phpunit.yml/badge.svg)](https://github.com/vitormattos/churchcrm-whatsapp-gowa/actions/workflows/phpunit.yml)
[![Psalm](https://github.com/vitormattos/churchcrm-whatsapp-gowa/actions/workflows/psalm.yml/badge.svg)](https://github.com/vitormattos/churchcrm-whatsapp-gowa/actions/workflows/psalm.yml)
[![PHP-CS-Fixer](https://github.com/vitormattos/churchcrm-whatsapp-gowa/actions/workflows/php-cs-fixer.yml/badge.svg)](https://github.com/vitormattos/churchcrm-whatsapp-gowa/actions/workflows/php-cs-fixer.yml)
[![REUSE](https://github.com/vitormattos/churchcrm-whatsapp-gowa/actions/workflows/reuse.yml/badge.svg)](https://github.com/vitormattos/churchcrm-whatsapp-gowa/actions/workflows/reuse.yml)

> [!IMPORTANT]
> **Help keep this integration maintained.** Every release requires compatibility work, security reviews, tests, documentation and support for changes to ChurchCRM and GoWA. If the plugin saves you time, consider [**sponsoring Vitor Mattos on GitHub**](https://github.com/sponsors/vitormattos). Your contribution helps fund ongoing maintenance and new features. Sponsorship is voluntary; the plugin remains free to use and modify.

## What you can do

- Configure a self-hosted GoWA (Go WhatsApp Web Multidevice) service without storing credentials in your browser.
- Register a dedicated gateway device and display its short-lived WhatsApp pairing QR code inside ChurchCRM.
- Check connection and login status in a human-readable administration page.
- Send an **individual** WhatsApp message to a recipient who has consented, when an administrator explicitly enables sending.
- Keep GoWA and ChurchCRM in separate containers and update the plugin without modifying ChurchCRM core.

## Security and vulnerability reporting

See [SECURITY.md](SECURITY.md) to report vulnerabilities privately. Never post credentials, QR codes, session data, phone numbers or message text.

## Status and limitations

**Early release / administrator-managed integration.** Bulk campaigns, member/group selection, scheduled messages, delivery histories and automatic retries are **not yet implemented**. No messages are sent automatically. GoWA uses an **unofficial WhatsApp Web integration**, which may be blocked or break after WhatsApp changes; there are no guarantees of account availability. Use only with consent, respect applicable privacy rules and WhatsApp policies, and avoid unsolicited messages.

## Installation and operation

| Guide | What it covers |
| --- | --- |
| [Install the ChurchCRM plugin](docs/installation.md) | Requirements, plugin deployment, persistent mount, activation |
| [Set up GoWA](docs/gateway.md) | Private Docker network, configuration, secrets, storage and QR pairing |
| [Configure ChurchCRM](docs/configuration.md) | Plugin settings, device registration, sending a test message |
| [Operations and troubleshooting](docs/operations.md) | Logs, upgrades, backups, troubleshooting and security |
| [Development and tests](docs/development.md) | PHPUnit, browser tests, isolated PHP tools, linting, CI |
| [Security and capability review](docs/security-audit.md) | Threat boundaries, permissions, risk classification and mitigations |

**Requirements:** ChurchCRM 7.x, PHP 8.4+ with cURL, a GoWA REST server reachable by ChurchCRM, and a dedicated WhatsApp account/number. The plugin does not require installing Composer dependencies on the production server.

## Support ongoing development

This project is independently maintained by **Vitor Mattos**. Sponsorship pays for the ongoing maintenance and testing work needed to keep an open-source integration useful. If your church or organization relies on it, [**become a GitHub Sponsor**](https://github.com/sponsors/vitormattos) or contribute a focused issue, documentation improvement or pull request.

Contributions and bug reports are welcome through [GitHub Issues](https://github.com/vitormattos/churchcrm-whatsapp-gowa/issues). Do not submit QR codes, session data, API passwords, phone numbers or other personal information in issues or logs.
