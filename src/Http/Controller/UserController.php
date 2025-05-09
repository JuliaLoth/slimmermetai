<?php

namespace App\Http\Controller;

use App\Application\Service\UserService;

class UserController
{
    public function __construct(private UserService $service) {}

    public function register(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $dto = $this->service->register($data['email'] ?? '', $data['password'] ?? '');
        header('Content-Type: application/json');
        echo json_encode($dto);
    }
} 