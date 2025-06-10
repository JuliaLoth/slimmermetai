<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\User;
use App\Domain\ValueObject\Email;

interface AuthRepositoryInterface
{
    public function findUserByEmail(Email $email): ?User;

    public function findUserByEmailAndPassword(Email $email, string $hashedPassword): ?User;

    public function createUser(string $name, Email $email, string $hashedPassword, string $role = 'user'): int;

    public function updateLastLogin(int $userId): bool;

    public function createEmailVerificationToken(int $userId, string $token, \DateTimeInterface $expiresAt): bool;

    public function verifyEmailToken(string $token): ?User;

    public function createPasswordResetToken(int $userId, string $token, \DateTimeInterface $expiresAt): bool;

    public function findPasswordResetToken(string $token): ?array;

    public function deleteUsedToken(string $token): bool;

    public function updatePassword(int $userId, string $hashedPassword): bool;

    public function deleteExpiredTokens(): int;

    public function getUserLoginHistory(int $userId, int $limit = 10): array;

    public function logLoginAttempt(string $email, bool $success, string $ipAddress, string $reason = ''): void;

    public function getFailedLoginAttempts(string $email, \DateTimeInterface $since): int;

    public function resetFailedLoginAttempts(string $email): bool;

    public function blacklistToken(string $token, int $userId, \DateTimeInterface $expiresAt): bool;

    public function isTokenBlacklisted(string $token): bool;

    public function logUserAction(int $userId, string $action, array $details = []): bool;

    public function updateLastActivity(int $userId): bool;
}
