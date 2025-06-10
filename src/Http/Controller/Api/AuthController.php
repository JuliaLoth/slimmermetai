<?php

declare(strict_types=1);

namespace App\Http\Controller\Api;

use App\Domain\Repository\AuthRepositoryInterface;
use App\Domain\ValueObject\Email;
use App\Domain\Service\PasswordHasherInterface;
use App\Domain\Security\JwtServiceInterface;
use App\Infrastructure\Database\DatabaseInterface;
use App\Http\Response\ApiResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AuthController implements RequestHandlerInterface
{
    public function __construct(
        private AuthRepositoryInterface $authRepository,
        private PasswordHasherInterface $passwordHasher,
        private JwtServiceInterface $jwtService,
        private DatabaseInterface $database
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        $method = $request->getMethod();

        return match (true) {
            $path === '/api/auth/register' && $method === 'POST' => $this->register($request),
            $path === '/api/auth/login' && $method === 'POST' => $this->login($request),
            $path === '/api/auth/verify-email' && $method === 'POST' => $this->verifyEmail($request),
            $path === '/api/auth/forgot-password' && $method === 'POST' => $this->forgotPassword($request),
            $path === '/api/auth/reset-password' && $method === 'POST' => $this->resetPassword($request),
            $path === '/api/auth/refresh' && $method === 'POST' => $this->refreshToken($request),
            $path === '/api/auth/me' && $method === 'GET' => $this->me($request),
            $path === '/api/auth/logout' && $method === 'POST' => $this->logout($request),
            default => ApiResponse::error('Endpoint niet gevonden', 404)
        };
    }

    private function register(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $data = $request->getParsedBody();

            // Validatie
            $errors = $this->validateRegistration($data);
            if (!empty($errors)) {
                return ApiResponse::error('Validatiefout', 422, $errors);
            }

            $email = new Email($data['email']);

            // Check if user already exists
            $existingUser = $this->authRepository->findUserByEmail($email);
            if ($existingUser) {
                return ApiResponse::error('Dit e-mailadres is al in gebruik', 409);
            }

            // Hash password
            $hashedPassword = $this->passwordHasher->hash($data['password']);

            // Create user
            $userId = $this->authRepository->createUser(
                $data['firstName'] . ' ' . $data['lastName'],
                $email,
                $hashedPassword
            );

            // Create email verification token
            $token = bin2hex(random_bytes(32));
            $expiresAt = new \DateTimeImmutable('+24 hours');

            $this->authRepository->createEmailVerificationToken($userId, $token, $expiresAt);

            // TODO: Send verification email

            // Generate JWT for immediate login
            $user = $this->authRepository->findUserByEmail($email);
            $jwtToken = $this->jwtService->generateToken([
                'user_id' => $user->getId(),
                'email' => (string)$user->getEmail(),
                'role' => $user->getRole()
            ]);

            return ApiResponse::success([
                'message' => 'Registratie succesvol. Controleer je e-mail om je account te bevestigen.',
                'token' => $jwtToken,
                'user' => [
                    'id' => $user->getId(),
                    'name' => $user->getName(),
                    'email' => (string)$user->getEmail(),
                    'role' => $user->getRole()
                ]
            ]);
        } catch (\Exception $e) {
            return ApiResponse::error('Er is een fout opgetreden bij de registratie', 500);
        }
    }

    private function login(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $data = $request->getParsedBody();

            if (empty($data['email']) || empty($data['password'])) {
                return ApiResponse::error('E-mail en wachtwoord zijn verplicht', 422);
            }

            $email = new Email($data['email']);

            // Check for too many failed attempts BEFORE validating credentials
            // This prevents brute force attacks
            $failedAttempts = $this->authRepository->getFailedLoginAttempts(
                (string)$email,
                new \DateTimeImmutable('-1 hour')
            );

            if ($failedAttempts >= 5) {
                // Log the rate-limited attempt
                $this->authRepository->logLoginAttempt(
                    (string)$email,
                    false,
                    $request->getServerParams()['REMOTE_ADDR'] ?? '',
                    'rate_limited'
                );

                return ApiResponse::error('Te veel mislukte inlogpogingen. Probeer het over een uur opnieuw.', 429);
            }

            $user = $this->authRepository->findUserByEmail($email);

            if (!$user || !$this->passwordHasher->verify($data['password'], $user->getPasswordHash())) {
                // Log failed login attempt
                $this->authRepository->logLoginAttempt(
                    (string)$email,
                    false,
                    $request->getServerParams()['REMOTE_ADDR'] ?? '',
                    'invalid_credentials'
                );

                return ApiResponse::error('Ongeldige inloggegevens', 401);
            }

            // Log successful login
            $this->authRepository->logLoginAttempt(
                (string)$email,
                true,
                $request->getServerParams()['REMOTE_ADDR'] ?? '',
                'success'
            );

            // Update last login and reset failed attempts
            $this->authRepository->updateLastLogin($user->getId());
            $this->authRepository->resetFailedLoginAttempts((string)$email);

            // Generate JWT
            $jwtToken = $this->jwtService->generateToken([
                'user_id' => $user->getId(),
                'email' => (string)$user->getEmail(),
                'role' => $user->getRole(),
                'login_time' => time() // Add login timestamp for token validation
            ]);

            return ApiResponse::success([
                'message' => 'Inloggen succesvol',
                'token' => $jwtToken,
                'user' => [
                    'id' => $user->getId(),
                    'name' => $user->getName(),
                    'email' => (string)$user->getEmail(),
                    'role' => $user->getRole()
                ]
            ]);
        } catch (\Exception $e) {
            return ApiResponse::error('Er is een fout opgetreden bij het inloggen', 500);
        }
    }

    private function verifyEmail(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $data = $request->getParsedBody();

            if (empty($data['token'])) {
                return ApiResponse::error('Verificatietoken is verplicht', 422);
            }

            $user = $this->authRepository->verifyEmailToken($data['token']);

            if (!$user) {
                return ApiResponse::error('Ongeldige of verlopen verificatietoken', 400);
            }

            return ApiResponse::success([
                'message' => 'E-mailadres succesvol geverifieerd',
                'user' => [
                    'id' => $user->getId(),
                    'name' => $user->getName(),
                    'email' => (string)$user->getEmail(),
                    'email_verified' => true
                ]
            ]);
        } catch (\Exception $e) {
            return ApiResponse::error('Er is een fout opgetreden bij de e-mailverificatie', 500);
        }
    }

    private function forgotPassword(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $data = $request->getParsedBody();

            if (empty($data['email'])) {
                return ApiResponse::error('E-mailadres is verplicht', 422);
            }

            $email = new Email($data['email']);
            $user = $this->authRepository->findUserByEmail($email);

            if (!$user) {
                // Don't reveal if user exists or not for security
                return ApiResponse::success([
                    'message' => 'Als dit e-mailadres bekend is, hebben we een herstellink verstuurd.'
                ]);
            }

            // Create password reset token
            $token = bin2hex(random_bytes(32));
            $expiresAt = new \DateTimeImmutable('+1 hour');

            $this->authRepository->createPasswordResetToken($user->getId(), $token, $expiresAt);

            // TODO: Send password reset email

            return ApiResponse::success([
                'message' => 'Als dit e-mailadres bekend is, hebben we een herstellink verstuurd.'
            ]);
        } catch (\Exception $e) {
            return ApiResponse::error('Er is een fout opgetreden', 500);
        }
    }

    private function resetPassword(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $data = $request->getParsedBody();

            if (empty($data['token']) || empty($data['password'])) {
                return ApiResponse::error('Token en nieuw wachtwoord zijn verplicht', 422);
            }

            $tokenData = $this->authRepository->findPasswordResetToken($data['token']);

            if (!$tokenData) {
                return ApiResponse::error('Ongeldige of verlopen hersteltoken', 400);
            }

            // Validate new password
            if (strlen($data['password']) < 8) {
                return ApiResponse::error('Wachtwoord moet minimaal 8 tekens lang zijn', 422);
            }

            // Hash new password
            $hashedPassword = $this->passwordHasher->hash($data['password']);

            // Update password
            $this->authRepository->updatePassword((int)$tokenData['user_id'], $hashedPassword);

            // Mark token as used
            $this->authRepository->deleteUsedToken($data['token']);

            return ApiResponse::success([
                'message' => 'Wachtwoord succesvol gewijzigd'
            ]);
        } catch (\Exception $e) {
            return ApiResponse::error('Er is een fout opgetreden bij het wijzigen van het wachtwoord', 500);
        }
    }

    private function refreshToken(ServerRequestInterface $request): ResponseInterface
    {
        // TODO: Implement refresh token logic
        return ApiResponse::error('Refresh token functionaliteit nog niet geïmplementeerd', 501);
    }

    private function logout(ServerRequestInterface $request): ResponseInterface
    {
        try {
            // Extract token from Authorization header
            $authHeader = $request->getHeaderLine('Authorization');

            if (empty($authHeader) || !str_starts_with($authHeader, 'Bearer ')) {
                // Allow logout without token (client-side cleanup)
                return ApiResponse::success(['message' => 'Succesvol uitgelogd']);
            }

            $token = substr($authHeader, 7);

            // Validate token format and get payload
            $payload = $this->jwtService->validateToken($token);

            if ($payload && isset($payload['user_id'])) {
                // Add token to blacklist to prevent reuse
                $this->authRepository->blacklistToken($token, $payload['user_id'], $payload['exp'] ?? time() + 3600);

                // Log successful logout
                $this->authRepository->logUserAction($payload['user_id'], 'logout', [
                    'ip_address' => $request->getServerParams()['REMOTE_ADDR'] ?? '',
                    'user_agent' => $request->getHeaderLine('User-Agent')
                ]);
            }

            return ApiResponse::success(['message' => 'Succesvol uitgelogd']);
        } catch (\Exception $e) {
            // Even if token processing fails, allow logout for UX
            return ApiResponse::success(['message' => 'Succesvol uitgelogd']);
        }
    }

    private function me(ServerRequestInterface $request): ResponseInterface
    {
        try {
            // Extract token from Authorization header
            $authHeader = $request->getHeaderLine('Authorization');

            if (empty($authHeader) || !str_starts_with($authHeader, 'Bearer ')) {
                return ApiResponse::error('Geen geldige autorisatie', 401);
            }

            $token = substr($authHeader, 7);

            // Check if token is blacklisted
            if ($this->authRepository->isTokenBlacklisted($token)) {
                return ApiResponse::error('Token is ongeldig gemaakt', 401);
            }

            $payload = $this->jwtService->validateToken($token);

            if (!$payload) {
                return ApiResponse::error('Ongeldig token', 401);
            }

            $user = $this->authRepository->findUserByEmail(new Email($payload['email']));

            if (!$user) {
                return ApiResponse::error('Gebruiker niet gevonden', 404);
            }

            // Update last activity timestamp
            $this->authRepository->updateLastActivity($user->getId());

            return ApiResponse::success([
                'user' => [
                    'id' => $user->getId(),
                    'name' => $user->getName(),
                    'email' => (string)$user->getEmail(),
                    'role' => $user->getRole(),
                    'email_verified' => $user->isEmailVerified(),
                    'last_login' => $user->getLastLogin()?->format('c')
                ]
            ]);
        } catch (\Exception $e) {
            return ApiResponse::error('Er is een fout opgetreden', 500);
        }
    }

    private function validateRegistration(array $data): array
    {
        $errors = [];

        if (empty($data['firstName'])) {
            $errors['firstName'] = 'Voornaam is verplicht';
        }

        if (empty($data['lastName'])) {
            $errors['lastName'] = 'Achternaam is verplicht';
        }

        if (empty($data['email'])) {
            $errors['email'] = 'E-mailadres is verplicht';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Ongeldig e-mailadres';
        }

        if (empty($data['password'])) {
            $errors['password'] = 'Wachtwoord is verplicht';
        } elseif (strlen($data['password']) < 8) {
            $errors['password'] = 'Wachtwoord moet minimaal 8 tekens lang zijn';
        }

        if (empty($data['termsAgreement'])) {
            $errors['termsAgreement'] = 'Je moet akkoord gaan met de algemene voorwaarden';
        }

        return $errors;
    }

    // Performance monitoring endpoint (development only)
    public function performanceStats(ServerRequestInterface $request): ResponseInterface
    {
        if ($_ENV['APP_ENV'] !== 'development') {
            return ApiResponse::error('Alleen beschikbaar in development mode', 403);
        }

        $stats = $this->database->getPerformanceStatistics();
        $slowQueries = $this->database->getSlowQueries();

        return ApiResponse::success([
            'database_performance' => $stats,
            'slow_queries' => array_slice($slowQueries, 0, 10), // Top 10 slow queries
            'total_slow_queries' => count($slowQueries)
        ]);
    }
}
