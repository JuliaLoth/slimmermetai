<?php

namespace App\Http\Controller\Auth;

use App\Application\Service\AuthService;
use App\Http\Response\ApiResponse;
use App\Infrastructure\Security\Validator;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Psr7\Response;

final class RegisterController
{
    public function __construct(private AuthService $auth)
    {
    }

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
            'email' => 'required|email',
            'password' => 'required|min:8',
            'agree_terms' => 'required'
        ]);

        if (!$validator->validate()) {
            // Check if this is a form submission for redirect vs API for JSON
            $accept = $request->getHeaderLine('Accept');
            if (str_contains($accept, 'application/json') || str_contains($contentType, 'application/json')) {
                return ApiResponse::validationError($validator->getErrors());
            } else {
                // Traditional form - redirect back with errors
                session_start();
                $_SESSION['register_errors'] = $validator->getErrors();
                $_SESSION['register_old_input'] = $body;
                return new Response(302, ['Location' => '/login?tab=register&error=validation']);
            }
        }

        $result = $this->auth->register($body['email'], $body['password']);
        if (!$result['success']) {
            $accept = $request->getHeaderLine('Accept');
            if (str_contains($accept, 'application/json') || str_contains($contentType, 'application/json')) {
                return ApiResponse::error($result['message'], 400);
            } else {
                // Traditional form - redirect back with error
                session_start();
                $_SESSION['register_error'] = $result['message'];
                $_SESSION['register_old_input'] = ['email' => $body['email']];
                return new Response(302, ['Location' => '/login?tab=register&error=registration']);
            }
        }

        // Success
        $accept = $request->getHeaderLine('Accept');
        if (str_contains($accept, 'application/json') || str_contains($contentType, 'application/json')) {
            return ApiResponse::success($result);
        } else {
            // Traditional form - redirect to dashboard
            return new Response(302, ['Location' => '/dashboard?register=success']);
        }
    }
}
