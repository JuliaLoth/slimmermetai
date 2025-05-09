<?php
namespace App\Http\Controller;

use App\Application\Service\AuthService;

class AuthController
{
    private AuthService $auth;
    public function __construct()
    {
        $this->auth = AuthService::getInstance();
    }

    public function login(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?: [];
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        $result = $this->auth->login($email, $password);
        header('Content-Type: application/json');
        echo json_encode($result);
    }

    public function register(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?: [];
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        $result = $this->auth->register($email, $password);
        header('Content-Type: application/json');
        echo json_encode($result);
    }

    public function refresh(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?: [];
        $token = $data['refresh_token'] ?? '';
        $result = $this->auth->refresh($token);
        header('Content-Type: application/json');
        echo json_encode($result);
    }

    public function me(): void
    {
        $payload = $_SERVER['auth_user'] ?? null;
        $result = $payload ? ['success' => true, 'user' => $this->auth->getCurrentUser($payload)] : ['success' => false];
        header('Content-Type: application/json');
        echo json_encode($result);
    }

    public function logout(): void
    {
        $result = $this->auth->logout();
        header('Content-Type: application/json');
        echo json_encode($result);
    }
} 