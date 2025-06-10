<?php

namespace App\Domain\Security;

interface JwtServiceInterface
{
    /**
     * Genereer een JWT token voor een gebruiker
     */
    public function generateToken(array $user): string;

    /**
     * Genereer JWT token met custom payload
     */
    public function generate(array $payload): string;

    /**
     * Verifieer en decode JWT token
     */
    public function verify(string $token): ?array;

    /**
     * Controleer of token geldig is
     */
    public function validateToken(string $token): bool;
}
