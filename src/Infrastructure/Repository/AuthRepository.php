<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Repository\AuthRepositoryInterface as DomainAuthRepositoryInterface;
use App\Infrastructure\Repository\AuthRepositoryInterface;
use App\Domain\Entity\User;
use App\Domain\ValueObject\Email;
use App\Infrastructure\Database\DatabaseInterface;
use PDOException;

class AuthRepository implements DomainAuthRepositoryInterface, AuthRepositoryInterface
{
    public function __construct(private DatabaseInterface $database)
    {
    }

    public function findUserByEmail(Email $email): ?User
    {
        $row = $this->database->fetch(
            "SELECT * FROM users WHERE email = ? AND deleted_at IS NULL",
            [(string)$email]
        );

        return $row ? $this->hydrateUser($row) : null;
    }

    public function findUserByEmailAndPassword(Email $email, string $hashedPassword): ?User
    {
        // Note: Deze methode is deprecated - gebruik password_verify() in de service laag
        $user = $this->findUserByEmail($email);

        if ($user && password_verify($hashedPassword, $user->getPasswordHash())) {
            return $user;
        }

        return null;
    }

    public function createUser(string $name, Email $email, string $hashedPassword, string $role = 'user'): int
    {
        $this->database->beginTransaction();

        try {
            $userId = $this->database->query(
                "INSERT INTO users (name, email, password, role, created_at, updated_at) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)",
                [$name, (string)$email, $hashedPassword, $role]
            );

            $id = (int)$this->database->lastInsertId();

            $this->database->commit();

            return $id;
        } catch (PDOException $e) {
            $this->database->rollBack();
            throw $e;
        }
    }

    public function updateLastLogin(int $userId): bool
    {
        return $this->database->execute(
            "UPDATE users SET last_login_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
            [$userId]
        );
    }

    public function createEmailVerificationToken(int $userId, string $token, \DateTimeInterface $expiresAt): bool
    {
        return $this->database->execute(
            "INSERT INTO email_tokens (user_id, token, type, expires_at, created_at) VALUES (?, ?, 'verification', ?, CURRENT_TIMESTAMP)",
            [$userId, $token, $expiresAt->format('Y-m-d H:i:s')]
        );
    }

    public function verifyEmailToken(string $token): ?User
    {
        $this->database->beginTransaction();

        try {
            // Vind actieve token
            $tokenRow = $this->database->fetch(
                "SELECT et.*, u.* FROM email_tokens et 
                 JOIN users u ON et.user_id = u.id 
                 WHERE et.token = ? AND et.type = 'verification' 
                 AND et.expires_at > CURRENT_TIMESTAMP AND et.used_at IS NULL",
                [$token]
            );

            if (!$tokenRow) {
                $this->database->rollBack();
                return null;
            }

            // Markeer token als gebruikt
            $this->database->execute(
                "UPDATE email_tokens SET used_at = CURRENT_TIMESTAMP WHERE token = ?",
                [$token]
            );

            // Markeer gebruiker als geverifieerd
            $this->database->execute(
                "UPDATE users SET email_verified_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
                [$tokenRow['user_id']]
            );

            $this->database->commit();

            return $this->hydrateUser($tokenRow);
        } catch (PDOException $e) {
            $this->database->rollBack();
            throw $e;
        }
    }

    public function createPasswordResetToken(int $userId, string $token = null, \DateTimeInterface $expiresAt = null): bool
    {
        // Voor backward compatibility: als alleen userId wordt meegegeven, genereer token en expiry
        if ($token === null) {
            $token = bin2hex(random_bytes(32));
        }
        if ($expiresAt === null) {
            $expiresAt = new \DateTimeImmutable('+1 hour');
        }

        // Eerst oude tokens invalideren
        $this->database->execute(
            "UPDATE email_tokens SET used_at = CURRENT_TIMESTAMP WHERE user_id = ? AND type = 'password_reset' AND used_at IS NULL",
            [$userId]
        );

        return $this->database->execute(
            "INSERT INTO email_tokens (user_id, token, type, expires_at, created_at) VALUES (?, ?, 'password_reset', ?, CURRENT_TIMESTAMP)",
            [$userId, $token, $expiresAt->format('Y-m-d H:i:s')]
        );
    }

    public function findPasswordResetToken(string $token): ?array
    {
        return $this->database->fetch(
            "SELECT et.*, u.email, u.name FROM email_tokens et 
             JOIN users u ON et.user_id = u.id 
             WHERE et.token = ? AND et.type = 'password_reset' 
             AND et.expires_at > CURRENT_TIMESTAMP AND et.used_at IS NULL",
            [$token]
        );
    }

    public function deleteUsedToken(string $token): bool
    {
        return $this->database->execute(
            "UPDATE email_tokens SET used_at = CURRENT_TIMESTAMP WHERE token = ?",
            [$token]
        );
    }

    public function updatePassword(int $userId, string $hashedPassword): bool
    {
        return $this->database->execute(
            "UPDATE users SET password = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
            [$hashedPassword, $userId]
        );
    }

