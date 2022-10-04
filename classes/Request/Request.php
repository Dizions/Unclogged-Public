<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Request;

use Dizions\Unclogged\Errors\HttpBadRequestException;
use JsonException;
use Psr\Http\Message\ServerRequestInterface;

class Request
{
    private array $allParams;
    private array $queryParams;
    private ServerRequestInterface $serverRequest;

    public function __construct(ServerRequestInterface $request)
    {
        $this->serverRequest = $request;
    }

    /**
     * Get all parameters from the request URI and body
     * @return array
     * @throws HttpBadRequestException If the content-type is application/json but the decoded body
     *                                 content is not an array, or the request URI and body specify
     *                                 conflicting values for a parameter.
     * @throws UnknownContentTypeException If an unrecognised content-type is specified, or a body
     *                                     is provided with no content-type
     */
    public function getAllParams(): array
    {
        if (!isset($this->allParams)) {
            $queryParams = $this->getQueryParams();
            $bodyParams = $this->getBodyParams();
            $potentialConflicts = array_intersect_key($queryParams, $bodyParams);
            foreach ($potentialConflicts as $key => $queryVersion) {
                if ($bodyParams[$key] !== $queryVersion) {
                    throw new HttpBadRequestException("Conflicting values for $key given in request");
                }
            }
            $this->allParams = array_merge($this->getQueryParams(), $this->getBodyParams());
        }
        return $this->allParams;
    }

    /**
     * Get all parameters from the request body
     * @return array
     * @throws HttpBadRequestException If the content-type is application/json but the decoded
     *                                 content is not an array
     * @throws UnknownContentTypeException If an unrecognised content-type is specified, or a body
     *                                     is provided with no content-type
     */
    public function getBodyParams(): array
    {
        $contentType = $this->serverRequest->getServerParams()['CONTENT_TYPE'] ?? '';
        if (empty($contentType)) {
            $this->assertBodyIsEmpty();
            return [];
        }
        return $this->decodeBodyParams($contentType);
    }

    public function getContentLength(): ?int
    {
        $contentLength =  $this->serverRequest->getServerParams()['CONTENT_LENGTH'] ?? null;
        return $contentLength === null ? null : (int)$contentLength;
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

    /**
     * Attempt to interpret the request body as json, and return the result.
     * Does not depend on the content-type being application/json.
     *
     * @return mixed The decoded result, or null on failure
     */
    public function getJsonParams()
    {
        try {
            return json_decode((string)$this->serverRequest->getBody(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            return null;
        }
    }

    /** Get the HTTP request method */
    public function getMethod(): string
    {
        return $this->serverRequest->getServerParams()['REQUEST_METHOD'] ?? '';
    }

    public function getQueryParams(): array
    {
        if (!isset($this->queryParams)) {
            $this->queryParams = [];
            parse_str($this->serverRequest->getUri()->getQuery(), $this->queryParams);
        }
        return $this->queryParams;
    }

    public function getRemoteAddress(): string
    {
        return $this->serverRequest->getServerParams()['REMOTE_ADDR'] ?? '';
    }

    public function getServerRequest(): ServerRequestInterface
    {
        return $this->serverRequest;
    }

    private function assertBodyIsEmpty(): void
    {
        if ((string)$this->serverRequest->getBody() || $this->serverRequest->getParsedBody()) {
            throw new UnknownContentTypeException('No content-type specified for request body');
        }
    }

    private function decodeBodyParams(string $contentType): array
    {
        switch ($contentType) {
            case 'application/json':
                return $this->getJsonArray();
            case 'application/x-www-form-urlencoded':
            case 'multipart/form-data':
                return $this->serverRequest->getParsedBody();
        }
        throw new UnknownContentTypeException("Unknown content-type: $contentType");
    }

    private function getJsonArray(): array
    {
        $decodedBody = $this->getJsonParams();
        if ($decodedBody === null) {
            return [];
        }
        if (is_array($decodedBody)) {
            return $decodedBody;
        }
        throw new HttpBadRequestException('Expected JSON-encoded array in request body');
    }
}
