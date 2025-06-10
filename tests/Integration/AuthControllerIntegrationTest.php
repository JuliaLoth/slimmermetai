<?php

namespace Tests\Integration;

use App\Http\Controller\Api\AuthController;
use App\Infrastructure\Repository\AuthRepository;
use App\Application\Service\PasswordHasher;
use App\Infrastructure\Security\JwtService;
use App\Infrastructure\Logging\ErrorLogger;
use App\Infrastructure\Config\Config;
use App\Domain\ValueObject\Email;
use GuzzleHttp\Psr7\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class AuthControllerIntegrationTest extends DatabaseTestCase
{
    private AuthController $controller;
    private AuthRepository $authRepository;
    private PasswordHasher $passwordHasher;
    private JwtService $jwtService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->authRepository = new AuthRepository($this->database);
        $this->passwordHasher = new PasswordHasher();
        $config = $this->createMockConfig('test-secret-key');
        $this->jwtService = new JwtService($config);
        $errorLogger = new ErrorLogger();
        
        $this->controller = new AuthController(
            $this->authRepository,
            $this->passwordHasher,
            $this->jwtService,
            $this->database
        );
    }

    public function testCompleteRegistrationFlow()
    {
        $email = 'integration@example.com';
        $password = 'TestPassword123!';
        
        $request = new ServerRequest('POST', '/api/auth/register');
        $request = $request->withParsedBody([
            'firstName' => 'Integration',
            'lastName' => 'Test',
            'email' => $email,
            'password' => $password,
            'termsAgreement' => true
        ]);

        $response = $this->controller->handle($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertTrue($body['success']);
        $this->assertArrayHasKey('token', $body['data']);
        $this->assertArrayHasKey('user', $body['data']);
        $this->assertEquals($email, $body['data']['user']['email']);

        // Verify user was actually created in database
        $user = $this->authRepository->findUserByEmail(new Email($email));
        $this->assertNotNull($user);
        $this->assertEquals('Integration Test', $user->getName());
        
        // Verify password was properly hashed
        $this->assertTrue($this->passwordHasher->verify($password, $user->getPasswordHash()));
        
        // Verify token is valid
        $tokenPayload = $this->jwtService->verify($body['data']['token']);
        $this->assertEquals($user->getId(), $tokenPayload['user_id']);
    }

    public function testCompleteLoginFlow()
    {
        // First create a user
        $email = 'loginflow@example.com';
        $password = 'LoginTest123!';
        $hashedPassword = $this->passwordHasher->hash($password);
        
        $userId = $this->authRepository->createUser('Login Test', new Email($email), $hashedPassword);

        // Now test login
        $request = new ServerRequest('POST', '/api/auth/login', [], null, '1.1', ['REMOTE_ADDR' => '127.0.0.1']);
        $request = $request->withParsedBody([
            'email' => $email,
            'password' => $password
        ]);

        $response = $this->controller->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertTrue($body['success']);
        $this->assertArrayHasKey('token', $body['data']);
        $this->assertEquals($email, $body['data']['user']['email']);

        // Verify login was logged in database
        $stmt = $this->pdo->prepare('SELECT * FROM login_history WHERE email = ? AND success = 1 ORDER BY created_at DESC LIMIT 1');
        $stmt->execute([$email]);
        $loginLog = $stmt->fetch();
        $this->assertNotFalse($loginLog);
        $this->assertEquals('127.0.0.1', $loginLog['ip_address']);

        // Verify last_login_at was updated
        $stmt = $this->pdo->prepare('SELECT last_login_at FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $lastLogin = $stmt->fetchColumn();
        $this->assertNotNull($lastLogin);
    }

    public function testLoginWithInvalidCredentials()
    {
        $request = new ServerRequest('POST', '/api/auth/login', [], null, '1.1', ['REMOTE_ADDR' => '127.0.0.1']);
        $request = $request->withParsedBody([
            'email' => 'nonexistent@example.com',
            'password' => 'wrongpassword'
        ]);

        $response = $this->controller->handle($request);

        $this->assertEquals(401, $response->getStatusCode());
        
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertFalse($body['success']);
        $this->assertEquals('Ongeldige inloggegevens', $body['message']);

        // Verify failed login was logged
        $stmt = $this->pdo->prepare('SELECT * FROM login_history WHERE email = ? AND success = 0 ORDER BY created_at DESC LIMIT 1');
        $stmt->execute(['nonexistent@example.com']);
        $failedLog = $stmt->fetch();
        $this->assertNotFalse($failedLog);
    }

    public function testMeEndpointWithValidToken()
    {
        // Create user and generate token
        $email = 'me@example.com';
        $userId = $this->createUser(['email' => $email, 'name' => 'Me Test User']);
        
        $token = $this->jwtService->generate([
            'user_id' => $userId,
            'email' => $email,
            'role' => 'user'
        ]);

        $request = new ServerRequest('GET', '/api/auth/me');
        $request = $request->withHeader('Authorization', 'Bearer ' . $token);

        $response = $this->controller->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertTrue($body['success']);
        $this->assertEquals($email, $body['data']['user']['email']);
        $this->assertEquals('Me Test User', $body['data']['user']['name']);
    }

    public function testMeEndpointWithInvalidToken()
    {
        $request = new ServerRequest('GET', '/api/auth/me');
        $request = $request->withHeader('Authorization', 'Bearer invalid.token.here');

        $response = $this->controller->handle($request);

        $this->assertEquals(401, $response->getStatusCode());
        
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertFalse($body['success']);
        $this->assertEquals('Ongeldig token', $body['message']);
    }

    public function testForgotPasswordFlow()
    {
        $email = 'forgot@example.com';
        $userId = $this->createUser(['email' => $email]);

        $request = new ServerRequest('POST', '/api/auth/forgot-password');
        $request = $request->withParsedBody([
            'email' => $email
        ]);

        $response = $this->controller->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertTrue($body['success']);
        $this->assertStringContainsString('herstellink verstuurd', $body['data']['message']);

        // Verify password reset token was created
        $stmt = $this->pdo->prepare('SELECT * FROM email_tokens WHERE user_id = ? AND type = "password_reset" ORDER BY created_at DESC LIMIT 1');
        $stmt->execute([$userId]);
        $resetToken = $stmt->fetch();
        $this->assertNotFalse($resetToken);
        $this->assertNotNull($resetToken['token']);
    }

    public function testResetPasswordFlow()
    {
        $email = 'reset@example.com';
        $userId = $this->createUser(['email' => $email]);
        $newPassword = 'NewPassword123!';
        
        // Create password reset token
        $resetToken = 'reset_' . uniqid();
        $expiresAt = new \DateTimeImmutable('+1 hour');
        $this->authRepository->createPasswordResetToken($userId, $resetToken, $expiresAt);

        $request = new ServerRequest('POST', '/api/auth/reset-password');
        $request = $request->withParsedBody([
            'token' => $resetToken,
            'password' => $newPassword
        ]);

        $response = $this->controller->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertTrue($body['success']);
        $this->assertEquals('Wachtwoord succesvol gewijzigd', $body['data']['message']);

        // Verify password was actually changed
        $user = $this->authRepository->findUserByEmail(new Email($email));
        $this->assertTrue($this->passwordHasher->verify($newPassword, $user->getPasswordHash()));

        // Verify token was marked as used
        $stmt = $this->pdo->prepare('SELECT used_at FROM email_tokens WHERE token = ?');
        $stmt->execute([$resetToken]);
        $usedAt = $stmt->fetchColumn();
        $this->assertNotNull($usedAt);
    }

    public function testEmailVerificationFlow()
    {
        $email = 'verify@example.com';
        $userId = $this->createUser(['email' => $email]);
        
        // Create email verification token
        $verifyToken = 'verify_' . uniqid();
        $expiresAt = new \DateTimeImmutable('+24 hours');
        $this->authRepository->createEmailVerificationToken($userId, $verifyToken, $expiresAt);

        $request = new ServerRequest('POST', '/api/auth/verify-email');
        $request = $request->withParsedBody([
            'token' => $verifyToken
        ]);

        $response = $this->controller->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertTrue($body['success']);
        $this->assertEquals('E-mailadres succesvol geverifieerd', $body['data']['message']);

        // Verify email was marked as verified
        $stmt = $this->pdo->prepare('SELECT email_verified_at FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $verifiedAt = $stmt->fetchColumn();
        $this->assertNotNull($verifiedAt);
    }

    public function testLogoutEndpoint()
    {
        $request = new ServerRequest('POST', '/api/auth/logout');

        $response = $this->controller->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertTrue($body['success']);
        $this->assertEquals('Succesvol uitgelogd', $body['data']['message']);
    }

    public function testRegistrationValidationErrors()
    {
        $request = new ServerRequest('POST', '/api/auth/register');
        $request = $request->withParsedBody([
            // Missing required fields
            'email' => 'invalid-email',
            'password' => '123' // Too short
        ]);

        $response = $this->controller->handle($request);

        $this->assertEquals(422, $response->getStatusCode());
        
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertFalse($body['success']);
        $this->assertEquals('Validatiefout', $body['message']);
        $this->assertArrayHasKey('errors', $body);
        $this->assertArrayHasKey('firstName', $body['errors']);
        $this->assertArrayHasKey('email', $body['errors']);
        $this->assertArrayHasKey('password', $body['errors']);
    }

    public function testInvalidEndpoint()
    {
        $request = new ServerRequest('GET', '/api/auth/invalid-endpoint');

        $response = $this->controller->handle($request);

        $this->assertEquals(404, $response->getStatusCode());
        
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertFalse($body['success']);
        $this->assertEquals('Endpoint niet gevonden', $body['message']);
    }

    public function testRateLimitingOnFailedLogins()
    {
        $email = 'ratelimit@example.com';
        $this->createUser(['email' => $email]);

        // Simulate 5 failed login attempts
        for ($i = 0; $i < 5; $i++) {
            $this->authRepository->logLoginAttempt($email, false, '127.0.0.1');
        }

        // 6th attempt should be rate limited
        $request = new ServerRequest('POST', '/api/auth/login', [], null, '1.1', ['REMOTE_ADDR' => '127.0.0.1']);
        $request = $request->withParsedBody([
            'email' => $email,
            'password' => 'TestPassword123!'
        ]);

        $response = $this->controller->handle($request);

        $this->assertEquals(429, $response->getStatusCode());
        
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertFalse($body['success']);
        $this->assertStringContainsString('Te veel mislukte inlogpogingen', $body['message']);
    }

    public function testCORSHeaders()
    {
        $request = new ServerRequest('POST', '/api/auth/login');
        $request = $request->withParsedBody([
            'email' => 'test@example.com',
            'password' => 'wrongpassword'
        ]);

        $response = $this->controller->handle($request);

        // Check CORS headers are present
        $this->assertNotEmpty($response->getHeaderLine('Access-Control-Allow-Origin'));
        $this->assertNotEmpty($response->getHeaderLine('Access-Control-Allow-Methods'));
        $this->assertNotEmpty($response->getHeaderLine('Access-Control-Allow-Headers'));
        $this->assertEquals('application/json; charset=UTF-8', $response->getHeaderLine('Content-Type'));
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
} 