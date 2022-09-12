<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Errors;

use Dizions\Unclogged\TestCase;

/**
 * @covers Dizions\Unclogged\Errors\HttpBadRequestException
 */
final class HttpBadRequestExceptionTest extends TestCase
{
    public function testResponseIncludesMessageForUser(): void
    {
        $exception = new HttpBadRequestException('the message');
        $this->assertStringContainsString('the message', (string)$exception->getResponse($this->createEmptyApplication())->getBody());
    }

    public function testResponseHasExpectedStatusCode(): void
    {
        $exception = new HttpBadRequestException('');
        $this->assertSame(400, $exception->getResponse($this->createEmptyApplication())->getStatusCode());
    }
}
