<?php

namespace ThomasMiceli\Router\Middleware;

use Closure;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ThomasMiceli\Router\Exceptions\EmptyResponseException;

final class MiddlewareDispatcher implements RequestHandlerInterface
{
    public function __construct(
        private RequestHandlerInterface $last,
    )
    {
    }

    public function add(Closure $callable)
    {
        $this->last = new Middleware($callable, $this->last);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (($h = $this->last->handle($request)) === null) {
            throw new EmptyResponseException();
        }
        return $h;
    }
}