<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Http\Request;

use Dizions\Unclogged\Errors\HttpBadRequestException;
use Dizions\Unclogged\TestCase;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Diactoros\StreamFactory;
use LogicException;

/** @covers Dizions\Unclogged\Http\Request\Request */
final class RequestTest extends TestCase
{
    public function testServerRequestCanBeRetrieved(): void
    {
        $serverRequest = ServerRequestFactory::fromGlobals(['HTTP_X_FOO' => 'BAR']);
        $this->assertSame('BAR', (new Request($serverRequest))->getServerRequest()->getServerParams()['HTTP_X_FOO']);
    }

    public function testContentLengthCanBeRetrieved(): void
    {
        $serverRequest = ServerRequestFactory::fromGlobals([]);
        $this->assertNull((new Request($serverRequest))->getContentLength());
        $serverRequest = ServerRequestFactory::fromGlobals(['CONTENT_LENGTH' => '1234']);
        $this->assertSame(1234, (new Request($serverRequest))->getContentLength());
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

    public function testConflictingParametersInGetAndPostAreDisallowed(): void
    {
        $factory = new ServerRequestFactory();
        $server = ['CONTENT_TYPE' => 'application/x-www-form-urlencoded'];
        $request = new Request($factory->createServerRequest('POST', '/?a=1', $server)->withParsedBody(['a' => '2']));
        $this->expectException(HttpBadRequestException::class);
        $request->getAllParams();
    }

    public function testRepeatedParametersInGetAndPostAreAllowed(): void
    {
        $factory = new ServerRequestFactory();
        $server = ['CONTENT_TYPE' => 'application/x-www-form-urlencoded'];
        $request = new Request($factory->createServerRequest('POST', '/?a=1', $server)->withParsedBody(['a' => '1']));
        $this->assertSame(['a' => '1'], $request->getAllParams());
    }

    public function testParametersAreReadFromQueryStringAndBody(): void
    {
        $factory = new ServerRequestFactory();
        $server = ['CONTENT_TYPE' => 'application/x-www-form-urlencoded'];
        $request = new Request($factory->createServerRequest('POST', '/?a=1', $server)->withParsedBody(['b' => '2']));
        $this->assertSame(['a' => '1', 'b' => '2'], $request->getAllParams());
    }

    public function testMissingJsonInBodyIsReturnedAsNull(): void
    {
        $factory = new ServerRequestFactory();
        $request = new Request($factory->createServerRequest('POST', ''));
        $this->assertNull($request->getJsonParams());
    }

    public function testBodyMayConsistOfJsonEncodedInt(): void
    {
        $factory = new ServerRequestFactory();
        $body = (new StreamFactory())->createStream('1');
        $request = new Request($factory->createServerRequest('POST', '')->withBody($body));
        $this->assertSame(1, $request->getJsonParams());
    }

    public function testBodyMayConsistOfJsonEncodedString(): void
    {
        $factory = new ServerRequestFactory();
        $body = (new StreamFactory())->createStream('"a"');
        $request = new Request($factory->createServerRequest('POST', '')->withBody($body));
        $this->assertSame('a', $request->getJsonParams());
    }

    public function testInvalidJsonInBodyIsReturnedAsNull(): void
    {
        $factory = new ServerRequestFactory();
        $body = (new StreamFactory())->createStream('{');
        $request = new Request($factory->createServerRequest('POST', '')->withBody($body));
        $this->assertNull($request->getJsonParams());
    }

    public function testJsonEncodedBodyParametersMayBeEmpty(): void
    {
        $factory = new ServerRequestFactory();
        $server = ['CONTENT_TYPE' => 'application/json'];
        $request = new Request($factory->createServerRequest('POST', '/?a=1', $server));
        $this->assertSame([], $request->getBodyParams());
    }

    public function testJsonEncodedBodyParametersMayBeArray(): void
    {
        $factory = new ServerRequestFactory();
        $server = ['CONTENT_TYPE' => 'application/json'];
        $body = (new StreamFactory())->createStream('{"foo": "bar", "bar": 1}');
        $request = new Request($factory->createServerRequest('POST', '/?a=1', $server)->withBody($body));
        $this->assertSame(['foo' => 'bar', 'bar' => 1], $request->getBodyParams());
    }

    public function testJsonEncodedBodyParametersMustBeArrayIfProvided(): void
    {
        $factory = new ServerRequestFactory();
        $server = ['CONTENT_TYPE' => 'application/json'];
        $body = (new StreamFactory())->createStream('1');
        $request = new Request($factory->createServerRequest('POST', '/?a=1', $server)->withBody($body));
        $this->expectException(HttpBadRequestException::class);
        $request->getBodyParams();
    }

    public function testMissingContentTypeIsIgnoredWhenThereIsNoBodyToDecode(): void
    {
        $factory = new ServerRequestFactory();
        $request = new Request($factory->createServerRequest('POST', '/?a=1'));
        $this->assertSame([], $request->getBodyParams());
    }

    public function testMissingContentTypeIsRejectedWhenThereIsBodyContentToDecode(): void
    {
        $factory = new ServerRequestFactory();
        $body = (new StreamFactory())->createStream('x');
        $request = new Request($factory->createServerRequest('POST', '/?a=1')->withBody($body));
        $this->expectException(UnknownContentTypeException::class);
        $request->getBodyParams();
    }

    public function testUnknownContentTypeIsRejected(): void
    {
        $factory = new ServerRequestFactory();
        $server = ['CONTENT_TYPE' => 'unknown'];
        $request = new Request($factory->createServerRequest('POST', '/?a=1', $server));
        $this->expectException(UnknownContentTypeException::class);
        $request->getBodyParams();
    }

    public function testIssetCanBeUsedToCheckIfParameterExists(): void
    {
        $factory = new ServerRequestFactory();
        $server = ['CONTENT_TYPE' => 'application/x-www-form-urlencoded'];
        $request = new Request(
            $factory->createServerRequest('POST', '/?a=1', $server)->withParsedBody(['b' => '2'])
        );
        $this->assertTrue(isset($request['a']));
        $this->assertTrue(isset($request['b']));
        $this->assertFalse(isset($request['c']));
    }

    public function testOffsetGetCanBeUsedToRetrieveParameter(): void
    {
        $factory = new ServerRequestFactory();
        $server = ['CONTENT_TYPE' => 'application/x-www-form-urlencoded'];
        $request = new Request(
            $factory->createServerRequest('POST', '/?a=1', $server)->withParsedBody(['b' => '2'])
        );
        $this->assertSame('1', $request['a']);
        $this->assertSame('2', $request['b']);
        $this->assertNull($request['c']);
    }

    public function testOffsetSetIsNotAllowed(): void
    {
        $factory = new ServerRequestFactory();
        $server = ['CONTENT_TYPE' => 'application/x-www-form-urlencoded'];
        $request = new Request(
            $factory->createServerRequest('POST', '/?a=1', $server)->withParsedBody(['b' => '2'])
        );
        $this->expectException(LogicException::class);
        $request['a'] = '3';
    }

    public function testOffsetUnsetIsNotAllowed(): void
    {
        $factory = new ServerRequestFactory();
        $server = ['CONTENT_TYPE' => 'application/x-www-form-urlencoded'];
        $request = new Request(
            $factory->createServerRequest('POST', '/?a=1', $server)->withParsedBody(['b' => '2'])
        );
        $this->expectException(LogicException::class);
        unset($request['a']);
    }

    public function testCanBeIteratedOver(): void
    {
        $factory = new ServerRequestFactory();
        $server = ['CONTENT_TYPE' => 'application/x-www-form-urlencoded'];
        $request = new Request(
            $factory->createServerRequest('POST', '/?a=1', $server)->withParsedBody(['b' => '2'])
        );
        $this->assertSame(['a' => '1', 'b' => '2'], iterator_to_array($request));
    }

    public function testParametersCanBeValidated(): void
    {
        $factory = new ServerRequestFactory();
        $server = ['CONTENT_TYPE' => 'application/x-www-form-urlencoded'];
        $request = new Request(
            $factory->createServerRequest('POST', '/?a=1', $server)->withParsedBody(['b' => '2'])
        );
        $validator = $request->getValidator();
        $this->assertSame(1, $validator->int('a')->options([1, 3])->get());
    }

    public function testCanDetermineIfRequestIsHardRefresh(): void
    {
        $server = ['HTTP_CACHE_CONTROL' => 'no-cache', 'HTTP_PRAGMA' => 'no-cache'];
        $request = new Request(ServerRequestFactory::fromGlobals($server));
        $this->assertTrue($request->isHardRefresh());
        $request = new Request(ServerRequestFactory::fromGlobals([]));
        $this->assertFalse($request->isHardRefresh());
    }
}