    public function deleteExpiredTokens(): int
    {
        $stmt = $this->database->query(
            "DELETE FROM email_tokens WHERE expires_at < CURRENT_TIMESTAMP OR used_at IS NOT NULL"
        );

        return $stmt->rowCount();
    }

    public function getUserLoginHistory(int $userId, int $limit = 10): array
    {
        return $this->database->fetchAll(
            "SELECT * FROM login_history WHERE user_id = ? ORDER BY created_at DESC LIMIT ?",
            [$userId, $limit]
        );
    }

    public function logLoginAttempt(string $email, bool $success, string $ipAddress): void
    {
        // Find user_id for this email to maintain consistency
        $user = $this->findUserByEmail(new Email($email));
        $userId = $user ? $user->getId() : null;

        $this->database->execute(
            "INSERT INTO login_history (user_id, email, success, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)",
            [$userId, $email, $success ? 1 : 0, $ipAddress, $_SERVER['HTTP_USER_AGENT'] ?? '']
        );
    }

    public function getFailedLoginAttempts(string $email, \DateTimeInterface|int $since): int
    {
        // Support voor int (timestamp) of DateTimeInterface
        if (is_int($since)) {
            $since = new \DateTimeImmutable("@{$since}");
        }

        // Simple datetime comparison - works with both SQLite and MySQL
        $result = $this->database->fetch(
            "SELECT COUNT(*) as count FROM login_history WHERE email = ? AND success = 0 AND created_at >= ?",
            [$email, $since->format('Y-m-d H:i:s')]
        );

        return (int)($result['count'] ?? 0);
    }

    // Additional methods for test compatibility and expanded functionality

    /**
     * Alias for findUserByEmail for test compatibility
     */
    public function findByEmail(string $email): ?User
    {
        return $this->findUserByEmail(new Email($email));
    }

    /**
     * Alias for findUserById
     */
    public function findById(int $id): ?User
    {
        $row = $this->database->fetch(
            "SELECT * FROM users WHERE id = ? AND deleted_at IS NULL",
            [$id]
        );

        return $row ? $this->hydrateUser($row) : null;
    }

    /**
     * Alias for createUser for test compatibility
     */
    public function create(array $userData): User
    {
        $name = $userData['name'] ?? '';
        $email = new Email($userData['email']);
        $password = $userData['password'] ?? '';
        $role = $userData['role'] ?? 'user';

        $userId = $this->createUser($name, $email, $password, $role);

        $user = $this->findById($userId);
        if (!$user) {
            throw new \RuntimeException("Failed to create user or retrieve created user");
        }

        return $user;
    }

    /**
     * Validate password reset token
     */
    public function validatePasswordResetToken(string $token): bool
    {
        $tokenData = $this->findPasswordResetToken($token);
        return $tokenData !== null;
    }

    /**
     * Delete password reset token
     */
    public function deletePasswordResetToken(string $token): bool
    {
        return $this->deleteUsedToken($token);
    }

    /**
     * Blacklist a JWT token
     */
    public function blacklistToken(string $token): bool
    {
        return $this->database->execute(
            "INSERT INTO blacklisted_tokens (token, blacklisted_at) VALUES (?, CURRENT_TIMESTAMP)",
            [$token]
        );
    }

    /**
     * Check if token is blacklisted
     */
    public function isTokenBlacklisted(string $token): bool
    {
        $result = $this->database->fetch(
            "SELECT COUNT(*) as count FROM blacklisted_tokens WHERE token = ?",
            [$token]
        );

        return (int)($result['count'] ?? 0) > 0;
    }

    /**
     * Get login history (alias for getUserLoginHistory)
     */
    public function getLoginHistory(int $userId, int $limit = 10): array
    {
        return $this->getUserLoginHistory($userId, $limit);
    }

    /**
     * Record login attempt (alias for logLoginAttempt)
     */
    public function recordLoginAttempt(string $email, bool $success, string $ipAddress): void
    {
        $this->logLoginAttempt($email, $success, $ipAddress);
    }

    /**
     * Deactivate user account
     */
    public function deactivateUser(int $userId): bool
    {
        return $this->database->execute(
            "UPDATE users SET active = 0, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
            [$userId]
        );
    }

    /**
     * Activate user account
     */
    public function activateUser(int $userId): bool
    {
        return $this->database->execute(
            "UPDATE users SET active = 1, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
            [$userId]
        );
    }

    /**
     * Create user within transaction for test purposes
     */
    public function createWithTransaction(array $userData): User
    {
        $this->database->beginTransaction();

        try {
            $user = $this->create($userData);
            $this->database->commit();
            return $user;
        } catch (\Exception $e) {
            $this->database->rollBack();
            throw $e;
        }
    }

    private function hydrateUser(array $row): User
    {
        return new User(
            new Email($row['email']),
            $row['password'],
            (int)$row['id'],
            $row['name'] ?? '',
            $row['role'] ?? 'user',
            new \DateTimeImmutable($row['created_at'] ?? 'now')
        );
    }
}
