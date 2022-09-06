<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Request;

use Psr\Http\Message\ServerRequestInterface;

class Request
{
    private ServerRequestInterface $serverRequest;

    public function __construct(ServerRequestInterface $request)
    {
        $this->serverRequest = $request;
    }

    /**
     * Get the first matching header, or null.
     * @param string $header
     * @return null|string
     */
    public function getHeader(string $header): ?string
    {
        return $this->serverRequest->getHeader($header)[0] ?? null;
    }

    /** Get the HTTP request method */
    public function getMethod(): string
    {
        return $this->serverRequest->getServerParams()['REQUEST_METHOD'] ?? '';
    }

    public function getRemoteAddress(): string
    {
        return $this->serverRequest->getServerParams()['REMOTE_ADDR'] ?? '';
    }

    public function getServerRequest(): ServerRequestInterface
    {
        return $this->serverRequest;
    }
}
