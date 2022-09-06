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
        $exception = new HttpUnauthorizedException($this->createEmptyApplication(), 'the message');
        $this->assertStringContainsString('the message', (string)$exception->getResponse()->getBody());
    }

    public function testResponseIncludesWwwAuthenticateHeader(): void
    {
        $exception = new HttpUnauthorizedException($this->createEmptyApplication(), '');
        $this->assertNotEmpty($exception->getResponse()->getHeader('WWW-Authenticate'));
    }
}
