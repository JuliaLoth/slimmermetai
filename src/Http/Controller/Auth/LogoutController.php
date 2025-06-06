<?php

namespace App\Http\Controller\Auth;

use App\Application\Service\AuthService;
use App\Infrastructure\Http\JsonResponse;

final class LogoutController
{
    public function __construct(private AuthService $auth)
    {
    }

    public function handle(): void
    {
        // Optionally invalidate refresh token cookie
        if (isset($_COOKIE['refresh_token'])) {
            setcookie('refresh_token', '', [
                'expires' => time() - 3600,
                'path' => '/',
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
        }
        $this->auth->logout();
        JsonResponse::send(['success' => true]);
    }
}
