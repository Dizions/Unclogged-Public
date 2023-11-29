<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Request;

use Dizions\Unclogged\TestCase;

/**
 * @covers Dizions\Unclogged\Request\BooleanParameter
 */
final class BooleanParameterTest extends TestCase
{
    /** @dataProvider truthyValuesProvider */
    public function testTruthyValuesAreConvertedToTrue($in): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->any())->method('getAllParams')->will($this->returnValue(['a' => $in]));
        $parameter = new BooleanParameter('a', $request);
        $this->assertTrue($parameter->get());
    }

    /** @dataProvider falsyValuesProvider */
    public function testFalsyValuesAreConvertedToFalse($in): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->any())->method('getAllParams')->will($this->returnValue(['a' => $in]));
        $parameter = new BooleanParameter('a', $request);
        $this->assertFalse($parameter->get());
    }

    /** @dataProvider nonBooleanValuesProvider */
    public function testNonBooleanValuesAreInvalid($in): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->any())->method('getAllParams')->will($this->returnValue(['a' => $in]));
        $parameter = new BooleanParameter('a', $request);
        $this->expectException(InvalidParameterException::class);
        $parameter->get();
    }

    public static function truthyValuesProvider(): array
    {
        return [
            [1],
            ['1'],
            [true],
            ['true'],
            ['True'],
            ['tRuE'],
            ['y'],
            ['Y'],
            ['yes'],
            ['Yes'],
            ['yEs'],
            ['on'],
        ];
    }

    public static function falsyValuesProvider(): array
    {
        return [
            [0],
            ['0'],
            [false],
            ['false'],
            ['False'],
            ['fAlSe'],
            ['n'],
            ['N'],
            ['no'],
            ['No'],
            ['nO'],
            ['off'],
        ];
    }

    public static function nonBooleanValuesProvider(): array
    {
        return [
            [1.0],
            [7],
            [null],
            ['apple'],
        ];
    }
}
