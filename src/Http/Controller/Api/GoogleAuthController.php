<?php

namespace App\Http\Controller\Api;

use App\Domain\Logging\ErrorLoggerInterface;
use App\Http\Response\ApiResponse;
use Google\Client;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

use function container;

final class GoogleAuthController
{
    public function __construct(private ErrorLoggerInterface $logger)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($request->getMethod() === 'OPTIONS') {
            return ApiResponse::success(['allow' => 'POST, OPTIONS']);
        }

        if ($request->getMethod() !== 'POST') {
            return ApiResponse::error('Alleen POST toegestaan', 405);
        }

        $body = json_decode((string)$request->getBody(), true);
        if (!isset($body['token'])) {
            return ApiResponse::validationError(['token' => 'Token ontbreekt']);
        }

        $token = $body['token'];
        $clientId = getenv('GOOGLE_CLIENT_ID');
        if (!$clientId) {
            $this->logger->logError('GOOGLE_CLIENT_ID niet ingesteld');
            return ApiResponse::serverError('Server configuratie fout');
        }

        $client = new Client(['client_id' => $clientId]);
        try {
            $payload = $client->verifyIdToken($token);
        } catch (\Throwable $e) {
            $this->logger->logError('Google token verificatie mislukt', ['error' => $e->getMessage()]);
            return ApiResponse::error('Token verificatie mislukt', 401);
        }

        if (!$payload) {
            return ApiResponse::error('Ongeldig token', 401);
        }

        // TODO: verplaats naar Authentication service zodra beschikbaar
        // Voorlopig sturen we enkel payload terug
        return ApiResponse::success(['payload' => $payload], 'Token geldig');
    }
}
