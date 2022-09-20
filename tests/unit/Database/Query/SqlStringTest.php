<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database\Query;

use Dizions\Unclogged\Database\Database;
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
        $db = $this->createMock(Database::class);
        $this->assertSame($in, (new SqlString($in))->render($db));
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
