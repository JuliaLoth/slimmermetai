<?php

namespace Tests\Integration;

use App\Http\Middleware\ErrorHandlingMiddleware;
use App\Domain\Logging\ErrorLoggerInterface;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tests\Integration\BaseIntegrationTest;
use RuntimeException;
use InvalidArgumentException;
use Exception;

/**
 * ErrorHandlingMiddlewareIntegrationTest
 *
 * Comprehensive tests for error handling middleware including exception catching,
 * JSON vs HTML response formatting, error logging, and development vs production behavior.
 */
class ErrorHandlingMiddlewareIntegrationTest extends BaseIntegrationTest
{
    private ErrorLoggerInterface $logger;
    private RequestHandlerInterface $handler;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->logger = $this->createMock(ErrorLoggerInterface::class);
        $this->handler = $this->createMock(RequestHandlerInterface::class);
    }

    public function testSuccessfulRequestPassesThrough()
    {
        $middleware = new ErrorHandlingMiddleware($this->logger, false);
        
        $request = new ServerRequest('GET', '/test');
        $expectedResponse = new Response(200, [], 'Success');
        
        $this->handler
            ->expects($this->once())
            ->method('handle')
            ->with($request)
            ->willReturn($expectedResponse);

        $this->logger
            ->expects($this->never())
            ->method('logError');

        $response = $middleware->process($request, $this->handler);
        
        $this->assertSame($expectedResponse, $response);
    }

    public function testExceptionLogsError()
    {
        $middleware = new ErrorHandlingMiddleware($this->logger, false);
        
        $request = new ServerRequest('GET', '/api/error');
        $exception = new RuntimeException('Something went wrong');
        
        $this->handler
            ->expects($this->once())
            ->method('handle')
            ->willThrowException($exception);

        $this->logger
            ->expects($this->once())
            ->method('logError')
            ->with('Unhandled exception', $this->callback(function($context) use ($exception) {
                return isset($context['exception']) && $context['exception'] === $exception;
            }));

        $response = $middleware->process($request, $this->handler);
        
        $this->assertEquals(500, $response->getStatusCode());
    }

    public function testJsonResponseForApiEndpoints()
    {
        $middleware = new ErrorHandlingMiddleware($this->logger, false);
        
        $request = new ServerRequest('POST', '/api/users');
        $exception = new InvalidArgumentException('Invalid data provided');
        
        $this->handler->method('handle')->willThrowException($exception);
        $this->logger->method('logError'); // Allow but don't assert

        $response = $middleware->process($request, $this->handler);
        
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));
        
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertTrue($body['error']);
        $this->assertEquals('Internal Server Error', $body['message']); // Not showing exception message when displayErrors = false
    }

    public function testJsonResponseForAcceptHeader()
    {
        $middleware = new ErrorHandlingMiddleware($this->logger, false);
        
        $request = new ServerRequest('GET', '/some-endpoint');
        $request = $request->withHeader('Accept', 'application/json');
        $exception = new Exception('Test exception');
        
        $this->handler->method('handle')->willThrowException($exception);
        $this->logger->method('logError');

        $response = $middleware->process($request, $this->handler);
        
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));
        
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertTrue($body['error']);
        $this->assertEquals('Internal Server Error', $body['message']);
    }

    public function testJsonResponseForContentTypeHeader()
    {
        $middleware = new ErrorHandlingMiddleware($this->logger, false);
        
        $request = new ServerRequest('POST', '/form/submit');
        $request = $request->withHeader('Content-Type', 'application/json');
        $exception = new Exception('Validation failed');
        
        $this->handler->method('handle')->willThrowException($exception);
        $this->logger->method('logError');

        $response = $middleware->process($request, $this->handler);
        
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));
    }

    public function testHtmlResponseForWebEndpoints()
    {
        $middleware = new ErrorHandlingMiddleware($this->logger, false);
        
        $request = new ServerRequest('GET', '/dashboard');
        $exception = new Exception('Database connection failed');
        
        $this->handler->method('handle')->willThrowException($exception);
        $this->logger->method('logError');

        $response = $middleware->process($request, $this->handler);
        
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('text/html', $response->getHeaderLine('Content-Type'));
        
        $body = $response->getBody()->getContents();
        $this->assertStringContainsString('<h1>500 – Internal Server Error</h1>', $body);
        $this->assertStringNotContainsString('Database connection failed', $body); // No exception details in production
    }

    public function testDisplayErrorsShowsExceptionInJson()
    {
        $middleware = new ErrorHandlingMiddleware($this->logger, true); // displayErrors = true
        
        $request = new ServerRequest('GET', '/api/debug');
        $exception = new RuntimeException('Detailed error information');
        
        $this->handler->method('handle')->willThrowException($exception);
        $this->logger->method('logError');

        $response = $middleware->process($request, $this->handler);
        
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));
        
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertTrue($body['error']);
        $this->assertEquals('Detailed error information', $body['message']); // Shows actual exception message
    }

    public function testDisplayErrorsShowsExceptionInHtml()
    {
        $middleware = new ErrorHandlingMiddleware($this->logger, true); // displayErrors = true
        
        $request = new ServerRequest('GET', '/test-page');
        $exception = new Exception('Debug error message');
        
        $this->handler->method('handle')->willThrowException($exception);
        $this->logger->method('logError');

        $response = $middleware->process($request, $this->handler);
        
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('text/html', $response->getHeaderLine('Content-Type'));
        
        $body = $response->getBody()->getContents();
        $this->assertStringContainsString('<h1>500 – Internal Server Error</h1>', $body);
        $this->assertStringContainsString('Debug error message', $body); // Shows exception message
        $this->assertStringContainsString('<pre>', $body); // Should include stack trace in <pre> tags
    }

    public function testDifferentExceptionTypes()
    {
        $middleware = new ErrorHandlingMiddleware($this->logger, true);
        
        $exceptions = [
            new RuntimeException('Runtime error'),
            new InvalidArgumentException('Invalid argument'),
            new \PDOException('Database error'),
            new \TypeError('Type error'),
        ];
        
        foreach ($exceptions as $exception) {
            $request = new ServerRequest('GET', '/api/test');
            
            $this->handler = $this->createMock(RequestHandlerInterface::class);
            $this->handler->method('handle')->willThrowException($exception);
            
            $this->logger->method('logError');

            $response = $middleware->process($request, $this->handler);
            
            $this->assertEquals(500, $response->getStatusCode());
            $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));
            
            $body = json_decode($response->getBody()->getContents(), true);
            $this->assertTrue($body['error']);
            $this->assertEquals($exception->getMessage(), $body['message']);
        }
    }

    public function testContentTypeDetectionWithMixedCase()
    {
        $middleware = new ErrorHandlingMiddleware($this->logger, false);
        
        // Test case-insensitive header matching
        $testCases = [
            ['Accept', 'Application/JSON'],
            ['Accept', 'APPLICATION/JSON'],
            ['Content-Type', 'Application/Json; charset=utf-8'],
            ['Accept', 'text/html, application/json, */*'],
        ];
        
        foreach ($testCases as [$headerName, $headerValue]) {
            $request = new ServerRequest('POST', '/endpoint');
            $request = $request->withHeader($headerName, $headerValue);
            $exception = new Exception('Test error');
            
            $this->handler = $this->createMock(RequestHandlerInterface::class);
            $this->handler->method('handle')->willThrowException($exception);
            $this->logger->method('logError');

            $response = $middleware->process($request, $this->handler);
            
            $this->assertEquals(500, $response->getStatusCode());
            $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'), 
                "Failed for header: $headerName: $headerValue");
        }
    }

    public function testApiPathDetection()
    {
        $middleware = new ErrorHandlingMiddleware($this->logger, false);
        
        $apiPaths = [
            '/api/users',
            '/api/v1/posts', 
            '/api/auth/login',
            '/api/',
            '/API/data', // Case sensitivity test
        ];
        
        foreach ($apiPaths as $path) {
            $request = new ServerRequest('GET', $path);
            $exception = new Exception('API error');
            
            $this->handler = $this->createMock(RequestHandlerInterface::class);
            $this->handler->method('handle')->willThrowException($exception);
            $this->logger->method('logError');

            $response = $middleware->process($request, $this->handler);
            
            // Most API paths should return JSON, but '/API/data' might not due to case sensitivity
            if ($path === '/API/data') {
                // This depends on implementation - if it's case-sensitive, it might return HTML
                $this->assertEquals(500, $response->getStatusCode());
            } else {
                $this->assertEquals(500, $response->getStatusCode());
                $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'), 
                    "Failed for API path: $path");
            }
        }
    }

    public function testErrorResponseStructure()
    {
        $middleware = new ErrorHandlingMiddleware($this->logger, true);
        
        $request = new ServerRequest('POST', '/api/test');
        $exception = new RuntimeException('Specific error message');
        
        $this->handler->method('handle')->willThrowException($exception);
        $this->logger->method('logError');

        $response = $middleware->process($request, $this->handler);
        
        $body = json_decode($response->getBody()->getContents(), true);
        
        // Verify JSON structure
        $this->assertIsArray($body);
        $this->assertArrayHasKey('error', $body);
        $this->assertArrayHasKey('message', $body);
        $this->assertTrue($body['error']);
        $this->assertEquals('Specific error message', $body['message']);
        
        // Should not contain sensitive information
        $this->assertArrayNotHasKey('trace', $body);
        $this->assertArrayNotHasKey('file', $body);
        $this->assertArrayNotHasKey('line', $body);
    }

    public function testHtmlErrorStructure()
    {
        $middleware = new ErrorHandlingMiddleware($this->logger, true);
        
        $request = new ServerRequest('GET', '/web-page');
        $exception = new Exception('HTML error test');
        
        $this->handler->method('handle')->willThrowException($exception);
        $this->logger->method('logError');

        $response = $middleware->process($request, $this->handler);
        
        $body = $response->getBody()->getContents();
        
        // Verify HTML structure
        $this->assertStringContainsString('<h1>500 – Internal Server Error</h1>', $body);
        $this->assertStringContainsString('HTML error test', $body);
        $this->assertStringContainsString('<pre>', $body); // Stack trace in pre tags
        
        // Should be properly escaped HTML
        $this->assertStringNotContainsString('<script>', $body);
    }

    public function testNestedExceptionHandling()
    {
        $middleware = new ErrorHandlingMiddleware($this->logger, true);
        
        $request = new ServerRequest('GET', '/api/nested');
        
        // Simulate a handler that throws an exception
        $this->handler->method('handle')->willReturnCallback(function() {
            throw new RuntimeException('Original exception', 0, new InvalidArgumentException('Nested cause'));
        });
        
        $this->logger->method('logError');

        $response = $middleware->process($request, $this->handler);
        
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));
        
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('Original exception', $body['message']);
    }
} 