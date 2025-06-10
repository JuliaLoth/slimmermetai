<?php

namespace Tests\Integration;

use App\Http\Middleware\BodyParsingMiddleware;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tests\Integration\BaseIntegrationTest;

/**
 * BodyParsingMiddlewareIntegrationTest
 *
 * Comprehensive tests for JSON and form-urlencoded body parsing middleware.
 * Tests various content types, malformed data, and HTTP methods.
 */
class BodyParsingMiddlewareIntegrationTest extends BaseIntegrationTest
{
    private BodyParsingMiddleware $middleware;
    private RequestHandlerInterface $handler;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->middleware = new BodyParsingMiddleware();
        $this->handler = $this->createMock(RequestHandlerInterface::class);
    }

    public function testJsonBodyParsingSuccess()
    {
        $jsonData = ['name' => 'Test User', 'email' => 'test@example.com', 'age' => 30];
        
        $request = new ServerRequest('POST', '/api/test');
        $request = $request
            ->withHeader('Content-Type', 'application/json')
            ->withBody(\GuzzleHttp\Psr7\Utils::streamFor(json_encode($jsonData)));

        $expectedResponse = new Response(200, [], 'Success');
        
        $this->handler
            ->expects($this->once())
            ->method('handle')
            ->with($this->callback(function($req) use ($jsonData) {
                return $req->getParsedBody() === $jsonData;
            }))
            ->willReturn($expectedResponse);

        $response = $this->middleware->process($request, $this->handler);
        
        $this->assertSame($expectedResponse, $response);
    }

    public function testJsonBodyParsingWithCharset()
    {
        $jsonData = ['message' => 'Héllo Wörld 🌍'];
        
        $request = new ServerRequest('PUT', '/api/update');
        $request = $request
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withBody(\GuzzleHttp\Psr7\Utils::streamFor(json_encode($jsonData)));

        $this->handler
            ->expects($this->once())
            ->method('handle')
            ->with($this->callback(function($req) use ($jsonData) {
                return $req->getParsedBody() === $jsonData;
            }))
            ->willReturn(new Response(200));

        $response = $this->middleware->process($request, $this->handler);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testInvalidJsonReturnsBadRequest()
    {
        $invalidJson = '{"name": "Test", "incomplete": }';
        
        $request = new ServerRequest('POST', '/api/test');
        $request = $request
            ->withHeader('Content-Type', 'application/json')
            ->withBody(\GuzzleHttp\Psr7\Utils::streamFor($invalidJson));

        $this->handler
            ->expects($this->never())
            ->method('handle');

        $response = $this->middleware->process($request, $this->handler);
        
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));
        
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('Invalid JSON', $body['error']);
    }

    public function testEmptyJsonBodyIsValid()
    {
        $request = new ServerRequest('POST', '/api/test');
        $request = $request
            ->withHeader('Content-Type', 'application/json')
            ->withBody(\GuzzleHttp\Psr7\Utils::streamFor(''));

        $this->handler
            ->expects($this->once())
            ->method('handle')
            ->with($this->callback(function($req) {
                return $req->getParsedBody() === null; // Empty body should not set parsed body
            }))
            ->willReturn(new Response(200));

        $response = $this->middleware->process($request, $this->handler);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testFormUrlencodedBodyParsing()
    {
        $formData = 'name=John+Doe&email=john%40example.com&age=25&active=true';
        $expectedData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => '25',
            'active' => 'true'
        ];
        
        $request = new ServerRequest('POST', '/form/submit');
        $request = $request
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withBody(\GuzzleHttp\Psr7\Utils::streamFor($formData));

        $this->handler
            ->expects($this->once())
            ->method('handle')
            ->with($this->callback(function($req) use ($expectedData) {
                return $req->getParsedBody() === $expectedData;
            }))
            ->willReturn(new Response(200));

        $response = $this->middleware->process($request, $this->handler);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testFormUrlencodedWithCharset()
    {
        $formData = 'message=Hello%20World&type=test';
        $expectedData = ['message' => 'Hello World', 'type' => 'test'];
        
        $request = new ServerRequest('PATCH', '/form/update');
        $request = $request
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded; charset=utf-8')
            ->withBody(\GuzzleHttp\Psr7\Utils::streamFor($formData));

        $this->handler
            ->expects($this->once())
            ->method('handle')
            ->with($this->callback(function($req) use ($expectedData) {
                return $req->getParsedBody() === $expectedData;
            }))
            ->willReturn(new Response(200));

        $response = $this->middleware->process($request, $this->handler);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testGetRequestIgnored()
    {
        $request = new ServerRequest('GET', '/api/users');
        $request = $request
            ->withHeader('Content-Type', 'application/json')
            ->withBody(\GuzzleHttp\Psr7\Utils::streamFor('{"ignored": true}'));

        $this->handler
            ->expects($this->once())
            ->method('handle')
            ->with($this->callback(function($req) {
                return $req->getParsedBody() === null; // GET requests should not be parsed
            }))
            ->willReturn(new Response(200));

        $response = $this->middleware->process($request, $this->handler);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testUnsupportedContentTypeIgnored()
    {
        $request = new ServerRequest('POST', '/api/upload');
        $request = $request
            ->withHeader('Content-Type', 'multipart/form-data; boundary=something')
            ->withBody(\GuzzleHttp\Psr7\Utils::streamFor('--something\r\nContent-Disposition: form-data; name="test"\r\n\r\nvalue\r\n--something--'));

        $this->handler
            ->expects($this->once())
            ->method('handle')
            ->with($this->callback(function($req) {
                return $req->getParsedBody() === null; // Unsupported content type should not be parsed
            }))
            ->willReturn(new Response(200));

        $response = $this->middleware->process($request, $this->handler);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testComplexJsonDataStructures()
    {
        $complexData = [
            'user' => [
                'id' => 123,
                'profile' => [
                    'name' => 'Complex User',
                    'preferences' => ['theme' => 'dark', 'notifications' => true]
                ],
                'roles' => ['admin', 'user'],
                'metadata' => null
            ],
            'timestamp' => '2024-01-01T12:00:00Z',
            'nested' => [
                'level1' => [
                    'level2' => [
                        'level3' => 'deep value'
                    ]
                ]
            ]
        ];
        
        $request = new ServerRequest('DELETE', '/api/complex');
        $request = $request
            ->withHeader('Content-Type', 'application/json')
            ->withBody(\GuzzleHttp\Psr7\Utils::streamFor(json_encode($complexData)));

        $this->handler
            ->expects($this->once())
            ->method('handle')
            ->with($this->callback(function($req) use ($complexData) {
                return $req->getParsedBody() === $complexData;
            }))
            ->willReturn(new Response(200));

        $response = $this->middleware->process($request, $this->handler);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testNullJsonValue()
    {
        $request = new ServerRequest('POST', '/api/null');
        $request = $request
            ->withHeader('Content-Type', 'application/json')
            ->withBody(\GuzzleHttp\Psr7\Utils::streamFor('null'));

        $this->handler
            ->expects($this->once())
            ->method('handle')
            ->with($this->callback(function($req) {
                return $req->getParsedBody() === [];  // null JSON becomes empty array
            }))
            ->willReturn(new Response(200));

        $response = $this->middleware->process($request, $this->handler);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testAllSupportedHttpMethods()
    {
        $methods = ['POST', 'PUT', 'PATCH', 'DELETE'];
        $testData = ['method' => 'test'];
        
        foreach ($methods as $method) {
            $request = new ServerRequest($method, '/api/test');
            $request = $request
                ->withHeader('Content-Type', 'application/json')
                ->withBody(\GuzzleHttp\Psr7\Utils::streamFor(json_encode($testData)));

            $this->handler
                ->expects($this->once())
                ->method('handle')
                ->with($this->callback(function($req) use ($testData) {
                    return $req->getParsedBody() === $testData;
                }))
                ->willReturn(new Response(200));

            $response = $this->middleware->process($request, $this->handler);
            $this->assertEquals(200, $response->getStatusCode(), "Failed for method: $method");
            
            // Reset mock for next iteration
            $this->handler = $this->createMock(RequestHandlerInterface::class);
        }
    }
} 