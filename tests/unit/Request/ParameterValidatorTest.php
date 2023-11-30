<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Request;

use DateTimeInterface;
use Dizions\Unclogged\Input\BooleanParameter;
use Dizions\Unclogged\Input\DateTimeParameter;
use Dizions\Unclogged\Input\IntParameter;
use Dizions\Unclogged\Input\IpAddressParameter;
use Dizions\Unclogged\Input\MissingParameterException;
use Dizions\Unclogged\Input\StringParameter;
use Dizions\Unclogged\TestCase;

/**
 * @covers Dizions\Unclogged\Request\ParameterValidator
 */
final class ParameterValidatorTest extends TestCase
{
    public function testBooleanParameterCanBeRetrieved(): void
    {
        $request = $this->createMock(Request::class);
        $this->assertInstanceOf(BooleanParameter::class, (new ParameterValidator($request))->boolean('a'));
    }

    public function testBooleanParameterCanBeRetrievedDirectly(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->any())->method('getAllParams')->will($this->returnValue(['a' => 1]));
        $this->assertTrue((new ParameterValidator($request))->getBoolean('a'));
    }

    public function testDateTimeParameterCanBeRetrieved(): void
    {
        $request = $this->createMock(Request::class);
        $this->assertInstanceOf(DateTimeParameter::class, (new ParameterValidator($request))->datetime('a'));
    }

    public function testDateTimeParameterCanBeRetrievedDirectly(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->any())->method('getAllParams')->will($this->returnValue(['a' => '2000-01-01']));
        $this->assertInstanceOf(DateTimeInterface::class, (new ParameterValidator($request))->getDateTime('a'));
    }

    public function testDateTimeParameterCanBeRetrievedDirectlyAsString(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->any())->method('getAllParams')->will($this->returnValue(['a' => '2000-01-01']));
        $this->assertIsString((new ParameterValidator($request))->getDateTimeString('a'));
    }

    public function testIntParameterCanBeRetrieved(): void
    {
        $request = $this->createMock(Request::class);
        $this->assertInstanceOf(IntParameter::class, (new ParameterValidator($request))->int('a'));
    }

    public function testIntParameterCanBeRetrievedDirectly(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->any())->method('getAllParams')->will($this->returnValue(['a' => 1]));
        $this->assertSame(1, (new ParameterValidator($request))->getInt('a'));
    }

    public function testIpAddressParameterCanBeRetrieved(): void
    {
        $request = $this->createMock(Request::class);
        $this->assertInstanceOf(IpAddressParameter::class, (new ParameterValidator($request))->ipAddress('a'));
    }

    public function testIpAddressParameterCanBeRetrievedDirectly(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->any())->method('getAllParams')->will($this->returnValue(['a' => '::1']));
        $this->assertSame('::1', (new ParameterValidator($request))->getIpAddress('a'));
    }

    public function testStringParameterCanBeRetrieved(): void
    {
        $request = $this->createMock(Request::class);
        $this->assertInstanceOf(StringParameter::class, (new ParameterValidator($request))->string('a'));
    }

    public function testStringParameterCanBeRetrievedDirectly(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->any())->method('getAllParams')->will($this->returnValue(['a' => 1]));
        $this->assertSame('1', (new ParameterValidator($request))->getString('a'));
    }

    public function testParameterCanBeRetrievedFromQueryStringAlone(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->any())->method('getAllParams')->will($this->returnValue(['a' => 1, 'b' => 2]));
        $request->expects($this->any())->method('getBodyParams')->will($this->returnValue(['a' => 1]));
        $request->expects($this->any())->method('getQueryParams')->will($this->returnValue(['b' => 2]));
        $validator = new ParameterValidator($request);
        $this->assertSame(1, $validator->fromBody()->getInt('a'));
        $this->expectException(MissingParameterException::class);
        $validator->fromBody()->getInt('b');
    }

    public function testParameterCanBeRetrievedFromBodyAlone(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->any())->method('getAllParams')->will($this->returnValue(['a' => 1, 'b' => 2]));
        $request->expects($this->any())->method('getBodyParams')->will($this->returnValue(['a' => 1]));
        $request->expects($this->any())->method('getQueryParams')->will($this->returnValue(['b' => 2]));
        $validator = new ParameterValidator($request);
        $this->assertSame(2, $validator->fromQueryString()->getInt('b'));
        $this->expectException(MissingParameterException::class);
        $validator->fromQueryString()->getInt('a');
    }
}
