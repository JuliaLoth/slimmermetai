<?php

namespace App\Application\Service;

use App\Domain\Security\JwtServiceInterface;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\Entity\User;
use App\Domain\ValueObject\Email;
use App\Domain\Logging\ErrorLoggerInterface;
use App\Domain\Service\PasswordHasherInterface;

class AuthService
{
    public function __construct(
        private UserRepositoryInterface $users,
        private PasswordHasherInterface $hasher,
        private JwtServiceInterface $jwt,
        private ErrorLoggerInterface $logger
    ) {
    }

    /**
     * Login met e-mail en wachtwoord
     */
    public function login(string $email, string $password): array
    {
        $user = $this->users->byEmail(new Email($email));
        if (!$user || !$this->hasher->verify($password, $user->getPasswordHash())) {
            return ['success' => false, 'message' => 'Invalid credentials'];
        }
        $access = $this->jwt->generate(['user_id' => $user->getId(), 'email' => (string)$user->getEmail()]);
        return [
            'success' => true,
            'tokens'  => [
                'access_token'  => $access,
                'refresh_token' => null,
                'expires_at'    => time() + 3600,
            ],
            'user'    => ['id' => $user->getId(), 'email' => (string)$user->getEmail()],
        ];
    }

    /**
     * Registreer een gebruiker
     */
    public function register(string $email, string $password): array
    {
        if (!$this->hasher->isStrong($password)) {
            return ['success' => false, 'message' => 'Password not strong enough'];
        }
        if ($this->users->byEmail(new Email($email))) {
            return ['success' => false, 'message' => 'Email exists'];
        }
        $user = new User(new Email($email), $this->hasher->hash($password));
        $this->users->save($user);
        $access = $this->jwt->generate(['user_id' => $user->getId(), 'email' => (string)$user->getEmail()]);
        return [
            'success' => true,
            'tokens'  => [
                'access_token'  => $access,
                'refresh_token' => null,
                'expires_at'    => time() + 3600,
            ],
            'user'    => ['id' => $user->getId(), 'email' => (string)$user->getEmail()],
        ];
    }

    public function verifyToken(string $token): ?array
    {
        return $this->jwt->verify($token);
    }

    public function refresh(string $token): array
    {
        $payload = $this->verifyToken($token);
        if (!$payload) {
            return ['success' => false, 'message' => 'Invalid token'];
        }
        // Simple refresh: issue new access token
        $access = $this->jwt->generate(['user_id' => $payload['user_id'], 'email' => $payload['email'] ?? '']);
        return [
            'success' => true,
            'access_token' => $access,
            'expires_at' => time() + 3600,
        ];
    }

    public function logout(): array
    {
        // Here you would blacklist token / remove refresh token, etc.
        return ['success' => true];
    }

    public function getCurrentUser(array $payload): array
    {
        return ['id' => $payload['user_id'] ?? null, 'email' => $payload['email'] ?? null];
    }
}
