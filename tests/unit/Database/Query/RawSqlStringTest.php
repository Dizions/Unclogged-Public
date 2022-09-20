<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database\Query;

use Dizions\Unclogged\Database\Schema\SqlRendererInterface;
use Dizions\Unclogged\TestCase;

/**
 * @covers Dizions\Unclogged\Database\Query\RawSqlString
 */
final class RawSqlStringTest extends TestCase
{
    public function testCannotBeReplacedWithPlaceholderInPreparedStatement(): void
    {
        $this->assertFalse((new RawSqlString('x'))->canUsePlaceholderInPreparedStatement());
    }

    public function testContentIsRenderedWithoutModification(): void
    {
        $raw = new RawSqlString("O'Reilly");
        $renderer = $this->createMock(SqlRendererInterface::class);
        $this->assertSame("O'Reilly", $raw->render($renderer));
    }
}
