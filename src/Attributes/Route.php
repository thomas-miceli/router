<?php

namespace ThomasMiceli\Router\Attributes;

use Attribute;

#[Attribute]
final class Route {

    private string|array|null $middlewares;

    public function __construct(
        private string $path,
        private string $method,
        private ?string $name = null,
        string|array|null ...$middlewares,
    )
    {
        if (is_array($middlewares)) {
            if (array_key_exists('middlewares', $middlewares) && is_array($middlewares['middlewares'])) {
                $this->middlewares = [...$middlewares['middlewares']];
            } else {
                $this->middlewares = array_values($middlewares);
            }
        } else {
            $this->middlewares = $middlewares;
        }

    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getMiddlewares(): string|array|null
    {
        return $this->middlewares;
    }
}
