<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database\Query;

use Dizions\Unclogged\Database\Database;
use Dizions\Unclogged\TestCase;

/**
 * @covers Dizions\Unclogged\Database\Query\ColumnName
 */
final class ColumnNameTest extends TestCase
{
    /** @dataProvider validStringsProvider */
    public function testCanBeConvertedtoString(string $in): void
    {
        $db = $this->createMock(Database::class);
        $this->assertIsString((string)(new ColumnName($db, $in)));
    }

    public function testCannotBeReplacedWithPlaceholderInPreparedStatement(): void
    {
        $db = $this->createMock(Database::class);
        $columnName = new ColumnName($db, 'x');
        $this->assertFalse($columnName->canUsePlaceholderInPreparedStatement());
    }

    /** @dataProvider invalidStringsProvider */
    public function testInvalidStringsAreRejected(string $invalid): void
    {
        $db = $this->createMock(Database::class);
        $this->expectException(InvalidIdentifierException::class);
        (string)(new ColumnName($db, $invalid));
    }

    public function testQuotedStringOutputIsCached()
    {
        $db = $this->createMock(Database::class);
        $db->expects($this->once())->method('quoteIdentifier');
        $columnName = new ColumnName($db, 'x');
        (string)$columnName;
        (string)$columnName;
    }

    public function invalidStringsProvider(): array
    {
        return [
            [''],
            ['"x"'],
            ["'x'"],
            ['`x`'],
            ['x.y.z'],
        ];
    }

    public function validStringsProvider(): array
    {
        return [
            ['x'],
            ['x.y'],
        ];
    }
}
