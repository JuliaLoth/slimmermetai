<?php

namespace App\Http\Controller\Auth;

use App\Application\Service\AuthService;
use App\Http\Response\ApiResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

final class MeController
{
    public function __construct(private AuthService $auth)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $token = $this->getBearerToken($request);
        if (!$token) {
            return ApiResponse::unauthorized('Bearer token ontbreekt');
        }

        $payload = $this->auth->verifyToken($token);
        if (!$payload) {
            return ApiResponse::unauthorized('Ongeldig token');
        }

        $user = $this->auth->getCurrentUser($payload);
        return ApiResponse::success(['user' => $user]);
    }

    private function getBearerToken(ServerRequestInterface $request): ?string
    {
        $header = $request->getHeaderLine('Authorization');
        if (preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
            return trim($matches[1]);
        }
        return null;
    }
}
