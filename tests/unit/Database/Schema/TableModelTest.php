<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database\Schema;

use Dizions\Unclogged\Database\Database;
use Dizions\Unclogged\TestCase;

/** @covers Dizions\Unclogged\Database\Schema\TableModel */
final class TableModelTest extends TestCase
{
    public function testCanCreated(): void
    {
        $database = $this->createEmptyDatabase();
        $class = get_class($this->getMockForAbstractClass(TableModel::class, [], '', false));
        $this->assertInstanceOf(TableModel::class, $class::fromDatabase($database));
    }

    public function testCanRetrieveRowById(): void
    {
        $database = $this->createEmptyDatabase();
        $model = $this->createTableModel(
            $database,
            TableSchema::new('test', [
                ColumnSchema::int('id')->setAutoIncrement(),
                ColumnSchema::varchar('name')
            ])->setPrimary(['id'])
        );
        $database->createTable($model->getSchema());
        $database->exec('INSERT INTO test (name) VALUES ("Alice"), ("Bob")');
        $this->assertSame(['id' => '2', 'name' => 'Bob'], $model->getRowById(2));
        $this->assertSame(null, $model->getRowById(3));
    }

    public function testMustNotProvideExtraColumnsToKey(): void
    {
        $database = $this->createEmptyDatabase();
        $model = $this->createTableModel(
            $database,
            TableSchema::new('test', [
                ColumnSchema::int('id')->setAutoIncrement(),
                ColumnSchema::varchar('name')
            ])->setPrimary(['id'])
        );
        $database->createTable($model->getSchema());
        $database->exec('INSERT INTO test (id, name) VALUES (1, "Alice"), (2, "Bob")');
        $this->expectException(InvalidPrimaryKeyException::class);
        $model->getRowByPrimaryKey(['id' => 1, 'name' => 'Alice']);
    }

    public function testCanRetrieveRowByCompositeKey(): void
    {
        $database = $this->createEmptyDatabase();
        $model = $this->createTableModel(
            $database,
            TableSchema::new('test', [
                ColumnSchema::int('id'),
                ColumnSchema::varchar('name')
            ])->setPrimary(['id', 'name'])
        );
        $database->createTable($model->getSchema());
        $database->exec('INSERT INTO test (id, name) VALUES (1, "Alice"), (2, "Bob")');
        $this->assertSame(['id' => '2', 'name' => 'Bob'], $model->getRowByPrimaryKey(['id' => 2, 'name' => 'Bob']));
        $this->assertSame(null, $model->getRowByPrimaryKey(['id' => 1, 'name' => 'Bob']));
    }

    public function testMustProvideFullCompositeKey(): void
    {
        $database = $this->createEmptyDatabase();
        $model = $this->createTableModel(
            $database,
            TableSchema::new('test', [
                ColumnSchema::int('id'),
                ColumnSchema::varchar('name')
            ])->setPrimary(['id', 'name'])
        );
        $database->createTable($model->getSchema());
        $database->exec('INSERT INTO test (id, name) VALUES (1, "Alice"), (2, "Bob")');
        $this->expectException(InvalidPrimaryKeyException::class);
        $model->getRowByPrimaryKey(['id' => 1]);
    }

    private function createTableModel(Database $database, TableSchema $schema): TableModel
    {
        $model = new class ($database) extends TableModel
        {
            public $schema;
            public function __construct(Database $database)
            {
                parent::__construct($database);
            }
            public function getTableDefinition(): TableDefinition
            {
                $definition = new class () extends TableDefinition
                {
                    public $schema;
                    public function getSchemasByVersion(): array
                    {
                        return [$this->schema];
                    }
                };
                $definition->schema = $this->schema;
                return $definition;
            }
        };
        $model->schema = $schema;
        return $model;
    }
}
