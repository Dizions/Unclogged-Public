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
        $exception = new HttpForbiddenException($this->createEmptyApplication(), 'the message');
        $this->assertStringContainsString('the message', (string)$exception->getResponse()->getBody());
    }

    public function testResponseHasExpectedStatusCode(): void
    {
        $exception = new HttpForbiddenException($this->createEmptyApplication(), '');
        $this->assertSame(403, $exception->getResponse()->getStatusCode());
    }
}
