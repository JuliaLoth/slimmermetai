<?php

namespace Tests\Integration;

use App\Http\Middleware\MiddlewareDispatcher;
use App\Http\Middleware\ErrorHandlingMiddleware;
use App\Http\Middleware\CorsMiddleware;
use App\Http\Middleware\BodyParsingMiddleware;
use App\Http\Middleware\CsrfMiddleware;
use App\Http\Middleware\AuthenticationMiddleware;
use App\Http\Middleware\RateLimitMiddleware;
use App\Http\Middleware\FinalHandler;
use App\Application\Service\AuthService;
use App\Infrastructure\Security\CsrfProtection;
use App\Domain\Logging\ErrorLoggerInterface;
use App\Infrastructure\Config\Config;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tests\Integration\BaseIntegrationTest;
use Exception;

/**
 * MiddlewareStackIntegrationTest
 *
 * Comprehensive tests for the complete PSR-15 middleware stack integration.
 * Tests middleware execution order, request/response flow, and real-world scenarios.
 */
class MiddlewareStackIntegrationTest extends BaseIntegrationTest
{
    private ErrorLoggerInterface $logger;
    private AuthService $authService;
    private CsrfProtection $csrfProtection;
    private Config $config;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->logger = $this->createMock(ErrorLoggerInterface::class);
        $this->authService = $this->createMock(AuthService::class);
        $this->csrfProtection = $this->createMock(CsrfProtection::class);
        $this->config = $this->createMockConfigWithValues([
            'rate_limit_max_requests' => 10,
            'rate_limit_window_seconds' => 60,
            'cors_allowed_origins' => ['https://example.com'],
            'cors_allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE'],
            'cors_allowed_headers' => ['Content-Type', 'Authorization'],
        ]);
    }

    public function testCompleteMiddlewareStackForSuccessfulApiRequest()
    {
        // Create middleware stack in typical order
        $middlewareStack = [
            new ErrorHandlingMiddleware($this->logger, false),
            new CorsMiddleware($this->config),
            new BodyParsingMiddleware(),
            new RateLimitMiddleware($this->config),
        ];
        
        // Create a final handler that returns a successful response
        $finalHandler = new class implements RequestHandlerInterface {
            public function handle(\Psr\Http\Message\ServerRequestInterface $request): ResponseInterface
            {
                $parsedBody = $request->getParsedBody();
                return new Response(200, ['Content-Type' => 'application/json'], 
                    json_encode(['success' => true, 'data' => $parsedBody]));
            }
        };
        
        $dispatcher = new MiddlewareDispatcher($middlewareStack, $finalHandler);
        
        $requestBody = ['name' => 'Test User', 'email' => 'test@example.com'];
        $request = new ServerRequest('POST', '/api/users', [
            'Content-Type' => 'application/json',
            'Origin' => 'https://example.com'
        ], json_encode($requestBody));
        $request = $request->withServerParams(['REMOTE_ADDR' => '127.0.0.1']);

        $response = $dispatcher->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));
        
        // Check CORS headers are applied
        $this->assertNotEmpty($response->getHeaderLine('Access-Control-Allow-Origin'));
        
        // Check rate limit headers are applied
        $this->assertNotEmpty($response->getHeaderLine('X-RateLimit-Limit'));
        $this->assertNotEmpty($response->getHeaderLine('X-RateLimit-Remaining'));
        
        // Verify body parsing worked
        $responseBody = json_decode($response->getBody()->getContents(), true);
        $this->assertTrue($responseBody['success']);
        $this->assertEquals($requestBody, $responseBody['data']);
    }

    public function testMiddlewareStackWithExceptionHandling()
    {
        $middlewareStack = [
            new ErrorHandlingMiddleware($this->logger, true), // Show errors for testing
            new BodyParsingMiddleware(),
        ];
        
        // Create a final handler that throws an exception
        $finalHandler = new class implements RequestHandlerInterface {
            public function handle(\Psr\Http\Message\ServerRequestInterface $request): ResponseInterface
            {
                throw new Exception('Database connection failed');
            }
        };
        
        $dispatcher = new MiddlewareDispatcher($middlewareStack, $finalHandler);
        
        $request = new ServerRequest('POST', '/api/test', ['Content-Type' => 'application/json'], '{"test": true}');

        $this->logger
            ->expects($this->once())
            ->method('logError')
            ->with('Unhandled exception', $this->anything());

        $response = $dispatcher->handle($request);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));
        
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertTrue($body['error']);
        $this->assertEquals('Database connection failed', $body['message']);
    }

    public function testRateLimitingInMiddlewareStack()
    {
        $config = $this->createMockConfigWithValues([
            'rate_limit_max_requests' => 2,
            'rate_limit_window_seconds' => 60,
        ]);
        
        $middlewareStack = [
            new RateLimitMiddleware($config),
        ];
        
        $finalHandler = new class implements RequestHandlerInterface {
            public function handle(\Psr\Http\Message\ServerRequestInterface $request): ResponseInterface
            {
                return new Response(200, [], 'Success');
            }
        };
        
        $dispatcher = new MiddlewareDispatcher($middlewareStack, $finalHandler);
        $clientIp = '192.168.1.100';
        
        // First two requests should succeed
        for ($i = 1; $i <= 2; $i++) {
            $request = new ServerRequest('GET', '/api/data', [], null, '1.1', ['REMOTE_ADDR' => $clientIp]);
            $response = $dispatcher->handle($request);
            $this->assertEquals(200, $response->getStatusCode(), "Request $i should succeed");
        }
        
        // Third request should be rate limited
        $request = new ServerRequest('GET', '/api/data', [], null, '1.1', ['REMOTE_ADDR' => $clientIp]);
        $response = $dispatcher->handle($request);
        $this->assertEquals(429, $response->getStatusCode());
        
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('Rate limit exceeded', $body['error']);
    }

    public function testJsonBodyParsingInStack()
    {
        $middlewareStack = [
            new BodyParsingMiddleware(),
        ];
        
        $finalHandler = new class implements RequestHandlerInterface {
            public function handle(\Psr\Http\Message\ServerRequestInterface $request): ResponseInterface
            {
                $data = $request->getParsedBody();
                return new Response(200, ['Content-Type' => 'application/json'], 
                    json_encode(['received' => $data]));
            }
        };
        
        $dispatcher = new MiddlewareDispatcher($middlewareStack, $finalHandler);
        
        $requestData = ['username' => 'testuser', 'password' => 'secret123'];
        $request = new ServerRequest('POST', '/api/login', ['Content-Type' => 'application/json'], 
            json_encode($requestData));

        $response = $dispatcher->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals($requestData, $body['received']);
    }

    public function testInvalidJsonHandlingInStack()
    {
        $middlewareStack = [
            new ErrorHandlingMiddleware($this->logger, false),
            new BodyParsingMiddleware(),
        ];
        
        $finalHandler = new class implements RequestHandlerInterface {
            public function handle(\Psr\Http\Message\ServerRequestInterface $request): ResponseInterface
            {
                return new Response(200, [], 'Should not reach here');
            }
        };
        
        $dispatcher = new MiddlewareDispatcher($middlewareStack, $finalHandler);
        
        $invalidJson = '{"name": "test", "invalid": }';
        $request = new ServerRequest('POST', '/api/data', ['Content-Type' => 'application/json'], $invalidJson);

        $response = $dispatcher->handle($request);

        // BodyParsingMiddleware should return 400 for invalid JSON
        $this->assertEquals(400, $response->getStatusCode());
        
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('Invalid JSON', $body['error']);
    }

    public function testCorsMiddlewareInStack()
    {
        $config = $this->createMockConfigWithValues([
            'cors_allowed_origins' => ['https://trusted-site.com'],
            'cors_allowed_methods' => ['GET', 'POST'],
            'cors_allowed_headers' => ['Content-Type', 'Authorization'],
            'cors_max_age' => 3600,
        ]);
        
        $middlewareStack = [
            new CorsMiddleware($config),
        ];
        
        $finalHandler = new class implements RequestHandlerInterface {
            public function handle(\Psr\Http\Message\ServerRequestInterface $request): ResponseInterface
            {
                return new Response(200, [], 'Success');
            }
        };
        
        $dispatcher = new MiddlewareDispatcher($middlewareStack, $finalHandler);
        
        $request = new ServerRequest('GET', '/api/data', ['Origin' => 'https://trusted-site.com']);

        $response = $dispatcher->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('https://trusted-site.com', $response->getHeaderLine('Access-Control-Allow-Origin'));
        $this->assertEquals('GET, POST', $response->getHeaderLine('Access-Control-Allow-Methods'));
        $this->assertEquals('Content-Type, Authorization', $response->getHeaderLine('Access-Control-Allow-Headers'));
    }

    public function testPreflightRequestInStack()
    {
        $config = $this->createMockConfigWithValues([
            'cors_allowed_origins' => ['https://app.example.com'],
            'cors_allowed_methods' => ['GET', 'POST', 'PUT'],
            'cors_allowed_headers' => ['Content-Type'],
        ]);
        
        $middlewareStack = [
            new CorsMiddleware($config),
        ];
        
        $finalHandler = new class implements RequestHandlerInterface {
            public function handle(\Psr\Http\Message\ServerRequestInterface $request): ResponseInterface
            {
                return new Response(200, [], 'Should not reach for preflight');
            }
        };
        
        $dispatcher = new MiddlewareDispatcher($middlewareStack, $finalHandler);
        
        $request = new ServerRequest('OPTIONS', '/api/users', [
            'Origin' => 'https://app.example.com',
            'Access-Control-Request-Method' => 'POST',
            'Access-Control-Request-Headers' => 'Content-Type'
        ]);

        $response = $dispatcher->handle($request);

        $this->assertEquals(204, $response->getStatusCode()); // Preflight should return 204
        $this->assertEquals('https://app.example.com', $response->getHeaderLine('Access-Control-Allow-Origin'));
        $this->assertEquals('GET, POST, PUT', $response->getHeaderLine('Access-Control-Allow-Methods'));
    }

    public function testComplexMiddlewareInteraction()
    {
        $config = $this->createMockConfigWithValues([
            'rate_limit_max_requests' => 5,
            'rate_limit_window_seconds' => 60,
            'cors_allowed_origins' => ['*'],
        ]);
        
        $middlewareStack = [
            new ErrorHandlingMiddleware($this->logger, false),
            new CorsMiddleware($config),
            new BodyParsingMiddleware(),
            new RateLimitMiddleware($config),
        ];
        
        $finalHandler = new class implements RequestHandlerInterface {
            public function handle(\Psr\Http\Message\ServerRequestInterface $request): ResponseInterface
            {
                $body = $request->getParsedBody();
                if (!isset($body['action'])) {
                    throw new Exception('Missing action parameter');
                }
                
                return new Response(200, ['Content-Type' => 'application/json'], 
                    json_encode(['action' => $body['action'], 'status' => 'completed']));
            }
        };
        
        $dispatcher = new MiddlewareDispatcher($middlewareStack, $finalHandler);
        
        // Test successful request with all middleware
        $requestData = ['action' => 'create_user', 'name' => 'John Doe'];
        $request = new ServerRequest('POST', '/api/actions', [
            'Content-Type' => 'application/json',
            'Origin' => 'https://client.com'
        ], json_encode($requestData));
        $request = $request->withServerParams(['REMOTE_ADDR' => '10.0.0.1']);

        $response = $dispatcher->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        
        // Verify all middleware added their headers/processing
        $this->assertNotEmpty($response->getHeaderLine('Access-Control-Allow-Origin')); // CORS
        $this->assertNotEmpty($response->getHeaderLine('X-RateLimit-Limit')); // Rate limiting
        
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('create_user', $body['action']); // Body parsing worked
        $this->assertEquals('completed', $body['status']);
    }

    public function testMiddlewareOrderMatters()
    {
        // Test that ErrorHandlingMiddleware catches exceptions from other middleware
        $middlewareStack = [
            new ErrorHandlingMiddleware($this->logger, true),
            // Simulate middleware that throws an exception
            new class implements \Psr\Http\Server\MiddlewareInterface {
                public function process(\Psr\Http\Message\ServerRequestInterface $request, \Psr\Http\Server\RequestHandlerInterface $handler): ResponseInterface
                {
                    throw new Exception('Middleware exception');
                }
            }
        ];
        
        $finalHandler = new class implements RequestHandlerInterface {
            public function handle(\Psr\Http\Message\ServerRequestInterface $request): ResponseInterface
            {
                return new Response(200, [], 'Should not reach here');
            }
        };
        
        $dispatcher = new MiddlewareDispatcher($middlewareStack, $finalHandler);
        
        $request = new ServerRequest('GET', '/api/test');

        $this->logger
            ->expects($this->once())
            ->method('logError');

        $response = $dispatcher->handle($request);

        // ErrorHandlingMiddleware should catch the exception and return 500
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));
        
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('Middleware exception', $body['message']);
    }

    public function testEmptyMiddlewareStack()
    {
        $finalHandler = new class implements RequestHandlerInterface {
            public function handle(\Psr\Http\Message\ServerRequestInterface $request): ResponseInterface
            {
                return new Response(200, [], 'Direct to handler');
            }
        };
        
        $dispatcher = new MiddlewareDispatcher([], $finalHandler);
        
        $request = new ServerRequest('GET', '/test');
        $response = $dispatcher->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Direct to handler', $response->getBody()->getContents());
    }

    public function testFinalHandlerUsage()
    {
        $finalHandler = new FinalHandler();
        $dispatcher = new MiddlewareDispatcher([], $finalHandler);
        
        $request = new ServerRequest('GET', '/unknown');
        $response = $dispatcher->handle($request);

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));
        
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertFalse($body['success']);
        $this->assertStringContainsString('Not Found', $body['message']);
    }

    public function testMiddlewareStackPerformance()
    {
        // Test with many middleware layers
        $middlewareStack = [
            new ErrorHandlingMiddleware($this->logger, false),
            new CorsMiddleware($this->config),
            new BodyParsingMiddleware(),
            new RateLimitMiddleware($this->config),
        ];
        
        $finalHandler = new class implements RequestHandlerInterface {
            public function handle(\Psr\Http\Message\ServerRequestInterface $request): ResponseInterface
            {
                return new Response(200, [], 'Success');
            }
        };
        
        $dispatcher = new MiddlewareDispatcher($middlewareStack, $finalHandler);
        
        $request = new ServerRequest('GET', '/api/performance-test');
        $request = $request->withServerParams(['REMOTE_ADDR' => '127.0.0.1']);
        
        $startTime = microtime(true);
        $response = $dispatcher->handle($request);
        $endTime = microtime(true);
        
        $this->assertEquals(200, $response->getStatusCode());
        
        // Middleware stack should process quickly (under 100ms for this simple test)
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        $this->assertLessThan(100, $executionTime, "Middleware stack took too long: {$executionTime}ms");
    }
} 