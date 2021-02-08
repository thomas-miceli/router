<?php

namespace ThomasMiceli\Router\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

final class Response implements ResponseInterface
{
    public function __construct(
        private ResponseInterface $response,
        private StreamFactoryInterface $streamFactory,
    ) {}

    public function getProtocolVersion(): string
    {
        return $this->response->getProtocolVersion();
    }

    public function withProtocolVersion($version): Response
    {
        return new Response($this->response->withProtocolVersion($version), $this->streamFactory);
    }

    public function getHeaders(): array
    {
        return $this->response->getHeaders();
    }

    public function hasHeader($name): bool
    {
        return $this->response->hasHeader($name);
    }

    public function getHeader($name): array
    {
        return $this->response->getHeader($name);
    }

    public function getHeaderLine($name): string
    {
        return $this->response->getHeaderLine($name);
    }

    public function withAddedHeader($name, $value): Response
    {
        return new Response($this->response->withAddedHeader($name, $value), $this->streamFactory);
    }

    public function withoutHeader($name): Response
    {
        return new Response($this->response->withoutHeader($name), $this->streamFactory);
    }

    public function getBody(): StreamInterface
    {
        return $this->response->getBody();
    }

    public function getStatusCode(): int
    {
        return $this->response->getStatusCode();
    }

    public function withStatus($code, $reasonPhrase = ''): Response
    {
        return new Response($this->response->withStatus($code, $reasonPhrase), $this->streamFactory);
    }

    public function getReasonPhrase(): string
    {
        return $this->response->getReasonPhrase();
    }

    public function json($data, ?int $status = 200, int $options = 0, int $depth = 512): Response
    {
        $response = $this
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->streamFactory->createStream(json_encode($data, $options, $depth)));

        $response = $response->response->withStatus($status);

        return new Response($response, $this->streamFactory);
    }

    public function withBody(StreamInterface $body): Response
    {
        return new Response($this->response->withBody($body), $this->streamFactory);
    }

    public function withHeader($name, $value): Response
    {
        return new Response($this->response->withHeader($name, $value), $this->streamFactory);
    }

    public function error(): Response
    {
        return $this->withStatus(500);
    }

    public function notFound(): Response
    {
        return $this->withStatus(404);
    }

    public function forbidden(): Response
    {
        return $this->withStatus(403);
    }

    public function setHeader($name, $value): Response
    {
        $this->response = $this->response->withHeader($name, $value);
        return $this;
    }

    public function setStatus($code, $reasonPhrase): Response
    {
        $this->response = $this->response->withStatus($code, $reasonPhrase);
        return $this;
    }

    public function setBody(StreamInterface $body): Response
    {
        $this->response = $this->response->withBody($body);
        return $this;
    }

    public function addHeader($name, $value): Response
    {
        $this->response = $this->response->withAddedHeader($name, $value);
        return $this;
    }

    public function removeHeader($name): Response
    {
        $this->response = $this->response->withoutHeader($name);
        return $this;
    }

    public function setProtocolVersion($version): Response
    {
        $this->response = $this->response->withProtocolVersion($version);
        return $this;
    }
}