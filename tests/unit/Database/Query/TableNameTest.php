<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database\Query;

use Dizions\Unclogged\Database\Schema\SqlRendererInterface;
use Dizions\Unclogged\TestCase;

/**
 * @covers Dizions\Unclogged\Database\Query\TableName
 */
final class TableNameTest extends TestCase
{
    public function testCanBeRenderedAsString(): void
    {
        $renderer = $this->createMock(SqlRendererInterface::class);
        $this->assertIsString((new TableName('x'))->render($renderer));
    }

    public function testCannotBeReplacedWithPlaceholderInPreparedStatement(): void
    {
        $columnName = new TableName('x');
        $this->assertFalse($columnName->canUsePlaceholderInPreparedStatement());
    }

    public function testQuotedStringOutputIsCached()
    {
        $renderer = $this->createMock(SqlRendererInterface::class);
        $renderer->expects($this->once())->method('quoteIdentifier');
        $columnName = new TableName('x');
        $columnName->render($renderer);
        $columnName->render($renderer);
    }
}
