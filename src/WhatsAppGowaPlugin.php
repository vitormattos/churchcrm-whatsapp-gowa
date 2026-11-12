<?php

declare(strict_types=1);

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

    public function boot(): void {}
    public function activate(): void {}
    public function deactivate(): void {}
    public function uninstall(): void {}

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
                (string) (($settings['apiBaseUrl'] ?? '') ?: $this->getConfigValue('apiBaseUrl')),
                (string) (($settings['apiUser'] ?? '') ?: $this->getConfigValue('apiUser')),
                (string) (($settings['apiPassword'] ?? '') ?: $this->getConfigValue('apiPassword')),
                (string) ($settings['deviceId'] ?? $this->getConfigValue('deviceId')),
            );
            $client->status();
            return ['success' => true, 'message' => 'GoWA API reachable'];
        } catch (\Throwable $error) {
            return ['success' => false, 'message' => 'GoWA connection failed'];
        }
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
            throw new \RuntimeException('A device ID is already configured');
        }
        $result = $this->client()->registerDevice('churchcrm');
        if (($result['code'] ?? '') !== 'SUCCESS') {
            throw new \RuntimeException('Gateway rejected device registration');
        }
        $this->setConfigValue('deviceId', 'churchcrm');
        return ['deviceId' => 'churchcrm'];
    }

    public function canSend(): bool
    {
        return $this->isEnabled() && $this->getBooleanConfigValue('sendingEnabled');
    }

    public function formatPhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($phone) < 10 || strlen($phone) > 15) {
            throw new \InvalidArgumentException('Phone must contain 10 to 15 digits including country code');
        }
        return $phone;
    }
}
