<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Vitor Mattos
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


require_once __DIR__ . '/../../../src/GoWaClient.php';

use ChurchCRM\Plugins\WhatsAppGowa\GoWaClient;
use PHPUnit\Framework\TestCase;

final class GoWaProtocolTest extends TestCase
{
    public function testDisconnectedDeviceStatusIsNormalized(): void
    {
        $result = GoWaClient::normalizeStatus([
            'code' => 'SUCCESS',
            'results' => ['is_connected' => false, 'is_logged_in' => false, 'jid' => ''],
        ], 'churchcrm');

        self::assertSame([
            'deviceId' => 'churchcrm',
            'deviceRegistered' => true,
            'connected' => false,
            'loggedIn' => false,
        ], $result);
    }

    public function testConnectedDeviceStatusIsNormalized(): void
    {
        $result = GoWaClient::normalizeStatus([
            'code' => 'SUCCESS',
            'results' => ['is_connected' => true, 'is_logged_in' => true],
        ], 'churchcrm');
        self::assertTrue($result['connected']);
        self::assertTrue($result['loggedIn']);
    }

    public function testInvalidStatusIsNotReportedAsConnected(): void
    {
        $this->expectException(RuntimeException::class);
        GoWaClient::normalizeStatus(['code' => 'ERROR'], 'churchcrm');
    }

    public function testValidQrLinkReturnsOnlyItsFilename(): void
    {
        self::assertSame('abc-123_.png', GoWaClient::qrFilename([
            'code' => 'SUCCESS',
            'results' => ['qr_link' => 'http://whatsapp-gowa:3000/statics/qrcode/abc-123_.png'],
        ]));
    }

    public function testArbitraryQrPathIsRejected(): void
    {
        $this->expectException(RuntimeException::class);
        GoWaClient::qrFilename([
            'code' => 'SUCCESS',
            'results' => ['qr_link' => 'https://example.org/../../admin/key.png'],
        ]);
    }

    public function testMissingQrImageIsRejected(): void
    {
        $this->expectException(RuntimeException::class);
        GoWaClient::qrFilename([
            'code' => 'SUCCESS',
            'results' => ['qr_duration' => 30],
        ]);
    }
}
