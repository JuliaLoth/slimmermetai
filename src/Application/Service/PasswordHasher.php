<?php

namespace App\Application\Service;

final class PasswordHasher
{
    private int $cost;
    public function __construct(int $cost = 12)
    {
        $this->cost = $cost;
    }

    public function hash(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => $this->cost]);
    }

    public function verify(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => $this->cost]);
    }

    public function isStrong(string $password, int $minLength = 8): bool
    {
        return strlen($password) >= $minLength && preg_match('/[A-Z]/', $password) && preg_match('/[a-z]/', $password) && preg_match('/\d/', $password);
    }
}
