<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Input;

use DateTime;
use DateTimeZone;
use stdClass;

/**
 * @covers Dizions\Unclogged\Input\DateTimeParameter
 */
final class DateTimeParameterTest extends TestCase
{
    /**
     * @dataProvider emptyValuesProvider
     * @dataProvider invalidValuesProvider
     */
    public function testEmptyAndInvalidValuesAreRejectedByDefault($in): void
    {
        $parameter = new DateTimeParameter('a', $this->getPostRequest(['a' => $in]));
        $this->expectException(InvalidParameterException::class);
        $parameter->get();
    }

    /** @dataProvider invalidValuesProvider */
    public function testInvalidValuesAreAlwaysRejected($in): void
    {
        $parameter = new DateTimeParameter('a', $this->getPostRequest(['a' => $in]));
        $this->expectException(InvalidParameterException::class);
        $parameter->allowEmpty()->get();
    }

    /** @dataProvider emptyValuesProvider */
    public function testAllowedEmptyValuesAreConvertedToNull($in): void
    {
        $parameter = new DateTimeParameter('a', $this->getPostRequest(['a' => $in]));
        $this->assertNull($parameter->allowEmpty()->get());
    }

    /** @dataProvider emptyValuesProvider */
    public function testAllowedEmptyValuesAreConvertedToStringButOtherwiseUnchanged($in): void
    {
        $parameter = new DateTimeParameter('a', $this->getPostRequest(['a' => $in]));
        $this->assertSame((string)$in, $parameter->allowEmpty()->getString());
    }

    /** @dataProvider validNonEmptyValuesProvider */
    public function testValidValuesAreConvertedToCorrectString($in, string $default, string $atom): void
    {
        $parameter = new DateTimeParameter('a', $this->getPostRequest(['a' => $in]));
        $this->assertSame($default, $parameter->getString());
        $this->assertSame($atom, $parameter->getString(DateTime::ATOM));
        $this->assertSame($atom, $parameter->get()->format(DateTime::ATOM));
        $parameter->allowEmpty();
        $this->assertSame($default, $parameter->getString());
        $this->assertSame($atom, $parameter->getString(DateTime::ATOM));
        $this->assertSame($atom, $parameter->get()->format(DateTime::ATOM));
    }

    public static function emptyValuesProvider(): array
    {
        return [
            [''],
            [null],
            ['0000-00-00'],
            ['0000-00-00 00:00:00'],
        ];
    }

    public static function invalidValuesProvider(): array
    {
        return [
            ['2022-09-31'],
            [1],
            ['abc'],
            ['2000-01-01 25:00:00'],
            [[]],
            [new stdClass()],
        ];
    }

    public static function validNonEmptyValuesProvider(): array
    {
        date_default_timezone_set('Europe/London');
        return [
            // [input, default string format, ATOM format]
            ['2022-01-01 09:00:00', '2022-01-01 09:00:00', '2022-01-01T09:00:00+00:00'],
            ['2022-01-01T09:00:00+00:00', '2022-01-01 09:00:00', '2022-01-01T09:00:00+00:00'],
            ['2022-01-01 10:00:00+01:00', '2022-01-01 09:00:00', '2022-01-01T09:00:00+00:00'],
            [new DateTime('2022-01-01 09:00:00'), '2022-01-01 09:00:00', '2022-01-01T09:00:00+00:00'],
            [
                (new DateTime('2022-01-01 09:00:00'))->setTimezone(new DateTimeZone('GMT+1')),
                '2022-01-01 09:00:00',
                '2022-01-01T09:00:00+00:00'
            ],
            ['2022-07-01 09:00:00', '2022-07-01 08:00:00', '2022-07-01T08:00:00+00:00'],
            ['2022-07-01T08:00:00+00:00', '2022-07-01 08:00:00', '2022-07-01T08:00:00+00:00'],
            ['2022-07-01T09:00:00+01:00', '2022-07-01 08:00:00', '2022-07-01T08:00:00+00:00'],
            [new DateTime('2022-07-01 09:00:00'), '2022-07-01 08:00:00', '2022-07-01T08:00:00+00:00'],
            [
                (new DateTime('2022-07-01 09:00:00'))->setTimezone(new DateTimeZone('UTC')),
                '2022-07-01 08:00:00',
                '2022-07-01T08:00:00+00:00'
            ],
        ];
    }
}
