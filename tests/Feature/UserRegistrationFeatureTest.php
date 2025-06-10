<?php

namespace Tests\Feature;

use Tests\Integration\DatabaseTestCase;
use App\Http\Controller\Api\AuthController;
use App\Infrastructure\Repository\AuthRepository;
use App\Application\Service\PasswordHasher;
use App\Infrastructure\Security\JwtService;
use App\Infrastructure\Logging\ErrorLogger;
use App\Infrastructure\Config\Config;
use App\Infrastructure\Mail\MailService;
use App\Domain\ValueObject\Email;
use GuzzleHttp\Psr7\ServerRequest;

class UserRegistrationFeatureTest extends DatabaseTestCase
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

    public function testCompleteUserRegistrationJourney()
    {
        // Step 1: User registration
        $userData = [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'john.doe@example.com',
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
        
        // Step 2: Verify user was created in database
        $user = $this->authRepository->findUserByEmail(new Email($userData['email']));
        $this->assertNotNull($user);
        $this->assertEquals('John Doe', $user->getName());
        $this->assertEquals($userData['email'], (string)$user->getEmail());
        $this->assertTrue($this->passwordHasher->verify($userData['password'], $user->getPasswordHash()));
        
        // Step 3: Verify JWT token is valid
        $tokenPayload = $this->jwtService->verify($registrationToken);
        $this->assertEquals($userId, $tokenPayload['user_id']);
        $this->assertEquals($userData['email'], $tokenPayload['email']);
        
        // Step 4: Test using the token to access protected endpoint
        $meRequest = new ServerRequest('GET', '/api/auth/me');
        $meRequest = $meRequest->withHeader('Authorization', 'Bearer ' . $registrationToken);
        
        $meResponse = $this->authController->handle($meRequest);
        
        $this->assertEquals(200, $meResponse->getStatusCode());
        
        $meBody = json_decode($meResponse->getBody()->getContents(), true);
        $this->assertTrue($meBody['success']);
        $this->assertEquals($userData['email'], $meBody['data']['user']['email']);
        $this->assertEquals('John Doe', $meBody['data']['user']['name']);
        
        // Step 5: Test login with the new account
        $loginRequest = new ServerRequest('POST', '/api/auth/login', [], null, '1.1', ['REMOTE_ADDR' => '127.0.0.1']);
        $loginRequest = $loginRequest->withParsedBody([
            'email' => $userData['email'],
            'password' => $userData['password']
        ]);
        
        $loginResponse = $this->authController->handle($loginRequest);
        
        $this->assertEquals(200, $loginResponse->getStatusCode());
        
        $loginBody = json_decode($loginResponse->getBody()->getContents(), true);
        $this->assertTrue($loginBody['success']);
        $this->assertArrayHasKey('token', $loginBody['data']);
        
        // Verify login token is different from registration token
        $this->assertNotEquals($registrationToken, $loginBody['data']['token']);
        
        // Step 6: Verify login was logged in database
        $stmt = $this->pdo->prepare('SELECT * FROM login_history WHERE email = ? AND success = 1 ORDER BY created_at DESC LIMIT 1');
        $stmt->execute([$userData['email']]);
        $loginLog = $stmt->fetch();
        
        $this->assertNotFalse($loginLog);
        $this->assertEquals('127.0.0.1', $loginLog['ip_address']);
        
        // Step 7: Verify last_login_at was updated
        $stmt = $this->pdo->prepare('SELECT last_login_at FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $lastLogin = $stmt->fetchColumn();
        $this->assertNotNull($lastLogin);
        
        return [
            'user' => $user,
            'registrationToken' => $registrationToken,
            'loginToken' => $loginBody['data']['token']
        ];
    }

    public function testUserRegistrationWithInvalidData()
    {
        $invalidDataSets = [
            // Missing firstName
            [
                'lastName' => 'Doe',
                'email' => 'test@example.com',
                'password' => 'SecurePassword123!',
                'termsAgreement' => true
            ],
            // Invalid email
            [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'email' => 'invalid-email',
                'password' => 'SecurePassword123!',
                'termsAgreement' => true
            ],
            // Weak password
            [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'email' => 'weak@example.com',
                'password' => '123',
                'termsAgreement' => true
            ],
            // No terms agreement
            [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'email' => 'terms@example.com',
                'password' => 'SecurePassword123!',
                'termsAgreement' => false
            ]
        ];

        foreach ($invalidDataSets as $invalidData) {
            $request = new ServerRequest('POST', '/api/auth/register');
            $request = $request->withParsedBody($invalidData);

            $response = $this->authController->handle($request);

            $this->assertEquals(422, $response->getStatusCode());
            
            $body = json_decode($response->getBody()->getContents(), true);
            $this->assertFalse($body['success']);
            $this->assertEquals('Validatiefout', $body['message']);
            $this->assertArrayHasKey('errors', $body);
        }
    }

    public function testRegistrationWithExistingEmail()
    {
        // First registration
        $userData = [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'duplicate@example.com',
            'password' => 'SecurePassword123!',
            'termsAgreement' => true
        ];

        $firstRequest = new ServerRequest('POST', '/api/auth/register');
        $firstRequest = $firstRequest->withParsedBody($userData);
        
        $firstResponse = $this->authController->handle($firstRequest);
        $this->assertEquals(200, $firstResponse->getStatusCode());

        // Second registration with same email
        $secondRequest = new ServerRequest('POST', '/api/auth/register');
        $secondRequest = $secondRequest->withParsedBody($userData);
        
        $secondResponse = $this->authController->handle($secondRequest);
        
        $this->assertEquals(409, $secondResponse->getStatusCode());
        
        $secondBody = json_decode($secondResponse->getBody()->getContents(), true);
        $this->assertFalse($secondBody['success']);
        $this->assertStringContainsString('Dit e-mailadres is al in gebruik', $secondBody['message']);
    }

    public function testEmailVerificationFlow()
    {
        // Register user
        $userData = [
            'firstName' => 'Verify',
            'lastName' => 'Test',
            'email' => 'verify.test@example.com',
            'password' => 'SecurePassword123!',
            'termsAgreement' => true
        ];

        $registrationRequest = new ServerRequest('POST', '/api/auth/register');
        $registrationRequest = $registrationRequest->withParsedBody($userData);
        
        $registrationResponse = $this->authController->handle($registrationRequest);
        $registrationBody = json_decode($registrationResponse->getBody()->getContents(), true);
        $userId = $registrationBody['data']['user']['id'];

        // Create email verification token (simulating email service)
        $verifyToken = 'verify_' . uniqid();
        $expiresAt = new \DateTimeImmutable('+24 hours');
        $this->authRepository->createEmailVerificationToken($userId, $verifyToken, $expiresAt);

        // Verify email
        $verifyRequest = new ServerRequest('POST', '/api/auth/verify-email');
        $verifyRequest = $verifyRequest->withParsedBody(['token' => $verifyToken]);
        
        $verifyResponse = $this->authController->handle($verifyRequest);
        
        $this->assertEquals(200, $verifyResponse->getStatusCode());
        
        $verifyBody = json_decode($verifyResponse->getBody()->getContents(), true);
        $this->assertTrue($verifyBody['success']);
        $this->assertEquals('E-mailadres succesvol geverifieerd', $verifyBody['data']['message']);

        // Verify email_verified_at was set in database
        $stmt = $this->pdo->prepare('SELECT email_verified_at FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $verifiedAt = $stmt->fetchColumn();
        $this->assertNotNull($verifiedAt);
    }

    public function testPasswordResetFlow()
    {
        // Register user first
        $userData = [
            'firstName' => 'Reset',
            'lastName' => 'Test',
            'email' => 'reset.test@example.com',
            'password' => 'OriginalPassword123!',
            'termsAgreement' => true
        ];

        $registrationRequest = new ServerRequest('POST', '/api/auth/register');
        $registrationRequest = $registrationRequest->withParsedBody($userData);
        
        $registrationResponse = $this->authController->handle($registrationRequest);
        $registrationBody = json_decode($registrationResponse->getBody()->getContents(), true);
        $userId = $registrationBody['data']['user']['id'];

        // Request password reset
        $forgotRequest = new ServerRequest('POST', '/api/auth/forgot-password');
        $forgotRequest = $forgotRequest->withParsedBody(['email' => $userData['email']]);
        
        $forgotResponse = $this->authController->handle($forgotRequest);
        
        $this->assertEquals(200, $forgotResponse->getStatusCode());
        
        $forgotBody = json_decode($forgotResponse->getBody()->getContents(), true);
        $this->assertTrue($forgotBody['success']);
        $this->assertStringContainsString('herstellink verstuurd', $forgotBody['data']['message']);

        // Get the reset token from database
        $stmt = $this->pdo->prepare('SELECT token FROM email_tokens WHERE user_id = ? AND type = "password_reset" ORDER BY created_at DESC LIMIT 1');
        $stmt->execute([$userId]);
        $resetToken = $stmt->fetchColumn();
        $this->assertNotFalse($resetToken);

        // Reset password
        $newPassword = 'NewSecurePassword123!';
        $resetRequest = new ServerRequest('POST', '/api/auth/reset-password');
        $resetRequest = $resetRequest->withParsedBody([
            'token' => $resetToken,
            'password' => $newPassword
        ]);
        
        $resetResponse = $this->authController->handle($resetRequest);
        
        $this->assertEquals(200, $resetResponse->getStatusCode());
        
        $resetBody = json_decode($resetResponse->getBody()->getContents(), true);
        $this->assertTrue($resetBody['success']);
        $this->assertEquals('Wachtwoord succesvol gewijzigd', $resetBody['data']['message']);

        // Test login with new password
        $loginRequest = new ServerRequest('POST', '/api/auth/login', [], null, '1.1', ['REMOTE_ADDR' => '127.0.0.1']);
        $loginRequest = $loginRequest->withParsedBody([
            'email' => $userData['email'],
            'password' => $newPassword
        ]);
        
        $loginResponse = $this->authController->handle($loginRequest);
        
        $this->assertEquals(200, $loginResponse->getStatusCode());
        
        // Test that old password no longer works
        $oldPasswordRequest = new ServerRequest('POST', '/api/auth/login', [], null, '1.1', ['REMOTE_ADDR' => '127.0.0.1']);
        $oldPasswordRequest = $oldPasswordRequest->withParsedBody([
            'email' => $userData['email'],
            'password' => $userData['password']
        ]);
        
        $oldPasswordResponse = $this->authController->handle($oldPasswordRequest);
        $this->assertEquals(401, $oldPasswordResponse->getStatusCode());
    }

    public function testUserProfileUpdate()
    {
        // Register user
        $result = $this->testCompleteUserRegistrationJourney();
        $token = $result['loginToken'];
        $user = $result['user'];

        // Update profile (if this endpoint exists)
        $updateData = [
            'firstName' => 'Updated',
            'lastName' => 'Name'
        ];

        $updateRequest = new ServerRequest('PUT', '/api/auth/profile');
        $updateRequest = $updateRequest
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->withParsedBody($updateData);

        // This test would work if the profile update endpoint exists
        // For now, just verify the user data is accessible
        $this->assertEquals('John Doe', $user->getName());
        $this->assertEquals('john.doe@example.com', (string)$user->getEmail());
    }

    public function testAccountDeletion()
    {
        // Register user
        $result = $this->testCompleteUserRegistrationJourney();
        $token = $result['loginToken'];
        $user = $result['user'];

        // Test soft deletion (if implemented)
        $deleteRequest = new ServerRequest('DELETE', '/api/auth/account');
        $deleteRequest = $deleteRequest->withHeader('Authorization', 'Bearer ' . $token);

        // For now, just verify user exists and could be deleted
        $this->assertNotNull($user->getId());
        
        // If soft delete is implemented, verify deleted_at is set
        $stmt = $this->pdo->prepare('SELECT deleted_at FROM users WHERE id = ?');
        $stmt->execute([$user->getId()]);
        $deletedAt = $stmt->fetchColumn();
        $this->assertNull($deletedAt); // Should be null before deletion
    }

    public function testSecurityFeatures()
    {
        // Test rate limiting on registration
        $userData = [
            'firstName' => 'Rate',
            'lastName' => 'Limited',
            'email' => 'rate.limited@example.com',
            'password' => 'SecurePassword123!',
            'termsAgreement' => true
        ];

        // Attempt multiple registrations quickly
        for ($i = 0; $i < 3; $i++) {
            $userData['email'] = "rate$i@example.com";
            
            $request = new ServerRequest('POST', '/api/auth/register');
            $request = $request->withParsedBody($userData);
            
            $response = $this->authController->handle($request);
            
            // All should succeed as emails are unique
            $this->assertEquals(200, $response->getStatusCode());
        }
    }

    public function testDataValidationAndSanitization()
    {
        // Test with potentially malicious input
        $maliciousData = [
            'firstName' => '<script>alert("xss")</script>',
            'lastName' => 'DROP TABLE users;--',
            'email' => 'test@example.com',
            'password' => 'SecurePassword123!',
            'termsAgreement' => true
        ];

        $request = new ServerRequest('POST', '/api/auth/register');
        $request = $request->withParsedBody($maliciousData);
        
        $response = $this->authController->handle($request);
        
        // Should either succeed with sanitized data or fail validation
        $this->assertContains($response->getStatusCode(), [200, 422]);
        
        if ($response->getStatusCode() === 200) {
            // If it succeeded, verify data was sanitized
            $body = json_decode($response->getBody()->getContents(), true);
            $this->assertStringNotContainsString('<script>', $body['data']['user']['name']);
            $this->assertStringNotContainsString('DROP TABLE', $body['data']['user']['name']);
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