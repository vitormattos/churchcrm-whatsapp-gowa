<!--
SPDX-FileCopyrightText: 2026 Vitor Mattos
SPDX-License-Identifier: AGPL-3.0-or-later
-->

# Install the ChurchCRM plugin

The integration is an external **community plugin**; no changes to ChurchCRM core are required. For deployment on Docker, the plugin directory must survive container recreation and upgrades.

## Prerequisites

- ChurchCRM 7.x with access to **Administration → Plugins**.
- PHP 8.4+ and the cURL PHP extension in the ChurchCRM container.
- Access to the host running ChurchCRM and a private GoWA API service; follow [GoWA setup](gateway.md) first.
- A usable WhatsApp account, preferably a dedicated church account, for pairing.

## 1. Install the plugin

Once this plugin is included in the official ChurchCRM registry, go to **Administration → Plugins → Browse Community Plugins**, review the listed capabilities and click **Install**. The installer verifies the release ZIP checksum. You must still provision GoWA yourself using [the gateway guide](gateway.md); installing the plugin does not deploy the GoWA service.

Alternatively, developers can install from Git as follows.

On a regular PHP installation, clone the repository as a child of ChurchCRM's `src/plugins/community` directory:

```bash
cd /path/to/churchcrm/src/plugins/community
# Clone only the plugin; do not clone the ChurchCRM core repository.
git clone https://github.com/vitormattos/churchcrm-whatsapp-gowa.git whatsapp-gowa
```

For a **Docker Compose** deployment, keep the plugin separately on the host and bind mount it into the PHP container:

```bash
mkdir -p /opt/churchcrm-plugins
git clone https://github.com/vitormattos/churchcrm-whatsapp-gowa.git /opt/churchcrm-plugins/whatsapp-gowa
```

In the **ChurchCRM Compose override** file, extend the existing PHP service's volumes (do not replace its data volumes):

```yaml
services:
  php:
    volumes:
      - /opt/churchcrm-plugins/whatsapp-gowa:/var/www/html/src/plugins/community/whatsapp-gowa:ro
```

Apply the override with the regular ChurchCRM Compose project. Check `docker compose config` and preserve existing volumes, networks, database settings and secrets before recreating services:

```bash
docker compose config --quiet
docker compose up -d --no-deps php
```

The plugin's runtime only depends on the PHP sources in `src/` and `routes/`; **do not run Composer, PHPUnit or frontend tools on the production server**.

## 2. Activate

1. Sign in as a ChurchCRM administrator.
2. Open **Administration → Plugins**; find **WhatsApp (GoWA)** under community plugins.
3. Enable the plugin. Community plugins installed manually may be marked as unverified; review the source and its origin before approving it.
4. Configure the API URL and authentication using the [configuration guide](configuration.md).
5. Use the **WhatsApp** entry in the left-hand administration menu (or open `/plugins/whatsapp-gowa/manage`).

For servers installed under a URL prefix, ChurchCRM adds the correct root path to the plugin menu link.

## 3. Confirm the installation

Verify that **Check connection** distinguishes an available GoWA API from a paired WhatsApp account. A new gateway initially shows **waiting for QR pairing**; it does not imply an error. Follow [pairing](configuration.md#connect-whatsapp).

**Important:** messages remain disabled until `sendingEnabled` is explicitly enabled in the ChurchCRM plugin settings. Never use a real member's number as a connectivity test without authorization.
