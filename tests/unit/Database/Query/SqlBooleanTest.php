<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database\Query;

use Dizions\Unclogged\Database\Schema\Renderers\SqlRendererInterface;
use Dizions\Unclogged\TestCase;

/** @covers Dizions\Unclogged\Database\Query\SqlBoolean */
final class SqlBooleanTest extends TestCase
{
    public function testValuesCanBeRendered(): void
    {
        $trueRenderer =  $this->createMock(SqlRendererInterface::class);
        $trueRenderer->expects($this->once())->method('renderBoolean')->with($this->equalTo(true));
        $sqlBoolean = new SqlBoolean(true);
        $this->assertIsString($sqlBoolean->render($trueRenderer));

        $falseRenderer =  $this->createMock(SqlRendererInterface::class);
        $falseRenderer->expects($this->once())->method('renderBoolean')->with($this->equalTo(false));
        $sqlBoolean = new SqlBoolean(false);
        $this->assertIsString($sqlBoolean->render($falseRenderer));
    }
}
