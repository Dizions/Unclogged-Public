<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database\Query;

use Dizions\Unclogged\Database\Schema\ColumnSchema;
use Dizions\Unclogged\Database\Schema\Renderers\SqlRendererInterface;
use Dizions\Unclogged\Database\Schema\TableSchema;
use Dizions\Unclogged\TestCase;
use PDO;

/** @covers Dizions\Unclogged\Database\Query\Where */
final class WhereTest extends TestCase
{
    public function testWhereConditionCanBeApplied(): void
    {
        $db = $this->createEmptyDatabase();
        $db->createTable(new TableSchema('test', [ColumnSchema::int('id')]));
        $db->exec('INSERT INTO test (id) VALUES (1), (2), (3)');
        $where = new Where();
        $where->setAssocValues(['id' => 2]);
        $parameters = $where->getParameters($db->getRenderer());
        $this->assertCount(1, $parameters);
        $select = $db->query("SELECT * FROM test {$where->getSqlString($db->getRenderer())}");
        $select->execute($parameters);
        $this->assertEquals([['id' => '2']], $select->fetchAll(PDO::FETCH_ASSOC));
    }

    public function testMultipleWhereConditionsCanBeApplied(): void
    {
        $db = $this->createEmptyDatabase();
        $db->createTable(new TableSchema('test', [ColumnSchema::int('id'), ColumnSchema::int('test')]));
        $db->exec('INSERT INTO test (id, test) VALUES (1, 4), (2, 4), (3, 5)');
        $where = new Where();
        $where->setAssocValues(['id' => 2, 'test' => 4]);
        $parameters = $where->getParameters($db->getRenderer());
        $this->assertCount(2, $parameters);
        $select = $db->query("SELECT * FROM test {$where->getSqlString($db->getRenderer())}");
        $select->execute($parameters);
        $this->assertEquals([['id' => '2', 'test' => '4']], $select->fetchAll(PDO::FETCH_ASSOC));
    }

    public function testWhereConditionMayBeEmpty(): void
    {
        $db = $this->createEmptyDatabase();
        $db->createTable(new TableSchema('test', [ColumnSchema::int('id')]));
        $db->exec('INSERT INTO test (id) VALUES (1), (2), (3)');

        $where = new Where($db->getRenderer());
        $parameters = $where->getParameters($db->getRenderer());
        $this->assertCount(0, $parameters);
        $select = $db->query("SELECT * FROM test {$where->getSqlString($db->getRenderer())}");
        $select->execute($parameters);
        $this->assertEquals([['id' => '1'], ['id' => '2'], ['id' => '3']], $select->fetchAll(PDO::FETCH_ASSOC));

        $where = new Where();
        $where->setAssocValues([]);
        $parameters = $where->getParameters($db->getRenderer());
        $this->assertCount(0, $parameters);
        $select = $db->query("SELECT * FROM test {$where->getSqlString($db->getRenderer())}");
        $select->execute($parameters);
        $this->assertEquals([['id' => '1'], ['id' => '2'], ['id' => '3']], $select->fetchAll(PDO::FETCH_ASSOC));
    }

    public function testRenderedResultsAreCached(): void
    {
        $renderer = $this->createMock(SqlRendererInterface::class);
        $renderer->expects($this->once())->method('quoteIdentifier');
        $where = new Where();
        $where->setAssocValues(['id' => 2]);
        $where->getParameters($renderer);
        $where->getSqlString($renderer);
        $where->getParameters($renderer);
        $where->getSqlString($renderer);
    }

    public function testCacheIsInvalidatedIfRendererChanges(): void
    {
        $renderer = $this->createMock(SqlRendererInterface::class);
        $renderer->expects($this->once())->method('quoteIdentifier');
        $where = new Where();
        $where->setAssocValues(['id' => 2]);
        $where->getParameters($renderer);
        $where->getSqlString($renderer);
        $renderer = $this->createMock(SqlRendererInterface::class);
        $renderer->expects($this->once())->method('quoteIdentifier');
        $where->getParameters($renderer);
        $where->getSqlString($renderer);
    }

    public function testCacheIsInvalidatedIfValuesChange(): void
    {
        $renderer = $this->createMock(SqlRendererInterface::class);
        $renderer->expects($this->exactly(2))->method('quoteIdentifier');
        $where = new Where();
        $where->setAssocValues(['id' => 2]);
        $where->getParameters($renderer);
        $where->getSqlString($renderer);
        $where->setAssocValues(['id' => 3]);
        $where->getParameters($renderer);
        $where->getSqlString($renderer);
    }
}
