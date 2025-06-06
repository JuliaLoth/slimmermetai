<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use App\Http\Controller\Api\AuthController;
use App\Application\Service\AuthService;
use App\Application\Service\JwtService;
use App\Infrastructure\Repository\AuthRepositoryInterface;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Response;

class AuthControllerTest extends TestCase
{
    private AuthController $controller;
    private $mockAuthService;
    private $mockJwtService;
    private $mockAuthRepository;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create mocks
        $this->mockAuthService = $this->createMock(AuthService::class);
        $this->mockJwtService = $this->createMock(JwtService::class);
        $this->mockAuthRepository = $this->createMock(AuthRepositoryInterface::class);
        
        // Create controller with mocked dependencies
        $this->controller = new AuthController($this->mockAuthService);
    }

    public function testLoginWithValidCredentials()
    {
        // Mock successful authentication
        $this->mockAuthService
            ->method('login')
            ->with('test@example.com', 'password123')
            ->willReturn([
                'success' => true,
                'user' => [
                    'id' => 1,
                    'email' => 'test@example.com',
                    'name' => 'Test User'
                ]
            ]);

        // Create request with valid credentials
        $request = new ServerRequest('POST', '/api/auth/login');
        $request = $request->withParsedBody([
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        // Execute login
        $response = $this->controller->login($request);

        // Assertions
        $this->assertEquals(200, $response->getStatusCode());
        
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertTrue($body['success']);
        $this->assertEquals('test@example.com', $body['user']['email']);
    }

    public function testLoginWithInvalidCredentials()
    {
        // Mock failed authentication
        $this->mockAuthService
            ->method('login')
            ->with('invalid@example.com', 'wrongpassword')
            ->willReturn([
                'success' => false,
                'error' => 'Invalid credentials'
            ]);

        $request = new ServerRequest('POST', '/api/auth/login');
        $request = $request->withParsedBody([
            'email' => 'invalid@example.com',
            'password' => 'wrongpassword'
        ]);

        $response = $this->controller->login($request);

        $this->assertEquals(401, $response->getStatusCode());
        
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertFalse($body['success']);
        $this->assertEquals('Invalid credentials', $body['error']);
    }

    public function testLoginValidationErrors()
    {
        $request = new ServerRequest('POST', '/api/auth/login');
        $request = $request->withParsedBody([
            'email' => '', // Missing email
            'password' => '' // Missing password
        ]);

        $response = $this->controller->login($request);

        $this->assertEquals(422, $response->getStatusCode());
        
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertFalse($body['success']);
        $this->assertArrayHasKey('errors', $body);
        $this->assertArrayHasKey('email', $body['errors']);
        $this->assertArrayHasKey('password', $body['errors']);
    }

    public function testRegisterWithValidData()
    {
        // Mock successful registration
        $this->mockAuthService
            ->method('register')
            ->willReturn([
                'success' => true,
                'user' => [
                    'id' => 2,
                    'email' => 'newuser@example.com',
                    'name' => 'New User'
                ]
            ]);

        $request = new ServerRequest('POST', '/api/auth/register');
        $request = $request->withParsedBody([
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'confirm_password' => 'password123'
        ]);

        $response = $this->controller->register($request);

        $this->assertEquals(201, $response->getStatusCode());
        
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertTrue($body['success']);
        $this->assertEquals('newuser@example.com', $body['user']['email']);
    }

    public function testRegisterWithPasswordMismatch()
    {
        $request = new ServerRequest('POST', '/api/auth/register');
        $request = $request->withParsedBody([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'confirm_password' => 'different_password'
        ]);

        $response = $this->controller->register($request);

        $this->assertEquals(422, $response->getStatusCode());
        
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertFalse($body['success']);
        $this->assertArrayHasKey('errors', $body);
        $this->assertContains('Passwords do not match', $body['errors']['confirm_password']);
    }

    public function testRegisterWithInvalidEmail()
    {
        $request = new ServerRequest('POST', '/api/auth/register');
        $request = $request->withParsedBody([
            'name' => 'Test User',
            'email' => 'invalid-email',
            'password' => 'password123',
            'confirm_password' => 'password123'
        ]);

        $response = $this->controller->register($request);

        $this->assertEquals(422, $response->getStatusCode());
        
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertFalse($body['success']);
        $this->assertArrayHasKey('errors', $body);
        $this->assertArrayHasKey('email', $body['errors']);
    }

    public function testLogoutWithValidToken()
    {
        // Mock successful logout
        $this->mockAuthService
            ->method('logout')
            ->willReturn(['success' => true]);

        $request = new ServerRequest('POST', '/api/auth/logout');
        $request = $request->withHeader('Authorization', 'Bearer valid-jwt-token');

        $response = $this->controller->logout($request);

        $this->assertEquals(200, $response->getStatusCode());
        
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertTrue($body['success']);
    }

    public function testForgotPasswordWithValidEmail()
    {
        // Mock successful password reset request
        $this->mockAuthService
            ->method('forgotPassword')
            ->with('test@example.com')
            ->willReturn([
                'success' => true,
                'message' => 'Password reset link sent to test@example.com'
            ]);

        $request = new ServerRequest('POST', '/api/auth/forgot-password');
        $request = $request->withParsedBody([
            'email' => 'test@example.com'
        ]);

        $response = $this->controller->forgotPassword($request);

        $this->assertEquals(200, $response->getStatusCode());
        
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertTrue($body['success']);
        $this->assertContains('test@example.com', $body['message']);
    }

    public function testForgotPasswordWithInvalidEmail()
    {
        $request = new ServerRequest('POST', '/api/auth/forgot-password');
        $request = $request->withParsedBody([
            'email' => 'invalid-email'
        ]);

        $response = $this->controller->forgotPassword($request);

        $this->assertEquals(422, $response->getStatusCode());
        
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertFalse($body['success']);
        $this->assertArrayHasKey('errors', $body);
        $this->assertArrayHasKey('email', $body['errors']);
    }

    public function testMeEndpointWithValidToken()
    {
        // Mock user data retrieval
        $this->mockAuthService
            ->method('getCurrentUser')
            ->willReturn([
                'id' => 1,
                'email' => 'test@example.com',
                'name' => 'Test User',
                'created_at' => '2024-01-01 00:00:00'
            ]);

        $request = new ServerRequest('GET', '/api/auth/me');
        $request = $request->withHeader('Authorization', 'Bearer valid-jwt-token');

        $response = $this->controller->me($request);

        $this->assertEquals(200, $response->getStatusCode());
        
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertTrue($body['success']);
        $this->assertArrayHasKey('user', $body);
        $this->assertEquals('test@example.com', $body['user']['email']);
    }

    public function testMeEndpointWithoutToken()
    {
        $request = new ServerRequest('GET', '/api/auth/me');
        // No Authorization header

        $response = $this->controller->me($request);

        $this->assertEquals(401, $response->getStatusCode());
        
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertFalse($body['success']);
        $this->assertEquals('Unauthorized', $body['error']);
    }

    /**
     * Test helper to verify response structure
     */
    private function assertValidJsonResponse($response, int $expectedStatusCode = 200): array
    {
        $this->assertEquals($expectedStatusCode, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));
        
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertIsArray($body);
        $this->assertArrayHasKey('success', $body);
        
        return $body;
    }

    /**
     * Test CORS headers in responses
     */
    public function testCorsHeaders()
    {
        $request = new ServerRequest('POST', '/api/auth/login');
        $request = $request->withParsedBody([
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $this->mockAuthService
            ->method('login')
            ->willReturn(['success' => true, 'user' => []]);

        $response = $this->controller->login($request);

        // Check CORS headers
        $this->assertNotEmpty($response->getHeaderLine('Access-Control-Allow-Origin'));
        $this->assertNotEmpty($response->getHeaderLine('Access-Control-Allow-Methods'));
        $this->assertNotEmpty($response->getHeaderLine('Access-Control-Allow-Headers'));
    }

    /**
     * Test rate limiting headers
     */
    public function testRateLimitingHeaders()
    {
        $request = new ServerRequest('POST', '/api/auth/login');
        $request = $request->withParsedBody([
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $this->mockAuthService
            ->method('login')
            ->willReturn(['success' => true, 'user' => []]);

        $response = $this->controller->login($request);

        // Check rate limiting headers (should be added by middleware)
        $this->assertTrue($response->hasHeader('X-RateLimit-Limit') || $response->hasHeader('X-Rate-Limit'));
    }
} 