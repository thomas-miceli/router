<?php

namespace ThomasMiceli\Router;

use Closure;
use DI\Container;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ThomasMiceli\Router\Http\Request;
use ThomasMiceli\Router\Middleware\MiddlewareDispatcher;
use ThomasMiceli\Router\Middleware\MiddlewareTrait;

final class Route implements RequestHandlerInterface
{

    use MiddlewareTrait;

    private array $matches = [];
    private array $matches_key = [];
    private ?MiddlewareDispatcher $middlewares = null;

    public function __construct(
        private string $path,
        private Closure|string $callable,
        private Container $container,
    )
    {
        $this->path = trim($path, '/');
    }

    public function match($url): bool
    {
        $url = ltrim($url, '/');
        $path = preg_replace_callback('#{([\w]+)}#', [$this, 'paramMatch'], $this->path);
        $regex = "#^$path$#i";
        if (!preg_match($regex, $url, $matches)) {
            return false;
        }
        array_shift($matches);
        $this->matches = $matches;
        return true;
    }

    public function call(ServerRequestInterface $request): ResponseInterface
    {
        return $this->middlewares?->handle($request) ?? $this->handle($request);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // update the request modified by middlewares into the DI
        if ($this->middlewares) {
            $this->container->set(Request::class, $request);
        }

        if (is_string($this->callable)) {
            $callable = explode('#', $this->callable);
            $class = $callable[0];
            $method = $callable[1];
            $this->container->make($class);
            return $this->container->call([$class, $method], array_combine($this->matches_key, $this->matches));
        } else {
            return $this->container->call($this->callable, array_combine($this->matches_key, $this->matches));
        }
    }

    private function paramMatch($match): string
    {
        $this->matches_key[] = $match[1];
        return '([^/]+)';
    }
}
