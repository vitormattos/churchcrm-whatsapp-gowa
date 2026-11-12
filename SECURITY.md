<!--
SPDX-FileCopyrightText: 2026 Vitor Mattos
SPDX-License-Identifier: AGPL-3.0-or-later
-->

# Security policy

## Supported versions

Only the latest published release is maintained for security fixes. Older versions should be upgraded after reviewing the release notes.

## Report a vulnerability privately

**Do not open a public issue for a security vulnerability.** Use the [GitHub private vulnerability reporting feature](https://github.com/vitormattos/churchcrm-whatsapp-gowa/security/advisories/new) when available. If private reporting is unavailable, contact the maintainer privately through the contact information on the [GitHub profile](https://github.com/vitormattos).

Include an impact description, affected version, reproduction instructions and a proposed fix if known. **Never share active WhatsApp QR codes, session files, phone numbers, message bodies or passwords in a report.**

The maintainer will aim to acknowledge reports within **7 calendar days** and provide a status update within **30 days**. These are response targets, not guarantees. Public disclosure should be coordinated after users have an opportunity to update.

## Scope

The plugin executes within an authenticated ChurchCRM administrator session and connects to a separately operated GoWA service. Protect both ChurchCRM and GoWA API credentials. ChurchCRM currently stores plugin configuration values in its database; protect database access and backups, restrict the plugin to administrators, and rotate exposed credentials. Do not expose GoWA directly to the public Internet. GoWA is based on the unofficial WhatsApp Web protocol; upstream changes and account restrictions are outside this plugin's security guarantees.
