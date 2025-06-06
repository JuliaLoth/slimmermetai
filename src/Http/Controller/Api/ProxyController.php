<?php

namespace App\Http\Controller\Api;

use App\Infrastructure\Config\Config;
use App\Http\Response\ApiResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * ApiProxyController
 *
 * Houdt eenvoudige "proxy"-achtige functionaliteit in stand voor legacy front-end calls.
 * Voor nu ondersteunt hij nog slechts een paar hard-gecodeerde endpoints.
 */
final class ProxyController
{
    public function __construct(private Config $config)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // query param ?endpoint=x
        $endpoint = $request->getQueryParams()['endpoint'] ?? '';
        if ($request->getMethod() === 'OPTIONS') {
            return ApiResponse::success(['allow' => 'GET, OPTIONS']);
        }

        switch ($endpoint) {
            case 'stripe':
                return ApiResponse::success([
                    'name'        => 'Stripe API',
                    'version'     => '1.0',
                    'description' => 'Betaalverwerking voor Slimmer met AI',
                    'timestamp'   => date('Y-m-d H:i:s'),
                ]);

            case 'stripe_config':
                return ApiResponse::success([
                    'public_key' => $this->config->get('stripe_public_key', ''),
                    'currency'   => 'EUR',
                    'locale'     => 'nl-NL',
                ]);

            case 'stripe_test':
                return ApiResponse::success([
                    'status'      => 'success',
                    'message'     => 'API proxy test is geslaagd',
                    'timestamp'   => date('Y-m-d H:i:s'),
                    'server_info' => [
                        'php_version'  => PHP_VERSION,
                        'server_name'  => $_SERVER['SERVER_NAME'] ?? 'unknown',
                        'request_uri'  => $_SERVER['REQUEST_URI'] ?? 'unknown',
                    ],
                ]);

            default:
                return ApiResponse::error('Unknown endpoint: ' . $endpoint, 404);
        }
    }
}
