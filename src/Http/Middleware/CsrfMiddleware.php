<?php
namespace App\Http\Middleware;

use App\Infrastructure\Security\CsrfProtection;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use GuzzleHttp\Psr7\Response;
use App\Infrastructure\Config\Config;

/**
 * CsrfMiddleware
 *
 * Valideert CSRF-tokens voor state changing requests.
 */
class CsrfMiddleware implements MiddlewareInterface
{
    /** @var array<int,string> */
    private array $excludedPaths = [];

    public function __construct(private CsrfProtection $csrf, ?array $excludedPaths = null)
    {
        $cfg = Config::getInstance();
        $this->excludedPaths = $excludedPaths ?? $cfg->getTyped('csrf_exclude_paths', 'array', [
            '/api', 
            '/stripe/webhook', 
            '/stripe/checkout',
            '/auth/login',
            '/auth/register', 
            '/auth/forgot-password'
        ]);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        foreach ($this->excludedPaths as $ex) {
            if (str_starts_with($path, $ex)) {
                return $handler->handle($request);
            }
        }

        $method = strtoupper($request->getMethod());
        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $token = $request->getHeaderLine('X-CSRF-Token') ?: $request->getParsedBody()['csrf_token'] ?? null;
            if (!$this->csrf->validateToken($token)) {
                return new Response(
                    403,
                    ['Content-Type' => 'application/json'],
                    json_encode(['error' => 'Invalid CSRF token'])
                );
            }
        }
        return $handler->handle($request);
    }
} 