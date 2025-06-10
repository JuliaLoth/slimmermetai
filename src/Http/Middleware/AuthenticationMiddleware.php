<?php

namespace App\Http\Middleware;

use App\Domain\Service\AuthServiceInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use GuzzleHttp\Psr7\Response;
use App\Infrastructure\Config\Config;

/**
 * AuthenticationMiddleware
 *
 * Controleert aanwezig JWT Bearer token.  Valide of publieke paden mogen
 * worden overgeslagen via $publicPaths.
 */
class AuthenticationMiddleware implements MiddlewareInterface
{
    /** @var array<int,string> */
    private array $publicPaths = [];
    private string $loginRoute;
/** @param array<int,string> $publicPaths  Paden (prefix) die geen auth vereisen */
    public function __construct(private AuthServiceInterface $authService, ?array $publicPaths = null)
    {
        $cfg = Config::getInstance();
        $this->publicPaths = $publicPaths ?? $cfg->getTyped('auth_public_paths', 'array', [
            '/',
            '/login',
            '/auth/login',
            '/auth/register',
            '/auth/forgot-password',
            '/nieuws',
            '/over-mij',
            '/forgot-password',
            '/betaling-succes',
            '/betaling-voltooid',
            '/login-success',
            '/api/auth/login.php',
            '/api/auth/register.php',
            '/api/auth/google.php',
            '/api/auth/google-callback.php',
            '/api/stripe/config',
            '/api/stripe/webhook'
        ]);
        $this->loginRoute = $cfg->get('login_route', '/login');
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        foreach ($this->publicPaths as $public) {
        // Special case for root path - exact match only
            if ($public === '/' && $path === '/') {
                return $handler->handle($request);
            }
            // For other paths, check if path starts with public path
            if ($public !== '/' && str_starts_with($path, $public)) {
                return $handler->handle($request);
            }
        }

        $header = $request->getHeaderLine('Authorization');
        if (!$header && $request->hasHeader('HTTP_AUTHORIZATION')) {
            $header = $request->getHeaderLine('HTTP_AUTHORIZATION');
        }
        if (!$header || !preg_match('/Bearer\s+(\S+)/', $header, $m)) {
            return $this->unauthorized('Bearer token ontbreekt', $request);
        }
        $payload = $this->authService->verifyToken($m[1]);
        if (!$payload) {
            return $this->unauthorized('Ongeldig token', $request);
        }

        // Sla payload op als request attribute zodat controllers deze kunnen uitlezen
        $request = $request->withAttribute('auth', $payload);
        return $handler->handle($request);
    }

    private function unauthorized(string $reason, ServerRequestInterface $request): ResponseInterface
    {
        $accept = strtolower($request->getHeaderLine('Accept'));
        $isApi = str_contains($accept, 'application/json') || str_starts_with($request->getUri()->getPath(), '/api');
        if ($isApi) {
            return new Response(401, ['Content-Type' => 'application/json'], json_encode(['error' => 'Unauthorized', 'reason' => $reason]));
        }

        // Web: redirect naar loginpagina
        return new Response(302, ['Location' => $this->loginRoute]);
    }
}
