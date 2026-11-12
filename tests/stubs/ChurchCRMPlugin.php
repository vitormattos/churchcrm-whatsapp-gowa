<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Vitor Mattos
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace ChurchCRM\Plugin;

/** Only the API surface of the external ChurchCRM class used by the plugin. */
abstract class AbstractPlugin
{
    public function __construct(string $basePath = '')
    {
    }

    protected function getConfigValue(string $key): string
    {
        return '';
    }

    protected function setConfigValue(string $key, string $value): void
    {
    }

    protected function getBooleanConfigValue(string $key): bool
    {
        return false;
    }

    public function isEnabled(): bool
    {
        return false;
    }
}
