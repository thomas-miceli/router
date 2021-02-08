# thomas-miceli/router

```shell
$ composer require thomas-miceli/router
```

### Dependencies
* Nyholm's [PSR-7 implementation](https://github.com/nyholm/psr7) (to create HTTP OO messages) and [PSR-7 server](https://github.com/nyholm/psr7-server) (for server requests)
* [PHP-DI](https://php-di.org) for dependency injection 
* [PSR-15 interfaces](https://www.php-fig.org/psr/psr-15/)

### Working full example

```php
<?php
// index.php
use Psr\Http\Server\RequestHandlerInterface;
use ThomasMiceli\Router\Http\Request;
use ThomasMiceli\Router\Http\Response;
use ThomasMiceli\Router\Router;

require 'vendor/autoload.php';

$router = new Router();

$dependency = new stdClass();
$dependency->hello = 'hello';
$router->registerInstance($dependency);

$router->middleware(function (Request $request, RequestHandlerInterface $handler) {
    $request = $request->withAttribute('global', true);
    
    // code executed before the route
    $response = $handler->handle($request);
    // code executed after the route

    return $response;
});

$router
    ->get('/hello/{name}', function ($name, Request $request, Response $response, stdClass $myClass) {
        if ($request->getAttribute('route') === true) {
            $myClass->route_middleware = 'works';
        }

        if ($request->getAttribute('global') === true) {
            $myClass->global_middleware = 'works';
        }
        $myClass->hello .= " $name";

        return $response->json($myClass);
        // or
        // $response->getBody()->write("$name");
        // return $response;

    })
    ->middleware(function (Request $request, RequestHandlerInterface $handler) {
        // executed second
        $request = $request->withAttribute('route', true);

        /** @var Response $response */
        $response = $handler->handle($request);
        $response->setHeader('after', 'true');
        return $response;
    })
    ->middleware(function (Request $request, RequestHandlerInterface $handler) {
        // executed first
        $request = $request->withAttribute('route', false);
        return $handler->handle($request);
    });

$router->run();

```