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

final class Router extends RouteManager implements RequestHandlerInterface
{
    private Request $request;
    private Response $response;
    private ?MiddlewareDispatcher $middlewares = null;

    public function __construct()
    {
        parent::__construct('/');
        $this->request = HttpFactory::request();
        $this->response = HttpFactory::response();
        $this->container = (new ContainerBuilder())->build();

        $this->container->set(Request::class, $this->request);
        $this->container->set(Response::class, $this->response);
    }

    public function registerClass($i): void
    {
        $this->container->set($i, create($i));
    }

    public function registerInstance($i): void
    {
        $this->container->set($i::class, $i);
    }

    public function getInstance($i): mixed
    {
        return $this->container->get($i::class);
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


}
