<?php
namespace App\Http\Middleware;

use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * MiddlewareDispatcher
 *
 * Een lichtgewicht PSR-15 runner waarmee we een array van MiddlewareInterfaces
 * kunnen doorlopen.  Door zelf een eenvoudige dispatcher te hebben zijn we
 * niet afhankelijk van externe packages en houden we de codebase compact.
 */
class MiddlewareDispatcher implements RequestHandlerInterface
{
    /** @var MiddlewareInterface[] */
    private array $stack;
    private RequestHandlerInterface $finalHandler;
    private int $index = 0;

    /**
     * @param MiddlewareInterface[] $stack
     */
    public function __construct(array $stack, RequestHandlerInterface $finalHandler)
    {
        $this->stack = array_values($stack);
        $this->finalHandler = $finalHandler;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (isset($this->stack[$this->index])) {
            $middleware = $this->stack[$this->index++];
            return $middleware->process($request, $this);
        }
        return $this->finalHandler->handle($request);
    }
} 