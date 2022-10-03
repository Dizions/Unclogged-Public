<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database;

use Dizions\Unclogged\Database\Query\Query;
use Dizions\Unclogged\Database\Schema\{ColumnSchema, ColumnType, IncompatibleSchemaVersionException, TableSchema};
use Dizions\Unclogged\Database\Schema\{TableDefinition, TableDefinitionInterface};
use Dizions\Unclogged\TestCase;
use PDO;

/**
 * @covers Dizions\Unclogged\Database\Database
 */
final class DatabaseTest extends TestCase
{
    /** @depends testConnectionCanBeCreated */
    public function testConcatFunctionWorks(): void
    {
        $db = new Database(new BasicConnectionParameters('sqlite', [':memory:']));
        $this->assertEquals('xy3', $db->query('SELECT CONCAT("x", "y", 3)')->fetchColumn());
    }

    /** @depends testConnectionCanBeCreated */
    public function testMd5FunctionWorks(): void
    {
        $db = new Database(new BasicConnectionParameters('sqlite', [':memory:']));
        $this->assertEquals('9dd4e461268c8034f5c8564e155c67a6', $db->query('SELECT MD5("x")')->fetchColumn());
    }

    public function testConnectionCanBeCreated(): void
    {
        $db = new Database(new BasicConnectionParameters('sqlite', [':memory:']));
        $this->assertInstanceOf(PDO::class, $db);
        $this->assertEquals(7, $db->query('SELECT 7')->fetchColumn());
    }

    public function testConnectionParametersCanBeRetrieved(): void
    {
        $this->assertInstanceOf(
            ConnectionParameters::class,
            (new Database(new BasicConnectionParameters('sqlite', [])))->getConnectionParameters()
        );
    }

    public function testIdentifiersCanBeQuoted(): void
    {
        $db = new Database(new BasicConnectionParameters('sqlite', [':memory:']));
        $this->assertIsString($db->quoteIdentifier('x'));
    }

    public function testQueryCanBeExecuted(): void
    {
        $query = $this->createMock(Query::class);
        $query->expects($this->once())->method('execute');
        $db = new Database(new BasicConnectionParameters('sqlite', [':memory:']));
        $this->assertIsBool($db->execute($query));
    }

    public function testQueryCanBeExecutedOrExceptionThrownOnError(): void
    {
        $query = $this->createMock(Query::class);
        $query->expects($this->once())->method('executeOrThrow');
        $db = new Database(new BasicConnectionParameters('sqlite', [':memory:']));
        $db->executeOrThrow($query);
    }

    public function testTableCanBeCreated(): void
    {
        $db = new Database(new BasicConnectionParameters('sqlite', [':memory:']));
        $columns = [new ColumnSchema('a'), new ColumnSchema('b')];
        $db->createTable(new TableSchema('test', $columns));
        $db->exec('INSERT INTO test (a, b) VALUES ("x", "y")');
        $this->assertSame(
            [['a' => 'x', 'b' => 'y']],
            $db->query('SELECT * FROM test')->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    public function testTableDefinitionCanBeCreated(): void
    {
        $class = get_class(new class () extends TableDefinition {
            public function getSchemasByVersion(): array
            {
                return [];
            }
        });
        $db = new Database(new BasicConnectionParameters('sqlite', [':memory:']));
        $this->assertInstanceOf(TableDefinitionInterface::class, $db->getCachedInstance($class, fn() => new $class()));
    }

    public function testTableDefinitionIsCreatedOnlyOnce(): void
    {
        $class = get_class(new class () extends TableDefinition {
            public static $calls = -1;
            public function __construct()
            {
                static::$calls++;
            }
            public function getSchemasByVersion(): array
            {
                return [];
            }
        });
        $db = new Database(new BasicConnectionParameters('sqlite', [':memory:']));
        $db->getCachedInstance($class, fn() => new $class());
        $db->getCachedInstance($class, fn() => new $class());
        $this->assertSame(1, $class::$calls);
    }

    public function testMysqlTablesAreCreatedUsingInnoDb(): void
    {
        $dummyDb = new class (new BasicConnectionParameters('sqlite', [':memory:'], [])) extends Database{
            public array $execLog = [];
            public function getConnectionParameters(): ConnectionParameters
            {
                return new BasicConnectionParameters('mysql', []);
            }
            public function exec($query)
            {
                $this->execLog[] = $query;
            }
        };
        $columns = [
            (new ColumnSchema('a'))->setType(ColumnType::int()),
            (new ColumnSchema('b'))->setType(ColumnType::int()),
        ];
        $dummyDb->createTable(new TableSchema('test', $columns));
        $this->assertCount(1, $dummyDb->execLog);
        $this->assertSame(
            'CREATE TABLE IF NOT EXISTS test(a INT NOT NULL, b INT NOT NULL) ENGINE=InnoDB',
            $this->reformatSql($dummyDb->execLog[0])
        );
    }

    public function testSchemaVersionCanBeSet(): void
    {
        $db = new Database(new BasicConnectionParameters('sqlite', [':memory:']));
        $db->setSchemaVersion(23);
        $this->assertSame(23, $db->getSchemaVersion());
    }

    public function testSchemaVersionDefaultsToLatest(): void
    {
        $db = new Database(new BasicConnectionParameters('sqlite', [':memory:']));
        $this->assertSame(TableDefinitionInterface::LATEST, $db->getSchemaVersion());
    }

    public function schemaCompatibilityCheckProvider(): array
    {
        return [
            // [schema version, check version, expected result]
            [1, null, true],
            [1, 1, true],
            [1, 2, false],
            [TableDefinitionInterface::LATEST, null, true],
            [TableDefinitionInterface::LATEST, TableDefinitionInterface::LATEST, true],
            [TableDefinitionInterface::LATEST, 1, false],
        ];
    }
}
