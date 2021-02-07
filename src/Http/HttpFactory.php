<?php

namespace ThomasMiceli\Router\Http;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;

final class HttpFactory
{
    public static function request(): Request
    {
        $psr17Factory = new Psr17Factory();
        $creator = new ServerRequestCreator(
            $psr17Factory, // ServerRequestFactory
            $psr17Factory, // UriFactory
            $psr17Factory, // UploadedFileFactory
            $psr17Factory  // StreamFactory
        );

        $request = $creator->fromGlobals();
        return new Request($request);
    }

    public static function response(): Response
    {
        $psr17Factory = new Psr17Factory();

        return new Response($psr17Factory->createResponse(), $psr17Factory);
    }

}