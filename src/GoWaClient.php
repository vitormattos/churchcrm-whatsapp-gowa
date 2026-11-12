<?php

declare(strict_types=1);

namespace ChurchCRM\Plugins\WhatsAppGowa;

final class GoWaClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $username,
        private readonly string $password,
        private readonly string $deviceId = '',
    ) {
        $parts = parse_url($baseUrl);
        if (!$parts || !in_array($parts['scheme'] ?? '', ['http', 'https'], true)
            || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])
            || isset($parts['query']) || isset($parts['fragment'])
        ) {
            throw new \InvalidArgumentException('Invalid GoWA API URL');
        }
        if (($parts['scheme'] ?? '') !== 'https' && !self::isPrivateHost($parts['host'])) {
            throw new \InvalidArgumentException('Public GoWA API URLs must use HTTPS');
        }
    }

    private static function isPrivateHost(string $host): bool
    {
        if (in_array($host, ['localhost', 'whatsapp-gowa', 'gowa'], true)) {
            return true;
        }
        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return !filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
        }
        return str_ends_with($host, '.internal') || str_ends_with($host, '.docker');
    }

    public function request(string $method, string $path, ?array $payload = null): array
    {
        $ch = curl_init(rtrim($this->baseUrl, '/') . $path);
        $headers = ['Accept: application/json'];
        if ($this->deviceId !== '') {
            $headers[] = 'X-Device-Id: ' . $this->deviceId;
        }
        if ($payload !== null) {
            $headers[] = 'Content-Type: application/json';
        }
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERPWD => $this->username . ':' . $this->password,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 12,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
        ]);
        if ($payload !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_THROW_ON_ERROR));
        }
        try {
            $raw = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            if ($raw === false || $status < 200 || $status >= 300) {
                throw new \RuntimeException('GoWA request failed (HTTP ' . $status . ')');
            }
            $result = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($result)) {
                throw new \RuntimeException('Invalid GoWA response');
            }
            return $result;
        } finally {
            curl_close($ch);
        }
    }

    public function registerDevice(string $id): array
    {
        if (!preg_match('/^[a-z0-9][a-z0-9_-]{1,30}$/D', $id)) {
            throw new \InvalidArgumentException('Invalid device ID');
        }
        return $this->request('POST', '/devices', ['device_id' => $id]);
    }

    public function status(): array
    {
        return $this->request('GET', '/app/status');
    }

    public function qr(): array
    {
        return $this->request('GET', '/app/login');
    }

    public static function normalizeStatus(array $response, string $deviceId): array
    {
        if (($response['code'] ?? '') !== 'SUCCESS' || !is_array($response['results'] ?? null)) {
            throw new \RuntimeException('Could not determine GoWA connection status');
        }

        $state = $response['results'];
        return [
            'deviceId' => $deviceId,
            'deviceRegistered' => $deviceId !== '',
            'connected' => ($state['is_connected'] ?? false) === true,
            'loggedIn' => ($state['is_logged_in'] ?? false) === true,
        ];
    }

    public function connectionStatus(): array
    {
        return self::normalizeStatus($this->status(), $this->deviceId);
    }

    public static function qrFilename(array $response): string
    {
        if (($response['code'] ?? '') !== 'SUCCESS') {
            throw new \RuntimeException('GoWA did not create a pairing QR code');
        }

        $link = $response['results']['qr_link'] ?? null;
        $path = is_string($link) ? parse_url($link, PHP_URL_PATH) : false;
        if (!is_string($path) || !preg_match('~^/statics/qrcode/([A-Za-z0-9_-]+\.png)$~D', $path, $matches)) {
            throw new \RuntimeException('GoWA returned an unsupported QR code URL');
        }
        return $matches[1];
    }

    public function pairingQr(): array
    {
        $result = $this->qr();
        $image = $this->qrImage(self::qrFilename($result));
        return [
            'image' => 'data:image/png;base64,' . base64_encode($image),
            'validForSeconds' => max(1, min(120, (int) ($result['results']['qr_duration'] ?? 30))),
        ];
    }

    public function qrImage(string $filename): string
    {
        if (!preg_match('/^[A-Za-z0-9_-]+\.png$/D', $filename)) {
            throw new \InvalidArgumentException('Invalid QR image filename');
        }
        $ch = curl_init(rtrim($this->baseUrl, '/') . '/statics/qrcode/' . $filename);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERPWD => $this->username . ':' . $this->password,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
        ]);
        try {
            $image = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            if ($image === false || $status !== 200 || strlen($image) > 1024 * 1024
                || !str_starts_with($image, "\x89PNG\r\n\x1a\n")) {
                throw new \RuntimeException('QR image unavailable');
            }
            return $image;
        } finally {
            curl_close($ch);
        }
    }

    public function send(string $phone, string $message): array
    {
        $result = $this->request('POST', '/send/message', ['phone' => $phone, 'message' => $message]);
        if (($result['code'] ?? '') !== 'SUCCESS') {
            throw new \RuntimeException('GoWA rejected the message');
        }
        return $result;
    }
}
