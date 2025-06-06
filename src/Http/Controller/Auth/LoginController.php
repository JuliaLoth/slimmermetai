<?php

namespace App\Http\Controller\Auth;

use App\Application\Service\AuthService;
use App\Infrastructure\Http\JsonResponse;
use App\Infrastructure\Security\Validator;

final class LoginController
{
    public function __construct(private AuthService $auth)
    {
    }

    public function handle(): void
    {
        // Support both JSON and form data
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (str_contains($contentType, 'application/json')) {
        // API request - JSON input
            $body = json_decode(file_get_contents('php://input'), true) ?? [];
        } else {
        // Traditional form submission
            $body = $_POST;
        }

        $validator = new Validator($body, [
            'email' => 'required|email',
            'password' => 'required|min:6'
        ]);
        if (!$validator->validate()) {
        // Check if this is a form submission for redirect vs API for JSON
            if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') || str_contains($contentType, 'application/json')) {
                JsonResponse::send(['success' => false, 'errors' => $validator->getErrors()], 422);
            } else {
        // Traditional form - redirect back with errors
                session_start();
                $_SESSION['login_errors'] = $validator->getErrors();
                $_SESSION['login_old_input'] = $body;
                header('Location: /login?tab=login&error=validation', true, 302);
                exit;
            }
        }

        $result = $this->auth->login($body['email'], $body['password']);
        if (!$result['success']) {
            if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') || str_contains($contentType, 'application/json')) {
                JsonResponse::send(['success' => false, 'message' => $result['message']], 401);
            } else {
    // Traditional form - redirect back with error
                session_start();
                $_SESSION['login_error'] = $result['message'];
                $_SESSION['login_old_input'] = ['email' => $body['email']];
                header('Location: /login?tab=login&error=credentials', true, 302);
                exit;
            }
        }

        // Success
        if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') || str_contains($contentType, 'application/json')) {
            JsonResponse::send($result);
        } else {
        // Traditional form - redirect to dashboard
            header('Location: /dashboard?login=success', true, 302);
            exit;
        }
    }
}
