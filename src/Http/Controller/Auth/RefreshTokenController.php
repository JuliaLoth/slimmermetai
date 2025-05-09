<?php
namespace App\Http\Controller\Auth;

use App\Application\Service\AuthService;
use App\Infrastructure\Http\JsonResponse;

final class RefreshTokenController
{
    public function handle(): void
    {
        // Haal bearer token uit Authorization header of cookie
        $token = $this->getBearerToken() ?? ($_COOKIE['refresh_token'] ?? null);
        if (!$token) {
            JsonResponse::send(['success' => false, 'message' => 'Geen refresh token gevonden'], 401);
        }
        $auth = AuthService::getInstance();
        $result = $auth->refresh($token);
        if (!$result['success']) {
            JsonResponse::send(['success' => false, 'message' => $result['message']], 401);
        }
        // Stel eventueel nieuwe refresh cookie (vereenvoudigd: niet veranderen)
        JsonResponse::send($result);
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