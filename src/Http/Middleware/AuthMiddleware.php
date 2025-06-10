<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Infrastructure\Security\JwtService;
use App\Infrastructure\Repository\AuthRepository;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use GuzzleHttp\Psr7\Response;

/**
 * AuthMiddleware - JWT Authentication Middleware
 *
 * Validates JWT tokens and checks blacklist status.
 * Designed for API endpoints that require authentication.
 */
class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(
        private JwtService $jwtService,
        private AuthRepository $authRepository
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $authHeader = $request->getHeaderLine('Authorization');

        // Check for authorization header
        if (empty($authHeader)) {
            return $this->unauthorizedResponse('Authorization header is missing');
        }

        // Check Bearer format
        if (!preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
            return $this->unauthorizedResponse('Invalid authorization header format');
        }

        $token = trim($matches[1]);

        // Verify JWT token
        $payload = $this->jwtService->verify($token);
        if (!$payload) {
            if ($this->isExpiredToken($token)) {
                return $this->unauthorizedResponse('Token has expired');
            }
            return $this->unauthorizedResponse('Invalid token');
        }

        // Check if token is blacklisted
        if ($this->authRepository->isTokenBlacklisted($token)) {
            return $this->unauthorizedResponse('Token has been blacklisted');
        }

        // Add user payload to request attributes
        $request = $request->withAttribute('auth_payload', $payload);
        $request = $request->withAttribute('auth_token', $token);

        return $handler->handle($request);
    }

    private function unauthorizedResponse(string $message): ResponseInterface
    {
        $body = json_encode([
            'success' => false,
            'message' => $message,
            'error_code' => 'UNAUTHORIZED'
        ]);

        return new Response(
            401,
            [
                'Content-Type' => 'application/json; charset=UTF-8',
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Headers' => 'Authorization, Content-Type',
                'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS'
            ],
            $body
        );
    }

    private function isExpiredToken(string $token): bool
    {
        try {
            // Try to decode without verification to check expiration
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                return false;
            }

            $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);

            return isset($payload['exp']) && $payload['exp'] < time();
        } catch (\Exception $e) {
            return false;
        }
    }
}
