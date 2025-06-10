<?php

namespace Tests\Integration;

use App\Infrastructure\Repository\AuthRepository;
use App\Domain\ValueObject\Email;
use App\Domain\Entity\User;

class AuthRepositoryIntegrationTest extends DatabaseTestCase
{
    private AuthRepository $authRepository;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create AuthRepository with real database
        $this->authRepository = new AuthRepository($this->database);
    }

    public function testCreateUserWithRealDatabase()
    {
        $name = 'Integration Test User';
        $email = new Email('integration@example.com');
        $password = '$2y$10$hashedpassword';

        $userId = $this->authRepository->createUser($name, $email, $password);

        $this->assertIsInt($userId);
        $this->assertGreaterThan(0, $userId);

        // Verify user was created
        $user = $this->authRepository->findUserByEmail($email);
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($name, $user->getName());
        $this->assertEquals($email, $user->getEmail());
    }

    public function testFindUserByEmailWithRealDatabase()
    {
        $email = new Email('test@example.com');
        
        $user = $this->authRepository->findUserByEmail($email);
        
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('Test User', $user->getName());
        $this->assertEquals($email, $user->getEmail());
    }

    public function testUpdatePasswordWithRealDatabase()
    {
        $newPassword = '$2y$10$newhashedpassword';
        
        $result = $this->authRepository->updatePassword(1, $newPassword);
        
        $this->assertTrue($result);
        
        // Verify password was updated
        $stmt = $this->pdo->prepare('SELECT password FROM users WHERE id = ?');
        $stmt->execute([1]);
        $storedPassword = $stmt->fetchColumn();
        
        $this->assertEquals($newPassword, $storedPassword);
    }

    public function testCreateEmailVerificationToken()
    {
        $userId = 1;
        $token = 'verification_token_' . uniqid();
        $expiresAt = new \DateTimeImmutable('+24 hours');

        $result = $this->authRepository->createEmailVerificationToken($userId, $token, $expiresAt);

        $this->assertTrue($result);

        // Verify token was created
        $stmt = $this->pdo->prepare('SELECT * FROM email_tokens WHERE user_id = ? AND token = ?');
        $stmt->execute([$userId, $token]);
        $tokenData = $stmt->fetch();

        $this->assertNotFalse($tokenData);
        $this->assertEquals('verification', $tokenData['type']);
        $this->assertEquals($token, $tokenData['token']);
    }

    public function testVerifyEmailTokenWithRealDatabase()
    {
        $userId = 1;
        $token = 'verify_token_' . uniqid();
        $expiresAt = new \DateTimeImmutable('+1 hour');

        // Create token first
        $this->authRepository->createEmailVerificationToken($userId, $token, $expiresAt);

        // Verify the token
        $user = $this->authRepository->verifyEmailToken($token);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals(1, $user->getId());

        // Verify token was marked as used
        $stmt = $this->pdo->prepare('SELECT used_at FROM email_tokens WHERE token = ?');
        $stmt->execute([$token]);
        $usedAt = $stmt->fetchColumn();
        $this->assertNotNull($usedAt);
    }

    public function testCreatePasswordResetTokenWithRealDatabase()
    {
        $userId = 1;
        $token = 'reset_token_' . uniqid();
        $expiresAt = new \DateTimeImmutable('+1 hour');

        $result = $this->authRepository->createPasswordResetToken($userId, $token, $expiresAt);

        $this->assertTrue($result);

        // Verify token exists
        $tokenData = $this->authRepository->findPasswordResetToken($token);
        $this->assertNotNull($tokenData);
        $this->assertEquals($userId, $tokenData['user_id']);
    }

    public function testLogLoginAttemptWithRealDatabase()
    {
        $email = 'test@example.com';
        $success = true;
        $ipAddress = '192.168.1.1';

        $this->authRepository->logLoginAttempt($email, $success, $ipAddress);

        // Verify login attempt was logged
        $stmt = $this->pdo->prepare('SELECT * FROM login_history WHERE email = ? ORDER BY created_at DESC LIMIT 1');
        $stmt->execute([$email]);
        $loginAttempt = $stmt->fetch();

        $this->assertNotFalse($loginAttempt);
        $this->assertEquals($email, $loginAttempt['email']);
        $this->assertEquals(1, $loginAttempt['success']);
        $this->assertEquals($ipAddress, $loginAttempt['ip_address']);
    }

    public function testGetFailedLoginAttemptsWithRealDatabase()
    {
        $email = 'failed@example.com';
        
        // Log some failed attempts
        for ($i = 0; $i < 3; $i++) {
            $this->authRepository->logLoginAttempt($email, false, '192.168.1.1');
        }

        // Use a time BEFORE the login attempts were made
        $beforeTime = new \DateTimeImmutable('-2 hours');
        $failedAttempts = $this->authRepository->getFailedLoginAttempts($email, $beforeTime);

        $this->assertEquals(3, $failedAttempts, "Should find 3 failed attempts since 2 hours ago");
    }

    public function testBlacklistTokenWithRealDatabase()
    {
        $token = 'jwt_token_to_blacklist_' . uniqid();

        $result = $this->authRepository->blacklistToken($token);

        $this->assertTrue($result);

        // Verify token is blacklisted
        $isBlacklisted = $this->authRepository->isTokenBlacklisted($token);
        $this->assertTrue($isBlacklisted);
    }

    public function testUpdateLastLoginWithRealDatabase()
    {
        $userId = 1;

        $result = $this->authRepository->updateLastLogin($userId);

        $this->assertTrue($result);

        // Verify last_login_at was updated
        $stmt = $this->pdo->prepare('SELECT last_login_at FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $lastLoginAt = $stmt->fetchColumn();

        $this->assertNotNull($lastLoginAt);
    }

    public function testDeleteExpiredTokensWithRealDatabase()
    {
        // Create an expired token
        $expiredToken = 'expired_token_' . uniqid();
        $expiredAt = new \DateTimeImmutable('-1 hour');
        
        $this->authRepository->createPasswordResetToken(1, $expiredToken, $expiredAt);

        // Create a valid token
        $validToken = 'valid_token_' . uniqid();
        $validAt = new \DateTimeImmutable('+1 hour');
        
        $this->authRepository->createPasswordResetToken(1, $validToken, $validAt);

        // Delete expired tokens
        $deletedCount = $this->authRepository->deleteExpiredTokens();

        $this->assertGreaterThan(0, $deletedCount);

        // Verify expired token is gone, valid token remains
        $this->assertNull($this->authRepository->findPasswordResetToken($expiredToken));
        $this->assertNotNull($this->authRepository->findPasswordResetToken($validToken));
    }

    public function testUserLoginHistoryWithRealDatabase()
    {
        $userId = 1;
        $email = 'test@example.com';

        // Create some login history
        for ($i = 0; $i < 5; $i++) {
            $this->authRepository->logLoginAttempt($email, true, '192.168.1.' . ($i + 1));
        }

        $history = $this->authRepository->getUserLoginHistory($userId, 3);

        $this->assertIsArray($history);
        $this->assertCount(3, $history); // Limited to 3
        $this->assertEquals($email, $history[0]['email']);
    }

    public function testCreateUserWithDuplicateEmailFails()
    {
        $name = 'Duplicate User';
        $email = new Email('test@example.com'); // Email that already exists
        $password = '$2y$10$hashedpassword';

        $this->expectException(\PDOException::class);

        $this->authRepository->createUser($name, $email, $password);
    }

    public function testTransactionRollbackOnError()
    {
        $initialUserCount = $this->pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();

        try {
            // This should fail due to duplicate email
            $this->authRepository->createUser('Test', new Email('test@example.com'), 'password');
        } catch (\PDOException $e) {
            // Expected
        }

        $finalUserCount = $this->pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();

        // User count should be unchanged due to transaction rollback
        $this->assertEquals($initialUserCount, $finalUserCount);
    }
} 