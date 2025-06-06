<?php
namespace App\Http\Controller\Auth;

use App\Application\Service\AuthService;
use App\Infrastructure\Http\JsonResponse;

final class MeController
{
    public function __construct(private AuthService $auth) {}

    public function handle(): void
    {
        $token = $this->getBearerToken();
        if (!$token) {
            JsonResponse::send(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        $payload = $this->auth->verifyToken($token);
        if (!$payload) {
            JsonResponse::send(['success' => false, 'message' => 'Invalid token'], 401);
        }
        $user = $this->auth->getCurrentUser($payload);
        JsonResponse::send(['success' => true, 'user' => $user]);
    }

    private function getBearerToken(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? ($_SERVER['Authorization'] ?? '');
        if (preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
            return trim($matches[1]);
        }
        return null;
    }
} 