<!--
SPDX-FileCopyrightText: 2026 Vitor Mattos
SPDX-License-Identifier: AGPL-3.0-or-later
-->

# Configure a private GoWA server

GoWA is a separate service. ChurchCRM talks to it over an internal HTTP connection; **no GoWA API ports need to be published on the Internet**.

## 1. Prepare a private network and credentials

When ChurchCRM uses Docker Compose, identify its internal network with `docker network ls`. For a conventional project the network may be `church-crm_internal`. This name is an example and must match **your** deployment.

```bash
mkdir -p /opt/churchcrm-gowa
cd /opt/churchcrm-gowa
umask 077
printf 'GOWA_API_USER=churchcrm\n' > .env
printf 'GOWA_API_PASSWORD=%s\n' "$(openssl rand -hex 32)" >> .env
# Adjust to the actual pre-existing internal network name:
printf 'CHURCHCRM_NETWORK=church-crm_internal\n' >> .env
chmod 600 .env
```

The environment file contains a secret. It must not be committed, shared publicly or copied into a support request.

## 2. Deploy GoWA

Create `/opt/churchcrm-gowa/compose.yaml`:

```yaml
services:
  whatsapp-gowa:
    image: ghcr.io/aldinokemal/go-whatsapp-web-multidevice:latest
    restart: unless-stopped
    environment:
      APP_BASIC_AUTH: ${GOWA_API_USER}:${GOWA_API_PASSWORD}
      APP_PORT: "3000"
      APP_DEBUG: "false"
    volumes:
      - ./storages:/app/storages
      - ./statics:/app/statics
    networks:
      churchcrm:
        aliases:
          - whatsapp-gowa
    security_opt:
      - no-new-privileges:true
networks:
  churchcrm:
    external: true
    name: ${CHURCHCRM_NETWORK}
```

For reproducible production upgrades, pin the GoWA image to a reviewed release tag or digest instead of `latest`. Confirm volume paths and environment options against the selected GoWA release before upgrading.

```bash
docker compose config --quiet
docker compose up -d
docker compose ps
```

The Compose file intentionally has **no `ports:` declaration**. Only other containers attached to the same private Docker network can reach `http://whatsapp-gowa:3000`. Use a dedicated gateway account and restrict access to the host and Docker API.

## 3. Confirm availability from ChurchCRM

From inside the ChurchCRM PHP container:

```bash
docker exec church-crm-php-1 curl --max-time 10 -fsS http://whatsapp-gowa:3000/health
```

A working `/health` endpoint indicates the API is reachable; it does **not** indicate that WhatsApp is paired. Avoid `curl -v` with API credentials, which may expose secrets in logs or shell history.

## 4. Pair a device

Follow [Configure ChurchCRM](configuration.md#connect-whatsapp). The administration page requests the short-lived QR from GoWA and displays the image inside the authenticated ChurchCRM session. **Never post or screenshot a pairing QR publicly**: it grants access to the WhatsApp account during its validity.

## Updating and storage

The `storages` directory contains WhatsApp account/session material. Restrict host permissions; back it up encrypted and test restoration securely. Treat `statics` as temporary generated content. During upgrades, review changes to GoWA device/session APIs and take a recoverable backup before replacing the image.
