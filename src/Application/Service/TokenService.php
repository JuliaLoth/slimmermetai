<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Infrastructure\Database\DatabaseInterface;

class TokenService
{
    private DatabaseInterface $database;

    public function __construct(DatabaseInterface $database)
    {
        $this->database = $database;
    }

    /**
     * Genereer refresh token voor gebruiker
     */
    public function generateRefreshToken(int $userId): string
    {
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+30 days'));

        // Bestaande tokens verwijderen
        $this->database->execute("DELETE FROM refresh_tokens WHERE user_id = ?", [$userId]);

        // Nieuw token opslaan
        $this->database->execute(
            "INSERT INTO refresh_tokens (user_id, token, expires_at, created_at) 
             VALUES (?, ?, ?, NOW())",
            [$userId, $token, $expiry]
        );

        return $token;
    }

    /**
     * Valideer refresh token
     */
    public function validateRefreshToken(string $token): ?array
    {
        return $this->database->fetch(
            "SELECT * FROM refresh_tokens 
             WHERE token = ? AND expires_at > NOW()",
            [$token]
        );
    }

    /**
     * Verwijder refresh token
     */
    public function revokeRefreshToken(string $token): bool
    {
        return $this->database->execute("DELETE FROM refresh_tokens WHERE token = ?", [$token]);
    }

    /**
     * Verwijder alle refresh tokens voor gebruiker
     */
    public function revokeAllRefreshTokens(int $userId): int
    {
        $this->database->execute("DELETE FROM refresh_tokens WHERE user_id = ?", [$userId]);

        // Note: DatabaseInterface execute() returns bool, not rowCount
        // Voor row count zou je een aparte query kunnen doen of de interface uitbreiden
        return 1; // Simplified for now - indicates success
    }
}
