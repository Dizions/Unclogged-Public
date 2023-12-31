<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database\Query;

use Dizions\Unclogged\Database\Schema\Renderers\SqlRendererInterface;
use Dizions\Unclogged\TestCase;

/**
 * @covers Dizions\Unclogged\Database\Query\ColumnName
 */
final class ColumnNameTest extends TestCase
{
    /** @dataProvider validStringsProvider */
    public function testCanBeRenderedToString(string $in): void
    {
        $renderer = $this->createMock(SqlRendererInterface::class);
        $this->assertIsString((new ColumnName($in))->render($renderer));
    }

    public function testCannotBeReplacedWithPlaceholderInPreparedStatement(): void
    {
        $columnName = new ColumnName('x');
        $this->assertFalse($columnName->canUsePlaceholderInPreparedStatement());
    }

    /** @dataProvider invalidStringsProvider */
    public function testInvalidStringsAreRejected(string $invalid): void
    {
        $renderer = $this->createMock(SqlRendererInterface::class);
        $this->expectException(InvalidIdentifierException::class);
        (new ColumnName($invalid))->render($renderer);
    }

    public function testQuotedStringOutputIsCached()
    {
        $renderer = $this->createMock(SqlRendererInterface::class);
        $renderer->expects($this->once())->method('quoteIdentifier');
        $columnName = new ColumnName('x');
        $columnName->render($renderer);
        $columnName->render($renderer);
    }

    public static function invalidStringsProvider(): array
    {
        return [
            [''],
            ['"x"'],
            ["'x'"],
            ['`x`'],
            ['x.y.z'],
        ];
    }

    public static function validStringsProvider(): array
    {
        return [
            ['x'],
            ['x.y'],
        ];
    }
}
