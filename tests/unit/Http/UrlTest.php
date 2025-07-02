<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Http;

use PHPUnit\Framework\TestCase;

/** @covers Dizions\Unclogged\Http\Url */
final class UrlTest extends TestCase
{
    public function testEmptyParametersCanBeDistinguished()
    {
        $url = 'https://www.example.com?foo=&bar';
        $urlObj = new Url($url);
        $this->assertEquals(['foo' => '', 'bar' => null], $urlObj->getParameters());
        $this->assertEquals('', $urlObj->getParameter('foo'));
        $this->assertNull($urlObj->getParameter('bar'));
        $this->assertTrue($urlObj->hasParameter('foo'));
        $this->assertNull($urlObj->getParameter('baz'));
        $this->assertFalse($urlObj->hasParameter('baz'));
        $this->assertNull($urlObj->withParameter('foo')->getParameter('foo'));
    }

    public function testParametersAreCorrectlyAdded()
    {
        $url = 'https://www.example.com';
        $this->assertEquals([], (new Url($url))->getParameters());
        $this->assertNull((new Url($url))->getQuery());
        $fooUrl = 'https://www.example.com?foo=1';
        $this->assertEquals($fooUrl, (string)(new Url($url))->withParameters(['foo' => 1]));
        $this->assertEquals($fooUrl, (string)(new Url($url))->withParametersIfUnset(['foo' => 1]));
        $barUrl = 'https://www.example.com?foo=1&bar=2';
        $this->assertEquals($barUrl, (string)(new Url($url))->withParameters(['foo' => 1, 'bar' => 2]));
        $this->assertEquals($barUrl, (string)(new Url($fooUrl))->withParameters(['bar' => 2]));
        $this->assertEquals(
            ['foo' => '1', 'bar' => '2'],
            (new Url($fooUrl))->withParameters(['bar' => 2])->getParameters()
        );
        $fooUrl = 'https://www.example.com?foo';
        $this->assertEquals($fooUrl, (string)(new Url($url))->withParameter('foo'));
    }

    /** @dataProvider urlProvider */
    public function testParametersAreCorrectlyRetained(string $url): void
    {
        $url = new Url($url);
        $this->assertSame((string)$url, (string)$url->withParameters([]));
    }

    public function testParametersCanBeRetrieved(): void
    {
        $url = new Url('https://www.example.com?foo=1&bar=2');
        $this->assertSame(['foo' => '1', 'bar' => '2'], $url->getParameters());
        $this->assertSame('1', $url->getParameter('foo'));
        $this->assertSame('2', $url->getParameter('bar'));
        $this->assertNull($url->getParameter('baz'));
    }

    public function testBaseUrlCanBeReplaced(): void
    {
        $url = new Url('https://www.example.com?foo=1&bar=2');
        $this->assertSame('https://www.example.com?foo=1&bar=2', (string)$url);
        $this->assertSame('host?foo=1&bar=2', (string)$url->withBaseUrl('host'));
        $this->assertSame('newhost', $url->withBaseUrl('newhost')->getBaseUrl());
    }

    public function testFragmentCanBeRetrievedAndSet()
    {
        $url = 'https://www.example.com#fragment';
        $this->assertEquals('fragment', (new Url($url))->getFragment());
        $url = 'https://www.example.com?foo#fragment';
        $this->assertEquals('fragment', (new Url($url))->getFragment());
        $url = 'https://www.example.com';
        $this->assertNull((new Url($url))->getFragment());
        $this->assertEquals(
            'https://www.example.com#fragment',
            (string)(new Url($url))->withFragment('fragment')
        );
        $url = 'https://www.example.com#fragment';
        $this->assertEquals(
            'https://www.example.com#new-fragment',
            (string)(new Url($url))->withFragment('new-fragment')
        );
        $url = 'https://www.example.com#';
        $this->assertEquals('', (new Url($url))->getFragment());
        $url = 'https://www.example.com#fragment';
        $this->assertEquals(
            'https://www.example.com',
            (string)(new Url($url))->withoutFragment()
        );
    }

    public function testHostCanBeRetrievedAndSet()
    {
        $url = 'https://www.example.com';
        $this->assertEquals('www.example.com', (new Url($url))->getHost());
        $this->assertEquals('https://example.org', (string)(new Url($url))->withHost('example.org'));
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

    public function testPathCanBeRetrievedAndSet()
    {
        $url = 'https://www.example.com';
        $this->assertNull((new Url($url))->getPath());
        $url = 'https://www.example.com/path/to/resource';
        $this->assertEquals('/path/to/resource', (new Url($url))->getPath());
        $this->assertEquals(
            'https://www.example.com/new/path',
            (string)(new Url($url))->withPath('/new/path')
        );
        $this->assertEquals(
            'https://www.example.com',
            (string)(new Url($url))->withPath('')
        );
    }

    public function testPortCanBeRetrievedAndSet()
    {
        $url = 'https://www.example.com';
        $this->assertNull((new Url($url))->getPort());
        $url = 'https://www.example.com:8080';
        $this->assertEquals(8080, (new Url($url))->getPort());
        $this->assertEquals(
            'https://www.example.com:9090',
            (string)(new Url($url))->withPort(9090)
        );
        $this->assertEquals(
            'https://www.example.com',
            (string)(new Url('https://www.example.com'))->withPort(null)
        );
        $this->assertEquals(
            'https://www.example.com',
            (string)(new Url('https://www.example.com:8080'))->withPort(null)
        );
    }

    public function testSchemeCanBeRetrievedAndSet()
    {
        $url = 'https://www.example.com';
        $this->assertEquals('https', (new Url($url))->getScheme());
        $url = 'http://www.example.com';
        $this->assertEquals('http', (new Url($url))->getScheme());
        $this->assertEquals(
            'https://www.example.com',
            (string)(new Url($url))->withScheme('https')
        );
        $this->assertEquals(
            'http://www.example.com',
            (string)(new Url($url))->withScheme('http')
        );
    }

    public function testUserAndPasswordCanBeRetrievedAndSet()
    {
        $url = 'https://www.example.com';
        $this->assertNull((new Url($url))->getUser());
        $this->assertNull((new Url($url))->getPassword());
        $url = 'https://user1@www.example.com';
        $this->assertEquals('user1', (new Url($url))->getUser());
        $this->assertNull((new Url($url))->getPassword());
        $url = 'https://user1:password1@www.example.com';
        $this->assertEquals('user1', (new Url($url))->getUser());
        $this->assertEquals('password1', (new Url($url))->getPassword());

        $url = 'https://www.example.com';
        $this->assertEquals(
            'https://user2@www.example.com',
            (string)(new Url($url))->withUser('user2')
        );
        $this->assertEquals(
            'https://user2:password2@www.example.com',
            (string)(new Url($url))->withUser('user2', 'password2')
        );
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
