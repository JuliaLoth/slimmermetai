<?php

namespace App\Http\Response;

use App\Domain\Logging\ErrorLoggerInterface;

use function container;

/**
 * Class ApiResponse
 * Een uniforme manier om JSON API-responses te versturen.
 */
final class ApiResponse
{
    /**
     * Succesvolle response.
     *
     * @param mixed       $data       Payload
     * @param string|null $message    Optioneel bericht
     * @param int         $statusCode HTTP-status (default 200)
     */
    public static function success(mixed $data = null, ?string $message = null, int $statusCode = 200): void
    {
        self::send([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $statusCode);
    }

    /**
     * Foutresponse.
     *
     * @param string      $message    Foutmelding
     * @param int         $statusCode HTTP-status
     * @param mixed|null  $errors     Extra foutinfo
     */
    public static function error(string $message, int $statusCode = 400, mixed $errors = null): void
    {
        $payload = [
            'success' => false,
            'message' => $message,
        ];
        if ($errors !== null) {
            $payload['errors'] = $errors;
        }
        self::send($payload, $statusCode);
    }

    /** Validation errors */
    public static function validationError(array $errors, string $message = 'Validatiefout'): void
    {
        self::error($message, 422, $errors);
    }

    /** 404 */
    public static function notFound(string $message = 'Resource niet gevonden'): void
    {
        self::error($message, 404);
    }

    /** 401 */
    public static function unauthorized(string $message = 'Ongeautoriseerde toegang'): void
    {
        self::error($message, 401);
    }

    /** 403 */
    public static function forbidden(string $message = 'Toegang geweigerd'): void
    {
        self::error($message, 403);
    }

    /** 429 */
    public static function tooManyRequests(string $message = 'Te veel verzoeken', int $retryAfter = 60): void
    {
        self::send([
            'success'     => false,
            'message'     => $message,
            'retry_after' => $retryAfter,
        ], 429, ['Retry-After' => $retryAfter]);
    }

    /** 304 */
    public static function notModified(?string $etag = null): void
    {
        $headers = $etag ? ['ETag' => $etag] : [];
        http_response_code(304);
        self::outputHeaders($headers);
        exit;
    }

    /* --------------------------------------------------------------------- */

    /**
     * Verstuur JSON-payload inclusief CORS-headers.
     */
    private static function send(array $payload, int $statusCode = 200, array $headers = []): void
    {
        http_response_code($statusCode);
// Basisheaders
        $defaultHeaders = [
            'Content-Type'                => 'application/json; charset=UTF-8',
            'Access-Control-Allow-Origin' => $_SERVER['HTTP_ORIGIN'] ?? '*',
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With',
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Max-Age'          => '86400', // 24 uur
        ];
        self::outputHeaders(array_merge($defaultHeaders, $headers));
// OPTIONS-preflight direct beantwoorden
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
            exit;
        }

        echo json_encode($payload);
        exit;
    }

    /**
     * Header-output helper.
     */
    private static function outputHeaders(array $headers): void
    {
        foreach ($headers as $name => $value) {
            header("$name: $value");
        }
    }

    /* --------------------------------------------------------------------- */

    /** 500 */
    public static function serverError(string $message = 'Interne serverfout', \Throwable|string|null $error = null): void
    {
        // Log fout via ErrorLoggerInterface indien beschikbaar
        container()->get(ErrorLoggerInterface::class)->logError($message, ['error' => (string)$error]);
        $debug = defined('DEBUG_MODE') && DEBUG_MODE;
        $details = $debug && $error ? ['details' => (string)$error] : null;
        self::error($message, 500, $details);
    }
}
