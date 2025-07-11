<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Http\Request;

use Dizions\Unclogged\Setup\Environment;
use Dizions\Unclogged\TestCase;

/** @covers Dizions\Unclogged\Http\Request\RequestFactory */
final class RequestFactoryTest extends TestCase
{
    public function testCanCreateRequestWithNoParameters(): void
    {
        $env = new Environment([]);
        $this->assertInstanceOf(Request::class, (new RequestFactory($env))->fromGlobals());
        $this->assertInstanceOf(Request::class, (new RequestFactory($env))->proxiedRequestFromGlobals());
    }

    /** @dataProvider remoteAddressServerInfoProvider */
    public function testCanDetermineRealSourceIp(string $expected, array $server): void
    {
        $request = (new RequestFactory($this->getDefaultEnvironment()))->fromGlobals($server);
        $request = RequestFactory::default()->fromGlobals($server);
        $this->assertSame($expected, $request->getRemoteAddress());
    }

    public function testTrustedRemotesCanBeExplicitlySet(): void
    {
        $server = [
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_X_FORWARDED_FOR' => '1.2.3.4',
        ];
        $this->assertSame(
            '127.0.0.1',
            RequestFactory::withTrustedProxies([])->fromGlobals($server)->getRemoteAddress()
        );
        $this->assertSame(
            '127.0.0.1',
            RequestFactory::withTrustedProxies(['1.2.3.4'])->fromGlobals($server)->getRemoteAddress()
        );
        $server = [
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_X_FORWARDED_FOR' => '1.2.3.4,10.0.0.5',
        ];
        $this->assertSame(
            '10.0.0.5',
            RequestFactory::withTrustedProxies(['127.0.0.1', '1.2.3.4'])->fromGlobals($server)
                                                                        ->getRemoteAddress()
        );
    }

    public static function remoteAddressServerInfoProvider()
    {
        return [
            'Local connection' => ['127.0.0.1', ['REMOTE_ADDR' => '127.0.0.1']],
            'Remote connection' => ['1.2.3.4', ['REMOTE_ADDR' => '1.2.3.4']],
            'Remote connection, local proxy' => ['1.2.3.4', [
                'REMOTE_ADDR' => '127.0.0.1',
                'HTTP_X_FORWARDED_FOR' => '1.2.3.4',
            ]],
            'Remote connection, local proxy using X-Real-IP' => ['1.2.3.4', [
                'REMOTE_ADDR' => '127.0.0.1',
                'HTTP_X_REAL_IP' => '1.2.3.4',
            ]],
            'X-Forwarded-For supersedes X-Real-IP' => ['1.2.3.4', [
                'REMOTE_ADDR' => '127.0.0.1',
                'HTTP_X_FORWARDED_FOR' => '1.2.3.4',
                'HTTP_X_REAL_IP' => '127.0.0.1',
            ]],
            'Remote connection, remote proxy' => ['1.2.3.4', [
                'REMOTE_ADDR' => '10.0.0.5',
                'HTTP_X_FORWARDED_FOR' => '1.2.3.4',
            ]],
            'Remote connection, multiple proxies' => ['1.2.3.4', [
                'REMOTE_ADDR' => '127.0.0.1',
                'HTTP_X_FORWARDED_FOR' => '1.2.3.4,10.0.0.5',
            ]],
            'Remote connection, multiple proxies, untrusted extra data' => ['1.2.3.4', [
                'REMOTE_ADDR' => '127.0.0.1',
                'HTTP_X_FORWARDED_FOR' => '2.3.4.5,1.2.3.4,10.0.0.5',
            ]],
            'Remote connection, multiple proxies, untrusted extra proxy data' => ['1.2.3.4', [
                'REMOTE_ADDR' => '127.0.0.1',
                'HTTP_X_FORWARDED_FOR' => '2.3.4.5,10.0.0.5,1.2.3.4,10.0.0.5',
            ]],
        ];
    }
}
