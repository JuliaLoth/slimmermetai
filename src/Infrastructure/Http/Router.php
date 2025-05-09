<?php
namespace App\Infrastructure\Http;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;

class Router
{
    /**
     * Basispad waaronder de API draait, standaard '/api'.
     */
    private const BASE_PATH = '/api';

    /**
     * Start de router en verstuur het verzoek naar de juiste controller.
     */
    public static function dispatch(): void
    {
        $dispatcher = simpleDispatcher(function (RouteCollector $r) {
            // ---------- Auth ----------
            $r->addRoute('POST', '/auth/login', [\App\Http\Controller\AuthController::class, 'login']);
            $r->addRoute('POST', '/auth/register', [\App\Http\Controller\AuthController::class, 'register']);
            $r->addRoute('POST', '/auth/refresh-token', [\App\Http\Controller\AuthController::class, 'refresh']);
            $r->addRoute('POST', '/auth/logout', [\App\Http\Controller\AuthController::class, 'logout']);
            $r->addRoute('GET',  '/auth/me', [self::class, 'withAuthMiddleware', [\App\Http\Controller\AuthController::class, 'me']]);

            // ---------- Gebruikers ----------
            $r->addRoute('POST', '/users/register', [\App\Http\Controller\UserController::class, 'register']);

            // ---------- Stripe ----------
            $r->addRoute('POST', '/stripe/checkout', [\App\Http\Controller\StripeController::class, 'createSession']);
            $r->addRoute('GET',  '/stripe/status/{id}', [\App\Http\Controller\StripeController::class, 'status']);
            $r->addRoute('POST', '/stripe/webhook', [\App\Http\Controller\StripeController::class, 'webhook']);
        });

        [$httpMethod, $uri] = [$_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']];

        // Strip query string en basispad
        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }
        // Verwijder base path prefix (\nmaakt trailing slash normalisatie eenvoudiger)
        if (substr_compare($uri, self::BASE_PATH, 0, strlen(self::BASE_PATH)) === 0) {
            $uri = substr($uri, strlen(self::BASE_PATH));
        }
        if ($uri === '') {
            $uri = '/';
        }

        $routeInfo = $dispatcher->dispatch($httpMethod, $uri);
        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                self::jsonResponse(['error' => 'Endpoint niet gevonden'], 404);
                return;
            case Dispatcher::METHOD_NOT_ALLOWED:
                self::jsonResponse(['error' => 'Methode niet toegestaan', 'allowed' => $routeInfo[1]], 405);
                return;
            case Dispatcher::FOUND:
                $handler = $routeInfo[1];
                $vars = $routeInfo[2];
                // Handler kan speciale vorm hebben wanneer auth-middleware moet worden toegepast
                if (is_array($handler) && $handler[0] === self::class && $handler[1] === 'withAuthMiddleware') {
                    // handler[2] bevat het werkelijke doel-array.
                    self::withAuthMiddleware($handler[2], $vars);
                    return;
                }
                self::invoke($handler, $vars);
                return;
        }
    }

    /**
     * Roept een controller via de DI-container aan.
     */
    private static function invoke(array $handler, array $vars = []): void
    {
        [$class, $method] = $handler;
        $container = \container();
        $controller = $container->get($class);
        // Voeg route-parameters als argumenten toe (indien methode type-hint heeft kunnen we dit uitbreiden)
        $controller->{$method}(...array_values($vars));
    }

    /**
     * Wrapt de aanroep in AuthMiddleware.
     */
    private static function withAuthMiddleware(array $handler, array $vars = []): void
    {
        $middleware = new \App\Http\Middleware\AuthMiddleware();
        $middleware->handle(fn() => self::invoke($handler, $vars));
    }

    private static function jsonResponse(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
    }
} 