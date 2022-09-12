<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Errors;

use Dizions\Unclogged\TestCase;

/**
 * @covers Dizions\Unclogged\Errors\HttpUnauthorizedException
 */
final class HttpUnauthorizedExceptionTest extends TestCase
{
    public function testResponseIncludesMessageForUser(): void
    {
        $exception = new HttpUnauthorizedException('the message');
        $this->assertStringContainsString(
            'the message',
            (string)$exception->getResponse($this->createEmptyApplication())->getBody()
        );
    }

    public function testResponseHasExpectedStatusCode(): void
    {
        $exception = new HttpUnauthorizedException('');
        $this->assertSame(401, $exception->getResponse($this->createEmptyApplication())->getStatusCode());
    }

    public function testResponseIncludesWwwAuthenticateHeader(): void
    {
        $exception = new HttpUnauthorizedException('');
        $this->assertNotEmpty($exception->getResponse($this->createEmptyApplication())->getHeader('WWW-Authenticate'));
    }
}
