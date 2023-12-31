<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database\Query;

use Dizions\Unclogged\Database\Database;
use Dizions\Unclogged\TestCase;
use PDO;
use PDOException;

/** @covers Dizions\Unclogged\Database\Query\Insert */
final class InsertTest extends TestCase
{
    public function testValuesMustBeProvided(): void
    {
        $db = $this->createMock(Database::class);
        $insert = new Insert('x');
        $this->expectException(InvalidQueryValuesException::class);
        $insert->execute($db);
    }

    /** @dataProvider invalidValuesProvider */
    public function testInvalidValuesAreRejected(array $values): void
    {
        $insert = new Insert('x');
        $this->expectException(InvalidQueryValuesException::class);
        $insert->values($values);
    }

    /** @dataProvider validValuesProvider */
    public function testValuesCanBeInsertedIntoDatabase(string $in): void
    {
        $db = $this->createTestDatabase();
        $insert = new Insert('test');
        $insert->values(['test' => $in])->execute($db);
        $this->assertSame([[$in]], $db->query('SELECT * FROM test')->fetchAll(PDO::FETCH_NUM));
    }

    /** @dataProvider validValuesProvider */
    public function testValuesCanBeInsertedIntoDatabaseAsSqlString(string $in): void
    {
        $db = $this->createTestDatabase();
        $insert = new Insert('test');
        $insert->values(['test' => new SqlString($in)])->execute($db);
        $this->assertSame([[$in]], $db->query('SELECT * FROM test')->fetchAll(PDO::FETCH_NUM));
    }

    /** @dataProvider validValuesProvider */
    public function testValuesCanBeInsertedIntoUsingRawSqlString(
        string $in,
        bool $isValidInRawSql,
        bool $outputValueShouldMatchInput = true
    ): void {
        $db = $this->createTestDatabase();
        $insert = new Insert('test');
        if ($isValidInRawSql) {
            $insert->values(['test' => new RawSqlString($in)])->execute($db);
            if ($outputValueShouldMatchInput) {
                $this->assertEquals([[$in]], $db->query('SELECT * FROM test')->fetchAll(PDO::FETCH_NUM));
            } else {
                $this->assertNotEquals([[$in]], $db->query('SELECT * FROM test')->fetchAll(PDO::FETCH_NUM));
            }
        } else {
            $this->expectException(PDOException::class);
            $insert->values(['test' => new RawSqlString($in)])->execute($db);
        }
    }

    public static function validValuesProvider(): array
    {
        return [
            // [string input, bool isValidInRawSql, bool outputValueShouldMatchInput]
            ['x', false],
            ['(SELECT 1)', true, false],
            ['CURRENT_TIMESTAMP', true, false],
            ['1', true],
            ["O'Reilly", false],
            ['`', false],
            ['"', false],
        ];
    }

    public static function invalidValuesProvider(): array
    {
        return [
            [['x', 'y']],
            [[]],
            [['a' => 'x', '' => 'y']],
        ];
    }

    private function createTestDatabase(): Database
    {
        $db = $this->createEmptyDatabase();
        $db->exec('CREATE TABLE test (test)');
        return $db;
    }
}
