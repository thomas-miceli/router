<?php
// index.php
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;
use ThomasMiceli\Router\CringeMiddleware;
use ThomasMiceli\Router\Http\Request;
use ThomasMiceli\Router\Http\Response;
use ThomasMiceli\Router\RouteManager;
use ThomasMiceli\Router\Router;

require 'vendor/autoload.php';
/**/
$router = new Router();

$dependency = new stdClass();
$dependency->hello = 'hello';

$router->registerInstance($dependency);

$router->middleware(function (Request $request, RequestHandlerInterface $handler1) {
    $request = $request->withAttribute('global', true);
    // code executed before the route
    $response = $handler1->handle($request);
    // code executed after the route
    return $response;
})->middleware(function (Request $request, RequestHandlerInterface $handler1) {
    $request = $request->withAttribute('global', false);
    // code executed before the route
    $response = $handler1->handle($request);
    // code executed after the route
    return $response;
});
$router->get('/azd/{name}', function ($name, Request $request, Response $response, stdClass $myClass) {
    if ($request->getAttribute('route') === true) {
        $myClass->route_middleware = 'works';
    }
    if ($request->getAttribute('global') === true) {
        $myClass->global_middleware = 'works';
    }
    $myClass->hello .= " $name";

    $response->json($myClass);
    // or
    // $response->getBody()->write("$name");
    // return $response;

});
$router->group('/ok', function (RouteManager $group) {

$group
    ->get('/hello/{name}', function ($name, Request $request, Response $response, stdClass $myClass) {
        if ($request->getAttribute('route') === true) {
            $myClass->route_middleware = 'works';
        }
        if ($request->getAttribute('global') === true) {
            $myClass->global_middleware = 'works';
        }
        $myClass->hello .= " $name";
        $response->setHeader('ok', 'ok');
        return $response->json($myClass);
        // or
        // $response->getBody()->write("$name");
        // return $response;

    })
    ->middleware(function (Request $request, RequestHandlerInterface $handler2) {
        // executed second
        $request = $request->withAttribute('route', true);
        $response = $handler2->handle($request);

        $response->setHeader('after', 'true');
        return $response;
    })
    ->middleware(function (Request $request, RequestHandlerInterface $handler3) {
        // executed first
        $request = $request->withAttribute('route', true);
        return $handler3->handle($request);
    })
    ->middleware(CringeMiddleware::class);

    $group->get('/azd/{name}', function ($name, Request $request, Response $response, stdClass $myClass) {
        if ($request->getAttribute('route') === true) {
            $myClass->route_middleware = 'works';
        }
        if ($request->getAttribute('global') === true) {
            $myClass->global_middleware = 'works';
        }
        $myClass->hello .= " $name";

        $response->json($myClass);
        // or
        // $response->getBody()->write("$name");
        // return $response;

    });
});

$router->run();
/**/

$app = AppFactory::create();

// Add Routing Middleware
$app->addRoutingMiddleware();

/**
 * Add Error Handling Middleware
 *
 * @param bool $displayErrorDetails -> Should be set to false in production
 * @param bool $logErrors -> Parameter is passed to the default ErrorHandler
 * @param bool $logErrorDetails -> Display error details in error log
 * which can be replaced by a callable of your choice.

 * Note: This middleware should be added last. It will not handle any exceptions/errors
 * for middleware added after it.
 */
$errorMiddleware = $app->addErrorMiddleware(true, true, true);

// Define app routes
$app->get('/hello/{name}', function (Request $request, Response $response, $args) {
    $name = $args['name'];
    $response->getBody()->write("Hello, $name");
    return $response;
});

$app->group('/users/{id:[0-9]+}', function (RouteCollectorProxy $group) {
    $group->map(['GET', 'DELETE', 'PATCH', 'PUT'], '', function ($request, $response, array $args) {
        // Find, delete, patch or replace user identified by $args['id']
        // ...

        return $response;
    })->setName('user');

    $group->get('/reset-password', function ($request, $response, array $args) {
        // Route for /users/{id:[0-9]+}/reset-password
        // Reset the password for user identified by $args['id']
        // ...

        return $response;
    })->setName('user-password-reset');
});
$app->addMiddleware(new CringeMiddleware());