<?php

declare(strict_types=1);

namespace App\Domain\Service;

interface PasswordHasherInterface
{
    /**
     * Hash a password using secure hashing algorithm
     */
    public function hash(string $password): string;

    /**
     * Verify a password against its hash
     */
    public function verify(string $password, string $hash): bool;

    /**
     * Check if password hash needs rehashing due to algorithm changes
     */
    public function needsRehash(string $hash): bool;

    /**
     * Check if password meets strength requirements
     */
    public function isStrong(string $password, int $minLength = 8): bool;
} 