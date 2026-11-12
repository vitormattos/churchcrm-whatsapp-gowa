<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Vitor Mattos
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


use PHPUnit\Framework\TestCase;

final class NavigationMenuTest extends TestCase
{
    public function testMenuPathIsRelativeToTheChurchCrmRoot(): void
    {
        $manifest = json_decode(
            file_get_contents(__DIR__ . '/../../../plugin.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        $items = array_values(array_filter(
            $manifest['menuItems'],
            static fn (array $item): bool => ($item['label'] ?? '') === 'WhatsApp',
        ));
        self::assertCount(1, $items);
        $url = $items[0]['url'];
        self::assertSame('plugins/whatsapp-gowa/manage', $url);
        self::assertFalse(str_starts_with($url, '/'));

        // ChurchCRM MenuItem::getURI() adds SystemURLs::getRootPath() and '/'.
        self::assertSame('/plugins/whatsapp-gowa/manage', '' . '/' . $url);
        self::assertSame('/churchcrm/plugins/whatsapp-gowa/manage', '/churchcrm' . '/' . $url);
    }
}
