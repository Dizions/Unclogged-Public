<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Request;

use Dizions\Unclogged\TestCase;

/**
 * @covers Dizions\Unclogged\Request\IpAddressParameter
 */
final class IpAddressParameterTest extends TestCase
{
    /** @dataProvider invalidValuesProvider */
    public function testInvalidValuesAreRejected($in): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->any())->method('getAllParams')->will($this->returnValue(['a' => $in]));
        $parameter = new IpAddressParameter('a', $request);
        $this->expectException(InvalidParameterException::class);
        $parameter->get();
    }

    /** @dataProvider validValuesProvider */
    public function testValidValuesAreAccepted(string $in): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->any())->method('getAllParams')->will($this->returnValue(['a' => $in]));
        $parameter = new IpAddressParameter('a', $request);
        $this->assertSame($in, $parameter->get());
    }

    public function invalidValuesProvider(): array
    {
        return [
            [''],
            [1],
            [null],
            [10.1],
            ['10.1'],
            [':1'],
        ];
    }

    public function validValuesProvider(): array
    {
        return [
            ['10.0.0.1'],
            ['127.0.0.1'],
            ['::1'],
            ['fd12:3456:789a::1'],
            ['fd12:3456:789a::ffff'],
        ];
    }
}
