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
    private ?Closure $notFoundClosure = null;
    private ?Closure $errorClosure = null;

    public function __construct(
        private string $mode = 'prod'
    )
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

    public function notFound(Closure $closure)
    {
        $this->notFoundClosure = $closure;
    }

    public function error(Closure $closure)
    {
        $this->errorClosure = $closure;
    }

    public function run()
    {
        try {
            $this->response = $this->middlewares?->handle($this->request) ?? $this->handle($this->request);
        } catch (NotFoundException $e) {
            if ($this->notFoundClosure) {
                $this->container->call($this->notFoundClosure);
            } else {
                $this->response->getBody()->write('Page <b>' . $this->request->getUri() . '</b> not found');
            }
            $this->response->notFound();

        } catch (Error|Exception $e) {
            $this->response->error();
            if ($this->mode === 'dev') {
                $this->response->getBody()->write('<h1>Error 500 ' . $this->response->getReasonPhrase() . '</h1>');
                $this->response->getBody()->write('<pre>' . $e->getMessage() . '</pre>');
                $this->response->getBody()->write('<pre>' . $e->getTraceAsString() . '</pre>');
            } else if ($this->errorClosure) {
                $this->container->call($this->errorClosure);
            } else {
                $this->response->getBody()->write('<h1>Error 500 ' . $this->response->getReasonPhrase() . '</h1>');
            }
            error_log($e->getMessage(), 0);
            error_log($e->getTraceAsString(), 0);

        }
        (new ResponseEmitter)->emit($this->response);
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

        throw new NotFoundException();
    }
}
