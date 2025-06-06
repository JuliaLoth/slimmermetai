<?php

namespace App\Http\Controller\Auth;

use App\Http\Response\ApiResponse;
use App\Infrastructure\Security\Validator;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Psr7\Response;

final class ForgotPasswordController
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Support both JSON and form data
        $contentType = $request->getHeaderLine('Content-Type');
        if (str_contains($contentType, 'application/json')) {
            // API request - JSON input
            $body = json_decode($request->getBody()->getContents(), true) ?? [];
        } else {
            // Traditional form submission
            $body = $request->getParsedBody() ?? [];
        }

        $validator = new Validator($body, [
            'email' => 'required|email'
        ]);

        if (!$validator->validate()) {
            // Check if this is a form submission for redirect vs API for JSON
            $accept = $request->getHeaderLine('Accept');
            if (str_contains($accept, 'application/json') || str_contains($contentType, 'application/json')) {
                return ApiResponse::validationError($validator->getErrors());
            } else {
                // Traditional form - redirect back with errors
                session_start();
                $_SESSION['forgot_errors'] = $validator->getErrors();
                $_SESSION['forgot_old_input'] = $body;
                return new Response(302, ['Location' => '/login?tab=forgot&error=validation']);
            }
        }

        // For now, just simulate success (password reset would be implemented later)
        $success_message = 'Als dit e-mailadres bij ons bekend is, ontvang je binnen enkele minuten een e-mail met instructies om je wachtwoord te herstellen.';

        // Always show success message for security (don't reveal if email exists)
        $accept = $request->getHeaderLine('Accept');
        if (str_contains($accept, 'application/json') || str_contains($contentType, 'application/json')) {
            return ApiResponse::success(['message' => $success_message]);
        } else {
            // Traditional form - redirect with success message
            session_start();
            $_SESSION['forgot_success'] = $success_message;
            return new Response(302, ['Location' => '/login?tab=forgot&success=sent']);
        }
    }
}
