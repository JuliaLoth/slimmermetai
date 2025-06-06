<?php

namespace App\Http\Response;

use App\Domain\Logging\ErrorLoggerInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Psr7\Response;

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
    public static function success(mixed $data = null, ?string $message = null, int $statusCode = 200): ResponseInterface
    {
        return self::createJsonResponse([
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
    public static function error(string $message, int $statusCode = 400, mixed $errors = null): ResponseInterface
    {
        $payload = [
            'success' => false,
            'message' => $message,
        ];
        if ($errors !== null) {
            $payload['errors'] = $errors;
        }
        return self::createJsonResponse($payload, $statusCode);
    }

    /** Validation errors */
    public static function validationError(array $errors, string $message = 'Validatiefout'): ResponseInterface
    {
        return self::error($message, 422, $errors);
    }

    /** 404 */
    public static function notFound(string $message = 'Resource niet gevonden'): ResponseInterface
    {
        return self::error($message, 404);
    }

    /** 401 */
    public static function unauthorized(string $message = 'Ongeautoriseerde toegang'): ResponseInterface
    {
        return self::error($message, 401);
    }

    /** 403 */
    public static function forbidden(string $message = 'Toegang geweigerd'): ResponseInterface
    {
        return self::error($message, 403);
    }

    /** 429 */
    public static function rateLimited(string $message = 'Te veel verzoeken'): ResponseInterface
    {
        return self::error($message, 429);
    }

    /** 304 */
    public static function notModified(?string $etag = null): ResponseInterface
    {
        $headers = [
            'Content-Type' => 'application/json; charset=UTF-8',
            'Access-Control-Allow-Origin' => $_SERVER['HTTP_ORIGIN'] ?? '*',
            'Access-Control-Allow-Methods' => 'GET,POST,PUT,PATCH,DELETE,OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With',
            'Access-Control-Allow-Credentials' => 'true'
        ];

        if ($etag) {
            $headers['ETag'] = $etag;
        }

        return new Response(304, $headers, '');
    }

    /** 500 */
    public static function serverError(string $message = 'Interne serverfout', mixed $error = null): ResponseInterface
    {
        // Log the error
        try {
            container()->get(ErrorLoggerInterface::class)->logError($message, ['error' => $error]);
        } catch (\Exception $e) {
            // Silently fail if logging fails
        }

        $payload = ['success' => false, 'message' => $message];
        if (getenv('APP_ENV') === 'local' && $error) {
            $payload['details'] = $error;
        }
        return self::createJsonResponse($payload, 500);
    }

    /**
     * Creates a JSON response with proper headers
     */
    private static function createJsonResponse(array $data, int $statusCode): ResponseInterface
    {
        $headers = [
            'Content-Type' => 'application/json; charset=UTF-8',
            'Access-Control-Allow-Origin' => $_SERVER['HTTP_ORIGIN'] ?? '*',
            'Access-Control-Allow-Methods' => 'GET,POST,PUT,PATCH,DELETE,OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With',
            'Access-Control-Allow-Credentials' => 'true'
        ];

        $jsonBody = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return new Response($statusCode, $headers, $jsonBody);
    }
}
