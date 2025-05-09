<?php
namespace App\Http\Middleware;

use App\Application\Service\AuthService;

class AuthMiddleware
{
    public function handle(callable $next)
    {
        $token = $this->getBearerToken();
        $payload = $token ? AuthService::getInstance()->verifyToken($token) : null;
        if (!$payload) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }
        // set user info in a global or request attribute (simplified)
        $_SERVER['auth_user'] = $payload;
        return $next();
    }

    private function getBearerToken(): ?string
    {
        $h = $_SERVER['HTTP_AUTHORIZATION'] ?? ($_SERVER['Authorization'] ?? null);
        if ($h && preg_match('/Bearer\s+(\S+)/', $h, $m)) return $m[1];
        return null;
    }
} 