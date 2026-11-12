# ChurchCRM WhatsApp (GoWA)

Community plugin for ChurchCRM 7.x. Integrates the Go WhatsApp Web Multidevice REST API with an admin-only setup and send page, without changes to ChurchCRM core.

## Installation

Copy this directory to `src/plugins/community/whatsapp-gowa`. The web service must retain this directory across image upgrades. Visit **Admin > Plugins** and enable **WhatsApp (GoWA)**, then configure the gateway URL, API username and password, and optional device ID. Supply a dedicated self-hosted GoWA instance reachable on the Docker network. **Do not expose its HTTP port publicly.** Configure `sendingEnabled` only after pairing and testing.

For a new GoWA v8/v9 installation, first click **Register device** (creates `churchcrm` once), then **Show pairing QR** under **Admin > WhatsApp**. The QR endpoint is proxied through an authenticated admin route. GoWA response formats differ between versions; current UI accepts a PNG/JPEG data URI or the standard GoWA `/statics/qrcode/*.png` link, proxied with administrator authorization.

Messages are sent only one at a time and require consent confirmation per recipient. There is no automatic retry, mass campaign scheduler, or contact import in this initial release. A transport timeout is ambiguous: confirm upstream delivery before resubmitting. GoWA uses the unofficial WhatsApp Web protocol, which may cause restrictions or service disruption; obtain consent and respect messaging policies and applicable data-protection obligations.

## Gateway

A compatible server is `ghcr.io/aldinokemal/go-whatsapp-web-multidevice`, using `APP_BASIC_AUTH` and a persistent volume for `/app/storages`. Use a dedicated WhatsApp account. Limit network ingress and back up session storage securely.

## Development

The plugin follows ChurchCRM `plugin.json`, `AbstractPlugin`, `routesFile`, and the built-in admin route authorization patterns. See `tests` for isolated client and input validation tests. Secrets are stored in ChurchCRM's existing plugin configuration; do not commit production configuration to Git.

## License

MIT. Based on API interoperability with the upstream GoWA project; no upstream source code is vendored.
