<?php

namespace ThomasMiceli\Router;

use DI\Container;
use DI\ContainerBuilder;
use Error;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ThomasMiceli\Router\Exceptions\EmptyResponseException;
use ThomasMiceli\Router\Exceptions\NotFoundException;
use ThomasMiceli\Router\Http\HttpFactory;
use ThomasMiceli\Router\Http\Request;
use ThomasMiceli\Router\Http\Response;
use ThomasMiceli\Router\Http\ResponseEmitter;
use ThomasMiceli\Router\Middleware\MiddlewareDispatcher;
use ThomasMiceli\Router\Middleware\MiddlewareTrait;
use function DI\create;

final class Router extends RouteManager implements RequestHandlerInterface
{
    use MiddlewareTrait;

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

    public function registerClass($i): self
    {
        $this->container->set($i, create($i));

        return $this;
    }

    public function registerInstance($i): self
    {
        $this->container->set($i::class, $i);

        return $this;
    }

    public function getInstance($i): mixed
    {
        return $this->container->get($i::class);
    }

    public function getContainer(): Container
    {
        return $this->container;
    }

    public function run()
    {
        try {
            $this->response = $this->middlewares?->handle($this->request) ?? $this->handle($this->request);
        } catch (NotFoundException $e) {
            $this->response->notFound();
            $this->response->getBody()->write($e->getMessage());
        } catch (Error|Exception $e) {
            $this->response->error();
            $this->response->getBody()->write('<h1>Error ' . $this->response->getStatusCode() . ' ' . $this->response->getReasonPhrase() . '</h1>');
            $this->response->getBody()->write('<pre>' . $e->getMessage() . '</pre>');
            $this->response->getBody()->write('<pre>' . $e->getTraceAsString() . '</pre>');
        }

        (new ResponseEmitter())->emit($this->response);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        $method = $request->getMethod();

        /* @var Route $route */
        foreach ($this->routes[$method] as $route) {
            if ($route->match($path)) {
                if (($h = $route->call($request)) === null) {
                    throw new EmptyResponseException();
                }
                return $h;
            }
        }

        throw new NotFoundException('Page not found');
    }


}
