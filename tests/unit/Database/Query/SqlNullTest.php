<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database\Query;

use Dizions\Unclogged\Database\Schema\SqlRendererInterface;
use Dizions\Unclogged\TestCase;

/** @covers Dizions\Unclogged\Database\Query\SqlNull */
final class SqlNullTest extends TestCase
{
    public function testValueCanBeRendered(): void
    {
        $renderer =  $this->createMock(SqlRendererInterface::class);
        $sqlNull = new SqlNull();
        $this->assertIsString($sqlNull->render($renderer));
    }
}
