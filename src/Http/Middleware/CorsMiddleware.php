<?php

namespace App\Http\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use GuzzleHttp\Psr7\Response;
use App\Infrastructure\Config\Config;

/**
 * CorsMiddleware
 *
 * Voegt CORS headers toe en handelt preflight OPTIONS requests af.
 */
class CorsMiddleware implements MiddlewareInterface
{
    public function __construct(?string $allowOrigin = null, ?string $allowMethods = null, ?string $allowHeaders = null)
    {
        $cfg = Config::getInstance();
        $this->allowOrigin  = $allowOrigin  ?? $cfg->get('cors_allow_origin', '*');
        $this->allowMethods = $allowMethods ?? $cfg->get('cors_allow_methods', 'GET,POST,PUT,PATCH,DELETE,OPTIONS');
        $this->allowHeaders = $allowHeaders ?? $cfg->get('cors_allow_headers', 'Content-Type, Authorization, X-CSRF-Token');
    }

    private string $allowOrigin;
    private string $allowMethods;
    private string $allowHeaders;
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getMethod() === 'OPTIONS') {
// Preflight response
            return $this->applyHeaders(new Response(204));
        }

        $response = $handler->handle($request);
        return $this->applyHeaders($response);
    }

    private function applyHeaders(ResponseInterface $response): ResponseInterface
    {
        return $response
            ->withHeader('Access-Control-Allow-Origin', $this->allowOrigin)
            ->withHeader('Access-Control-Allow-Methods', $this->allowMethods)
            ->withHeader('Access-Control-Allow-Headers', $this->allowHeaders);
    }
}
