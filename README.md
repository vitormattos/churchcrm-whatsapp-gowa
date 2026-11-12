# ChurchCRM WhatsApp (GoWA)

Community plugin for ChurchCRM 7.x. Integrates the Go WhatsApp Web Multidevice REST API with an admin-only setup and send page, without changes to ChurchCRM core.

## Installation

Copy this directory to `src/plugins/community/whatsapp-gowa`. The web service must retain this directory across image upgrades. Visit **Admin > Plugins** and enable **WhatsApp (GoWA)**, then configure the gateway URL, API username and password, and optional device ID. Supply a dedicated self-hosted GoWA instance reachable on the Docker network. **Do not expose its HTTP port publicly.** Configure `sendingEnabled` only after pairing and testing.

For a new GoWA v8/v9 installation, first click **Register device** (creates `churchcrm` once), then **Show pairing QR** under **Admin > WhatsApp**. The QR image is returned through an authenticated, no-cache admin API; gateway credentials and internal URLs are never sent to the browser. The supported GoWA API returns a `/statics/qrcode/*.png` image link, which the backend fetches and returns as a short-lived inline PNG to the authenticated administrator.

The connection screen shows a human-readable online status, detects existing devices and displays the pairing QR directly in the browser. Refresh the QR if it expires. Messages are sent only one at a time and require consent confirmation per recipient. There is no automatic retry, mass campaign scheduler, or contact import in this initial release. A transport timeout is ambiguous: confirm upstream delivery before resubmitting. GoWA uses the unofficial WhatsApp Web protocol, which may cause restrictions or service disruption; obtain consent and respect messaging policies and applicable data-protection obligations.

## Gateway

A compatible server is `ghcr.io/aldinokemal/go-whatsapp-web-multidevice`, using `APP_BASIC_AUTH` and a persistent volume for `/app/storages`. Use a dedicated WhatsApp account. Limit network ingress and back up session storage securely.

## Development

The plugin follows ChurchCRM `plugin.json`, `AbstractPlugin`, `routesFile`, and the built-in admin route authorization patterns. See `tests` for isolated client/protocol checks and browser UI regression tests. Run `composer test` for PHP and `npm ci && npm run test:ui` for the UI (requires Chrome). Secrets are stored in ChurchCRM's existing plugin configuration; do not commit production configuration to Git.

## License

MIT. Based on API interoperability with the upstream GoWA project; no upstream source code is vendored.
