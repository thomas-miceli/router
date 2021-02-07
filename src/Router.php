<?php

namespace ThomasMiceli\Router;

use DI\Container;
use DI\ContainerBuilder;
use ThomasMiceli\Router\Http\HttpFactory;
use ThomasMiceli\Router\Http\Request;
use ThomasMiceli\Router\Http\Response;
use ThomasMiceli\Router\Http\ResponseEmitter;
use function DI\create;

final class Router
{
    private Request $req;
    private Response $res;
    private array $routes = [];
    private array $namedRoutes = [];
    private Container $container;

    public function __construct()
    {
        $this->container = (new ContainerBuilder())->build();
        $this->req = HttpFactory::request();
        $this->res = HttpFactory::response();

        $this->container->set(Request::class, $this->req);
        $this->container->set(Response::class, $this->res);
    }

    public function get($path, $callable, $name = null): Route
    {
        return $this->route($path, $callable, $name, 'GET');
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

    public function route($path, $callable, $name, $method): Route
    {
        $route = new Route($path, $callable, $this->container);
        $this->routes[$method][] = $route;

        if (is_string($callable) && $name === null) {
            $name = $callable;
        }

        if ($name) {
            $this->namedRoutes[$name] = $route;
        }

        return $route;
    }

    public function run(): void
    {
        $path = $this->req->getUri()->getPath();
        $method = $this->req->getMethod();
        $res = $this->res->notFound();

        foreach ($this->routes[$method] as $route) {
            /* @var Route $route */
            if ($route->match($path)) {
                $res = $route->call($this->req);
                break;
            }
        }
        (new ResponseEmitter())->emit($res);
    }

    public function registerClass($i): void
    {
        $this->container->set($i, create($i));
    }

    public function registerInstance($i): void
    {
        $this->container->set($i::class, $i);
    }

    public function getContainer(): Container
    {
        return $this->container;
    }
}
