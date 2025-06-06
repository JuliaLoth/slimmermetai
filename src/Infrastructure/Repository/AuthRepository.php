<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Repository\AuthRepositoryInterface;
use App\Domain\Entity\User;
use App\Domain\ValueObject\Email;
use App\Infrastructure\Database\DatabaseInterface;
use PDOException;

class AuthRepository implements AuthRepositoryInterface
{
    public function __construct(private DatabaseInterface $database) {}

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
                "INSERT INTO users (name, email, password, role, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())",
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
            "UPDATE users SET last_login_at = NOW(), updated_at = NOW() WHERE id = ?",
            [$userId]
        );
    }

    public function createEmailVerificationToken(int $userId, string $token, \DateTimeInterface $expiresAt): bool
    {
        return $this->database->execute(
            "INSERT INTO email_tokens (user_id, token, type, expires_at, created_at) VALUES (?, ?, 'verification', ?, NOW())",
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
                 AND et.expires_at > NOW() AND et.used_at IS NULL",
                [$token]
            );
            
            if (!$tokenRow) {
                $this->database->rollBack();
                return null;
            }
            
            // Markeer token als gebruikt
            $this->database->execute(
                "UPDATE email_tokens SET used_at = NOW() WHERE token = ?",
                [$token]
            );
            
            // Markeer gebruiker als geverifieerd
            $this->database->execute(
                "UPDATE users SET email_verified_at = NOW(), updated_at = NOW() WHERE id = ?",
                [$tokenRow['user_id']]
            );
            
            $this->database->commit();
            
            return $this->hydrateUser($tokenRow);
            
        } catch (PDOException $e) {
            $this->database->rollBack();
            throw $e;
        }
    }

    public function createPasswordResetToken(int $userId, string $token, \DateTimeInterface $expiresAt): bool
    {
        // Eerst oude tokens invalideren
        $this->database->execute(
            "UPDATE email_tokens SET used_at = NOW() WHERE user_id = ? AND type = 'password_reset' AND used_at IS NULL",
            [$userId]
        );
        
        return $this->database->execute(
            "INSERT INTO email_tokens (user_id, token, type, expires_at, created_at) VALUES (?, ?, 'password_reset', ?, NOW())",
            [$userId, $token, $expiresAt->format('Y-m-d H:i:s')]
        );
    }

    public function findPasswordResetToken(string $token): ?array
    {
        return $this->database->fetch(
            "SELECT et.*, u.email, u.name FROM email_tokens et 
             JOIN users u ON et.user_id = u.id 
             WHERE et.token = ? AND et.type = 'password_reset' 
             AND et.expires_at > NOW() AND et.used_at IS NULL",
            [$token]
        );
    }

    public function deleteUsedToken(string $token): bool
    {
        return $this->database->execute(
            "UPDATE email_tokens SET used_at = NOW() WHERE token = ?",
            [$token]
        );
    }

    public function updatePassword(int $userId, string $hashedPassword): bool
    {
        return $this->database->execute(
            "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?",
            [$hashedPassword, $userId]
        );
    }

    public function deleteExpiredTokens(): int
    {
        $stmt = $this->database->query(
            "DELETE FROM email_tokens WHERE expires_at < NOW() OR used_at IS NOT NULL"
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
        $this->database->execute(
            "INSERT INTO login_history (email, success, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, NOW())",
            [$email, $success ? 1 : 0, $ipAddress, $_SERVER['HTTP_USER_AGENT'] ?? '']
        );
    }

    public function getFailedLoginAttempts(string $email, \DateTimeInterface $since): int
    {
        $result = $this->database->fetch(
            "SELECT COUNT(*) as count FROM login_history WHERE email = ? AND success = 0 AND created_at > ?",
            [$email, $since->format('Y-m-d H:i:s')]
        );
        
        return (int)($result['count'] ?? 0);
    }

    private function hydrateUser(array $row): User
    {
        return new User(
            new Email($row['email']),
            $row['password'],
            (int)$row['id'],
            new \DateTimeImmutable($row['created_at']),
            $row['name'] ?? '',
            $row['role'] ?? 'user'
        );
    }
} 