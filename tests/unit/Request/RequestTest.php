<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Request;

use Laminas\Diactoros\ServerRequestFactory;
use Dizions\Unclogged\TestCase;

/** @covers Dizions\Unclogged\Request\Request */
final class RequestTest extends TestCase
{
    public function testServerRequestCanBeRetrieved(): void
    {
        $serverRequest = ServerRequestFactory::fromGlobals(['HTTP_X_FOO' => 'BAR']);
        $this->assertSame('BAR', (new Request($serverRequest))->getServerRequest()->getServerParams()['HTTP_X_FOO']);
    }

    public function testHeaderCanBeRetrievedCaseInsensitively(): void
    {
        $request = new Request(ServerRequestFactory::fromGlobals(['HTTP_X_FOO' => 'BAR']));
        $this->assertSame('BAR', $request->getHeader('X-FOO'));
        $this->assertSame('BAR', $request->getHeader('X-Foo'));
        $this->assertSame('BAR', $request->getHeader('x-foo'));
    }

    public function testMethodCanBeRetrieved(): void
    {
        $this->assertSame('', (new Request(ServerRequestFactory::fromGlobals([])))->getMethod());
        $this->assertSame(
            'GET',
            (new Request(ServerRequestFactory::fromGlobals(['REQUEST_METHOD' => 'GET'])))->getMethod()
        );
    }

    public function testRemoteAddressCanBeRetrieved(): void
    {
        $this->assertSame('', (new Request(ServerRequestFactory::fromGlobals([])))->getRemoteAddress());
        $this->assertSame(
            '::1',
            (new Request(ServerRequestFactory::fromGlobals(['REMOTE_ADDR' => '::1'])))->getRemoteAddress()
        );
    }
}
