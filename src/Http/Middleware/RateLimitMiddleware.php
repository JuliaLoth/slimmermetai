<?php

namespace App\Http\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use GuzzleHttp\Psr7\Response;
use App\Infrastructure\Config\Config;

/**
 * RateLimitMiddleware
 *
 * Implementeert rate limiting voor API endpoints om misbruik te voorkomen.
 * Gebruikt sliding window algoritme met in-memory storage.
 */
class RateLimitMiddleware implements MiddlewareInterface
{
    private array $requests = [];
    private int $maxRequests;
    private int $windowSeconds;
    private array $exemptPaths;
    public function __construct(?Config $config = null)
    {
        $config = $config ?? Config::getInstance();
        $this->maxRequests = $config->get('rate_limit_max_requests', 100);
        $this->windowSeconds = $config->get('rate_limit_window_seconds', 3600);
        $this->exemptPaths = $config->get('rate_limit_exempt_paths', [
            '/api/stripe/webhook',
            '/api/health',
            '/api/status'
        ]);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();
// Skip rate limiting for exempt paths
        foreach ($this->exemptPaths as $exemptPath) {
            if (str_starts_with($path, $exemptPath)) {
                return $handler->handle($request);
            }
        }

        // Only apply rate limiting to API endpoints
        if (!str_starts_with($path, '/api/')) {
            return $handler->handle($request);
        }

        $clientIp = $this->getClientIp($request);
        $now = time();
// Clean old requests outside the window
        $this->cleanOldRequests($clientIp, $now);
// Check if client has exceeded rate limit
        if ($this->isRateLimited($clientIp, $now)) {
            return $this->createRateLimitResponse();
        }

        // Record this request
        $this->recordRequest($clientIp, $now);
// Add rate limit headers to response
        $response = $handler->handle($request);
        return $this->addRateLimitHeaders($response, $clientIp, $now);
    }

    private function getClientIp(ServerRequestInterface $request): string
    {
        $serverParams = $request->getServerParams();
// Check for forwarded IP (useful behind proxies like Cloudflare)
        if (!empty($serverParams['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $serverParams['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        }

        if (!empty($serverParams['HTTP_X_REAL_IP'])) {
            return $serverParams['HTTP_X_REAL_IP'];
        }

        return $serverParams['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    private function cleanOldRequests(string $clientIp, int $now): void
    {
        if (!isset($this->requests[$clientIp])) {
            return;
        }

        $cutoff = $now - $this->windowSeconds;
        $this->requests[$clientIp] = array_filter($this->requests[$clientIp], fn($timestamp) => $timestamp >= $cutoff);
    }

    private function isRateLimited(string $clientIp, int $now): bool
    {
        if (!isset($this->requests[$clientIp])) {
            return false;
        }

        return count($this->requests[$clientIp]) >= $this->maxRequests;
    }

    private function recordRequest(string $clientIp, int $now): void
    {
        if (!isset($this->requests[$clientIp])) {
            $this->requests[$clientIp] = [];
        }

        $this->requests[$clientIp][] = $now;
    }

    private function createRateLimitResponse(): ResponseInterface
    {
        $resetTime = time() + $this->windowSeconds;
        return new Response(429, [
                'Content-Type' => 'application/json',
                'Retry-After' => (string)$this->windowSeconds,
                'X-RateLimit-Limit' => (string)$this->maxRequests,
                'X-RateLimit-Remaining' => '0',
                'X-RateLimit-Reset' => (string)$resetTime
            ], json_encode([
                'error' => 'Rate limit exceeded',
                'message' => "Maximum {$this->maxRequests} requests per {$this->windowSeconds} seconds allowed",
                'retry_after' => $this->windowSeconds,
                'reset_time' => $resetTime
            ]));
    }

    private function addRateLimitHeaders(ResponseInterface $response, string $clientIp, int $now): ResponseInterface
    {
        $requestCount = isset($this->requests[$clientIp]) ? count($this->requests[$clientIp]) : 0;
        $remaining = max(0, $this->maxRequests - $requestCount);
        $resetTime = $now + $this->windowSeconds;
        return $response
            ->withHeader('X-RateLimit-Limit', (string)$this->maxRequests)
            ->withHeader('X-RateLimit-Remaining', (string)$remaining)
            ->withHeader('X-RateLimit-Reset', (string)$resetTime);
    }
}
