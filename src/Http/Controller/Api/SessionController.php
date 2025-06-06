<?php

declare(strict_types=1);

namespace App\Http\Controller\Api;

use App\Http\Response\ApiResponse;
use App\Application\Service\AuthService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class SessionController
{
    public function __construct(
        private AuthService $authService
    ) {
    }

    public function getSession(ServerRequestInterface $request): ResponseInterface
    {
        try {
            // Extract JWT token from request
            $token = $this->extractJwtToken($request);
            if (!$token) {
                return ApiResponse::error('No authentication token provided', 401);
            }

            // Verify token and get payload
            $payload = $this->authService->verifyToken($token);
            if (!$payload) {
                return ApiResponse::error('Invalid authentication token', 401);
            }

            // Get user info from payload
            $user = $this->authService->getCurrentUser($payload);

            if (!$user || !$user['id']) {
                return ApiResponse::error('No active session', 401);
            }

            return ApiResponse::success([
                'authenticated' => true,
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['name'] ?? 'User',
                    'email' => $user['email'],
                    'role' => $user['role'] ?? 'user'
                ]
            ]);
        } catch (\Exception $e) {
            return ApiResponse::error('Session validation failed', 401);
        }
    }

    public function getUserProgress(ServerRequestInterface $request): ResponseInterface
    {
        // Dispatch based on HTTP method
        return match ($request->getMethod()) {
            'GET' => $this->handleGetUserProgress($request),
            'POST' => $this->handleSaveUserProgress($request),
            'OPTIONS' => $this->handleOptions($request),
            default => ApiResponse::error('Method not allowed', 405)
        };
    }

    private function handleGetUserProgress(ServerRequestInterface $request): ResponseInterface
    {
        try {
            // Extract JWT token from request
            $token = $this->extractJwtToken($request);
            if (!$token) {
                return ApiResponse::error('Authentication required', 401);
            }

            // Verify token and get payload
            $payload = $this->authService->verifyToken($token);
            if (!$payload) {
                return ApiResponse::error('Invalid authentication token', 401);
            }

            // Get user info from payload
            $user = $this->authService->getCurrentUser($payload);

            if (!$user || !$user['id']) {
                return ApiResponse::error('Authentication required', 401);
            }

            // TODO: Fetch from database instead of localStorage
            // For now, return empty progress - will be implemented in step 3
            $progress = [
                'courses' => [],
                'certificates' => [],
                'favorites' => []
            ];

            return ApiResponse::success($progress);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to fetch user progress', 500);
        }
    }

    private function handleSaveUserProgress(ServerRequestInterface $request): ResponseInterface
    {
        try {
            // Extract JWT token from request
            $token = $this->extractJwtToken($request);
            if (!$token) {
                return ApiResponse::error('Authentication required', 401);
            }

            // Verify token and get payload
            $payload = $this->authService->verifyToken($token);
            if (!$payload) {
                return ApiResponse::error('Invalid authentication token', 401);
            }

            // Get user info from payload
            $user = $this->authService->getCurrentUser($payload);

            if (!$user || !$user['id']) {
                return ApiResponse::error('Authentication required', 401);
            }

            $body = $request->getParsedBody();

            // TODO: Save to database instead of localStorage
            // For now, just acknowledge the request

            return ApiResponse::success(['message' => 'Progress saved successfully']);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to save progress', 500);
        }
    }

    private function handleOptions(ServerRequestInterface $request): ResponseInterface
    {
        $headers = [
            'Allow' => 'GET, POST, OPTIONS',
            'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization'
        ];

        // Create the response with proper parameter order: data, message, statusCode
        $response = ApiResponse::success([], null, 200);

        // Add custom headers to the response
        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        return $response;
    }

    /**
     * Extract JWT token from Authorization header
     */
    private function extractJwtToken(ServerRequestInterface $request): ?string
    {
        $authHeader = $request->getHeaderLine('Authorization');
        if (empty($authHeader)) {
            return null;
        }

        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $matches[1];
        }

        return null;
    }

    // Legacy methods - deprecated
    public function saveUserProgress(ServerRequestInterface $request): ResponseInterface
    {
        return $this->handleSaveUserProgress($request);
    }
}
