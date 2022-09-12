<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Request;

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
}
