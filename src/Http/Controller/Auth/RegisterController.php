<?php

namespace App\Http\Controller\Auth;

use App\Application\Service\AuthService;
use App\Infrastructure\Http\JsonResponse;
use App\Infrastructure\Security\Validator;

final class RegisterController
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
            'password' => 'required|min:8',
            'agree_terms' => 'required'
        ]);
        if (!$validator->validate()) {
        // Check if this is a form submission for redirect vs API for JSON
            if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') || str_contains($contentType, 'application/json')) {
                JsonResponse::send(['success' => false, 'errors' => $validator->getErrors()], 422);
            } else {
        // Traditional form - redirect back with errors
                session_start();
                $_SESSION['register_errors'] = $validator->getErrors();
                $_SESSION['register_old_input'] = $body;
                header('Location: /login?tab=register&error=validation', true, 302);
                exit;
            }
        }

        $result = $this->auth->register($body['email'], $body['password']);
        if (!$result['success']) {
            if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') || str_contains($contentType, 'application/json')) {
                JsonResponse::send(['success' => false, 'message' => $result['message']], 400);
            } else {
    // Traditional form - redirect back with error
                session_start();
                $_SESSION['register_error'] = $result['message'];
                $_SESSION['register_old_input'] = ['email' => $body['email']];
                header('Location: /login?tab=register&error=failed', true, 302);
                exit;
            }
        }

        // Success
        if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') || str_contains($contentType, 'application/json')) {
            JsonResponse::send($result, 201);
        } else {
        // Traditional form - redirect to login with success message
            session_start();
            $_SESSION['register_success'] = 'Account succesvol aangemaakt! Je kunt nu inloggen.';
            header('Location: /login?tab=login&register=success', true, 302);
            exit;
        }
    }
}
