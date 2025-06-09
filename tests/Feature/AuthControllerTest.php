<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use App\Http\Controller\Api\AuthController;
use App\Domain\Repository\AuthRepositoryInterface;
use App\Domain\Service\PasswordHasherInterface;
use App\Domain\Security\JwtServiceInterface;
use App\Infrastructure\Database\DatabaseInterface;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Response;

class AuthControllerTest extends TestCase
{
    private AuthController $controller;
    private $mockAuthRepository;
    private $mockPasswordHasher;
    private $mockJwtService;
    private $mockDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create mocks for all AuthController dependencies - use interfaces instead of final classes
        $this->mockAuthRepository = $this->createMock(AuthRepositoryInterface::class);
        $this->mockPasswordHasher = $this->createMock(PasswordHasherInterface::class);
        $this->mockJwtService = $this->createMock(JwtServiceInterface::class);
        $this->mockDatabase = $this->createMock(DatabaseInterface::class);
        
        // Create controller with all required mocked dependencies
        $this->controller = new AuthController(
            $this->mockAuthRepository,
            $this->mockPasswordHasher,
            $this->mockJwtService,
            $this->mockDatabase
        );
    }

    public function testLoginWithValidCredentials()
    {
        // Mock successful authentication
        $this->mockAuthRepository
            ->method('findByEmail')
            ->with('test@example.com')
            ->willReturn([
                'id' => 1,
                'email' => 'test@example.com',
                'name' => 'Test User'
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
        $this->mockAuthRepository
            ->method('findByEmail')
            ->with('invalid@example.com')
            ->willReturn(null);

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
        $this->mockAuthRepository
            ->method('findByEmail')
            ->with('newuser@example.com')
            ->willReturn(null);

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
        $this->mockJwtService
            ->method('validateToken')
            ->willReturn(true);

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
        $this->mockAuthRepository
            ->method('findByEmail')
            ->with('test@example.com')
            ->willReturn([
                'email' => 'test@example.com'
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
        $this->mockAuthRepository
            ->method('findById')
            ->with(1)
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

        $this->mockAuthRepository
            ->method('findByEmail')
            ->willReturn([
                'id' => 1,
                'email' => 'test@example.com',
                'name' => 'Test User'
            ]);

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

        $this->mockAuthRepository
            ->method('findByEmail')
            ->willReturn([
                'id' => 1,
                'email' => 'test@example.com',
                'name' => 'Test User'
            ]);

        $response = $this->controller->login($request);

        // Check rate limiting headers (should be added by middleware)
        $this->assertTrue($response->hasHeader('X-RateLimit-Limit') || $response->hasHeader('X-Rate-Limit'));
    }
} 