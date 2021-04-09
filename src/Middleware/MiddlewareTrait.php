<?php

namespace ThomasMiceli\Router\Middleware;

use Closure;
use InvalidArgumentException;
use Psr\Http\Server\MiddlewareInterface;

trait MiddlewareTrait
{
    public function middleware(Closure|string $callable): self
    {
        if (!$this->middlewares) {
            $this->middlewares = new MiddlewareDispatcher($this);
        }

        if (is_string($callable)) {
            if (!(($instance = new $callable) instanceof MiddlewareInterface)) {
                throw new InvalidArgumentException('Middleware must implement \Psr\Http\Server\MiddlewareInterface');
            }
            $this->middlewares->add(Closure::fromCallable([$instance, 'process']));
        } else {
            $this->middlewares->add($callable);
        }

        return $this;
    }
}