<?php

namespace App\Http\Controller\Api;

use App\Infrastructure\Config\Config;
use App\Infrastructure\Http\ApiResponse;
use Psr\Http\Message\ServerRequestInterface;

final class IndexController
{
    public function __construct(private Config $config)
    {
    }

    public function handle(ServerRequestInterface $request): void
    {
        ApiResponse::success([
            'name' => 'Slimmer met AI - API',
            'version' => '1.0',
            'endpoints' => [
                'stripe' => [
                    'info' => 'Stripe betalings-API',
                    'proxy_endpoints' => [
                        '/api-proxy?endpoint=stripe_config',
                    ],
                    'routes' => [
                        '/stripe/checkout',
                        '/stripe/status/{id}',
                        '/stripe/webhook',
                    ],
                ],
            ],
            'timestamp' => date('Y-m-d H:i:s'),
            'server' => [
                'php_version' => PHP_VERSION,
                'env' => $this->config->get('app_env', 'production'),
            ],
        ]);
    }
}
