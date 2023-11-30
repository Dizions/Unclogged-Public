<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Request;

/**
 * @covers Dizions\Unclogged\Request\IntParameter
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

    public function testValidValuesAreConvertedToInteger(): void
    {
        $request = $this->getPostRequest(['a' => 3, 'b' => '3']);
        $this->assertSame(3, (new IntParameter('a', $request))->get());
        $this->assertSame(3, (new IntParameter('b', $request))->get());
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
}
