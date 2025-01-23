<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Errors;

use Dizions\Unclogged\TestCase;

/**
 * @covers Dizions\Unclogged\Errors\HttpForbiddenException
 */
final class HttpForbiddenExceptionTest extends TestCase
{
    public function testResponseIncludesMessageForUser(): void
    {
        $exception = new HttpForbiddenException('the message');
        $this->assertStringContainsString(
            'the message',
            (string)$exception->getResponse($this->createEmptyApplication())->getBody()
        );
    }

    public function testResponseHasExpectedStatusCode(): void
    {
        $exception = new HttpForbiddenException('');
        $this->assertSame(403, $exception->getResponse($this->createEmptyApplication())->getStatusCode());
    }
}
