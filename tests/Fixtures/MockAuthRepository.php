<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use App\Domain\Repository\AuthRepositoryInterface;
use App\Domain\Entity\User;
use App\Domain\ValueObject\Email;

/**
 * Mock AuthRepository voor testing
 */
class MockAuthRepository implements AuthRepositoryInterface
{
    private array $users = [];
    private array $tokens = [];
    private int $nextId = 1;

    public function findUserByEmail(Email $email): ?User
    {
        foreach ($this->users as $userData) {
            if ($userData['email'] === $email->getValue()) {
                return new User(
                    $userData['id'],
                    $userData['name'],
                    $email,
                    $userData['password'],
                    $userData['role'] ?? 'user'
                );
            }
        }
        return null;
    }

    public function findUserByEmailAndPassword(Email $email, string $hashedPassword): ?User
    {
        $user = $this->findUserByEmail($email);
        if ($user && password_verify($hashedPassword, $user->getPassword())) {
            return $user;
        }
        return null;
    }

    public function createUser(string $name, Email $email, string $hashedPassword, string $role = 'user'): int
    {
        $id = $this->nextId++;
        $this->users[$id] = [
            'id' => $id,
            'name' => $name,
            'email' => $email->getValue(),
            'password' => $hashedPassword,
            'role' => $role,
            'created_at' => date('Y-m-d H:i:s')
        ];
        return $id;
    }

    public function updateLastLogin(int $userId): bool
    {
        if (isset($this->users[$userId])) {
            $this->users[$userId]['last_login'] = date('Y-m-d H:i:s');
            return true;
        }
        return false;
    }

    public function createEmailVerificationToken(int $userId, string $token, \DateTimeInterface $expiresAt): bool
    {
        $this->tokens[$token] = [
            'user_id' => $userId,
            'type' => 'email_verification',
            'expires_at' => $expiresAt->format('Y-m-d H:i:s')
        ];
        return true;
    }

    public function verifyEmailToken(string $token): ?User
    {
        if (isset($this->tokens[$token])) {
            $tokenData = $this->tokens[$token];
            if ($tokenData['type'] === 'email_verification') {
                $userId = $tokenData['user_id'];
                if (isset($this->users[$userId])) {
                    $userData = $this->users[$userId];
                    return new User(
                        $userData['id'],
                        $userData['name'],
                        new Email($userData['email']),
                        $userData['password'],
                        $userData['role'] ?? 'user'
                    );
                }
            }
        }
        return null;
    }

    public function createPasswordResetToken(int $userId, string $token, \DateTimeInterface $expiresAt): bool
    {
        $this->tokens[$token] = [
            'user_id' => $userId,
            'type' => 'password_reset',
            'expires_at' => $expiresAt->format('Y-m-d H:i:s')
        ];
        return true;
    }

    public function findPasswordResetToken(string $token): ?array
    {
        if (isset($this->tokens[$token]) && $this->tokens[$token]['type'] === 'password_reset') {
            return $this->tokens[$token];
        }
        return null;
    }

    public function deleteUsedToken(string $token): bool
    {
        if (isset($this->tokens[$token])) {
            unset($this->tokens[$token]);
            return true;
        }
        return false;
    }

    public function updatePassword(int $userId, string $hashedPassword): bool
    {
        if (isset($this->users[$userId])) {
            $this->users[$userId]['password'] = $hashedPassword;
            return true;
        }
        return false;
    }

    public function deleteExpiredTokens(): int
    {
        $deleted = 0;
        $now = new \DateTime();
        
        foreach ($this->tokens as $token => $tokenData) {
            $expiresAt = new \DateTime($tokenData['expires_at']);
            if ($expiresAt < $now) {
                unset($this->tokens[$token]);
                $deleted++;
            }
        }
        
        return $deleted;
    }

    public function getUserLoginHistory(int $userId, int $limit = 10): array
    {
        return []; // Mock implementation
    }

    public function logLoginAttempt(string $email, bool $success, string $ipAddress): void
    {
        // Mock implementation
    }

    public function getFailedLoginAttempts(string $email, \DateTimeInterface $since): int
    {
        return 0; // Mock implementation
    }

    // Helper methods for testing
    public function addUser(array $userData): void
    {
        $id = $userData['id'] ?? $this->nextId++;
        $this->users[$id] = $userData;
    }

    public function clearUsers(): void
    {
        $this->users = [];
        $this->nextId = 1;
    }

    public function clearTokens(): void
    {
        $this->tokens = [];
    }
} 