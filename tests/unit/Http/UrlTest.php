<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Http;

use PHPUnit\Framework\TestCase;

/** @covers Dizions\Unclogged\Http\Url */
final class UrlTest extends TestCase
{
    /** @dataProvider urlProvider */
    public function testParametersAreCorrectlyRetained(string $url): void
    {
        $url = new Url($url);
        $this->assertSame((string)$url, (string)$url->withParameters([]));
    }

    public function testParametersCanBeRetrieved(): void
    {
        $url = new Url('https://www.example.com?foo=1&bar=2');
        $this->assertSame('1', $url->getParameter('foo'));
        $this->assertSame('2', $url->getParameter('bar'));
        $this->assertNull($url->getParameter('baz'));
    }

    public function testBaseUrlCanBeReplaced(): void
    {
        $url = new Url('https://www.example.com?foo=1&bar=2');
        $this->assertSame('https://www.example.com?foo=1&bar=2', (string)$url);
        $this->assertSame('host?foo=1&bar=2', (string)$url->withBaseUrl('host'));
    }

    public function testParametersCanBeAddedAndReplaced(): void
    {
        $url = new Url('https://www.example.com?foo=1');
        $this->assertSame('https://www.example.com?foo=1&bar=2', (string)$url->withParameter('bar', '2'));
        $this->assertSame('https://www.example.com?foo=2', (string)$url->withParameter('foo', '2'));
        $this->assertSame(
            'https://www.example.com?foo=2&bar=2',
            (string)$url->withParameters(['foo' => '2', 'bar' => '2'])
        );
    }

    public function testParametersCanBeAddedIfUnset(): void
    {
        $url = new Url('https://www.example.com?foo=1');
        $this->assertSame('https://www.example.com?foo=1&bar=2', (string)$url->withParameterIfUnset('bar', '2'));
        $this->assertSame('https://www.example.com?foo=1', (string)$url->withParameterIfUnset('foo', '2'));
        $this->assertSame(
            'https://www.example.com?foo=1&bar=2',
            (string)$url->withParametersIfUnset(['foo' => '2', 'bar' => '2'])
        );
    }

    public function testParametersCanBeRemoved(): void
    {
        $url = new Url('https://www.example.com?foo=1&bar=2');
        $this->assertSame('https://www.example.com?bar=2', (string)$url->withoutParameter('foo'));
        $this->assertSame('https://www.example.com', (string)$url->withoutParameter('foo')->withoutParameter('bar'));
    }

    public function testParametersCanBeCleared(): void
    {
        $url = new Url('https://www.example.com?foo=1&bar=2');
        $this->assertSame('https://www.example.com', (string)$url->withoutParameters());
    }

    public static function urlProvider(): array
    {
        return [
            ['host'],
            ['https://www.example.com'],
            ['https://www.example.com?foo=1'],
            ['https://www.example.com?foo=1&bar=2'],
            ['https://www.example.com?foo'],
            ['https://www.example.com?foo&bar'],
        ];
    }
}
