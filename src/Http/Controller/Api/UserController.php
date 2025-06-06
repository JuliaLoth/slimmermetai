<?php

declare(strict_types=1);

namespace App\Http\Controller\Api;

use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\ValueObject\Email;
use App\Application\Service\PasswordHasher;
use App\Infrastructure\Security\JwtService;
use App\Infrastructure\Database\DatabaseInterface;
use App\Http\Response\ApiResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class UserController implements RequestHandlerInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PasswordHasher $passwordHasher,
        private JwtService $jwtService,
        private DatabaseInterface $database
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        $method = $request->getMethod();

        return match(true) {
            $path === '/api/users/profile' && $method === 'GET' => $this->getProfile($request),
            $path === '/api/users/profile' && $method === 'PUT' => $this->updateProfile($request),
            $path === '/api/users/password' && $method === 'PUT' => $this->updatePassword($request),
            $path === '/api/users/preferences' && $method === 'GET' => $this->getPreferences($request),
            $path === '/api/users/preferences' && $method === 'PUT' => $this->updatePreferences($request),
            $path === '/api/users/stats' && $method === 'GET' => $this->getUserStats($request),
            $path === '/api/users/courses' && $method === 'GET' => $this->getUserCourses($request),
            $path === '/api/users/tools' && $method === 'GET' => $this->getUserTools($request),
            $path === '/api/users/deactivate' && $method === 'POST' => $this->deactivateAccount($request),
            default => ApiResponse::error('Endpoint niet gevonden', 404)
        };
    }

    private function getProfile(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $user = $this->getUserFromToken($request);
            if (!$user) {
                return ApiResponse::error('Unauthorized', 401);
            }

            $profile = $this->userRepository->byId($user['user_id']);
            if (!$profile) {
                return ApiResponse::error('Gebruiker niet gevonden', 404);
            }

            return ApiResponse::success([
                'profile' => [
                    'id' => $profile->getId(),
                    'name' => $profile->getName(),
                    'email' => (string)$profile->getEmail(),
                    'role' => $profile->getRole(),
                    'created_at' => $profile->getCreatedAt()->format('Y-m-d H:i:s')
                ]
            ]);

        } catch (\Exception $e) {
            return ApiResponse::error('Er is een fout opgetreden', 500);
        }
    }

    private function updateProfile(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $user = $this->getUserFromToken($request);
            if (!$user) {
                return ApiResponse::error('Unauthorized', 401);
            }

            $data = $request->getParsedBody();
            $errors = $this->validateProfileData($data);
            
            if (!empty($errors)) {
                return ApiResponse::error('Validatiefout', 422, $errors);
            }

            $success = $this->userRepository->updateProfile($user['user_id'], $data);

            if (!$success) {
                return ApiResponse::error('Profiel kon niet worden bijgewerkt', 500);
            }

            return ApiResponse::success([
                'message' => 'Profiel succesvol bijgewerkt'
            ]);

        } catch (\Exception $e) {
            return ApiResponse::error('Er is een fout opgetreden', 500);
        }
    }

    private function updatePassword(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $user = $this->getUserFromToken($request);
            if (!$user) {
                return ApiResponse::error('Unauthorized', 401);
            }

            $data = $request->getParsedBody();

            if (empty($data['current_password']) || empty($data['new_password'])) {
                return ApiResponse::error('Huidig wachtwoord en nieuw wachtwoord zijn verplicht', 422);
            }

            // Verify current password
            $currentUser = $this->userRepository->byId($user['user_id']);
            if (!$currentUser || !$this->passwordHasher->verify($data['current_password'], $currentUser->getPasswordHash())) {
                return ApiResponse::error('Huidig wachtwoord is incorrect', 400);
            }

            // Validate new password
            if (strlen($data['new_password']) < 8) {
                return ApiResponse::error('Nieuw wachtwoord moet minimaal 8 tekens lang zijn', 422);
            }

            // Hash new password
            $hashedPassword = $this->passwordHasher->hash($data['new_password']);

            // Update password
            $success = $this->userRepository->updatePassword($user['user_id'], $hashedPassword);

            if (!$success) {
                return ApiResponse::error('Wachtwoord kon niet worden bijgewerkt', 500);
            }

            return ApiResponse::success([
                'message' => 'Wachtwoord succesvol gewijzigd'
            ]);

        } catch (\Exception $e) {
            return ApiResponse::error('Er is een fout opgetreden', 500);
        }
    }

    private function getPreferences(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $user = $this->getUserFromToken($request);
            if (!$user) {
                return ApiResponse::error('Unauthorized', 401);
            }

            $preferences = $this->userRepository->getUserPreferences($user['user_id']);

            return ApiResponse::success([
                'preferences' => $preferences
            ]);

        } catch (\Exception $e) {
            return ApiResponse::error('Er is een fout opgetreden', 500);
        }
    }

    private function updatePreferences(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $user = $this->getUserFromToken($request);
            if (!$user) {
                return ApiResponse::error('Unauthorized', 401);
            }

            $data = $request->getParsedBody();
            $preferences = $data['preferences'] ?? [];

            if (empty($preferences) || !is_array($preferences)) {
                return ApiResponse::error('Ongeldige voorkeuren data', 422);
            }

            $success = $this->userRepository->updateUserPreferences($user['user_id'], $preferences);

            if (!$success) {
                return ApiResponse::error('Voorkeuren konden niet worden bijgewerkt', 500);
            }

            return ApiResponse::success([
                'message' => 'Voorkeuren succesvol bijgewerkt'
            ]);

        } catch (\Exception $e) {
            return ApiResponse::error('Er is een fout opgetreden', 500);
        }
    }

    private function getUserStats(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $user = $this->getUserFromToken($request);
            if (!$user) {
                return ApiResponse::error('Unauthorized', 401);
            }

            $stats = $this->userRepository->getUserStats($user['user_id']);

            return ApiResponse::success([
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            return ApiResponse::error('Er is een fout opgetreden', 500);
        }
    }

    private function getUserCourses(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $user = $this->getUserFromToken($request);
            if (!$user) {
                return ApiResponse::error('Unauthorized', 401);
            }

            $courses = $this->userRepository->getUserCourses($user['user_id']);

            return ApiResponse::success([
                'courses' => $courses
            ]);

        } catch (\Exception $e) {
            return ApiResponse::error('Er is een fout opgetreden', 500);
        }
    }

    private function getUserTools(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $user = $this->getUserFromToken($request);
            if (!$user) {
                return ApiResponse::error('Unauthorized', 401);
            }

            $tools = $this->userRepository->getUserTools($user['user_id']);

            return ApiResponse::success([
                'tools' => $tools
            ]);

        } catch (\Exception $e) {
            return ApiResponse::error('Er is een fout opgetreden', 500);
        }
    }

    private function deactivateAccount(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $user = $this->getUserFromToken($request);
            if (!$user) {
                return ApiResponse::error('Unauthorized', 401);
            }

            $data = $request->getParsedBody();

            if (empty($data['password'])) {
                return ApiResponse::error('Wachtwoord is verplicht voor account deactivering', 422);
            }

            // Verify password before deactivation
            $currentUser = $this->userRepository->byId($user['user_id']);
            if (!$currentUser || !$this->passwordHasher->verify($data['password'], $currentUser->getPasswordHash())) {
                return ApiResponse::error('Wachtwoord is incorrect', 400);
            }

            $success = $this->userRepository->deactivateUser($user['user_id']);

            if (!$success) {
                return ApiResponse::error('Account kon niet worden gedeactiveerd', 500);
            }

            return ApiResponse::success([
                'message' => 'Account succesvol gedeactiveerd'
            ]);

        } catch (\Exception $e) {
            return ApiResponse::error('Er is een fout opgetreden', 500);
        }
    }

    private function getUserFromToken(ServerRequestInterface $request): ?array
    {
        $authHeader = $request->getHeaderLine('Authorization');
        
        if (empty($authHeader) || !str_starts_with($authHeader, 'Bearer ')) {
            return null;
        }

        $token = substr($authHeader, 7);
        return $this->jwtService->validateToken($token);
    }

    private function validateProfileData(array $data): array
    {
        $errors = [];

        if (isset($data['name']) && (empty($data['name']) || strlen($data['name']) > 255)) {
            $errors['name'] = 'Naam is verplicht en mag max 255 tekens zijn';
        }

        if (isset($data['bio']) && strlen($data['bio']) > 1000) {
            $errors['bio'] = 'Biografie mag max 1000 tekens zijn';
        }

        if (isset($data['phone']) && !empty($data['phone']) && !preg_match('/^[\+]?[0-9\s\-\(\)]{7,20}$/', $data['phone'])) {
            $errors['phone'] = 'Ongeldig telefoonnummer formaat';
        }

        if (isset($data['company']) && strlen($data['company']) > 255) {
            $errors['company'] = 'Bedrijfsnaam mag max 255 tekens zijn';
        }

        if (isset($data['job_title']) && strlen($data['job_title']) > 255) {
            $errors['job_title'] = 'Functietitel mag max 255 tekens zijn';
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
            'slow_queries' => array_slice($slowQueries, 0, 10),
            'total_slow_queries' => count($slowQueries)
        ]);
    }
} 