<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/GoWaClient.php';

use ChurchCRM\Plugins\WhatsAppGowa\GoWaClient;
use PHPUnit\Framework\TestCase;

final class GoWaClientTest extends TestCase
{
    public function testRejectsInsecurePublicGateway(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new GoWaClient('http://example.com', 'user', 'secret');
    }

    public function testRejectsEmbeddedCredentials(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new GoWaClient('https://user:secret@example.com', 'user', 'secret');
    }

    public function testAllowsPrivateDockerService(): void
    {
        self::assertInstanceOf(GoWaClient::class, new GoWaClient('http://whatsapp-gowa:3000', 'user', 'secret'));
    }

    public function testRejectsInvalidSchemes(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new GoWaClient('file:///etc/passwd', 'user', 'secret');
    }
}
