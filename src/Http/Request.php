<?php

namespace ThomasMiceli\Router\Http;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

final class Request implements ServerRequestInterface
{
    public function __construct(
        private ServerRequestInterface $request,
    ) {}

    public function getProtocolVersion(): string
    {
        return $this->request->getProtocolVersion();
    }

    public function withProtocolVersion($version): Request
    {
        return new Request($this->request->withProtocolVersion($version));
    }

    public function getHeaders(): array
    {
        return $this->request->getHeaders();
    }

    public function hasHeader($name): bool
    {
        return $this->request->hasHeader($name);
    }

    public function getHeader($name): array
    {
        return $this->request->getHeader($name);
    }

    public function getHeaderLine($name): string
    {
        return $this->request->getHeaderLine($name);
    }

    public function withHeader($name, $value): Request
    {
        return new Request($this->request->withHeader($name, $value));
    }

    public function withAddedHeader($name, $value): Request
    {
        return new Request($this->request->withAddedHeader($name, $value));
    }

    public function withoutHeader($name): Request
    {
        return new Request($this->request->withoutHeader($name));
    }

    public function getBody(): StreamInterface
    {
        return $this->request->getBody();
    }

    public function withBody(StreamInterface $body): Request
    {
        return new Request($this->request->withBody($body));
    }

    public function getRequestTarget(): string
    {
        return $this->request->getRequestTarget();
    }

    public function withRequestTarget($requestTarget): Request
    {
        return new Request($this->request->withRequestTarget($requestTarget));
    }

    public function getMethod(): string
    {
        return $this->request->getMethod();
    }

    public function withMethod($method): Request
    {
        return new Request($this->request->withMethod($method));
    }

    public function getUri(): UriInterface
    {
        return $this->request->getUri();
    }

    public function withUri(UriInterface $uri, $preserveHost = false): Request
    {
        return new Request($this->request->withUri($uri, $preserveHost));
    }

    public function getServerParams(): array
    {
        return $this->request->getServerParams();
    }

    public function getCookieParams(): array
    {
        return $this->request->getCookieParams();
    }

    public function withCookieParams(array $cookies): Request
    {
        return new Request($this->request->withCookieParams($cookies));
    }

    public function getQueryParams(): array
    {
        return $this->request->getQueryParams();
    }

    public function withQueryParams(array $query): Request
    {
        return new Request($this->request->withQueryParams($query));
    }

    public function getUploadedFiles(): array
    {
        return $this->request->getUploadedFiles();
    }

    public function withUploadedFiles(array $uploadedFiles): Request
    {
        return new Request($this->request->withUploadedFiles($uploadedFiles));
    }

    public function getParsedBody(): object|array|null
    {
        return $this->request->getParsedBody();
    }

    public function withParsedBody($data): Request
    {
        return new Request($this->request->withParsedBody($data));
    }

    public function getAttributes(): array
    {
        return $this->request->getAttributes();
    }

    public function getAttribute($name, $default = null)
    {
        return $this->request->getAttribute($name, $default);
    }

    public function withAttribute($name, $value): Request
    {
        return new Request($this->request->withAttribute($name, $value));
    }

    public function withoutAttribute($name): Request
    {
        return new Request($this->request->withoutAttribute($name));
    }
}