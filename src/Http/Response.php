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
    )
    {
    }

    public function getProtocolVersion(): string
    {
        return $this->response->getProtocolVersion();
    }

    public function withProtocolVersion($version): self
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

    public function withAddedHeader($name, $value): self
    {
        return new Response($this->response->withAddedHeader($name, $value), $this->streamFactory);
    }

    public function withoutHeader($name): self
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

    public function getReasonPhrase(): string
    {
        return $this->response->getReasonPhrase();
    }

    public function json($data, ?int $status = 200, int $options = 0, int $depth = 512): self
    {
        $response = $this
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->streamFactory->createStream(json_encode($data, $options, $depth)));

        $response = $response->response->withStatus($status);

        return new Response($response, $this->streamFactory);
    }

    public function withBody(StreamInterface $body): self
    {
        return new Response($this->response->withBody($body), $this->streamFactory);
    }

    public function withHeader($name, $value): self
    {
        return new Response($this->response->withHeader($name, $value), $this->streamFactory);
    }

    public function error(): self
    {
        $this->setStatus(500);

        return $this;
    }

    public function withStatus($code, $reasonPhrase = ''): self
    {
        return new Response($this->response->withStatus($code, $reasonPhrase), $this->streamFactory);
    }

    public function notFound(): self
    {
        $this->setStatus(404);

        return $this;
    }

    public function forbidden(): self
    {
        $this->setStatus(403);

        return $this;
    }

    public function setHeader($name, $value): self
    {
        $this->response = $this->response->withHeader($name, $value);
        
        return $this;
    }

    public function setStatus($code, $reasonPhrase = ''): self
    {
        $this->response = $this->response->withStatus($code, $reasonPhrase);
        
        return $this;
    }

    public function setBody(StreamInterface $body): self
    {
        $this->response = $this->response->withBody($body);
        
        return $this;
    }

    public function addHeader($name, $value): self
    {
        $this->response = $this->response->withAddedHeader($name, $value);
        
        return $this;
    }

    public function removeHeader($name): self
    {
        $this->response = $this->response->withoutHeader($name);
        
        return $this;
    }

    public function setProtocolVersion($version): self
    {
        $this->response = $this->response->withProtocolVersion($version);
        
        return $this;
    }
}