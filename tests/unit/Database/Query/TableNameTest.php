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
    public function testCanBeRenderedString(): void
    {
        $db = $this->createMock(Database::class);
        $this->assertIsString((new TableName('x'))->render($db));
    }

    public function testCannotBeReplacedWithPlaceholderInPreparedStatement(): void
    {
        $columnName = new TableName('x');
        $this->assertFalse($columnName->canUsePlaceholderInPreparedStatement());
    }

    public function testQuotedStringOutputIsCached()
    {
        $db = $this->createMock(Database::class);
        $db->expects($this->once())->method('quoteIdentifier');
        $columnName = new TableName('x');
        $columnName->render($db);
        $columnName->render($db);
    }
}
