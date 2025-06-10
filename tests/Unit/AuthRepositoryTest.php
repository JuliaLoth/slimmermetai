<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Infrastructure\Repository\AuthRepository;
use App\Infrastructure\Database\DatabaseInterface;
use App\Domain\Entity\User;
use App\Domain\ValueObject\Email;

class AuthRepositoryTest extends TestCase
{
    private AuthRepository $authRepository;
    private $mockDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create mock for DatabaseInterface (not Database class)
        $this->mockDatabase = $this->createMock(DatabaseInterface::class);
        
        // Create AuthRepository with mocked database interface
        $this->authRepository = new AuthRepository($this->mockDatabase);
    }

    public function testFindByEmailWithExistingUser()
    {
        $email = 'test@example.com';
        $userData = [
            'id' => 1,
            'email' => $email,
            'password' => '$2y$10$hashedpassword',
            'name' => 'Test User',
            'role' => 'user',
            'created_at' => '2024-01-01 00:00:00'
        ];

        // Mock database fetch to return user data array
        $this->mockDatabase
            ->method('fetch')
            ->willReturn($userData);

        $result = $this->authRepository->findByEmail($email);

        // Should return User entity, not array
        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals($email, (string)$result->getEmail());
        $this->assertEquals(1, $result->getId());
    }

    public function testFindByEmailWithNonExistentUser()
    {
        $email = 'nonexistent@example.com';

        // Mock database to return null (no user found)
        $this->mockDatabase
            ->method('fetch')
            ->willReturn(null);

        $result = $this->authRepository->findByEmail($email);

        $this->assertNull($result);
    }

    public function testFindUserByEmailWithEmailValueObject()
    {
        $email = 'test@example.com';
        $emailVO = new Email($email);
        $userData = [
            'id' => 1,
            'email' => $email,
            'password' => '$2y$10$hashedpassword',
            'name' => 'Test User',
            'role' => 'user',
            'created_at' => '2024-01-01 00:00:00'
        ];

        $this->mockDatabase
            ->method('fetch')
            ->willReturn($userData);

        $result = $this->authRepository->findUserByEmail($emailVO);

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals($email, (string)$result->getEmail());
    }

    public function testFindByIdWithExistingUser()
    {
        $userId = 1;
        $userData = [
            'id' => $userId,
            'email' => 'test@example.com',
            'password' => '$2y$10$hashedpassword',
            'name' => 'Test User',
            'role' => 'user',
            'created_at' => '2024-01-01 00:00:00'
        ];

        $this->mockDatabase
            ->method('fetch')
            ->willReturn($userData);

        $result = $this->authRepository->findById($userId);

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals($userId, $result->getId());
    }

    public function testCreateUser()
    {
        $userData = [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => '$2y$10$hashedpassword',
            'role' => 'user'
        ];

        $newUserId = 2;

        // Mock database operations for user creation
        $this->mockDatabase
            ->method('beginTransaction')
            ->willReturn(true);

        $mockStatement = $this->createMock(\PDOStatement::class);
        $this->mockDatabase
            ->method('query')
            ->willReturn($mockStatement);

        $this->mockDatabase
            ->method('lastInsertId')
            ->willReturn((string)$newUserId);

        $this->mockDatabase
            ->method('commit')
            ->willReturn(true);

        // Mock fetch for retrieving created user
        $createdUserData = array_merge($userData, [
            'id' => $newUserId,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        $this->mockDatabase
            ->method('fetch')
            ->willReturn($createdUserData);

        $result = $this->authRepository->create($userData);

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals($userData['email'], (string)$result->getEmail());
        $this->assertEquals($userData['name'], $result->getName());
    }

    public function testCreateUserWithTransaction()
    {
        $userData = [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => '$2y$10$hashedpassword'
        ];

        // Mock transaction methods (may be called multiple times)
        $this->mockDatabase
            ->method('beginTransaction')
            ->willReturn(true);

        $this->mockDatabase
            ->method('commit')
            ->willReturn(true);

        // Mock database operations
        $mockStatement = $this->createMock(\PDOStatement::class);
        $this->mockDatabase
            ->method('query')
            ->willReturn($mockStatement);

        $this->mockDatabase
            ->method('lastInsertId')
            ->willReturn('1');

        $this->mockDatabase
            ->method('fetch')
            ->willReturn(array_merge($userData, ['id' => 1, 'role' => 'user', 'created_at' => date('Y-m-d H:i:s')]));

        $result = $this->authRepository->createWithTransaction($userData);

        $this->assertInstanceOf(User::class, $result);
    }

    public function testUpdatePassword()
    {
        $userId = 1;
        $newPasswordHash = '$2y$10$newhashedpassword';

        $this->mockDatabase
            ->method('execute')
            ->willReturn(true);

        $result = $this->authRepository->updatePassword($userId, $newPasswordHash);

        $this->assertTrue($result);
    }

    public function testUpdateLastLogin()
    {
        $userId = 1;

        $this->mockDatabase
            ->method('execute')
            ->willReturn(true);

        $result = $this->authRepository->updateLastLogin($userId);

        $this->assertTrue($result);
    }

    public function testCreatePasswordResetToken()
    {
        $userId = 1;

        $this->mockDatabase
            ->method('execute')
            ->willReturn(true);

        $result = $this->authRepository->createPasswordResetToken($userId);

        $this->assertTrue($result);
    }

    public function testFindPasswordResetToken()
    {
        $token = 'valid_reset_token';
        $tokenData = [
            'user_id' => 1,
            'token' => $token,
            'type' => 'password_reset',
            'expires_at' => date('Y-m-d H:i:s', time() + 3600),
            'email' => 'test@example.com',
            'name' => 'Test User'
        ];

        $this->mockDatabase
            ->method('fetch')
            ->willReturn($tokenData);

        $result = $this->authRepository->findPasswordResetToken($token);

        $this->assertEquals($tokenData, $result);
        $this->assertEquals(1, $result['user_id']);
    }

    public function testValidatePasswordResetToken()
    {
        $token = 'valid_reset_token';
        $tokenData = [
            'user_id' => 1,
            'token' => $token,
            'type' => 'password_reset',
            'expires_at' => date('Y-m-d H:i:s', time() + 3600)
        ];

        $this->mockDatabase
            ->method('fetch')
            ->willReturn($tokenData);

        $result = $this->authRepository->validatePasswordResetToken($token);

        $this->assertTrue($result);
    }

    public function testValidateExpiredPasswordResetToken()
    {
        $token = 'expired_reset_token';

        // Mock database to return null for expired token
        $this->mockDatabase
            ->method('fetch')
            ->willReturn(null);

        $result = $this->authRepository->validatePasswordResetToken($token);

        $this->assertFalse($result);
    }

    public function testDeletePasswordResetToken()
    {
        $token = 'reset_token_to_delete';

        $this->mockDatabase
            ->method('execute')
            ->willReturn(true);

        $result = $this->authRepository->deletePasswordResetToken($token);

        $this->assertTrue($result);
    }

    public function testBlacklistToken()
    {
        $token = 'jwt_token_to_blacklist';

        $this->mockDatabase
            ->method('execute')
            ->willReturn(true);

        $result = $this->authRepository->blacklistToken($token);

        $this->assertTrue($result);
    }

    public function testIsTokenBlacklisted()
    {
        $token = 'blacklisted_token';

        $this->mockDatabase
            ->method('fetch')
            ->willReturn(['count' => 1]); // Token exists in blacklist

        $result = $this->authRepository->isTokenBlacklisted($token);

        $this->assertTrue($result);
    }

    public function testIsTokenNotBlacklisted()
    {
        $token = 'valid_token';

        $this->mockDatabase
            ->method('fetch')
            ->willReturn(['count' => 0]); // Token not in blacklist

        $result = $this->authRepository->isTokenBlacklisted($token);

        $this->assertFalse($result);
    }

    public function testGetUserLoginHistory()
    {
        $userId = 1;
        $loginHistory = [
            [
                'id' => 1,
                'user_id' => $userId,
                'email' => 'test@example.com',
                'success' => 1,
                'ip_address' => '127.0.0.1',
                'created_at' => '2024-01-01 12:00:00'
            ],
            [
                'id' => 2,
                'user_id' => $userId,
                'email' => 'test@example.com',
                'success' => 1,
                'ip_address' => '127.0.0.1',
                'created_at' => '2024-01-02 12:00:00'
            ]
        ];

        $this->mockDatabase
            ->method('fetchAll')
            ->willReturn($loginHistory);

        $result = $this->authRepository->getUserLoginHistory($userId);

        $this->assertEquals($loginHistory, $result);
        $this->assertCount(2, $result);
    }

    public function testGetLoginHistory()
    {
        // Test alias method
        $userId = 1;
        $loginHistory = [
            [
                'id' => 1,
                'user_id' => $userId,
                'email' => 'test@example.com',
                'success' => 1,
                'ip_address' => '127.0.0.1',
                'created_at' => '2024-01-01 12:00:00'
            ]
        ];

        $this->mockDatabase
            ->method('fetchAll')
            ->willReturn($loginHistory);

        $result = $this->authRepository->getLoginHistory($userId);

        $this->assertEquals($loginHistory, $result);
        $this->assertCount(1, $result);
    }

    public function testLogLoginAttempt()
    {
        $email = 'test@example.com';
        $success = true;
        $ipAddress = '127.0.0.1';

        $this->mockDatabase
            ->method('execute')
            ->willReturn(true);

        // Should not throw exception
        $this->authRepository->logLoginAttempt($email, $success, $ipAddress);
        $this->assertTrue(true); // If we reach here, the method worked
    }

    public function testRecordLoginAttempt()
    {
        $email = 'test@example.com';
        $success = true;
        $ipAddress = '127.0.0.1';

        $this->mockDatabase
            ->method('execute')
            ->willReturn(true);

        // Should not throw exception (void method)
        $this->authRepository->recordLoginAttempt($email, $success, $ipAddress);
        $this->assertTrue(true); // If we reach here, the method worked
    }

    public function testGetFailedLoginAttempts()
    {
        $email = 'test@example.com';
        $since = time() - 3600; // 1 hour ago

        $this->mockDatabase
            ->method('fetch')
            ->willReturn(['count' => 3]);

        $result = $this->authRepository->getFailedLoginAttempts($email, $since);

        $this->assertEquals(3, $result);
    }

    public function testDeactivateUser()
    {
        $userId = 1;

        $this->mockDatabase
            ->method('execute')
            ->willReturn(true);

        $result = $this->authRepository->deactivateUser($userId);

        $this->assertTrue($result);
    }

    public function testActivateUser()
    {
        $userId = 1;

        $this->mockDatabase
            ->method('execute')
            ->willReturn(true);

        $result = $this->authRepository->activateUser($userId);

        $this->assertTrue($result);
    }

    public function testDeleteExpiredTokens()
    {
        $mockStatement = $this->createMock(\PDOStatement::class);
        $mockStatement
            ->method('rowCount')
            ->willReturn(5);

        $this->mockDatabase
            ->method('query')
            ->willReturn($mockStatement);

        $result = $this->authRepository->deleteExpiredTokens();

        $this->assertEquals(5, $result);
    }

    public function testCreateEmailVerificationToken()
    {
        $userId = 1;
        $token = 'verification_token_123';
        $expiresAt = new \DateTimeImmutable('+24 hours');

        $this->mockDatabase
            ->method('execute')
            ->willReturn(true);

        $result = $this->authRepository->createEmailVerificationToken($userId, $token, $expiresAt);

        $this->assertTrue($result);
    }

    public function testVerifyEmailToken()
    {
        $token = 'valid_verification_token';
        $userData = [
            'id' => 1,
            'user_id' => 1,
            'email' => 'test@example.com',
            'password' => '$2y$10$hashedpassword',
            'name' => 'Test User',
            'role' => 'user',
            'created_at' => '2024-01-01 00:00:00'
        ];

        $this->mockDatabase
            ->method('beginTransaction')
            ->willReturn(true);

        $this->mockDatabase
            ->method('fetch')
            ->willReturn($userData);

        $this->mockDatabase
            ->method('execute')
            ->willReturn(true);

        $this->mockDatabase
            ->method('commit')
            ->willReturn(true);

        $result = $this->authRepository->verifyEmailToken($token);

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals('test@example.com', (string)$result->getEmail());
    }
} 