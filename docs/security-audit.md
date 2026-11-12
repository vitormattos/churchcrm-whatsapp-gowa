<!--
SPDX-FileCopyrightText: 2026 Vitor Mattos
SPDX-License-Identifier: AGPL-3.0-or-later
-->

# Security and capability review for v0.1.3

This review is for the initial ChurchCRM community-registry submission. **Risk: high.** It is an internal developer assessment, not an approval or independent penetration test. ChurchCRM maintainers must independently review the tagged ZIP and verify the exact SHA-256 before approval.

## Data and trust boundaries

A ChurchCRM administrator configures a GoWA endpoint and Basic Auth credentials. ChurchCRM's configuration database stores those credentials; protect database access and backups. The plugin sends a recipient phone number and message text to the configured GoWA API only when an administrator enables sending, confirms per-recipient consent and submits an individual message. GoWA itself uses the unofficial WhatsApp Web protocol. No contact synchronization, bulk jobs or automatic retries are present.

## Permission / capability inventory

| Registry permission | Used | Evidence | Notes |
| --- | --- | --- | --- |
| `network.outbound` | Yes | `src/GoWaClient.php` | cURL to administrator-configured GoWA for status, registration, pairing and send; phone/message PII crosses this boundary |
| `network.inbound` | Yes | `routes/routes.php` | Authenticated admin routes for status, QR, registration and individual sending |
| `secrets.store` | Yes | `plugin.json`, `src/WhatsAppGowaPlugin.php` | GoWA API username/password stored in ChurchCRM plugin configuration |
| `db.read` | No | — | Does not query ChurchCRM people or finance tables |
| `db.write` | No | — | Plugin-owned settings use the approved ChurchCRM configuration abstraction |
| `fs.read` | No | — | Only reads its own `plugin.json` |
| `fs.write` | No | — | No filesystem writes by the plugin |
| `ui.inject` | No | — | Only a dedicated admin page and declared menu item |
| `cron` | No | — | No scheduled job |
| `hooks.person` | No | — | Does not subscribe to person record events |
| `hooks.family` | No | — | Does not subscribe to family record events |
| `hooks.event` | No | — | No event hooks |
| `hooks.financial` | No | — | No financial hooks |
| `hooks.email` | No | — | No email hooks |
| `email.send` | No | — | No email sender |
| `sms.send` | No | — | WhatsApp via GoWA, not SMS |
| `calendar.register` | No | — | No calendar integration |

## Mitigations and known limitations

- Routes require `AdminRoleAuthMiddleware`; POST actions require ChurchCRM's `CSRFMiddleware` and the browser includes a session CSRF token. QR image retrieval is a POST and is not publicly exposed.
- The GoWA REST API is accessed server-to-server. The browser never receives the API password or the internal GoWA URL.
- cURL does not follow redirects, has bounded connection/request timeouts, requires HTTPS for public hosts and restricts QR filenames to an ASCII `.png` basename. QR image bytes must start with a PNG signature and fit within 1 MiB.
- GoWA is recommended on a private Docker network without a host-exposed port. The admin remains responsible for validating the configured destination and restricting server egress.
- Sending defaults to disabled. A checkbox is **not** a durable consent registry: administrators must obtain and maintain proper records separately. There is no server-side rate limiter or queue in this release; administrators must avoid bulk use and comply with WhatsApp policy.
- Ambiguous send timeouts never trigger an automatic retry. The operator must check delivery state before retrying.
- The API password is stored through the ChurchCRM plugin configuration mechanism. Do **not** claim end-to-end encryption of this secret at rest; database and backup protections are the responsibility of the deployment.
- Public QR screenshots and session archives must never be attached to reports or issues. See [SECURITY.md](../SECURITY.md).

## Release verification

Run `scripts/package-release.sh v0.1.3` from a tagged commit. Run the ChurchCRM scanner against the **unpacked runtime ZIP**, not the development repository. Record scanner summary, package checksum, runtime smoke tests, PHPUnit, Playwright, Psalm, PHP-CS-Fixer, REUSE and the GitHub Actions check conclusions in the registry PR. The registry `reviewedAt` field must be set by the maintainer who actually reviews the artifact.
