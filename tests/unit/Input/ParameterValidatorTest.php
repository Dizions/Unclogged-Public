<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Input;

use DateTimeInterface;
use Dizions\Unclogged\TestCase;

/**
 * @covers Dizions\Unclogged\Input\ParameterValidator
 */
final class ParameterValidatorTest extends TestCase
{
    public function testBooleanParameterCanBeRetrieved(): void
    {
        $this->assertInstanceOf(BooleanParameter::class, (new ParameterValidator())->boolean('a'));
    }

    public function testBooleanParameterCanBeRetrievedDirectly(): void
    {
        $this->assertTrue((new ParameterValidator())->setData(['a' => 1])->getBoolean('a'));
    }

    public function testDateTimeParameterCanBeRetrieved(): void
    {
        $this->assertInstanceOf(DateTimeParameter::class, (new ParameterValidator())->datetime('a'));
    }

    public function testDateTimeParameterCanBeRetrievedDirectly(): void
    {
        $this->assertInstanceOf(
            DateTimeInterface::class,
            (new ParameterValidator())->setData(['a' => '2000-01-01'])->getDateTime('a')
        );
    }

    public function testDateTimeParameterCanBeRetrievedDirectlyAsString(): void
    {
        $this->assertIsString(
            (new ParameterValidator())->setData(['a' => '2000-01-01'])->getDateTimeString('a')
        );
    }

    public function testIntParameterCanBeRetrieved(): void
    {
        $this->assertInstanceOf(IntParameter::class, (new ParameterValidator())->int('a'));
    }

    public function testIntParameterCanBeRetrievedDirectly(): void
    {
        $this->assertSame(1, (new ParameterValidator())->setData(['a' => 1])->getInt('a'));
    }

    public function testIpAddressParameterCanBeRetrieved(): void
    {
        $this->assertInstanceOf(
            IpAddressParameter::class,
            (new ParameterValidator())->ipAddress('a')
        );
    }

    public function testIpAddressParameterCanBeRetrievedDirectly(): void
    {
        $this->assertSame(
            '::1',
            (new ParameterValidator())->setData(['a' => '::1'])->getIpAddress('a')
        );
    }

    public function testStringParameterCanBeRetrieved(): void
    {
        $this->assertInstanceOf(StringParameter::class, (new ParameterValidator())->string('a'));
    }

    public function testStringParameterCanBeRetrievedDirectly(): void
    {
        $this->assertSame('1', (new ParameterValidator())->setData(['a' => 1])->getString('a'));
    }
}
