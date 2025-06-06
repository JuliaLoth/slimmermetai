<?php

namespace App\Http\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use GuzzleHttp\Psr7\Response;

/**
 * FinalHandler
 *
 * Eenvoudige fallback wanneer geen router is aangesloten.
 */
class FinalHandler implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new Response(404, ['Content-Type' => 'application/json'], json_encode(['error' => 'Endpoint niet gevonden']));
    }
}
