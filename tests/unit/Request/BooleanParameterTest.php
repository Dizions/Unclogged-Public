<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Request;

/**
 * @covers Dizions\Unclogged\Request\BooleanParameter
 */
final class BooleanParameterTest extends TestCase
{
    /** @dataProvider truthyValuesProvider */
    public function testTruthyValuesAreConvertedToTrue($in): void
    {
        $parameter = new BooleanParameter('a', $this->getPostRequest(['a' => $in]));
        $this->assertTrue($parameter->get());
    }

    /** @dataProvider falsyValuesProvider */
    public function testFalsyValuesAreConvertedToFalse($in): void
    {
        $parameter = new BooleanParameter('a', $this->getPostRequest(['a' => $in]));
        $this->assertFalse($parameter->get());
    }

    /** @dataProvider nonBooleanValuesProvider */
    public function testNonBooleanValuesAreInvalid($in): void
    {
        $parameter = new BooleanParameter('a', $this->getPostRequest(['a' => $in]));
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
