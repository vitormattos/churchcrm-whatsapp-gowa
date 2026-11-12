<!--
SPDX-FileCopyrightText: 2026 Vitor Mattos
SPDX-License-Identifier: AGPL-3.0-or-later
-->

# Development, quality and tests

The project follows ChurchCRM's plugin contract (`plugin.json`, `AbstractPlugin`, `routesFile`, admin-only routes) and LibreSign's isolated PHP tooling approach. Production execution requires **no `vendor/` directory**.

## Layout

| Directory | Responsibility |
| --- | --- |
| `src/` | PHP service and ChurchCRM plugin code |
| `routes/` and `views/` | Administrator HTTP routes and UI |
| `tests/php/Unit/` | Isolated PHPUnit tests (no running ChurchCRM required) |
| `tests/php/phpunit.xml` | PHPUnit configuration |
| `tests/ui/` | Playwright browser regression tests with stubbed API |
| `vendor-bin/phpunit/` | Isolated PHPUnit dependencies |
| `vendor-bin/psalm/` | Isolated Psalm static analyzer |
| `vendor-bin/coding-standard/` | Isolated PHP-CS-Fixer |
| `.github/workflows/` | One independent workflow per quality tool |

## Requirements

Use PHP 8.4+, Composer 2, Node.js 22+ and a supported Chromium installation for Playwright. The root Composer project only requires `bamarni/composer-bin-plugin`; it installs each tool in `vendor-bin/<tool>/vendor/`.

```bash
composer install
composer run test:unit
composer run psalm
composer run cs:check
composer run lint:php
npm ci
npx playwright install --with-deps chromium
npm run test:ui
```

To install a single isolated PHP tool without installing all three, use `composer install --no-scripts` followed by `composer bin phpunit install`, `composer bin psalm install` or `composer bin coding-standard install`.

`cs:fix` modifies local PHP code; use `composer run cs:fix` before reviewing the diff. PHPUnit, Psalm, PHP-CS-Fixer, browser tests and REUSE checks run separately in CI. Psalm focuses on the plugin's PHP services; framework API types are modelled with a small local stub instead of pulling ChurchCRM's entire application into the analyzer.

## New tests

Add isolated PHP tests in `tests/php/Unit/` and browser behavior regressions in `tests/ui/`. Tests should never contact real WhatsApp accounts or send messages. End-to-end tests against a paired device are manual and must use a dedicated test account. Maintain SPDX headers or REUSE annotations on new files.

## Reporting vulnerabilities

Do not publish session keys, API credentials, personal data or live pairing QR codes in issues. Use GitHub's private vulnerability reporting if it is available, or contact the maintainer privately.
