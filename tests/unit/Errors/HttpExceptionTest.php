<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Errors;

use Psr\Http\Message\ResponseInterface;
use Dizions\Unclogged\TestCase;

/**
 * @covers Dizions\Unclogged\Errors\HttpException
 */
final class HttpExceptionTest extends TestCase
{
    public function testHttpExceptionCanGenerateResponseObject(): void
    {
        $this->assertInstanceOf(
            ResponseInterface::class,
            (new HttpException($this->createEmptyApplication(), '', 500))->getResponse()
        );
    }

    public function testResponseHasCorrectStatusCode(): void
    {
        $this->assertSame(
            400,
            (new HttpException($this->createEmptyApplication(), '', 400))->getResponse()->getStatusCode()
        );
        $this->assertSame(
            500,
            (new HttpException($this->createEmptyApplication(), '', 500))->getResponse()->getStatusCode()
        );
    }

    public function testResponseHasCorrectContentType(): void
    {
        $response = (new HttpException($this->createEmptyApplication(), 'the message', 400))->getResponse();
        $this->assertSame(['application/json'], $response->getHeader('content-type'));
    }

    public function testResponseHasCorrectContent(): void
    {
        $response = (new HttpException($this->createEmptyApplication(), 'the message', 400))->getResponse();
        $this->assertSame('"the message"', (string)$response->getBody());
    }
}
