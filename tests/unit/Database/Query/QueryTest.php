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
        $query = $this->getMockForAbstractClass(Query::class);
        $query->expects($this->any())->method('getSqlStringAndParameters')->will($this->returnValue(['', []]));
        $this->assertTrue($query->execute($db));

        $statement = $this->createMock(PDOStatement::class);
        $statement->expects($this->any())->method('execute')->will($this->returnValue(false));
        $db = $this->createMock(Database::class);
        $db->expects($this->any())->method('prepare')->will($this->returnValue($statement));
        $query = $this->getMockForAbstractClass(Query::class);
        $query->expects($this->any())->method('getSqlStringAndParameters')->will($this->returnValue(['', []]));
        $this->assertFalse($query->execute($db));
    }

    public function testExecuteOrThrowMethodWillThrowExceptionOnFailure(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->expects($this->any())->method('execute')->will($this->returnValue(true));
        $db = $this->createMock(Database::class);
        $db->expects($this->any())->method('prepare')->will($this->returnValue($statement));
        $query = $this->getMockForAbstractClass(Query::class);
        $query->expects($this->any())->method('getSqlStringAndParameters')->will($this->returnValue(['', []]));
        $query->executeOrThrow($db);

        $statement = $this->createMock(PDOStatement::class);
        $statement->expects($this->any())->method('execute')->will($this->returnValue(false));
        $db = $this->createMock(Database::class);
        $db->expects($this->any())->method('prepare')->will($this->returnValue($statement));
        $query = $this->getMockForAbstractClass(Query::class);
        $query->expects($this->any())->method('getSqlStringAndParameters')->will($this->returnValue(['', []]));
        $this->expectException(QueryFailureException::class);
        $query->executeOrThrow($db);
    }
}
