<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database\Query;

use Dizions\Unclogged\Database\Database;
use Dizions\Unclogged\TestCase;
use PDOStatement;

/**
 * @covers Dizions\Unclogged\Database\Query\Query
 */
final class QueryTest extends TestCase
{
    public function testQueryReturnsSuccessStatusWhenExecuted(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->expects($this->any())->method('execute')->will($this->returnValue(true));
        $db = $this->createMock(Database::class);
        $db->expects($this->any())->method('prepare')->will($this->returnValue($statement));
        $query = $this->getMockForAbstractClass(Query::class, [$db]);
        $query->expects($this->any())->method('getSqlStringAndParameters')->will($this->returnValue(['', []]));
        $this->assertTrue($query->execute());

        $statement = $this->createMock(PDOStatement::class);
        $statement->expects($this->any())->method('execute')->will($this->returnValue(false));
        $db = $this->createMock(Database::class);
        $db->expects($this->any())->method('prepare')->will($this->returnValue($statement));
        $query = $this->getMockForAbstractClass(Query::class, [$db]);
        $query->expects($this->any())->method('getSqlStringAndParameters')->will($this->returnValue(['', []]));
        $this->assertFalse($query->execute());
    }

    public function testExecuteOrThrowMethodWillThrowExceptionOnFailure(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->expects($this->any())->method('execute')->will($this->returnValue(true));
        $db = $this->createMock(Database::class);
        $db->expects($this->any())->method('prepare')->will($this->returnValue($statement));
        $query = $this->getMockForAbstractClass(Query::class, [$db]);
        $query->expects($this->any())->method('getSqlStringAndParameters')->will($this->returnValue(['', []]));
        $query->executeOrThrow();

        $statement = $this->createMock(PDOStatement::class);
        $statement->expects($this->any())->method('execute')->will($this->returnValue(false));
        $db = $this->createMock(Database::class);
        $db->expects($this->any())->method('prepare')->will($this->returnValue($statement));
        $query = $this->getMockForAbstractClass(Query::class, [$db]);
        $query->expects($this->any())->method('getSqlStringAndParameters')->will($this->returnValue(['', []]));
        $this->expectException(QueryFailureException::class);
        $query->executeOrThrow();
    }

    public function testDatabaseCanBeRetrieved(): void
    {
        $db = $this->createMock(Database::class);
        $query = new class ($db) extends Query {
            protected function getSqlStringAndParameters(Database $database): array
            {
                return [];
            }
            public function getDatabase(): Database
            {
                return parent::getDatabase();
            }
        };
        $this->assertInstanceOf(Database::class, $query->getDatabase());
    }

    public function testColumnNameCanBeCreated(): void
    {
        $db = $this->createMock(Database::class);
        $query = new class ($db) extends Query {
            protected function getSqlStringAndParameters(Database $database): array
            {
                return [];
            }
            public function createColumnNameFromString(string $name): ColumnName
            {
                return parent::createColumnNameFromString($name);
            }
        };
        $this->assertInstanceOf(ColumnName::class, $query->createColumnNameFromString('x'));
    }

    public function testSqlStringCanBeCreated(): void
    {
        $db = $this->createMock(Database::class);
        $query = new class ($db) extends Query {
            protected function getSqlStringAndParameters(Database $database): array
            {
                return [];
            }
            public function createSqlStringFromString(string $name): SqlString
            {
                return parent::createSqlStringFromString($name);
            }
        };
        $this->assertInstanceOf(SqlString::class, $query->createSqlStringFromString('x'));
    }
}
