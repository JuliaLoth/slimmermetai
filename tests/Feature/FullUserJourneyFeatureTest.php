<?php

namespace Tests\Feature;

use Tests\Integration\DatabaseTestCase;
use App\Http\Controller\Api\AuthController;
use App\Infrastructure\Repository\AuthRepository;
use App\Application\Service\PasswordHasher;
use App\Infrastructure\Security\JwtService;
use App\Infrastructure\Logging\ErrorLogger;
use App\Infrastructure\Config\Config;
use App\Domain\ValueObject\Email;
use GuzzleHttp\Psr7\ServerRequest;

class FullUserJourneyFeatureTest extends DatabaseTestCase
{
    private AuthController $authController;
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
        
        $this->authController = new AuthController(
            $this->authRepository,
            $this->passwordHasher,
            $this->jwtService,
            $this->database
        );
    }

    public function testCompleteUserLifecycleFromRegistrationToLogin()
    {
        // PHASE 1: REGISTRATION
        $userData = [
            'firstName' => 'Journey',
            'lastName' => 'Test',
            'email' => 'journey.test@example.com',
            'password' => 'SecurePassword123!',
            'termsAgreement' => true
        ];

        $registrationRequest = new ServerRequest('POST', '/api/auth/register');
        $registrationRequest = $registrationRequest->withParsedBody($userData);

        $registrationResponse = $this->authController->handle($registrationRequest);

        // Verify registration success
        $this->assertEquals(200, $registrationResponse->getStatusCode());
        
        $registrationBody = json_decode($registrationResponse->getBody()->getContents(), true);
        $this->assertTrue($registrationBody['success']);
        $this->assertArrayHasKey('token', $registrationBody['data']);
        $this->assertArrayHasKey('user', $registrationBody['data']);
        
        $registrationToken = $registrationBody['data']['token'];
        $userId = $registrationBody['data']['user']['id'];

        // PHASE 2: DATABASE VERIFICATION
        $user = $this->authRepository->findUserByEmail(new Email($userData['email']));
        $this->assertNotNull($user);
        $this->assertEquals('Journey Test', $user->getName());
        $this->assertEquals($userData['email'], (string)$user->getEmail());
        $this->assertTrue($this->passwordHasher->verify($userData['password'], $user->getPasswordHash()));

        // PHASE 3: TOKEN VERIFICATION
        $tokenPayload = $this->jwtService->verify($registrationToken);
        $this->assertEquals($userId, $tokenPayload['user_id']);
        $this->assertEquals($userData['email'], $tokenPayload['email']);

        // PHASE 4: PROTECTED ENDPOINT ACCESS
        $meRequest = new ServerRequest('GET', '/api/auth/me');
        $meRequest = $meRequest->withHeader('Authorization', 'Bearer ' . $registrationToken);
        
        $meResponse = $this->authController->handle($meRequest);
        $this->assertEquals(200, $meResponse->getStatusCode());
        
        $meBody = json_decode($meResponse->getBody()->getContents(), true);
        $this->assertTrue($meBody['success']);
        $this->assertEquals($userData['email'], $meBody['data']['user']['email']);

        // PHASE 5: LOGIN WITH NEW ACCOUNT
        $loginRequest = new ServerRequest('POST', '/api/auth/login', [], null, '1.1', ['REMOTE_ADDR' => '192.168.1.100']);
        $loginRequest = $loginRequest->withParsedBody([
            'email' => $userData['email'],
            'password' => $userData['password']
        ]);
        
        $loginResponse = $this->authController->handle($loginRequest);
        $this->assertEquals(200, $loginResponse->getStatusCode());
        
        $loginBody = json_decode($loginResponse->getBody()->getContents(), true);
        $this->assertTrue($loginBody['success']);
        $this->assertArrayHasKey('token', $loginBody['data']);

        // PHASE 6: LOGIN AUDIT VERIFICATION
        $stmt = $this->pdo->prepare('SELECT * FROM login_history WHERE email = ? AND success = 1 ORDER BY created_at DESC LIMIT 1');
        $stmt->execute([$userData['email']]);
        $loginLog = $stmt->fetch();
        
        $this->assertNotFalse($loginLog);
        $this->assertEquals('192.168.1.100', $loginLog['ip_address']);

        return $loginBody['data']['token'];
    }

    public function testPasswordResetCompleteFlow()
    {
        // Create user first
        $email = 'reset.journey@example.com';
        $originalPassword = 'OriginalPass123!';
        $newPassword = 'NewPassword123!';
        
        $userId = $this->createUser([
            'email' => $email,
            'password' => $this->passwordHasher->hash($originalPassword),
            'name' => 'Reset Journey User'
        ]);

        // Step 1: Request password reset
        $forgotRequest = new ServerRequest('POST', '/api/auth/forgot-password');
        $forgotRequest = $forgotRequest->withParsedBody(['email' => $email]);
        
        $forgotResponse = $this->authController->handle($forgotRequest);
        $this->assertEquals(200, $forgotResponse->getStatusCode());

        // Step 2: Get reset token from database
        $stmt = $this->pdo->prepare('SELECT token FROM email_tokens WHERE user_id = ? AND type = "password_reset" ORDER BY created_at DESC LIMIT 1');
        $stmt->execute([$userId]);
        $resetToken = $stmt->fetchColumn();
        $this->assertNotFalse($resetToken);

        // Step 3: Reset password
        $resetRequest = new ServerRequest('POST', '/api/auth/reset-password');
        $resetRequest = $resetRequest->withParsedBody([
            'token' => $resetToken,
            'password' => $newPassword
        ]);
        
        $resetResponse = $this->authController->handle($resetRequest);
        $this->assertEquals(200, $resetResponse->getStatusCode());

        // Step 4: Verify old password doesn't work
        $oldLoginRequest = new ServerRequest('POST', '/api/auth/login');
        $oldLoginRequest = $oldLoginRequest->withParsedBody([
            'email' => $email,
            'password' => $originalPassword
        ]);
        
        $oldLoginResponse = $this->authController->handle($oldLoginRequest);
        $this->assertEquals(401, $oldLoginResponse->getStatusCode());

        // Step 5: Verify new password works
        $newLoginRequest = new ServerRequest('POST', '/api/auth/login');
        $newLoginRequest = $newLoginRequest->withParsedBody([
            'email' => $email,
            'password' => $newPassword
        ]);
        
        $newLoginResponse = $this->authController->handle($newLoginRequest);
        $this->assertEquals(200, $newLoginResponse->getStatusCode());
    }

    public function testEmailVerificationCompleteFlow()
    {
        $email = 'verify.journey@example.com';
        $userId = $this->createUser(['email' => $email, 'name' => 'Verify Journey User']);

        // Step 1: Create verification token
        $verifyToken = 'verify_' . uniqid();
        $expiresAt = new \DateTimeImmutable('+24 hours');
        $this->authRepository->createEmailVerificationToken($userId, $verifyToken, $expiresAt);

        // Step 2: Verify email is not verified yet
        $stmt = $this->pdo->prepare('SELECT email_verified_at FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $verifiedAt = $stmt->fetchColumn();
        $this->assertNull($verifiedAt);

        // Step 3: Verify email
        $verifyRequest = new ServerRequest('POST', '/api/auth/verify-email');
        $verifyRequest = $verifyRequest->withParsedBody(['token' => $verifyToken]);
        
        $verifyResponse = $this->authController->handle($verifyRequest);
        $this->assertEquals(200, $verifyResponse->getStatusCode());

        // Step 4: Verify email is now verified
        $stmt = $this->pdo->prepare('SELECT email_verified_at FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $verifiedAt = $stmt->fetchColumn();
        $this->assertNotNull($verifiedAt);

        // Step 5: Verify token is marked as used
        $stmt = $this->pdo->prepare('SELECT used_at FROM email_tokens WHERE token = ?');
        $stmt->execute([$verifyToken]);
        $usedAt = $stmt->fetchColumn();
        $this->assertNotNull($usedAt);
    }

    public function testMultipleFailedLoginAttempts()
    {
        $email = 'failed.attempts@example.com';
        $this->createUser([
            'email' => $email,
            'password' => $this->passwordHasher->hash('CorrectPassword123!'),
            'name' => 'Failed Attempts User'
        ]);

        // Attempt 5 failed logins
        for ($i = 0; $i < 5; $i++) {
            $loginRequest = new ServerRequest('POST', '/api/auth/login', [], null, '1.1', ['REMOTE_ADDR' => '192.168.1.50']);
            $loginRequest = $loginRequest->withParsedBody([
                'email' => $email,
                'password' => 'WrongPassword'
            ]);
            
            $response = $this->authController->handle($loginRequest);
            $this->assertEquals(401, $response->getStatusCode());
        }

        // Verify failed attempts were logged
        $failedAttempts = $this->authRepository->getFailedLoginAttempts($email, new \DateTimeImmutable('-1 hour'));
        $this->assertEquals(5, $failedAttempts);

        // 6th attempt should be rate limited
        $rateLimitedRequest = new ServerRequest('POST', '/api/auth/login', [], null, '1.1', ['REMOTE_ADDR' => '192.168.1.50']);
        $rateLimitedRequest = $rateLimitedRequest->withParsedBody([
            'email' => $email,
            'password' => 'CorrectPassword123!'
        ]);
        
        $rateLimitedResponse = $this->authController->handle($rateLimitedRequest);
        $this->assertEquals(429, $rateLimitedResponse->getStatusCode());
    }

    public function testTokenBlacklistingFlow()
    {
        // Create user and get token
        $token = $this->testCompleteUserLifecycleFromRegistrationToLogin();

        // Verify token works initially
        $meRequest = new ServerRequest('GET', '/api/auth/me');
        $meRequest = $meRequest->withHeader('Authorization', 'Bearer ' . $token);
        
        $meResponse = $this->authController->handle($meRequest);
        $this->assertEquals(200, $meResponse->getStatusCode());

        // Blacklist the token
        $this->authRepository->blacklistToken($token);

        // Verify token no longer works
        $blacklistedRequest = new ServerRequest('GET', '/api/auth/me');
        $blacklistedRequest = $blacklistedRequest->withHeader('Authorization', 'Bearer ' . $token);
        
        $blacklistedResponse = $this->authController->handle($blacklistedRequest);
        $this->assertEquals(401, $blacklistedResponse->getStatusCode());
    }

    public function testUserAccountSecurity()
    {
        $email = 'security.test@example.com';
        $password = 'SecurityTest123!';
        
        // Register user
        $userData = [
            'firstName' => 'Security',
            'lastName' => 'Test',
            'email' => $email,
            'password' => $password,
            'termsAgreement' => true
        ];

        $registrationRequest = new ServerRequest('POST', '/api/auth/register');
        $registrationRequest = $registrationRequest->withParsedBody($userData);
        
        $registrationResponse = $this->authController->handle($registrationRequest);
        $this->assertEquals(200, $registrationResponse->getStatusCode());

        // Test password strength validation
        $weakPasswords = ['123', 'password', 'PASSWORD', '12345678'];
        
        foreach ($weakPasswords as $weakPassword) {
            $this->assertFalse($this->passwordHasher->isStrong($weakPassword));
        }

        // Test strong passwords
        $strongPasswords = ['SecurePass123!', 'MyS3cur3P@ssw0rd', 'Str0ng!P@ssw0rd'];
        
        foreach ($strongPasswords as $strongPassword) {
            $this->assertTrue($this->passwordHasher->isStrong($strongPassword));
        }

        // Test password hashing
        $plainPassword = 'TestPassword123!';
        $hash = $this->passwordHasher->hash($plainPassword);
        
        $this->assertNotEquals($plainPassword, $hash);
        $this->assertTrue($this->passwordHasher->verify($plainPassword, $hash));
        $this->assertFalse($this->passwordHasher->verify('wrongpassword', $hash));
    }

    public function testDataIntegrityThroughoutJourney()
    {
        $originalData = [
            'firstName' => 'Integrity',
            'lastName' => 'Test',
            'email' => 'integrity.test@example.com',
            'password' => 'IntegrityTest123!',
            'termsAgreement' => true
        ];

        // Register
        $registrationRequest = new ServerRequest('POST', '/api/auth/register');
        $registrationRequest = $registrationRequest->withParsedBody($originalData);
        
        $registrationResponse = $this->authController->handle($registrationRequest);
        $registrationBody = json_decode($registrationResponse->getBody()->getContents(), true);
        $userId = $registrationBody['data']['user']['id'];

        // Verify data integrity at each step
        $user = $this->authRepository->findUserByEmail(new Email($originalData['email']));
        $this->assertEquals('Integrity Test', $user->getName());
        $this->assertEquals($originalData['email'], (string)$user->getEmail());
        $this->assertTrue($this->passwordHasher->verify($originalData['password'], $user->getPasswordHash()));

        // Login and verify data consistency
        $loginRequest = new ServerRequest('POST', '/api/auth/login');
        $loginRequest = $loginRequest->withParsedBody([
            'email' => $originalData['email'],
            'password' => $originalData['password']
        ]);
        
        $loginResponse = $this->authController->handle($loginRequest);
        $loginBody = json_decode($loginResponse->getBody()->getContents(), true);
        
        $this->assertEquals($originalData['email'], $loginBody['data']['user']['email']);
        $this->assertEquals('Integrity Test', $loginBody['data']['user']['name']);
        $this->assertEquals($userId, $loginBody['data']['user']['id']);
    }

    public function testConcurrentUserOperations()
    {
        $baseEmail = 'concurrent.test';
        $userIds = [];

        // Create multiple users concurrently (simulated)
        for ($i = 1; $i <= 10; $i++) {
            $userData = [
                'firstName' => "User$i",
                'lastName' => 'Concurrent',
                'email' => "$baseEmail$i@example.com",
                'password' => 'ConcurrentTest123!',
                'termsAgreement' => true
            ];

            $request = new ServerRequest('POST', '/api/auth/register');
            $request = $request->withParsedBody($userData);
            
            $response = $this->authController->handle($request);
            $this->assertEquals(200, $response->getStatusCode());
            
            $body = json_decode($response->getBody()->getContents(), true);
            $userIds[] = $body['data']['user']['id'];
        }

        // Verify all users were created successfully
        $this->assertCount(10, $userIds);
        $this->assertCount(10, array_unique($userIds)); // All IDs should be unique

        // Verify each user can login
        for ($i = 1; $i <= 10; $i++) {
            $loginRequest = new ServerRequest('POST', '/api/auth/login');
            $loginRequest = $loginRequest->withParsedBody([
                'email' => "$baseEmail$i@example.com",
                'password' => 'ConcurrentTest123!'
            ]);
            
            $loginResponse = $this->authController->handle($loginRequest);
            $this->assertEquals(200, $loginResponse->getStatusCode());
        }
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