<?php

namespace ThomasMiceli\Router;

use Closure;
use DI\Container;
use DI\ContainerBuilder;
use Error;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ThomasMiceli\Router\Http\HttpFactory;
use ThomasMiceli\Router\Http\Request;
use ThomasMiceli\Router\Http\Response;
use ThomasMiceli\Router\Http\ResponseEmitter;
use ThomasMiceli\Router\Middleware\MiddlewareDispatcher;
use function DI\create;

final class Router implements RequestHandlerInterface
{
    private Request $request;
    private Response $response;
    private array $routes = [];
    private array $namedRoutes = [];
    private Container $container;
    private ?MiddlewareDispatcher $middlewares = null;

    public function __construct()
    {
        $this->container = (new ContainerBuilder())->build();
        $this->request = HttpFactory::request();
        $this->response = HttpFactory::response();

        $this->container->set(Request::class, $this->request);
        $this->container->set(Response::class, $this->response);
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

    public function middleware(Closure $callable): Router
    {
        if (!$this->middlewares) {
            $this->middlewares = new MiddlewareDispatcher($this);
        }
        $this->middlewares->add($callable);

        return $this;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        $method = $request->getMethod();

        /* @var Route $route */
        foreach ($this->routes[$method] as $route) {
            if ($route->match($path)) {
                return $route->call($request);
            }
        }
        throw new Exception('Page not found');
    }

    public function run()
    {
        try {
            /** @var Response $response */
            $response = $this->middlewares?->handle($this->request) ?? $this->handle($this->request);
        } catch (Error $e) {
            $response = $this->response->error();
            $response->getBody()->write($e->getMessage());
            $response->getBody()->write($e->getTraceAsString());
        } catch (Exception $e) {
            $response = $this->response->notFound();
            $response->getBody()->write($e->getMessage());

        }
        (new ResponseEmitter())->emit($response);

    }


}
