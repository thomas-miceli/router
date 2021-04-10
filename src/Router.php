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
use ReflectionObject;
use ThomasMiceli\Router\Attributes\ControllerRoute;
use ThomasMiceli\Router\Attributes\Route as RouteAttribute;
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

    private ?MiddlewareDispatcher $middlewares = null;
    private ?Closure $notFoundClosure = null;
    private ?Closure $errorClosure = null;

    public function __construct(
        private string $mode = 'prod'
    )
    {
        parent::__construct('/');
        $this->container = (new ContainerBuilder())->build();

        $this->container->set(Request::class, HttpFactory::request());
        $this->container->set(Response::class, HttpFactory::response());

        $this->container->get(Request::class)->withAttribute('azd', 'azd');
    }

    public function containerGet($name): self
    {
        $this->container->get($name);

        return $this;
    }

    public function containerSet($name, $obj): self
    {
        $this->container->set($name, $obj);

        return $this;
    }

    public function containerHas($name): bool
    {
        return $this->container->has($name);
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

    public function controller(string $class)
    {
        $reflection = new ReflectionObject($a = $this->container->make($class));
        $objectAttributes = $reflection->getAttributes(ControllerRoute::class)[0] ?? null;
        $objectAttributes = $objectAttributes?->newInstance();
        $methods = $reflection->getMethods();
        foreach ($methods as $method) {
            /** @var RouteAttribute $methodAttribute */
            if (!empty($methodAttribute = $method->getAttributes(RouteAttribute::class))) {
                $methodAttribute = $method->getAttributes(RouteAttribute::class)[0]->newInstance();
                $r = $this->route(($objectAttributes?->getPath() ?? '') . $methodAttribute->getPath(), $method->getClosure($a), $methodAttribute->getName(), $methodAttribute->getMethod());
                $objectMiddlewares = $objectAttributes?->getMiddlewares() ?? [];
                $methodMiddlewares = $methodAttribute->getMiddlewares();

                foreach ($methodMiddlewares as $middleware) {
                    $r->middleware($middleware);
                }

                foreach ($objectMiddlewares as $middleware) {
                    $r->middleware($middleware);
                }
            }
        }
    }

    public function run()
    {
        $request = $this->container->get(Request::class);
        $response = $this->container->get(Response::class);
        try {
            $response = $this->middlewares?->handle($this->container->get(Request::class)) ?? $this->handle($this->container->get(Request::class));
        } catch (NotFoundException $e) {
            if ($this->notFoundClosure) {
                $this->container->call($this->notFoundClosure);
            } else {
                $response->getBody()->write('Page <b>' . $request->getUri() . '</b> not found');
            }
            $response->notFound();

        } catch (Error|Exception $e) {
            $response->error();
            if ($this->mode === 'dev') {
                $response->getBody()->write('<h1>Error 500 ' . $response->getReasonPhrase() . '</h1>');
                $response->getBody()->write('<pre>' . $e->getMessage() . '</pre>');
                $response->getBody()->write('<pre>' . $e->getTraceAsString() . '</pre>');
            } else if ($this->errorClosure) {
                $this->container->call($this->errorClosure);
            } else {
                $response->getBody()->write('<h1>Error 500 ' . $response->getReasonPhrase() . '</h1>');
            }
            error_log($e->getMessage(), 0);
            error_log($e->getTraceAsString(), 0);

        }
        (new ResponseEmitter)->emit($response);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        $method = $request->getMethod();

        if ($this->routes[$method]) {
            /* @var Route $route */
            foreach ($this->routes[$method] as $route) {
                if ($route->match($path)) {
                    if (($h = $route->call($request)) === null) {
                        throw new EmptyResponseException();
                    }
                    return $h;
                }
            }
        }
        throw new NotFoundException();
    }
}
