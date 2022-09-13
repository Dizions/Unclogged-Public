<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Request;

use Dizions\Unclogged\TestCase;

/**
 * @covers Dizions\Unclogged\Request\IntParameter
 */
final class IntParameterTest extends TestCase
{
    /** @dataProvider invalidValuesProvider */
    public function testInvalidValuesAreRejected($in): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->any())->method('getAllParams')->will($this->returnValue(['a' => $in]));
        $parameter = new IntParameter('a', $request);
        $this->expectException(InvalidParameterException::class);
        $parameter->get();
    }

    public function testValidValuesAreConvertedToInteger(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->any())->method('getAllParams')->will($this->returnValue(['a' => 3, 'b' => '3']));
        $this->assertSame(3, (new IntParameter('a', $request))->get());
        $this->assertSame(3, (new IntParameter('b', $request))->get());
    }

    public function invalidValuesProvider(): array
    {
        return [
            [1.0],
            [null],
            ['a'],
            [''],
            ['1.0'],
            [INF],
            [NAN],
        ];
    }
}
