<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Request;

use ArrayAccess;
use Dizions\Unclogged\Errors\HttpBadRequestException;
use Iterator;
use JsonException;
use LogicException;
use Psr\Http\Message\ServerRequestInterface;
use ReturnTypeWillChange;

class Request implements ArrayAccess, Iterator
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
        $this->initialiseAllParameters();
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
     * @param string $header Case-insensitive header name
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

    public function getValidator(): ParameterValidator
    {
        return new ParameterValidator($this);
    }

    #[ReturnTypeWillChange]
    public function current()
    {
        $this->initialiseAllParameters();
        return current($this->allParams);
    }

    #[ReturnTypeWillChange]
    public function key()
    {
        $this->initialiseAllParameters();
        return key($this->allParams);
    }

    public function next(): void
    {
        $this->initialiseAllParameters();
        next($this->allParams);
    }

    public function rewind(): void
    {
        $this->initialiseAllParameters();
        reset($this->allParams);
    }

    public function valid(): bool
    {
        $this->initialiseAllParameters();
        return key($this->allParams) !== null;
    }

    /**
     * Check whether a parameter exists in the request URI or body
     *
     * @param mixed $offset
     * @return bool
     * @throws HttpBadRequestException
     * @throws UnknownContentTypeException
     */
    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->getAllParams());
    }

    /**
     * Get a parameter from the request URI or body
     *
     * @param mixed $offset
     * @return mixed
     * @throws HttpBadRequestException
     * @throws UnknownContentTypeException
     */
    #[ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->getAllParams()[$offset] ?? null;
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     * @return never
     * @throws LogicException
     */
    public function offsetSet($offset, $value): void
    {
        throw new LogicException('Request parameters are read-only');
    }

    /**
     * @param mixed $offset
     * @return never
     * @throws LogicException
     */
    public function offsetUnset($offset): void
    {
        throw new LogicException('Request parameters are read-only');
    }

    private function assertBodyIsEmpty(): void
    {
        if ((string)$this->serverRequest->getBody() || $this->serverRequest->getParsedBody()) {
            throw new UnknownContentTypeException('No content-type specified for request body');
        }
    }

    private function decodeBodyParams(string $contentType): array
    {
        switch (explode(';', $contentType)[0]) {
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

    /**
     * @return void
     * @throws HttpBadRequestException
     * @throws UnknownContentTypeException
     */
    private function initialiseAllParameters(): void
    {
        if (isset($this->allParams)) {
            return;
        }
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
}
