<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Strings;

use Dizions\Unclogged\TestCase;

/**
 * @covers Dizions\Unclogged\Strings\BasicInterpolator
 */
final class BasicInterpolatorTest extends TestCase
{
    public function testSimpleStringIsUnchangedByInterpolation(): void
    {
        $this->assertSame('simple', (new BasicInterpolator())->interpolate('simple', []));
    }

    public function testStringsCanBeInterpolated(): void
    {
        $this->assertSame('a string', (new BasicInterpolator())->interpolate('a {0}', ['string']));
        $this->assertSame('a string', (new BasicInterpolator())->interpolate('a {string}', ['string' => 'string']));
    }

    public function testArraysAreInterpolatedAsJson(): void
    {
        $array = ['x', 'y'];
        $this->assertSame('an array: ["x","y"]', (new BasicInterpolator())->interpolate('an array: {0}', [$array]));
        $this->assertSame(
            'an array: ["x","y"]',
            (new BasicInterpolator())->interpolate('an array: {array}', ['array' => $array])
        );
    }
}
