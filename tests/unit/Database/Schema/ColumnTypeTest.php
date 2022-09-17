<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database\Schema;

use TypeError;
use Dizions\Unclogged\TestCase;

/**
 * @covers Dizions\Unclogged\Database\Schema\ColumnType
 */
final class ColumnTypeTest extends TestCase
{
    public function testUndefinedColumnHasNoType(): void
    {
        $this->assertSame('', ColumnType::undefined()->getType());
    }

    public function testColumnIsSignedByDefault(): void
    {
        $this->assertFalse(ColumnType::int()->getUnsigned());
    }

    public function testColumnCanBeMadeUnsigned(): void
    {
        $this->assertTrue(ColumnType::int()->setUnsigned()->getUnsigned());
    }

    public function testCharacterSetIsUnspecifiedByDefault(): void
    {
        $this->assertSame('', ColumnType::varchar()->getCharacterSet());
        $this->assertSame('', ColumnType::char()->getCharacterSet());
        $this->assertSame('', ColumnType::text()->getCharacterSet());
    }

    public function testCharacterCanBeSpecified(): void
    {
        $this->assertSame('ascii', ColumnType::varchar(null, 'ascii')->getCharacterSet());
        $this->assertSame('ascii', ColumnType::char(null, 'ascii')->getCharacterSet());
        $this->assertSame('ascii', ColumnType::text('ascii')->getCharacterSet());
    }

    public function testLengthAndDecimalDigitsAreUnspecifiedByDefault(): void
    {
        $type = ColumnType::decimal();
        $this->assertNull($type->getLength());
        $this->assertNull($type->getDecimalDigits());
    }

    public function testLengthCanBeSpecified(): void
    {
        $type = ColumnType::decimal(5);
        $this->assertSame(5, $type->getLength());
        $this->assertNull($type->getDecimalDigits());
        $type = ColumnType::varchar(5);
        $this->assertSame(5, $type->getLength());
        $type = ColumnType::char(5);
        $this->assertSame(5, $type->getLength());
    }

    public function testLengthAndDecimalDigitsCanBeSpecified(): void
    {
        $type = ColumnType::decimal(5, 2);
        $this->assertSame(5, $type->getLength());
        $this->assertSame(2, $type->getDecimalDigits());
    }

    public function testLengthMustBeInt(): void
    {
        $this->expectException(TypeError::class);
        ColumnType::int('10');
    }

    public function testDecimalDigitsMustBeInt(): void
    {
        $this->expectException(TypeError::class);
        ColumnType::decimal(10, $this);
    }
}
