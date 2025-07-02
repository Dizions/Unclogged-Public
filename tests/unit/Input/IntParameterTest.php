<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Input;

/**
 * @covers Dizions\Unclogged\Input\IntParameter
 */
final class IntParameterTest extends TestCase
{
    /** @dataProvider invalidValuesProvider */
    public function testInvalidValuesAreRejected($in): void
    {
        $parameter = new IntParameter('a', $this->getPostRequest(['a' => $in]));
        $this->expectException(InvalidParameterException::class);
        $parameter->get();
    }

    /** @dataProvider validValuesProvider */
    public function testValidValuesAreConvertedToInteger($in, $expected): void
    {
        $request = $this->getPostRequest(['a' => $in]);
        $this->assertSame($expected, (new IntParameter('a', $request))->get());
    }

    public static function invalidValuesProvider(): array
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

    public static function validValuesProvider(): array
    {
        return [
            [3, 3],
            ['3', 3],
            ['0003', 3],
            ['00', 0],
            ['0', 0],
            [-1, -1],
            ['-1', -1],
        ];
    }
}
