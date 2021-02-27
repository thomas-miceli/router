<?php

namespace ThomasMiceli\Router;

use Closure;
use DI\Container;

class RouteManager
{

    protected array $routes = [];
    protected array $namedRoutes = [];

    public function __construct(
        private string $prefix,
        protected ?Container $container = null,
    )
    {
    }

    public function get($path, $callable, $name = null): Route
    {
        return $this->route($path, $callable, $name, 'GET');
    }

    public function route($path, $callable, $name, $method): Route
    {
        $route = new Route($this->prefix . $path, $callable, $this->container);
        $this->routes[$method][] = $route;

        if (is_string($callable) && $name === null) {
            $name = $callable;
        }

        if ($name) {
            $this->namedRoutes[$name] = $route;
        }

        return $route;
    }

    public function post($path, $callable, $name = null): Route
    {
        return $this->route($path, $callable, $name, 'POST');
    }

    public function put($path, $callable, $name = null): Route
    {
        return $this->route($path, $callable, $name, 'PUT');
    }

    public function patch($path, $callable, $name = null): Route
    {
        return $this->route($path, $callable, $name, 'PATCH');
    }

    public function delete($path, $callable, $name = null): Route
    {
        return $this->route($path, $callable, $name, 'DELETE');
    }

    public function options($path, $callable, $name = null): Route
    {
        return $this->route($path, $callable, $name, 'OPTIONS');
    }

    public function group(string $prefix, Closure $routes)
    {
        $r = new RouteManager($prefix, $this->container);
        $routes($r);
        $this->routes = array_merge_recursive($this->routes, $r->resolveRoutes());
    }

    public function resolveRoutes(): array
    {
        return $this->routes;
    }
}