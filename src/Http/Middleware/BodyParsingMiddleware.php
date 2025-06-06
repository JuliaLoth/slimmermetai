<?php

namespace App\Http\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use GuzzleHttp\Psr7\Response;

/**
 * BodyParsingMiddleware
 *
 * Parseert JSON en x-www-form-urlencoded bodies en stelt deze beschikbaar via
 * $request->getParsedBody().  Invalid JSON resulteert in een 400 response.
 */
class BodyParsingMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $method = strtoupper($request->getMethod());
        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $contentType = $request->getHeaderLine('Content-Type');
            $raw = (string) $request->getBody();
            if (str_starts_with($contentType, 'application/json')) {
                if ($raw !== '') {
                    $data = json_decode($raw, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        return new Response(400, ['Content-Type' => 'application/json'], json_encode(['error' => 'Invalid JSON']));
                    }
                    $request = $request->withParsedBody($data ?? []);
                }
            } elseif (str_starts_with($contentType, 'application/x-www-form-urlencoded')) {
                parse_str($raw, $data);
                $request = $request->withParsedBody($data ?? []);
            }
        }
        return $handler->handle($request);
    }
}
