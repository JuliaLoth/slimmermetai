<?php
namespace App\Http\Controller\Api;

use App\Infrastructure\Config\Config;
use App\Infrastructure\Http\ApiResponse;
use Psr\Http\Message\ServerRequestInterface;

/**
 * ApiProxyController
 *
 * Houdt eenvoudige "proxy"-achtige functionaliteit in stand voor legacy front-end calls.
 * Voor nu ondersteunt hij nog slechts een paar hard-gecodeerde endpoints.
 */
final class ProxyController
{
    public function __construct(private Config $config) {}

    public function handle(ServerRequestInterface $request): void
    {
        // query param ?endpoint=x
        $endpoint = $request->getQueryParams()['endpoint'] ?? '';

        if ($request->getMethod() === 'OPTIONS') {
            ApiResponse::success(['allow' => 'GET, OPTIONS']);
        }

        switch ($endpoint) {
            case 'stripe':
                ApiResponse::success([
                    'name'        => 'Stripe API',
                    'version'     => '1.0',
                    'description' => 'Betaalverwerking voor Slimmer met AI',
                    'timestamp'   => date('Y-m-d H:i:s'),
                ]);
                break;

            case 'stripe_config':
                ApiResponse::success([
                    'publishableKey' => $this->config->get('stripe_public_key', ''),
                    'timestamp'      => date('Y-m-d H:i:s'),
                    'proxy'          => true,
                ]);
                break;

            case 'stripe_test':
                ApiResponse::success([
                    'status'      => 'success',
                    'message'     => 'API proxy test is geslaagd',
                    'timestamp'   => date('Y-m-d H:i:s'),
                    'server_info' => [
                        'php_version'  => PHP_VERSION,
                        'server_name'  => $_SERVER['SERVER_NAME'] ?? 'unknown',
                        'request_uri'  => $_SERVER['REQUEST_URI'] ?? 'unknown',
                    ],
                ]);
                break;

            default:
                ApiResponse::notFound('Onbekend API endpoint: ' . $endpoint);
        }
    }
} 