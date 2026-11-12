<!--
SPDX-FileCopyrightText: 2026 Vitor Mattos
SPDX-License-Identifier: AGPL-3.0-or-later
-->

# Operations, security and troubleshooting

## Health checks

Use the ChurchCRM **Check connection** button. A working API with a disconnected WhatsApp account is not an outage: it means device linking is still required. From the host:

```bash
docker compose -f /opt/churchcrm-gowa/compose.yaml ps
docker compose -f /opt/churchcrm-gowa/compose.yaml logs --tail=50 whatsapp-gowa
```

Avoid logging credentials, full phone numbers, pairing QR images or message bodies.

## Common issues

| Symptom | What to check |
| --- | --- |
| Gateway unavailable | Container health, internal network name, DNS alias, credentials and `/health` response |
| Register device disabled | Existing device is already registered; check status before trying to register again |
| QR image not shown | Admin authentication, GoWA `/app/login`, QR expiry and whether the API can return its image |
| Device disconnected | Re-pair through WhatsApp Linked devices; check session volume, host clock and GoWA logs |
| Send button disabled | Both connection/login and the explicit `sendingEnabled` setting must be true |
| Message not confirmed | Inspect gateway/recipient before retrying; do not send twice on an uncertain outcome |
| Incorrect sidebar link | Ensure plugin version has a **relative** `plugins/whatsapp-gowa/manage` menu path |

## Backups and upgrades

- Back up the existing ChurchCRM database and configuration **before database changes**.
- Back up GoWA's persistent `storages` directory with encryption and restricted access. Never commit session files.
- Before a plugin upgrade, review upstream changes, fetch a specific reviewed commit or tag, and test the plugin using the scripts in [development](development.md).
- For rollback, check out the previous plugin revision, recreate or restart **only** the ChurchCRM PHP container if needed, and verify that GoWA and ChurchCRM stay healthy. Do not delete GoWA's session volume.
- Re-pair only if the existing device session is genuinely invalid; an existing registration should be reused.

## Privacy and legal considerations

Use only numbers with documented authorization for the intended communication, ensure recipients can opt out, protect phone numbers and consent records, and follow applicable data-protection law and WhatsApp policies. GoWA interacts through an unofficial WhatsApp Web interface and may encounter account restrictions or incompatibilities.

The ChurchCRM plugin is a small admin integration, **not** a bulk messaging compliance or consent management system. For scaled outreach, evaluate the official WhatsApp Business Platform and its template and opt-in requirements.
