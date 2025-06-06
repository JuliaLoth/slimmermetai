<?php

namespace App\Http\Controller;

use App\Application\Service\UserService;
use App\Http\Response\ApiResponse;

class UserController
{
    public function __construct(private UserService $service) {}

    public function register(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $dto = $this->service->register($data['email'] ?? '', $data['password'] ?? '');
        ApiResponse::success($dto, 'Gebruiker geregistreerd', 201);
    }
} 