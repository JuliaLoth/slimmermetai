<?php
namespace App\Http\Controller\Auth;

use App\Application\Service\AuthService;
use App\Infrastructure\Http\JsonResponse;
use App\Infrastructure\Security\Validator;

final class LoginController
{
    public function handle(): void
    {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        $validator = new Validator($body, [
            'email' => 'required|email',
            'password' => 'required|min:6'
        ]);
        if (!$validator->validate()) {
            JsonResponse::send(['success' => false, 'errors' => $validator->getErrors()], 422);
        }

        $auth = AuthService::getInstance();
        $result = $auth->login($body['email'], $body['password']);
        if (!$result['success']) {
            JsonResponse::send(['success' => false, 'message' => $result['message']], 401);
        }
        JsonResponse::send($result);
    }
} 