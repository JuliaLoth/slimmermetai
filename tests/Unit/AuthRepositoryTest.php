<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Infrastructure\Repository\AuthRepository;
use App\Infrastructure\Database\Database;

class AuthRepositoryTest extends TestCase
{
    private AuthRepository $authRepository;
    private $mockDatabase;
    private $mockPdo;
    private $mockStatement;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create mocks
        $this->mockDatabase = $this->createMock(Database::class);
        $this->mockPdo = $this->createMock(\PDO::class);
        $this->mockStatement = $this->createMock(\PDOStatement::class);
        
        // Configure database mock to return PDO mock
        $this->mockDatabase
            ->method('getConnection')
            ->willReturn($this->mockPdo);
        
        // Create AuthRepository with mocked database
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
            'active' => 1,
            'created_at' => '2024-01-01 00:00:00'
        ];

        // Mock PDO statement
        $this->mockPdo
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->method('execute')
            ->with([$email])
            ->willReturn(true);

        $this->mockStatement
            ->method('fetch')
            ->willReturn($userData);

        $result = $this->authRepository->findByEmail($email);

        $this->assertEquals($userData, $result);
        $this->assertEquals($email, $result['email']);
    }

    public function testFindByEmailWithNonExistentUser()
    {
        $email = 'nonexistent@example.com';

        $this->mockPdo
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->method('execute')
            ->with([$email])
            ->willReturn(true);

        $this->mockStatement
            ->method('fetch')
            ->willReturn(false); // No user found

        $result = $this->authRepository->findByEmail($email);

        $this->assertNull($result);
    }

    public function testFindByIdWithExistingUser()
    {
        $userId = 1;
        $userData = [
            'id' => $userId,
            'email' => 'test@example.com',
            'name' => 'Test User',
            'active' => 1,
            'created_at' => '2024-01-01 00:00:00'
        ];

        $this->mockPdo
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->method('execute')
            ->with([$userId])
            ->willReturn(true);

        $this->mockStatement
            ->method('fetch')
            ->willReturn($userData);

        $result = $this->authRepository->findById($userId);

        $this->assertEquals($userData, $result);
        $this->assertEquals($userId, $result['id']);
    }

    public function testCreateUser()
    {
        $userData = [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => '$2y$10$hashedpassword'
        ];

        $newUserId = 2;

        // Mock prepare for insert
        $this->mockPdo
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->method('execute')
            ->willReturn(true);

        // Mock lastInsertId
        $this->mockPdo
            ->method('lastInsertId')
            ->willReturn($newUserId);

        // Mock second query to fetch created user
        $createdUserData = array_merge($userData, [
            'id' => $newUserId,
            'active' => 1,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        $this->mockStatement
            ->method('fetch')
            ->willReturn($createdUserData);

        $result = $this->authRepository->create($userData);

        $this->assertEquals($newUserId, $result['id']);
        $this->assertEquals($userData['email'], $result['email']);
        $this->assertEquals($userData['name'], $result['name']);
    }

    public function testUpdatePassword()
    {
        $userId = 1;
        $newPasswordHash = '$2y$10$newhashedpassword';

        $this->mockPdo
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->method('execute')
            ->with([$newPasswordHash, $userId])
            ->willReturn(true);

        $this->mockStatement
            ->method('rowCount')
            ->willReturn(1);

        $result = $this->authRepository->updatePassword($userId, $newPasswordHash);

        $this->assertTrue($result);
    }

    public function testUpdatePasswordWithInvalidUser()
    {
        $userId = 999; // Non-existent user
        $newPasswordHash = '$2y$10$newhashedpassword';

        $this->mockPdo
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->method('execute')
            ->with([$newPasswordHash, $userId])
            ->willReturn(true);

        $this->mockStatement
            ->method('rowCount')
            ->willReturn(0); // No rows affected

        $result = $this->authRepository->updatePassword($userId, $newPasswordHash);

        $this->assertFalse($result);
    }

    public function testCreatePasswordResetToken()
    {
        $userId = 1;
        $token = 'reset_token_123456';

        $this->mockPdo
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->method('execute')
            ->willReturn(true);

        $result = $this->authRepository->createPasswordResetToken($userId);

        $this->assertIsString($result);
        $this->assertEquals(64, strlen($result)); // Should be 64 character token
    }

    public function testValidatePasswordResetToken()
    {
        $token = 'valid_reset_token';
        $tokenData = [
            'user_id' => 1,
            'token' => $token,
            'expires_at' => date('Y-m-d H:i:s', time() + 3600),
            'created_at' => date('Y-m-d H:i:s')
        ];

        $this->mockPdo
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->method('execute')
            ->with([$token])
            ->willReturn(true);

        $this->mockStatement
            ->method('fetch')
            ->willReturn($tokenData);

        $result = $this->authRepository->validatePasswordResetToken($token);

        $this->assertEquals($tokenData, $result);
        $this->assertEquals(1, $result['user_id']);
    }

    public function testValidateExpiredPasswordResetToken()
    {
        $token = 'expired_reset_token';
        $tokenData = [
            'user_id' => 1,
            'token' => $token,
            'expires_at' => date('Y-m-d H:i:s', time() - 3600), // Expired 1 hour ago
            'created_at' => date('Y-m-d H:i:s', time() - 7200)
        ];

        $this->mockPdo
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->method('execute')
            ->with([$token])
            ->willReturn(true);

        $this->mockStatement
            ->method('fetch')
            ->willReturn($tokenData);

        $result = $this->authRepository->validatePasswordResetToken($token);

        $this->assertNull($result); // Should be null for expired token
    }

    public function testDeletePasswordResetToken()
    {
        $token = 'reset_token_to_delete';

        $this->mockPdo
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->method('execute')
            ->with([$token])
            ->willReturn(true);

        $this->mockStatement
            ->method('rowCount')
            ->willReturn(1);

        $result = $this->authRepository->deletePasswordResetToken($token);

        $this->assertTrue($result);
    }

    public function testBlacklistToken()
    {
        $token = 'jwt_token_to_blacklist';

        $this->mockPdo
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->method('execute')
            ->willReturn(true);

        $result = $this->authRepository->blacklistToken($token);

        $this->assertTrue($result);
    }

    public function testIsTokenBlacklisted()
    {
        $token = 'blacklisted_token';

        $this->mockPdo
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->method('execute')
            ->with([$token])
            ->willReturn(true);

        $this->mockStatement
            ->method('fetch')
            ->willReturn(['token' => $token]); // Token exists in blacklist

        $result = $this->authRepository->isTokenBlacklisted($token);

        $this->assertTrue($result);
    }

    public function testIsTokenNotBlacklisted()
    {
        $token = 'valid_token';

        $this->mockPdo
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->method('execute')
            ->with([$token])
            ->willReturn(true);

        $this->mockStatement
            ->method('fetch')
            ->willReturn(false); // Token not in blacklist

        $result = $this->authRepository->isTokenBlacklisted($token);

        $this->assertFalse($result);
    }

    public function testUpdateLastLogin()
    {
        $userId = 1;

        $this->mockPdo
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->method('execute')
            ->willReturn(true);

        $this->mockStatement
            ->method('rowCount')
            ->willReturn(1);

        $result = $this->authRepository->updateLastLogin($userId);

        $this->assertTrue($result);
    }

    public function testGetUserLoginHistory()
    {
        $userId = 1;
        $loginHistory = [
            [
                'id' => 1,
                'user_id' => $userId,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Mozilla/5.0...',
                'login_at' => '2024-01-01 12:00:00'
            ],
            [
                'id' => 2,
                'user_id' => $userId,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Mozilla/5.0...',
                'login_at' => '2024-01-02 12:00:00'
            ]
        ];

        $this->mockPdo
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->method('execute')
            ->with([$userId])
            ->willReturn(true);

        $this->mockStatement
            ->method('fetchAll')
            ->willReturn($loginHistory);

        $result = $this->authRepository->getLoginHistory($userId);

        $this->assertEquals($loginHistory, $result);
        $this->assertCount(2, $result);
    }

    public function testRecordLoginAttempt()
    {
        $email = 'test@example.com';
        $ipAddress = '127.0.0.1';
        $userAgent = 'Mozilla/5.0...';
        $successful = true;

        $this->mockPdo
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->method('execute')
            ->willReturn(true);

        $result = $this->authRepository->recordLoginAttempt(
            $email,
            $ipAddress,
            $userAgent,
            $successful
        );

        $this->assertTrue($result);
    }

    public function testGetFailedLoginAttempts()
    {
        $email = 'test@example.com';
        $timeWindow = 3600; // 1 hour
        $attempts = [
            [
                'email' => $email,
                'ip_address' => '127.0.0.1',
                'attempted_at' => date('Y-m-d H:i:s', time() - 1800)
            ]
        ];

        $this->mockPdo
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->method('execute')
            ->willReturn(true);

        $this->mockStatement
            ->method('fetchAll')
            ->willReturn($attempts);

        $result = $this->authRepository->getFailedLoginAttempts($email, $timeWindow);

        $this->assertEquals($attempts, $result);
        $this->assertCount(1, $result);
    }

    public function testDeactivateUser()
    {
        $userId = 1;

        $this->mockPdo
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->method('execute')
            ->with([0, $userId])
            ->willReturn(true);

        $this->mockStatement
            ->method('rowCount')
            ->willReturn(1);

        $result = $this->authRepository->deactivateUser($userId);

        $this->assertTrue($result);
    }

    public function testActivateUser()
    {
        $userId = 1;

        $this->mockPdo
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->method('execute')
            ->with([1, $userId])
            ->willReturn(true);

        $this->mockStatement
            ->method('rowCount')
            ->willReturn(1);

        $result = $this->authRepository->activateUser($userId);

        $this->assertTrue($result);
    }

    public function testDatabaseExceptionHandling()
    {
        $email = 'test@example.com';

        // Mock PDO to throw exception
        $this->mockPdo
            ->method('prepare')
            ->willThrowException(new \PDOException('Database connection failed'));

        $this->expectException(\PDOException::class);
        $this->expectExceptionMessage('Database connection failed');

        $this->authRepository->findByEmail($email);
    }

    public function testTransactionHandling()
    {
        $userData = [
            'name' => 'Transaction User',
            'email' => 'transaction@example.com',
            'password' => '$2y$10$hashedpassword'
        ];

        // Mock transaction methods
        $this->mockPdo
            ->expects($this->once())
            ->method('beginTransaction')
            ->willReturn(true);

        $this->mockPdo
            ->expects($this->once())
            ->method('commit')
            ->willReturn(true);

        $this->mockPdo
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->method('execute')
            ->willReturn(true);

        $this->mockPdo
            ->method('lastInsertId')
            ->willReturn(1);

        $this->mockStatement
            ->method('fetch')
            ->willReturn(array_merge($userData, ['id' => 1]));

        $result = $this->authRepository->createWithTransaction($userData);

        $this->assertNotNull($result);
        $this->assertEquals($userData['email'], $result['email']);
    }
} 