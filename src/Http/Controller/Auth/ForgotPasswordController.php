<?php

namespace App\Http\Controller\Auth;

use App\Infrastructure\Http\JsonResponse;
use App\Infrastructure\Security\Validator;

final class ForgotPasswordController
{
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
            'email' => 'required|email'
        ]);
        if (!$validator->validate()) {
        // Check if this is a form submission for redirect vs API for JSON
            if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') || str_contains($contentType, 'application/json')) {
                JsonResponse::send(['success' => false, 'errors' => $validator->getErrors()], 422);
            } else {
        // Traditional form - redirect back with errors
                session_start();
                $_SESSION['forgot_errors'] = $validator->getErrors();
                $_SESSION['forgot_old_input'] = $body;
                header('Location: /login?tab=forgot&error=validation', true, 302);
                exit;
            }
        }

        // For now, just simulate success (password reset would be implemented later)
        $success_message = 'Als dit e-mailadres bij ons bekend is, ontvang je binnen enkele minuten een e-mail met instructies om je wachtwoord te herstellen.';
// Success
        if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') || str_contains($contentType, 'application/json')) {
            JsonResponse::send(['success' => true, 'message' => $success_message]);
        } else {
        // Traditional form - redirect back with success message
            session_start();
            $_SESSION['forgot_success'] = $success_message;
            header('Location: /login?tab=forgot&success=sent', true, 302);
            exit;
        }
    }
}
