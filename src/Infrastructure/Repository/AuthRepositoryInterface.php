<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\User;
use App\Domain\ValueObject\Email;

/**
 * Infrastructure AuthRepositoryInterface for test compatibility
 * This interface extends the domain interface with additional test-specific methods
 */
interface AuthRepositoryInterface extends \App\Domain\Repository\AuthRepositoryInterface
{
    // Test compatibility aliases
    public function findByEmail(string $email): ?User;
    public function findById(int $id): ?User;
    public function create(array $userData): User;
    public function validatePasswordResetToken(string $token): bool;
    public function deletePasswordResetToken(string $token): bool;

    // Updated method signatures for enhanced security features
    public function blacklistToken(string $token, int $userId, \DateTimeInterface $expiresAt): bool;
    public function isTokenBlacklisted(string $token): bool;
    public function resetFailedLoginAttempts(string $email): bool;
    public function logUserAction(int $userId, string $action, array $metadata = []): bool;
    public function updateLastActivity(int $userId): bool;

    public function getLoginHistory(int $userId, int $limit = 10): array;
    public function recordLoginAttempt(string $email, bool $success, string $ipAddress): void;
    public function deactivateUser(int $userId): bool;
    public function activateUser(int $userId): bool;
    public function createWithTransaction(array $userData): User;
}
