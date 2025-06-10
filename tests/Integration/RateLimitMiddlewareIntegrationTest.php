<?php

namespace Tests\Integration;

use App\Http\Middleware\RateLimitMiddleware;
use App\Infrastructure\Config\Config;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tests\Integration\BaseIntegrationTest;

/**
 * RateLimitMiddlewareIntegrationTest
 *
 * Comprehensive tests for rate limiting middleware including sliding window algorithm,
 * IP extraction from headers, exempt paths, and rate limit response headers.
 */
class RateLimitMiddlewareIntegrationTest extends BaseIntegrationTest
{
    private RequestHandlerInterface $handler;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->handler = $this->createMock(RequestHandlerInterface::class);
        $this->handler->method('handle')->willReturn(new Response(200, [], 'Success'));
    }

    public function testRequestUnderLimitPassesThrough()
    {
        $config = $this->createMockConfigWithValues([
            'rate_limit_max_requests' => 5,
            'rate_limit_window_seconds' => 60,
            'rate_limit_exempt_paths' => []
        ]);
        
        $middleware = new RateLimitMiddleware($config);
        
        $request = new ServerRequest('GET', '/api/users', [], null, '1.1', ['REMOTE_ADDR' => '127.0.0.1']);

        $this->handler
            ->expects($this->once())
            ->method('handle')
            ->willReturn(new Response(200));

        $response = $middleware->process($request, $this->handler);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('5', $response->getHeaderLine('X-RateLimit-Limit'));
        $this->assertEquals('4', $response->getHeaderLine('X-RateLimit-Remaining')); // 5 - 1 = 4
        $this->assertNotEmpty($response->getHeaderLine('X-RateLimit-Reset'));
    }

    public function testRateLimitExceededReturns429()
    {
        $config = $this->createMockConfigWithValues([
            'rate_limit_max_requests' => 2,
            'rate_limit_window_seconds' => 60,
            'rate_limit_exempt_paths' => []
        ]);
        
        $middleware = new RateLimitMiddleware($config);
        $clientIp = '192.168.1.100';
        
        // Make 2 requests to reach limit
        for ($i = 1; $i <= 2; $i++) {
            $request = new ServerRequest('POST', '/api/data', [], null, '1.1', ['REMOTE_ADDR' => $clientIp]);
            $response = $middleware->process($request, $this->handler);
            $this->assertEquals(200, $response->getStatusCode(), "Request $i should succeed");
        }
        
        // 3rd request should be rate limited
        $request = new ServerRequest('POST', '/api/data', [], null, '1.1', ['REMOTE_ADDR' => $clientIp]);
        $response = $middleware->process($request, $this->handler);
        
        $this->assertEquals(429, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertEquals('60', $response->getHeaderLine('Retry-After'));
        $this->assertEquals('2', $response->getHeaderLine('X-RateLimit-Limit'));
        $this->assertEquals('0', $response->getHeaderLine('X-RateLimit-Remaining'));
        
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('Rate limit exceeded', $body['error']);
        $this->assertStringContainsString('Maximum 2 requests per 60 seconds', $body['message']);
        $this->assertEquals(60, $body['retry_after']);
        $this->assertArrayHasKey('reset_time', $body);
    }

    public function testExemptPathsBypassRateLimit()
    {
        $config = $this->createMockConfigWithValues([
            'rate_limit_max_requests' => 1,
            'rate_limit_window_seconds' => 60,
            'rate_limit_exempt_paths' => ['/api/stripe/webhook', '/api/health']
        ]);
        
        $middleware = new RateLimitMiddleware($config);
        $clientIp = '10.0.0.1';
        
        // Exhaust rate limit on regular endpoint
        $request = new ServerRequest('GET', '/api/users', [], null, '1.1', ['REMOTE_ADDR' => $clientIp]);
        $response = $middleware->process($request, $this->handler);
        $this->assertEquals(200, $response->getStatusCode());
        
        // Regular endpoint should now be rate limited
        $request = new ServerRequest('GET', '/api/data', [], null, '1.1', ['REMOTE_ADDR' => $clientIp]);
        $response = $middleware->process($request, $this->handler);
        $this->assertEquals(429, $response->getStatusCode());
        
        // But exempt paths should still work
        $exemptPaths = ['/api/stripe/webhook', '/api/health'];
        foreach ($exemptPaths as $path) {
            $request = new ServerRequest('POST', $path, [], null, '1.1', ['REMOTE_ADDR' => $clientIp]);
            $response = $middleware->process($request, $this->handler);
            $this->assertEquals(200, $response->getStatusCode(), "Exempt path $path should bypass rate limit");
        }
    }

    public function testNonApiPathsBypassRateLimit()
    {
        $config = $this->createMockConfigWithValues([
            'rate_limit_max_requests' => 1,
            'rate_limit_window_seconds' => 60,
            'rate_limit_exempt_paths' => []
        ]);
        
        $middleware = new RateLimitMiddleware($config);
        $clientIp = '172.16.0.1';
        
        // Exhaust rate limit on API endpoint
        $request = new ServerRequest('GET', '/api/users', [], null, '1.1', ['REMOTE_ADDR' => $clientIp]);
        $response = $middleware->process($request, $this->handler);
        $this->assertEquals(200, $response->getStatusCode());
        
        // API endpoint should now be rate limited
        $request = new ServerRequest('GET', '/api/data', [], null, '1.1', ['REMOTE_ADDR' => $clientIp]);
        $response = $middleware->process($request, $this->handler);
        $this->assertEquals(429, $response->getStatusCode());
        
        // But non-API paths should still work
        $nonApiPaths = ['/', '/login', '/about', '/contact'];
        foreach ($nonApiPaths as $path) {
            $request = new ServerRequest('GET', $path, [], null, '1.1', ['REMOTE_ADDR' => $clientIp]);
            $response = $middleware->process($request, $this->handler);
            $this->assertEquals(200, $response->getStatusCode(), "Non-API path $path should bypass rate limit");
        }
    }

    public function testXForwardedForIpExtraction()
    {
        $config = $this->createMockConfigWithValues([
            'rate_limit_max_requests' => 2,
            'rate_limit_window_seconds' => 60,
            'rate_limit_exempt_paths' => []
        ]);
        
        $middleware = new RateLimitMiddleware($config);
        
        // Test single forwarded IP
        $request = new ServerRequest('GET', '/api/test', [], null, '1.1', [
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_X_FORWARDED_FOR' => '203.0.113.1'
        ]);
        
        $response = $middleware->process($request, $this->handler);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('1', $response->getHeaderLine('X-RateLimit-Remaining'));
        
        // Test multiple forwarded IPs (should use first one)
        $request = new ServerRequest('GET', '/api/test', [], null, '1.1', [
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_X_FORWARDED_FOR' => '203.0.113.1, 198.51.100.1, 127.0.0.1'
        ]);
        
        $response = $middleware->process($request, $this->handler);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('0', $response->getHeaderLine('X-RateLimit-Remaining')); // Same IP, so limit decreases
    }

    public function testXRealIpHeaderPriority()
    {
        $config = $this->createMockConfigWithValues([
            'rate_limit_max_requests' => 1,
            'rate_limit_window_seconds' => 60,
            'rate_limit_exempt_paths' => []
        ]);
        
        $middleware = new RateLimitMiddleware($config);
        
        $request = new ServerRequest('GET', '/api/test', [], null, '1.1', [
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_X_REAL_IP' => '198.51.100.1'
        ]);
        
        $response = $middleware->process($request, $this->handler);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('0', $response->getHeaderLine('X-RateLimit-Remaining'));
        
        // Same real IP should be rate limited
        $request = new ServerRequest('GET', '/api/test2', [], null, '1.1', [
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_X_REAL_IP' => '198.51.100.1'
        ]);
        
        $response = $middleware->process($request, $this->handler);
        $this->assertEquals(429, $response->getStatusCode());
    }

    public function testDifferentIpsHaveSeparateLimits()
    {
        $config = $this->createMockConfigWithValues([
            'rate_limit_max_requests' => 1,
            'rate_limit_window_seconds' => 60,
            'rate_limit_exempt_paths' => []
        ]);
        
        $middleware = new RateLimitMiddleware($config);
        
        // First IP exhausts its limit
        $request1 = new ServerRequest('GET', '/api/test', [], null, '1.1', ['REMOTE_ADDR' => '10.0.0.1']);
        $response1 = $middleware->process($request1, $this->handler);
        $this->assertEquals(200, $response1->getStatusCode());
        
        $request1_2 = new ServerRequest('GET', '/api/test', [], null, '1.1', ['REMOTE_ADDR' => '10.0.0.1']);
        $response1_2 = $middleware->process($request1_2, $this->handler);
        $this->assertEquals(429, $response1_2->getStatusCode());
        
        // Second IP should still have its limit available
        $request2 = new ServerRequest('GET', '/api/test', [], null, '1.1', ['REMOTE_ADDR' => '10.0.0.2']);
        $response2 = $middleware->process($request2, $this->handler);
        $this->assertEquals(200, $response2->getStatusCode());
        $this->assertEquals('0', $response2->getHeaderLine('X-RateLimit-Remaining'));
    }

    public function testSlidingWindowBehavior()
    {
        $config = $this->createMockConfigWithValues([
            'rate_limit_max_requests' => 3,
            'rate_limit_window_seconds' => 2, // Short window for testing
            'rate_limit_exempt_paths' => []
        ]);
        
        $middleware = new RateLimitMiddleware($config);
        $clientIp = '192.168.1.50';
        
        // Make 3 requests to reach limit
        for ($i = 1; $i <= 3; $i++) {
            $request = new ServerRequest('GET', '/api/test', [], null, '1.1', ['REMOTE_ADDR' => $clientIp]);
            $response = $middleware->process($request, $this->handler);
            $this->assertEquals(200, $response->getStatusCode(), "Request $i should succeed");
        }
        
        // 4th request should be rate limited
        $request = new ServerRequest('GET', '/api/test', [], null, '1.1', ['REMOTE_ADDR' => $clientIp]);
        $response = $middleware->process($request, $this->handler);
        $this->assertEquals(429, $response->getStatusCode());
        
        // Wait for window to slide (3 seconds to be safe)
        sleep(3);
        
        // Should be able to make requests again
        $request = new ServerRequest('GET', '/api/test', [], null, '1.1', ['REMOTE_ADDR' => $clientIp]);
        $response = $middleware->process($request, $this->handler);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('2', $response->getHeaderLine('X-RateLimit-Remaining'));
    }

    public function testRateLimitHeadersAreCorrect()
    {
        $maxRequests = 10;
        $windowSeconds = 300;
        
        $config = $this->createMockConfigWithValues([
            'rate_limit_max_requests' => $maxRequests,
            'rate_limit_window_seconds' => $windowSeconds,
            'rate_limit_exempt_paths' => []
        ]);
        
        $middleware = new RateLimitMiddleware($config);
        $clientIp = '203.0.113.195';
        
        // Make first request
        $request = new ServerRequest('GET', '/api/test', [], null, '1.1', ['REMOTE_ADDR' => $clientIp]);
        $response = $middleware->process($request, $this->handler);
        
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals((string)$maxRequests, $response->getHeaderLine('X-RateLimit-Limit'));
        $this->assertEquals((string)($maxRequests - 1), $response->getHeaderLine('X-RateLimit-Remaining'));
        
        $resetTime = (int)$response->getHeaderLine('X-RateLimit-Reset');
        $currentTime = time();
        $this->assertGreaterThan($currentTime, $resetTime);
        $this->assertLessThanOrEqual($currentTime + $windowSeconds, $resetTime);
    }

    public function testDefaultConfigValues()
    {
        // Test with default config (no custom values)
        $config = $this->createMockConfig();
        $middleware = new RateLimitMiddleware($config);
        
        $request = new ServerRequest('GET', '/api/test', [], null, '1.1', ['REMOTE_ADDR' => '127.0.0.1']);
        $response = $middleware->process($request, $this->handler);
        
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('100', $response->getHeaderLine('X-RateLimit-Limit')); // Default max requests
        $this->assertEquals('99', $response->getHeaderLine('X-RateLimit-Remaining'));
    }

    public function testFallbackIpWhenNoHeaders()
    {
        $config = $this->createMockConfigWithValues([
            'rate_limit_max_requests' => 1,
            'rate_limit_window_seconds' => 60,
            'rate_limit_exempt_paths' => []
        ]);
        
        $middleware = new RateLimitMiddleware($config);
        
        // Request without REMOTE_ADDR should use fallback
        $request = new ServerRequest('GET', '/api/test', [], null, '1.1', []);
        $response = $middleware->process($request, $this->handler);
        
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('0', $response->getHeaderLine('X-RateLimit-Remaining'));
        
        // Second request from same fallback IP should be rate limited
        $request = new ServerRequest('GET', '/api/test', [], null, '1.1', []);
        $response = $middleware->process($request, $this->handler);
        $this->assertEquals(429, $response->getStatusCode());
    }
} 