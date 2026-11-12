<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Vitor Mattos
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


use ChurchCRM\dto\SystemURLs;
use ChurchCRM\Plugins\WhatsAppGowa\WhatsAppGowaPlugin;
use ChurchCRM\Slim\Middleware\CSRFMiddleware;
use ChurchCRM\Slim\Middleware\Request\Auth\AdminRoleAuthMiddleware;
use ChurchCRM\Slim\SlimUtils;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteCollectorProxy;
use Slim\Views\PhpRenderer;

$plugin = WhatsAppGowaPlugin::instance();
if ($plugin === null) {
    return;
}

$app->get('/whatsapp-gowa/manage', function (Request $request, Response $response): Response {
    return (new PhpRenderer(__DIR__ . '/../views/'))->render($response, 'manage.php', [
        'sRootPath' => SystemURLs::getRootPath(),
        'sPageTitle' => dgettext('whatsapp-gowa', 'WhatsApp'),
    ]);
})->add(AdminRoleAuthMiddleware::class);

$app->group('/whatsapp-gowa/api', function (RouteCollectorProxy $group) use ($plugin): void {
    $group->get('/status', function (Request $request, Response $response) use ($plugin): Response {
        try {
            return SlimUtils::renderJSON($response->withHeader('Cache-Control', 'no-store'), [
                'success' => true,
                'data' => [...$plugin->client()->connectionStatus(), 'sendingEnabled' => $plugin->canSend()],
            ]);
        } catch (\Throwable $error) {
            return SlimUtils::renderJSON($response, ['success' => false, 'message' => dgettext('whatsapp-gowa', 'Gateway unavailable')], 503);
        }
    });

    $group->post('/register-device', function (Request $request, Response $response) use ($plugin): Response {
        try {
            return SlimUtils::renderJSON($response, ['success' => true, 'data' => $plugin->registerDevice()]);
        } catch (\Throwable $error) {
            return SlimUtils::renderJSON($response, ['success' => false, 'message' => dgettext('whatsapp-gowa', 'Device registration failed. Check the GoWA API.')], 409);
        }
    });

    $group->post('/qr', function (Request $request, Response $response) use ($plugin): Response {
        try {
            return SlimUtils::renderJSON($response->withHeader('Cache-Control', 'no-store'), [
                'success' => true,
                'data' => $plugin->client()->pairingQr(),
            ]);
        } catch (\Throwable $error) {
            return SlimUtils::renderJSON($response, ['success' => false, 'message' => dgettext('whatsapp-gowa', 'QR unavailable')], 503);
        }
    });

    $group->post('/send', function (Request $request, Response $response) use ($plugin): Response {
        if (!$plugin->canSend()) {
            return SlimUtils::renderJSON($response, ['success' => false, 'message' => dgettext('whatsapp-gowa', 'Sending is disabled')], 403);
        }
        $data = $request->getParsedBody();
        if (!is_array($data) || ($data['consent'] ?? '') !== 'yes') {
            return SlimUtils::renderJSON($response, ['success' => false, 'message' => dgettext('whatsapp-gowa', 'Recipient consent required')], 400);
        }
        $message = trim((string) ($data['message'] ?? ''));
        if ($message === '' || mb_strlen($message) > 2000) {
            return SlimUtils::renderJSON($response, ['success' => false, 'message' => dgettext('whatsapp-gowa', 'Message length must be 1-2000 characters')], 400);
        }
        try {
            $number = $plugin->formatPhone((string) ($data['phone'] ?? ''));
            $result = $plugin->client()->send($number, $message);
            return SlimUtils::renderJSON($response, ['success' => true, 'messageId' => $result['results']['message_id'] ?? null]);
        } catch (\InvalidArgumentException $error) {
            return SlimUtils::renderJSON($response, ['success' => false, 'message' => $error->getMessage()], 400);
        } catch (\Throwable $error) {
            return SlimUtils::renderJSON($response, ['success' => false, 'message' => dgettext('whatsapp-gowa', 'Delivery could not be confirmed; do not retry automatically')], 502);
        }
    });
})->add(new CSRFMiddleware())->add(AdminRoleAuthMiddleware::class);
