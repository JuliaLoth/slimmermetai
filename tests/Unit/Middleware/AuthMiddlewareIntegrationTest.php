<?php

namespace Tests\Unit\Middleware;

use PHPUnit\Framework\TestCase;
use App\Http\Middleware\AuthMiddleware;
use App\Infrastructure\Security\JwtService;
use App\Infrastructure\Repository\AuthRepository;
use Tests\Support\TestDatabase;
use App\Infrastructure\Logging\ErrorLogger;
use App\Infrastructure\Config\Config;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use PDO;

class AuthMiddlewareIntegrationTest extends TestCase
{
    private AuthMiddleware $middleware;
    private JwtService $jwtService;
    private AuthRepository $authRepository;
    private RequestHandlerInterface $handler;
    private ?PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create in-memory SQLite database
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->setupTestTables();
        
        // Use TestDatabase instead of real Database
        $database = new TestDatabase($this->pdo);
        
        $config = $this->createMockConfig('test-secret-key');
        $this->jwtService = new JwtService($config);
        $this->authRepository = new AuthRepository($database);
        $this->handler = $this->createMock(RequestHandlerInterface::class);
        
        $this->middleware = new AuthMiddleware($this->jwtService, $this->authRepository);
    }

    private function setupTestTables(): void
    {
        // Users table - match BaseIntegrationTest schema
        $this->pdo->exec('
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email TEXT UNIQUE NOT NULL,
                password_hash TEXT NOT NULL,
                name TEXT NOT NULL,
                google_id TEXT,
                role TEXT DEFAULT "user",
                failed_login_attempts INTEGER DEFAULT 0,
                last_failed_login DATETIME,
                login_count INTEGER DEFAULT 0,
                last_login DATETIME,
                last_activity_at DATETIME,
                email_verified BOOLEAN DEFAULT 0,
                email_verified_at DATETIME,
                active BOOLEAN DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                deleted_at DATETIME NULL
            )
        ');
        
        // Blacklisted tokens table - match expected schema with token_hash column
        $this->pdo->exec('
            CREATE TABLE blacklisted_tokens (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                token_hash TEXT NOT NULL UNIQUE,
                user_id INTEGER,
                expires_at DATETIME,
                blacklisted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            )
        ');
        
        // Login history table for tracking
        $this->pdo->exec('
            CREATE TABLE login_history (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                email TEXT NOT NULL,
                success BOOLEAN NOT NULL,
                ip_address TEXT,
                user_agent TEXT,
                reason TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            )
        ');
        
        // User actions table for audit
        $this->pdo->exec('
            CREATE TABLE user_actions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                action TEXT NOT NULL,
                metadata TEXT,
                ip_address TEXT,
                user_agent TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            )
        ');
    }

    public function testValidTokenPassesThrough()
    {
        $payload = [
            'user_id' => 1,
            'email' => 'test@example.com',
            'exp' => time() + 3600
        ];
        
        $token = $this->jwtService->generate($payload);

        $request = new ServerRequest('GET', '/protected');
        $request = $request->withHeader('Authorization', 'Bearer ' . $token);

        $expectedResponse = new Response(200, [], 'Success');
        $this->handler
            ->expects($this->once())
            ->method('handle')
            ->willReturn($expectedResponse);

        $response = $this->middleware->process($request, $this->handler);

        $this->assertSame($expectedResponse, $response);
    }

    public function testMissingAuthorizationHeaderReturnsUnauthorized()
    {
        $request = new ServerRequest('GET', '/protected');

        $this->handler
            ->expects($this->never())
            ->method('handle');

        $response = $this->middleware->process($request, $this->handler);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(401, $response->getStatusCode());
        
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertFalse($body['success']);
        $this->assertStringContainsString('authorization', strtolower($body['message']));
    }

    public function testInvalidAuthorizationFormatReturnsUnauthorized()
    {
        $request = new ServerRequest('GET', '/protected');
        $request = $request->withHeader('Authorization', 'InvalidFormat token123');

        $response = $this->middleware->process($request, $this->handler);

        $this->assertEquals(401, $response->getStatusCode());
        
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertStringContainsString('authorization', strtolower($body['message']));
    }

    public function testInvalidTokenReturnsUnauthorized()
    {
        $token = 'invalid.jwt.token';
        $request = new ServerRequest('GET', '/protected');
        $request = $request->withHeader('Authorization', 'Bearer ' . $token);

        $response = $this->middleware->process($request, $this->handler);

        $this->assertEquals(401, $response->getStatusCode());
        
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertStringContainsString('token', strtolower($body['message']));
    }

    public function testExpiredTokenReturnsUnauthorized()
    {
        $expiredPayload = [
            'user_id' => 1,
            'email' => 'test@example.com',
            'exp' => time() - 3600 // Expired 1 hour ago
        ];
        
        $token = $this->jwtService->generate($expiredPayload);

        $request = new ServerRequest('GET', '/protected');
        $request = $request->withHeader('Authorization', 'Bearer ' . $token);

        $response = $this->middleware->process($request, $this->handler);

        $this->assertEquals(401, $response->getStatusCode());
        
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertStringContainsString('expired', strtolower($body['message']));
    }

    public function testBlacklistedTokenReturnsUnauthorized()
    {
        $payload = [
            'user_id' => 1,
            'email' => 'test@example.com',
            'exp' => time() + 3600
        ];
        
        $token = $this->jwtService->generate($payload);
        
        // Blacklist the token using hash like AuthRepository does
        $tokenHash = hash('sha256', $token);
        $this->pdo->prepare('INSERT INTO blacklisted_tokens (token_hash, user_id, expires_at) VALUES (?, ?, ?)')
            ->execute([$tokenHash, 1, date('Y-m-d H:i:s', time() + 3600)]);

        $request = new ServerRequest('GET', '/protected');
        $request = $request->withHeader('Authorization', 'Bearer ' . $token);

        $response = $this->middleware->process($request, $this->handler);

        $this->assertEquals(401, $response->getStatusCode());
        
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertStringContainsString('blacklist', strtolower($body['message']));
    }

    public function testDifferentAuthorizationSchemes()
    {
        $schemes = [
            'Basic dXNlcjpwYXNz',
            'Digest realm="test"',
            'Token abc123',
            'JWT eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9'
        ];

        foreach ($schemes as $authHeader) {
            $request = new ServerRequest('GET', '/protected');
            $request = $request->withHeader('Authorization', $authHeader);

            $response = $this->middleware->process($request, $this->handler);

            $this->assertEquals(401, $response->getStatusCode());
            
            $body = json_decode($response->getBody()->getContents(), true);
            $this->assertStringContainsString('authorization', strtolower($body['message']));
        }
    }

    public function testCaseInsensitiveBearerScheme()
    {
        $payload = [
            'user_id' => 1,
            'email' => 'test@example.com',
            'exp' => time() + 3600
        ];
        
        $token = $this->jwtService->generate($payload);

        $caseVariations = [
            'Bearer ' . $token,
            'bearer ' . $token,
            'BEARER ' . $token,
            'bEaReR ' . $token
        ];

        foreach ($caseVariations as $authHeader) {
            $request = new ServerRequest('GET', '/protected');
            $request = $request->withHeader('Authorization', $authHeader);

            $expectedResponse = new Response(200, [], 'Success');
            $this->handler
                ->method('handle')
                ->willReturn($expectedResponse);

            $response = $this->middleware->process($request, $this->handler);

            $this->assertEquals(200, $response->getStatusCode());
        }
    }

    public function testTokenWithoutExpiration()
    {
        $payload = [
            'user_id' => 1,
            'email' => 'test@example.com'
            // No 'exp' field
        ];
        
        $token = $this->jwtService->generate($payload);

        $request = new ServerRequest('GET', '/protected');
        $request = $request->withHeader('Authorization', 'Bearer ' . $token);

        $expectedResponse = new Response(200);
        $this->handler
            ->expects($this->once())
            ->method('handle')
            ->willReturn($expectedResponse);

        $response = $this->middleware->process($request, $this->handler);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testResponseHeaders()
    {
        $request = new ServerRequest('GET', '/protected');

        $response = $this->middleware->process($request, $this->handler);

        $this->assertEquals('application/json; charset=UTF-8', $response->getHeaderLine('Content-Type'));
        $this->assertNotEmpty($response->getHeaderLine('Access-Control-Allow-Origin'));
    }

    private function createMockConfig(string $jwtSecret): Config
    {
        $config = $this->createMock(Config::class);
        $config->method('get')->willReturnCallback(function ($key, $default = null) use ($jwtSecret) {
            return match($key) {
                'jwt_secret' => $jwtSecret,
                'jwt_expiration' => 3600,
                default => $default
            };
        });
        return $config;
    }

    protected function tearDown(): void
    {
        $this->pdo = null;
        parent::tearDown();
    }
} 