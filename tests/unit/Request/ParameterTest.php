<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Request;

use Dizions\Unclogged\TestCase;

/**
 * @covers Dizions\Unclogged\Request\Parameter
 */
final class ParameterTest extends TestCase
{
    public function testDefaultWillBeUsedIfParameterWasNotGiven(): void
    {
        $request = $this->createMock(Request::class);
        $parameter = $this->getMockForAbstractClass(Parameter::class, ['var', $request]);
        $parameter->default(5);
        $this->assertSame(5, $parameter->get());
    }

    public function testDefaultMayBeNull(): void
    {
        $request = $this->createMock(Request::class);
        $parameter = $this->getMockForAbstractClass(Parameter::class, ['var', $request]);
        $parameter->default(null);
        $this->assertSame(null, $parameter->get());
    }

    public function testParameterCanBeCastToString(): void
    {
        $request = $this->createMock(Request::class);
        $parameter = $this->getMockForAbstractClass(Parameter::class, ['var', $request]);
        $parameter->default(5);
        $this->assertSame('5', (string)$parameter);
    }

    public function testExceptionWillBeThrownIfRequiredParameterWasNotGiven(): void
    {
        $request = $this->createMock(Request::class);
        $parameter = $this->getMockForAbstractClass(Parameter::class, ['var', $request]);
        $this->expectException(MissingParameterException::class);
        $parameter->get();
    }

    public function testParameterCanBeRetrievedFromQueryStringOrBody(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->any())->method('getAllParams')->will($this->returnValue(['a' => 1, 'b' => 2]));
        $request->expects($this->any())->method('getBodyParams')->will($this->returnValue(['a' => 1]));
        $request->expects($this->any())->method('getQueryParams')->will($this->returnValue(['b' => 2]));
        $parameter = $this->getMockForAbstractClass(Parameter::class, ['a', $request]);
        $this->assertSame(1, $parameter->get());
        $parameter = $this->getMockForAbstractClass(Parameter::class, ['b', $request]);
        $this->assertSame(2, $parameter->get());
    }

    public function testValidOptionsCanBeSpecified(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->any())->method('getAllParams')->will($this->returnValue(['a' => 1, 'b' => 2]));
        $parameter = $this->getMockForAbstractClass(Parameter::class, ['a', $request]);
        $this->assertSame(1, $parameter->options([1, 2, 3])->get());
        $parameter = $this->getMockForAbstractClass(Parameter::class, ['b', $request]);
        $this->expectException(InvalidParameterException::class);
        $parameter->options([1, 3])->get();
    }

    public function testCustomValidatorCanBeRefined(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->any())->method('getAllParams')->will($this->returnValue(['a' => 1]));
        $parameter = $this->getMockForAbstractClass(Parameter::class, ['a', $request]);
        $parameter->addValidator(fn () =>  true);
        $this->assertSame(1, $parameter->get());
        $parameter = $this->getMockForAbstractClass(Parameter::class, ['a', $request]);
        $parameter->addValidator(fn () =>  false);
        $this->expectException(InvalidParameterException::class);
        $parameter->get();
    }

    public function testCustomValidatorsMustAllPass(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->any())->method('getAllParams')->will($this->returnValue(['a' => 1]));
        $parameter = $this->getMockForAbstractClass(Parameter::class, ['a', $request]);
        $parameter->addValidator(fn () =>  true);
        $parameter->addValidator(fn () =>  false);
        $this->expectException(InvalidParameterException::class);
        $parameter->get();
    }
}
