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
            (new HttpException('', 500))->getResponse($this->createEmptyApplication())
        );
    }

    public function testResponseHasCorrectStatusCode(): void
    {
        $this->assertSame(
            400,
            (new HttpException('', 400))->getResponse($this->createEmptyApplication())->getStatusCode()
        );
        $this->assertSame(
            500,
            (new HttpException('', 500))->getResponse($this->createEmptyApplication())->getStatusCode()
        );
    }

    public function testResponseHasCorrectContentType(): void
    {
        $response = (new HttpException('the message', 400))->getResponse($this->createEmptyApplication());
        $this->assertSame(['application/json'], $response->getHeader('content-type'));
    }

    public function testResponseHasCorrectContent(): void
    {
        $response = (new HttpException('the message', 400))->getResponse($this->createEmptyApplication());
        $this->assertSame('"the message"', (string)$response->getBody());
    }
}
