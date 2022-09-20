<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database\Query;

use Dizions\Unclogged\TestCase;

/**
 * @covers Dizions\Unclogged\Database\Query\RawSqlString
 */
final class RawSqlStringTest extends TestCase
{
    public function testCanBeReplacedWithPlaceholderInPreparedStatement(): void
    {
        $this->assertFalse((new RawSqlString('x'))->canUsePlaceholderInPreparedStatement());
    }
}
