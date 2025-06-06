<?php

namespace App\Infrastructure\Http;

use App\Domain\Logging\ErrorLoggerInterface;

use function container;

/**
 * ApiResponse
 *
 * Biedt een consistente manier om JSON API-responses te sturen.
 */
final class ApiResponse
{
    public static function success(mixed $data = null, ?string $message = null, int $status = 200): void
    {
        self::send(['success' => true,'message' => $message,'data' => $data], $status);
    }

    public static function error(string $message, int $status = 400, mixed $errors = null): void
    {
        $payload = ['success' => false,'message' => $message];
        if ($errors !== null) {
            $payload['errors'] = $errors;
        }
        self::send($payload, $status);
    }

    public static function validationError(array $errors, string $message = 'Validatiefout'): void
    {
        self::error($message, 422, $errors);
    }

    public static function unauthorized(string $message = 'Ongeautoriseerde toegang'): void
    {
        self::error($message, 401);
    }

    public static function forbidden(string $message = 'Toegang geweigerd'): void
    {
        self::error($message, 403);
    }

    public static function notFound(string $message = 'Resource niet gevonden'): void
    {
        self::error($message, 404);
    }

    public static function methodNotAllowed(string $message = 'Methode niet toegestaan', array $allowed = []): void
    {
        $h = [];
        if ($allowed) {
            $h['Allow'] = implode(', ', $allowed);
        }
        self::send(['success' => false,'message' => $message], 405, $h);
    }

    public static function serverError(string $message = 'Interne serverfout', mixed $error = null): void
    {
        container()->get(ErrorLoggerInterface::class)->logError($message, ['error' => $error]);
        $payload = ['success' => false,'message' => $message];
        if (getenv('APP_ENV') === 'local' && $error) {
            $payload['details'] = $error;
        }
        self::send($payload, 500);
    }

    private static function send(array $data, int $status, array $headers = []): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_ORIGIN'] ?? '*'));
        header('Access-Control-Allow-Methods: GET,POST,PUT,PATCH,DELETE,OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Allow-Credentials: true');
        foreach ($headers as $k => $v) {
            header("$k: $v");
        }
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            exit;
        }
        echo json_encode($data);
        exit;
    }
}

// Backward-compatibility met legacy namespace
\class_alias(\App\Infrastructure\Http\ApiResponse::class, 'SlimmerMetAI\\Utils\\ApiResponse');
