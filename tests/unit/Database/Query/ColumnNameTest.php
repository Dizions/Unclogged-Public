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
    public function testCanBeRenderedString(string $in): void
    {
        $db = $this->createMock(Database::class);
        $this->assertIsString((new ColumnName($in))->render($db));
    }

    public function testCannotBeReplacedWithPlaceholderInPreparedStatement(): void
    {
        $columnName = new ColumnName('x');
        $this->assertFalse($columnName->canUsePlaceholderInPreparedStatement());
    }

    /** @dataProvider invalidStringsProvider */
    public function testInvalidStringsAreRejected(string $invalid): void
    {
        $db = $this->createMock(Database::class);
        $this->expectException(InvalidIdentifierException::class);
        (new ColumnName($invalid))->render($db);
    }

    public function testQuotedStringOutputIsCached()
    {
        $db = $this->createMock(Database::class);
        $db->expects($this->once())->method('quoteIdentifier');
        $columnName = new ColumnName('x');
        $columnName->render($db);
        $columnName->render($db);
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
