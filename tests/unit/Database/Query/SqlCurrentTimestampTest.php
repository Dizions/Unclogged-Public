<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database\Query;

use Dizions\Unclogged\Database\Schema\SqlRendererInterface;
use Dizions\Unclogged\TestCase;

/** @covers Dizions\Unclogged\Database\Query\SqlCurrentTimestamp */
final class SqlCurrentTimestampTest extends TestCase
{
    public function testValueCanBeRendered(): void
    {
        $renderer =  $this->createMock(SqlRendererInterface::class);
        $renderer->expects($this->once())->method('renderCurrentTimestamp');
        $sqlCurrentTimestamp = new SqlCurrentTimestamp();
        $this->assertIsString($sqlCurrentTimestamp->render($renderer));
    }
}
