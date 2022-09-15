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
        $db = $this->createMock(Database::class);
        $this->assertInstanceOf(SqlString::class, new SqlString($db, 'x'));
    }

    public function testCanBeReplacedWithPlaceholderInPreparedStatement(): void
    {
        $db = $this->createMock(Database::class);
        $this->assertTrue((new SqlString($db, 'x'))->canUsePlaceholderInPreparedStatement());
    }

    public function testDatabaseCanBeRetrieved(): void
    {
        $db = $this->createMock(Database::class);
        $sqlString = new class ($db, 'x') extends SqlString {
            public function get()
            {
                return $this->getDatabase();
            }
        };
        $this->assertInstanceOf(Database::class, $sqlString->get());
    }

    public function testRawStringCanBeRetrieved(): void
    {
        $db = $this->createMock(Database::class);
        $sqlString = new class ($db, 'x') extends SqlString {
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
        $this->assertSame($in, (string)(new SqlString($db, $in)));
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
