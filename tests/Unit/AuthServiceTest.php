<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Application\Service\AuthService;
use App\Domain\Security\JwtServiceInterface;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\Service\PasswordHasherInterface;
use App\Domain\Logging\ErrorLoggerInterface;
use App\Domain\Entity\User;
use App\Domain\ValueObject\Email;

class AuthServiceTest extends TestCase
{
    private AuthService $authService;
    private $mockUserRepository;
    private $mockJwtService;
    private $mockPasswordHasher;
    private $mockErrorLogger;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create mocks for correct interfaces
        $this->mockUserRepository = $this->createMock(UserRepositoryInterface::class);
        $this->mockPasswordHasher = $this->createMock(PasswordHasherInterface::class);
        $this->mockJwtService = $this->createMock(JwtServiceInterface::class);
        $this->mockErrorLogger = $this->createMock(ErrorLoggerInterface::class);
        
        // Create AuthService with correct mocked dependencies
        $this->authService = new AuthService(
            $this->mockUserRepository,
            $this->mockPasswordHasher,
            $this->mockJwtService,
            $this->mockErrorLogger
        );
    }

    public function testSuccessfulLogin()
    {
        $email = 'test@example.com';
        $password = 'password123';
        $hashedPassword = '$2y$10$hashedpassword';
        
        // Create mock User entity
        $mockUser = $this->createMock(User::class);
        $mockUser->method('getId')->willReturn(1);
        $mockUser->method('getEmail')->willReturn(new Email($email));
        $mockUser->method('getPasswordHash')->willReturn($hashedPassword);

        // Mock repository to find user by Email value object
        $this->mockUserRepository
            ->method('byEmail')
            ->with($this->callback(function($emailObj) use ($email) {
                return $emailObj instanceof Email && (string)$emailObj === $email;
            }))
            ->willReturn($mockUser);

        // Mock password verification
        $this->mockPasswordHasher
            ->method('verify')
            ->with($password, $hashedPassword)
            ->willReturn(true);

        // Mock JWT token generation
        $this->mockJwtService
            ->method('generate')
            ->willReturn('mock.jwt.token');

        // Execute login
        $result = $this->authService->login($email, $password);

        // Assertions
        $this->assertTrue($result['success']);
        $this->assertEquals($email, $result['user']['email']);
        $this->assertEquals('mock.jwt.token', $result['tokens']['access_token']);
        $this->assertArrayHasKey('expires_at', $result['tokens']);
    }

    public function testLoginWithInvalidEmail()
    {
        $email = 'nonexistent@example.com';
        $password = 'password123';

        // Mock repository to return null (user not found)
        $this->mockUserRepository
            ->method('byEmail')
            ->willReturn(null);

        $result = $this->authService->login($email, $password);

        $this->assertFalse($result['success']);
        $this->assertEquals('Invalid credentials', $result['message']);
    }

    public function testLoginWithWrongPassword()
    {
        $email = 'test@example.com';
        $password = 'wrongpassword';
        $hashedPassword = '$2y$10$hashedpassword';
        
        $mockUser = $this->createMock(User::class);
        $mockUser->method('getPasswordHash')->willReturn($hashedPassword);

        $this->mockUserRepository
            ->method('byEmail')
            ->willReturn($mockUser);

        // Mock password verification to fail
        $this->mockPasswordHasher
            ->method('verify')
            ->with($password, $hashedPassword)
            ->willReturn(false);

        $result = $this->authService->login($email, $password);

        $this->assertFalse($result['success']);
        $this->assertEquals('Invalid credentials', $result['message']);
    }

    public function testSuccessfulRegistration()
    {
        $email = 'newuser@example.com';
        $password = 'Password123!';
        $hashedPassword = '$2y$10$hashedpassword';

        // Mock email uniqueness check (no existing user)
        $this->mockUserRepository
            ->method('byEmail')
            ->willReturn(null);

        // Mock password strength validation
        $this->mockPasswordHasher
            ->method('isStrong')
            ->with($password)
            ->willReturn(true);

        // Mock password hashing
        $this->mockPasswordHasher
            ->method('hash')
            ->with($password)
            ->willReturn($hashedPassword);

        // Mock user save operation
        $this->mockUserRepository
            ->expects($this->once())
            ->method('save');

        // Mock JWT token generation
        $this->mockJwtService
            ->method('generate')
            ->willReturn('mock.jwt.token');

        $result = $this->authService->register($email, $password);

        $this->assertTrue($result['success']);
        $this->assertEquals('mock.jwt.token', $result['tokens']['access_token']);
        $this->assertEquals($email, $result['user']['email']);
    }

    public function testRegistrationWithExistingEmail()
    {
        $email = 'existing@example.com';
        $password = 'Password123!';

        $mockUser = $this->createMock(User::class);

        // Mock password strength check first (AuthService checks this first)
        $this->mockPasswordHasher
            ->method('isStrong')
            ->with($password)
            ->willReturn(true);

        // Mock repository to return existing user
        $this->mockUserRepository
            ->method('byEmail')
            ->willReturn($mockUser);

        $result = $this->authService->register($email, $password);

        $this->assertFalse($result['success']);
        $this->assertEquals('Email exists', $result['message']);
    }

    public function testRegistrationWithWeakPassword()
    {
        $email = 'test@example.com';
        $password = '123';

        // Mock repository - no existing user
        $this->mockUserRepository
            ->method('byEmail')
            ->willReturn(null);

        // Mock password strength validation to fail
        $this->mockPasswordHasher
            ->method('isStrong')
            ->with($password)
            ->willReturn(false);

        $result = $this->authService->register($email, $password);

        $this->assertFalse($result['success']);
        $this->assertEquals('Password not strong enough', $result['message']);
    }

    public function testTokenVerification()
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

        $result = $this->authService->verifyToken($token);

        $this->assertEquals($payload, $result);
    }

    public function testTokenVerificationWithInvalidToken()
    {
        $token = 'invalid.jwt.token';

        // Mock token verification to return null
        $this->mockJwtService
            ->method('verify')
            ->with($token)
            ->willReturn(null);

        $result = $this->authService->verifyToken($token);

        $this->assertNull($result);
    }

    public function testTokenRefresh()
    {
        $token = 'valid.refresh.token';
        $payload = [
            'user_id' => 1,
            'email' => 'test@example.com'
        ];

        // Mock token verification (called once in verifyToken, once in refresh)
        $this->mockJwtService
            ->method('verify')
            ->willReturn($payload);

        // Mock new token generation
        $this->mockJwtService
            ->method('generate')
            ->willReturn('new.access.token');

        $result = $this->authService->refresh($token);

        $this->assertTrue($result['success']);
        $this->assertEquals('new.access.token', $result['access_token']);
        $this->assertArrayHasKey('expires_at', $result);
    }

    public function testTokenRefreshWithInvalidToken()
    {
        $token = 'invalid.refresh.token';

        // Mock token verification to return null
        $this->mockJwtService
            ->method('verify')
            ->willReturn(null);

        $result = $this->authService->refresh($token);

        $this->assertFalse($result['success']);
        $this->assertEquals('Invalid token', $result['message']);
    }

    public function testLogout()
    {
        $result = $this->authService->logout();

        $this->assertTrue($result['success']);
    }

    public function testGetCurrentUser()
    {
        $payload = [
            'user_id' => 1,
            'email' => 'test@example.com'
        ];

        $result = $this->authService->getCurrentUser($payload);

        $this->assertEquals(1, $result['id']);
        $this->assertEquals('test@example.com', $result['email']);
    }

    public function testGetCurrentUserWithMissingData()
    {
        $payload = []; // Empty payload

        $result = $this->authService->getCurrentUser($payload);

        $this->assertNull($result['id']);
        $this->assertNull($result['email']);
    }
} 