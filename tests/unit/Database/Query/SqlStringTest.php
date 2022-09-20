<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database\Query;

use Dizions\Unclogged\Database\Schema\SqlRendererInterface;
use Dizions\Unclogged\TestCase;

/**
 * @covers Dizions\Unclogged\Database\Query\SqlString
 */
final class SqlStringTest extends TestCase
{
    public function testCanBeConstructed(): void
    {
        $this->assertInstanceOf(SqlString::class, new SqlString('x'));
    }

    public function testCanBeReplacedWithPlaceholderInPreparedStatement(): void
    {
        $this->assertTrue((new SqlString('x'))->canUsePlaceholderInPreparedStatement());
    }

    public function testRawStringCanBeRetrieved(): void
    {
        $sqlString = new class ('x') extends SqlString {
            public function get()
            {
                return $this->getRaw();
            }
        };
        $this->assertSame('x', $sqlString->get());
    }

    /** @dataProvider stringProvider */
    public function testStringCanBeRetrievedUnchanged(string $in): void
    {
        $this->assertSame($in, (new SqlString($in))->getRaw());
        $renderer = $this->createMock(SqlRendererInterface::class);
        $renderer->expects($this->once())->method('quoteString')->willReturnArgument(0);
        $this->assertSame($in, (new SqlString($in))->render($renderer));
    }

    public function stringProvider(): array
    {
        return [
            ['x'],
            [''],
            ["O'Reilly"],
            ['with spaces'],
            ['`quoted`'],
            ['"quoted"'],
            ["'quoted'"],
        ];
    }
}
