<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Vitor Mattos
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace ChurchCRM\Plugins\WhatsAppGowa;

use ChurchCRM\Plugin\AbstractPlugin;

final class WhatsAppGowaPlugin extends AbstractPlugin
{
    private static ?self $instance = null;

    public function __construct(string $basePath = '')
    {
        parent::__construct($basePath);
        self::$instance = $this;
    }

    public static function instance(): ?self
    {
        return self::$instance;
    }

    public function getId(): string
    {
        return 'whatsapp-gowa';
    }

    public function getName(): string
    {
        return 'WhatsApp (GoWA)';
    }

    public function getDescription(): string
    {
        return 'Send opted-in WhatsApp messages using a self-hosted GoWA gateway.';
    }

    public function getSettingsSchema(): array
    {
        return json_decode((string) file_get_contents(__DIR__ . '/../plugin.json'), true, 512, JSON_THROW_ON_ERROR)['settings'];
    }

    public function getMenuItems(): array
    {
        return json_decode((string) file_get_contents(__DIR__ . '/../plugin.json'), true, 512, JSON_THROW_ON_ERROR)['menuItems'];
    }

    public function boot(): void
    {
    }

    public function isConfigured(): bool
    {
        return $this->getConfigValue('apiBaseUrl') !== ''
            && $this->getConfigValue('apiUser') !== ''
            && $this->getConfigValue('apiPassword') !== '';
    }

    public function testWithSettings(array $settings): array
    {
        try {
            $client = new GoWaClient(
                $this->settingOrConfigured($settings, 'apiBaseUrl'),
                $this->settingOrConfigured($settings, 'apiUser'),
                $this->settingOrConfigured($settings, 'apiPassword'),
                $this->settingOrConfigured($settings, 'deviceId'),
            );
            $client->status();
            return ['success' => true, 'message' => 'GoWA API reachable'];
        } catch (\Throwable $error) {
            return ['success' => false, 'message' => 'GoWA connection failed'];
        }
    }

    private function settingOrConfigured(array $settings, string $key): string
    {
        $value = $settings[$key] ?? null;
        return is_string($value) && $value !== '' ? $value : $this->getConfigValue($key);
    }

    public function client(): GoWaClient
    {
        if (!$this->isConfigured()) {
            throw new \RuntimeException('Configure GoWA API credentials first');
        }
        return new GoWaClient(
            $this->getConfigValue('apiBaseUrl'),
            $this->getConfigValue('apiUser'),
            $this->getConfigValue('apiPassword'),
            $this->getConfigValue('deviceId'),
        );
    }

    public function registerDevice(): array
    {
        if ($this->getConfigValue('deviceId') !== '') {
            return ['deviceId' => $this->getConfigValue('deviceId'), 'alreadyRegistered' => true];
        }
        $result = $this->client()->registerDevice('churchcrm');
        if (($result['code'] ?? '') !== 'SUCCESS') {
            throw new \RuntimeException('Gateway rejected device registration');
        }
        $this->setConfigValue('deviceId', 'churchcrm');
        if ($this->getConfigValue('deviceId') !== 'churchcrm') {
            throw new \RuntimeException('Device registration was not saved');
        }
        return ['deviceId' => 'churchcrm', 'alreadyRegistered' => false];
    }

    public function canSend(): bool
    {
        return $this->isEnabled() && $this->getBooleanConfigValue('sendingEnabled');
    }

    public function formatPhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if ($phone === null || strlen($phone) < 10 || strlen($phone) > 15) {
            throw new \InvalidArgumentException('Phone must contain 10 to 15 digits including country code');
        }
        return $phone;
    }
}
