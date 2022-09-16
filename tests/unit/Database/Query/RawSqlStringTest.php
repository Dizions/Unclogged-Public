<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database\Query;

use Dizions\Unclogged\Database\Database;
use Dizions\Unclogged\TestCase;

/**
 * @covers Dizions\Unclogged\Database\Query\RawSqlString
 */
final class RawSqlStringTest extends TestCase
{
    public function testCanBeReplacedWithPlaceholderInPreparedStatement(): void
    {
        $db = $this->createMock(Database::class);
        $this->assertFalse((new RawSqlString($db, 'x'))->canUsePlaceholderInPreparedStatement());
    }
}
