<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database\Query;

use Dizions\Unclogged\Database\Database;
use Dizions\Unclogged\TestCase;

/**
 * @covers Dizions\Unclogged\Database\Query\TableName
 */
final class TableNameTest extends TestCase
{
    public function testCanBeConvertedtoString(): void
    {
        $db = $this->createMock(Database::class);
        $this->assertIsString((string)(new TableName($db, 'x')));
    }

    public function testCannotBeReplacedWithPlaceholderInPreparedStatement(): void
    {
        $db = $this->createMock(Database::class);
        $columnName = new TableName($db, 'x');
        $this->assertFalse($columnName->canUsePlaceholderInPreparedStatement());
    }

    public function testQuotedStringOutputIsCached()
    {
        $db = $this->createMock(Database::class);
        $db->expects($this->once())->method('quoteIdentifier');
        $columnName = new TableName($db, 'x');
        (string)$columnName;
        (string)$columnName;
    }
}
