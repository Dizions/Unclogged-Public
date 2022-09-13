<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Request;

use DateTime;
use DateTimeZone;
use Dizions\Unclogged\TestCase;
use stdClass;

/**
 * @covers Dizions\Unclogged\Request\DateTimeParameter
 */
final class DateTimeParameterTest extends TestCase
{
    /**
     * @dataProvider emptyValuesProvider
     * @dataProvider invalidValuesProvider
     */
    public function testEmptyAndInvalidValuesAreRejectedByDefault($in): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->any())->method('getAllParams')->will($this->returnValue(['a' => $in]));
        $parameter = new DateTimeParameter('a', $request);
        $this->expectException(InvalidParameterException::class);
        $parameter->get();
    }

    /** @dataProvider invalidValuesProvider */
    public function testInvalidValuesAreAlwaysRejected($in): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->any())->method('getAllParams')->will($this->returnValue(['a' => $in]));
        $parameter = new DateTimeParameter('a', $request);
        $this->expectException(InvalidParameterException::class);
        $parameter->allowEmpty()->get();
    }

    /** @dataProvider emptyValuesProvider */
    public function testAllowedEmptyValuesAreConvertedToNull($in): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->any())->method('getAllParams')->will($this->returnValue(['a' => $in]));
        $parameter = new DateTimeParameter('a', $request);
        $this->assertNull($parameter->allowEmpty()->get());
    }

    /** @dataProvider emptyValuesProvider */
    public function testAllowedEmptyValuesAreConvertedToStringButOtherwiseUnchanged($in): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->any())->method('getAllParams')->will($this->returnValue(['a' => $in]));
        $parameter = new DateTimeParameter('a', $request);
        $this->assertSame((string)$in, $parameter->allowEmpty()->getString());
    }

    /** @dataProvider validNonEmptyValuesProvider */
    public function testValidValuesAreConvertedToCorrectString($in, string $default, string $atom): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->any())->method('getAllParams')->will($this->returnValue(['a' => $in]));
        $parameter = new DateTimeParameter('a', $request);
        $this->assertSame($default, $parameter->getString());
        $this->assertSame($atom, $parameter->getString(DateTime::ATOM));
        $this->assertSame($atom, $parameter->get()->format(DateTime::ATOM));
        $parameter->allowEmpty();
        $this->assertSame($default, $parameter->getString());
        $this->assertSame($atom, $parameter->getString(DateTime::ATOM));
        $this->assertSame($atom, $parameter->get()->format(DateTime::ATOM));
    }

    public function emptyValuesProvider(): array
    {
        return [
            [''],
            [null],
            ['0000-00-00'],
            ['0000-00-00 00:00:00'],
        ];
    }

    public function invalidValuesProvider(): array
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

    public function validNonEmptyValuesProvider(): array
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
