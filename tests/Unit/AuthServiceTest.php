<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Application\Service\AuthService;
use App\Application\Service\JwtService;
use App\Infrastructure\Repository\AuthRepositoryInterface;
use App\Domain\Service\PasswordHasherInterface;

class AuthServiceTest extends TestCase
{
    private AuthService $authService;
    private $mockAuthRepository;
    private $mockJwtService;
    private $mockPasswordHasher;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create mocks
        $this->mockAuthRepository = $this->createMock(AuthRepositoryInterface::class);
        $this->mockJwtService = $this->createMock(JwtService::class);
        $this->mockPasswordHasher = $this->createMock(PasswordHasherInterface::class);
        
        // Create AuthService with mocked dependencies
        $this->authService = new AuthService(
            $this->mockAuthRepository,
            $this->mockJwtService,
            $this->mockPasswordHasher
        );
    }

    public function testSuccessfulLogin()
    {
        $email = 'test@example.com';
        $password = 'password123';
        $hashedPassword = '$2y$10$hashedpassword';
        
        $userData = [
            'id' => 1,
            'email' => $email,
            'password' => $hashedPassword,
            'name' => 'Test User',
            'active' => 1
        ];

        // Mock repository to find user
        $this->mockAuthRepository
            ->method('findByEmail')
            ->with($email)
            ->willReturn($userData);

        // Mock password verification
        $this->mockPasswordHasher
            ->method('verify')
            ->with($password, $hashedPassword)
            ->willReturn(true);

        // Mock JWT token generation
        $this->mockJwtService
            ->method('generateToken')
            ->willReturn('mock.jwt.token');

        // Execute login
        $result = $this->authService->login($email, $password);

        // Assertions
        $this->assertTrue($result['success']);
        $this->assertEquals($email, $result['user']['email']);
        $this->assertEquals('Test User', $result['user']['name']);
        $this->assertEquals('mock.jwt.token', $result['token']);
        $this->assertArrayNotHasKey('password', $result['user']); // Password should be removed
    }

    public function testLoginWithInvalidEmail()
    {
        $email = 'nonexistent@example.com';
        $password = 'password123';

        // Mock repository to return null (user not found)
        $this->mockAuthRepository
            ->method('findByEmail')
            ->with($email)
            ->willReturn(null);

        $result = $this->authService->login($email, $password);

        $this->assertFalse($result['success']);
        $this->assertEquals('Invalid credentials', $result['error']);
    }

    public function testLoginWithWrongPassword()
    {
        $email = 'test@example.com';
        $password = 'wrongpassword';
        $hashedPassword = '$2y$10$hashedpassword';
        
        $userData = [
            'id' => 1,
            'email' => $email,
            'password' => $hashedPassword,
            'name' => 'Test User',
            'active' => 1
        ];

        $this->mockAuthRepository
            ->method('findByEmail')
            ->with($email)
            ->willReturn($userData);

        // Mock password verification to fail
        $this->mockPasswordHasher
            ->method('verify')
            ->with($password, $hashedPassword)
            ->willReturn(false);

        $result = $this->authService->login($email, $password);

        $this->assertFalse($result['success']);
        $this->assertEquals('Invalid credentials', $result['error']);
    }

    public function testLoginWithInactiveUser()
    {
        $email = 'inactive@example.com';
        $password = 'password123';
        
        $userData = [
            'id' => 1,
            'email' => $email,
            'password' => '$2y$10$hashedpassword',
            'name' => 'Inactive User',
            'active' => 0 // User is inactive
        ];

        $this->mockAuthRepository
            ->method('findByEmail')
            ->with($email)
            ->willReturn($userData);

        $result = $this->authService->login($email, $password);

        $this->assertFalse($result['success']);
        $this->assertEquals('Account is deactivated', $result['error']);
    }

    public function testSuccessfulRegistration()
    {
        $userData = [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123'
        ];

        // Mock email uniqueness check
        $this->mockAuthRepository
            ->method('findByEmail')
            ->with($userData['email'])
            ->willReturn(null);

        // Mock password hashing
        $this->mockPasswordHasher
            ->method('hash')
            ->with($userData['password'])
            ->willReturn('$2y$10$hashedpassword');

        // Mock user creation
        $this->mockAuthRepository
            ->method('create')
            ->willReturn([
                'id' => 2,
                'name' => $userData['name'],
                'email' => $userData['email'],
                'created_at' => date('Y-m-d H:i:s')
            ]);

        // Mock JWT token generation
        $this->mockJwtService
            ->method('generateToken')
            ->willReturn('mock.jwt.token');

        $result = $this->authService->register($userData);

        $this->assertTrue($result['success']);
        $this->assertEquals($userData['email'], $result['user']['email']);
        $this->assertEquals('mock.jwt.token', $result['token']);
    }

    public function testRegistrationWithExistingEmail()
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'existing@example.com',
            'password' => 'password123'
        ];

        // Mock repository to return existing user
        $this->mockAuthRepository
            ->method('findByEmail')
            ->with($userData['email'])
            ->willReturn([
                'id' => 1,
                'email' => $userData['email']
            ]);

        $result = $this->authService->register($userData);

        $this->assertFalse($result['success']);
        $this->assertEquals('Email already exists', $result['error']);
    }

    public function testTokenValidation()
    {
        $token = 'valid.jwt.token';
        $payload = [
            'user_id' => 1,
            'email' => 'test@example.com',
            'exp' => time() + 3600
        ];

        // Mock token verification
        $this->mockJwtService
            ->method('verify')
            ->with($token)
            ->willReturn($payload);

        $result = $this->authService->validateToken($token);

        $this->assertTrue($result['valid']);
        $this->assertEquals($payload, $result['payload']);
    }

    public function testTokenValidationWithInvalidToken()
    {
        $token = 'invalid.jwt.token';

        // Mock token verification to throw exception
        $this->mockJwtService
            ->method('verify')
            ->with($token)
            ->willThrowException(new \Exception('Invalid token'));

        $result = $this->authService->validateToken($token);

        $this->assertFalse($result['valid']);
        $this->assertEquals('Invalid token', $result['error']);
    }

    public function testForgotPassword()
    {
        $email = 'test@example.com';
        $userData = [
            'id' => 1,
            'email' => $email,
            'name' => 'Test User'
        ];

        // Mock user lookup
        $this->mockAuthRepository
            ->method('findByEmail')
            ->with($email)
            ->willReturn($userData);

        // Mock reset token creation
        $this->mockAuthRepository
            ->method('createPasswordResetToken')
            ->with(1)
            ->willReturn('reset_token_123');

        $result = $this->authService->forgotPassword($email);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString($email, $result['message']);
    }

    public function testForgotPasswordWithInvalidEmail()
    {
        $email = 'nonexistent@example.com';

        // Mock repository to return null
        $this->mockAuthRepository
            ->method('findByEmail')
            ->with($email)
            ->willReturn(null);

        $result = $this->authService->forgotPassword($email);

        $this->assertFalse($result['success']);
        $this->assertEquals('Email not found', $result['error']);
    }

    public function testResetPassword()
    {
        $token = 'valid_reset_token';
        $newPassword = 'newpassword123';
        $hashedPassword = '$2y$10$newhashedpassword';

        // Mock token validation
        $this->mockAuthRepository
            ->method('validatePasswordResetToken')
            ->with($token)
            ->willReturn(['user_id' => 1]);

        // Mock password hashing
        $this->mockPasswordHasher
            ->method('hash')
            ->with($newPassword)
            ->willReturn($hashedPassword);

        // Mock password update
        $this->mockAuthRepository
            ->method('updatePassword')
            ->with(1, $hashedPassword)
            ->willReturn(true);

        // Mock token cleanup
        $this->mockAuthRepository
            ->method('deletePasswordResetToken')
            ->with($token)
            ->willReturn(true);

        $result = $this->authService->resetPassword($token, $newPassword);

        $this->assertTrue($result['success']);
        $this->assertEquals('Password reset successfully', $result['message']);
    }

    public function testResetPasswordWithInvalidToken()
    {
        $token = 'invalid_reset_token';
        $newPassword = 'newpassword123';

        // Mock token validation to fail
        $this->mockAuthRepository
            ->method('validatePasswordResetToken')
            ->with($token)
            ->willReturn(null);

        $result = $this->authService->resetPassword($token, $newPassword);

        $this->assertFalse($result['success']);
        $this->assertEquals('Invalid or expired reset token', $result['error']);
    }

    public function testGetCurrentUser()
    {
        $userId = 1;
        $userData = [
            'id' => $userId,
            'email' => 'test@example.com',
            'name' => 'Test User',
            'created_at' => '2024-01-01 00:00:00'
        ];

        // Mock user retrieval
        $this->mockAuthRepository
            ->method('findById')
            ->with($userId)
            ->willReturn($userData);

        $result = $this->authService->getCurrentUser($userId);

        $this->assertEquals($userData, $result);
        $this->assertArrayNotHasKey('password', $result);
    }

    public function testLogout()
    {
        $token = 'valid.jwt.token';

        // Mock token blacklisting
        $this->mockAuthRepository
            ->method('blacklistToken')
            ->with($token)
            ->willReturn(true);

        $result = $this->authService->logout($token);

        $this->assertTrue($result['success']);
        $this->assertEquals('Logged out successfully', $result['message']);
    }

    public function testPasswordStrengthValidation()
    {
        // Test weak passwords
        $weakPasswords = ['123', 'password', 'abc', 'qwerty'];
        
        foreach ($weakPasswords as $password) {
            $isStrong = $this->authService->validatePasswordStrength($password);
            $this->assertFalse($isStrong, "Password '$password' should be considered weak");
        }

        // Test strong passwords
        $strongPasswords = ['Password123!', 'MyS3cur3P@ssw0rd', 'C0mpl3x!ty'];
        
        foreach ($strongPasswords as $password) {
            $isStrong = $this->authService->validatePasswordStrength($password);
            $this->assertTrue($isStrong, "Password '$password' should be considered strong");
        }
    }

    public function testEmailValidation()
    {
        // Test valid emails
        $validEmails = [
            'user@example.com',
            'test.email@domain.co.uk',
            'user+tag@example.org'
        ];
        
        foreach ($validEmails as $email) {
            $isValid = $this->authService->validateEmail($email);
            $this->assertTrue($isValid, "Email '$email' should be valid");
        }

        // Test invalid emails
        $invalidEmails = [
            'invalid-email',
            '@example.com',
            'user@',
            'user@.com'
        ];
        
        foreach ($invalidEmails as $email) {
            $isValid = $this->authService->validateEmail($email);
            $this->assertFalse($isValid, "Email '$email' should be invalid");
        }
    }
} 