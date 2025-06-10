<?php

namespace App\Domain\Service;

interface AuthServiceInterface
{
    /**
     * Login met e-mail en wachtwoord
     */
    public function login(string $email, string $password): array;

    /**
     * Registreer nieuwe gebruiker
     */
    public function register(string $email, string $password): array;

    /**
     * Verifieer JWT token
     */
    public function verifyToken(string $token): ?array;

    /**
     * Refresh JWT token
     */
    public function refresh(string $token): array;

    /**
     * Logout gebruiker
     */
    public function logout(): array;

    /**
     * Haal huidige gebruiker op basis van payload
     */
    public function getCurrentUser(array $payload): array;
}
