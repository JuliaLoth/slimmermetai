<?php
namespace App\Http\Controller;

use App\Application\Service\AuthService;
use App\Http\Response\ApiResponse;

class AuthController
{
    public function __construct(private AuthService $auth) {}

    public function login(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?: [];
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        $result = $this->auth->login($email, $password);
        $result['success']
            ? ApiResponse::success($result)
            : ApiResponse::error($result['message'] ?? 'Onbekende fout');
    }

    public function register(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?: [];
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        $result = $this->auth->register($email, $password);
        $result['success']
            ? ApiResponse::success($result, 'Registratie gelukt', 201)
            : ApiResponse::error($result['message'] ?? 'Registratie mislukt');
    }

    public function refresh(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?: [];
        $token = $data['refresh_token'] ?? '';
        $result = $this->auth->refresh($token);
        $result['success']
            ? ApiResponse::success($result)
            : ApiResponse::error($result['message'] ?? 'Token ongeldig');
    }

    public function me(): void
    {
        $payload = $_SERVER['auth_user'] ?? null;
        $result = $payload ? ['success' => true, 'user' => $this->auth->getCurrentUser($payload)] : ['success' => false];
        $result['success']
            ? ApiResponse::success($result)
            : ApiResponse::error('Niet ingelogd', 401);
    }

    public function logout(): void
    {
        $result = $this->auth->logout();
        ApiResponse::success($result, 'Uitgelogd');
    }
} 