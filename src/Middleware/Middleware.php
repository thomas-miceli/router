<?php

namespace ThomasMiceli\Router\Middleware;

use Closure;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ThomasMiceli\Router\Exceptions\EmptyResponseException;

final class Middleware implements RequestHandlerInterface
{
    public function __construct(
        private RequestHandlerInterface|Closure $callable,
        private RequestHandlerInterface $next,
    )
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (($h = ($this->callable)($request, $this->next)) === null) {
            throw new EmptyResponseException();
        }

        return $h;
    }
}