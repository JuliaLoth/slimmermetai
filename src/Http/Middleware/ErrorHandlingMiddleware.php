<?php
namespace App\Http\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use GuzzleHttp\Psr7\Response;
use Throwable;
use App\Domain\Logging\ErrorLoggerInterface;
use App\Infrastructure\Config\Config;

/**
 * ErrorHandlingMiddleware
 *
 * Vangt alle onopgevangen exceptions af uit de stack en zet deze om naar een
 * gestandaardiseerde PSR-7 Response.  Hiermee centraliseren we error handling
 * en kunnen we op één plek bepalen of we fouten als JSON of HTML teruggeven.
 */
class ErrorHandlingMiddleware implements MiddlewareInterface
{
    private bool $displayErrors;

    public function __construct(
        private ErrorLoggerInterface $logger,
        ?bool $displayErrors = null
    ) {
        if ($displayErrors === null) {
            $this->displayErrors = Config::getInstance()->get('display_errors', false);
        } else {
            $this->displayErrors = $displayErrors;
        }
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (Throwable $e) {
            // Log via domein-logger
            $this->logger->logError('Unhandled exception', [
                'exception' => $e,
            ]);

            $accept = strtolower($request->getHeaderLine('Accept'));
            $contentType = strtolower($request->getHeaderLine('Content-Type'));
            $path = $request->getUri()->getPath();

            $wantsJson = str_contains($accept, 'application/json')
                || str_contains($contentType, 'application/json')
                || str_starts_with($path, '/api');

            if ($wantsJson) {
                $payload = json_encode([
                    'error' => true,
                    'message' => $this->displayErrors ? $e->getMessage() : 'Internal Server Error',
                ]);
            } else {
                $payload = '<h1>500 – Internal Server Error</h1>';
                if ($this->displayErrors) {
                    $payload .= '<pre>' . htmlspecialchars($e->getMessage() . "\n" . $e->getTraceAsString()) . '</pre>';
                }
            }

            return new Response(
                500,
                ['Content-Type' => $wantsJson ? 'application/json' : 'text/html'],
                $payload
            );
        }
    }
} 