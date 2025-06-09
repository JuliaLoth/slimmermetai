<?php

namespace App\Http\Routing;

use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use FastRoute\Dispatcher as FastRouteDispatcher;
use GuzzleHttp\Psr7\Response;

use function container;

/**
 * FastRouteRequestHandler
 *
 * Dit is de laatste handler in de middleware­keten. Hij maakt gebruik van
 * FastRoute om het verzoek aan de juiste controller toe te wijzen en zet het
 * resultaat om naar een PSR-7 Response.
 */
class FastRouteRequestHandler implements RequestHandlerInterface
{
    public function __construct(private FastRouteDispatcher $dispatcher)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeInfo = $this->dispatcher->dispatch($request->getMethod(), $request->getUri()->getPath());
        switch ($routeInfo[0]) {
            case FastRouteDispatcher::NOT_FOUND:
                return new Response(
                    404,
                    ['Content-Type' => 'application/json'],
                    json_encode(['error' => 'Pagina niet gevonden'])
                );
            case FastRouteDispatcher::METHOD_NOT_ALLOWED:
                return new Response(
                    405,
                    ['Content-Type' => 'application/json'],
                    json_encode(['error' => 'Method Not Allowed'])
                );
            case FastRouteDispatcher::FOUND:
                $handler = $routeInfo[1];
                $vars = $routeInfo[2];

                // ---- Buffer legacy echo output ----
                ob_start();
                $result = $this->invokeHandler($handler, $request, $vars);
                $buffer = ob_get_clean();

                if ($result instanceof ResponseInterface) {
                    return $result;
                }

                $body = '';
                if (is_string($result)) {
                    $body = $result;
                }
                $body .= $buffer;

                return new Response(
                    200,
                    ['Content-Type' => 'text/html; charset=utf-8'],
                    $body
                );
        }

        // Fallback – zou theoretisch nooit moeten gebeuren
        return new Response(500, [], 'Onbekende router status');
    }

    /**
     * Roept de gevonden handler aan, met ondersteuning voor dependency-injectie
     * op moderne controllers.
     *
     * @param callable|array{class-string, string} $handler
     * @param array<string,string>                 $vars
     */
    private function invokeHandler($handler, ServerRequestInterface $request, array $vars): mixed
    {
        // Inline callable (closure of function)
        if (is_callable($handler) && !is_array($handler)) {
            return call_user_func_array($handler, $vars);
        }

        // Controller klas met methode
        /** @var class-string $class */
        [$class, $method] = $handler;
        $controller = container()->get($class);
        $reflection = new \ReflectionMethod($controller, $method);
        $invokeParams = [];
        if ($reflection->getNumberOfParameters() > 0) {
            $first = $reflection->getParameters()[0];
            $type  = $first->getType();

            // PHP 8+ compatible type checking
            if (
                $type instanceof \ReflectionNamedType &&
                is_a($type->getName(), ServerRequestInterface::class, true)
            ) {
                $invokeParams[] = $request;
            }
        }

        $invokeParams = array_merge($invokeParams, $vars);
        return $controller->$method(...$invokeParams);
    }
}
