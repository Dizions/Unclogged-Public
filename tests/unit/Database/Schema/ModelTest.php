<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database\Schema;

use Dizions\Unclogged\Database\Database;
use Dizions\Unclogged\TestCase;

/** @covers Dizions\Unclogged\Database\Schema\Model */
final class ModelTest extends TestCase
{
    public function testDatabaseCanBeRetrieved(): void
    {
        $model = new class ($this->createEmptyDatabase()) extends Model
        {
            public function __construct(Database $database)
            {
                parent::__construct($database);
            }
            protected function getTableDefinition(): TableDefinitionInterface
            {
                return new class () extends TableDefinition
                {
                    protected function getSchemasByVersion(): array
                    {
                        return [];
                    }
                };
            }
        };
        $this->assertInstanceOf(Database::class, $model->getDatabase());
    }

    public function testSchemaCanBeRetrieved(): void
    {
        $tableSchema = $this->createMock(TableSchema::class);
        $tableDefinition = $this->createMock(TableDefinition::class);
        $tableDefinition->expects($this->once())->method('getSchema')->will($this->returnValue($tableSchema));
        $database = $this->createMock(Database::class);
        $model = $this->getMockBuilder(Model::class)
                      ->disableOriginalConstructor()
                      ->onlyMethods(['getDatabase'])
                      ->getMockForAbstractClass();
        $model->expects($this->once())->method('getDatabase')->will($this->returnValue($database));
        $model->expects($this->once())->method('getTableDefinition')->will($this->returnValue($tableDefinition));
        $this->assertInstanceOf(TableSchema::class, $model->getSchema());
    }

    public function testSchemaIsCached(): void
    {
        $tableSchema = $this->createMock(TableSchema::class);
        $tableDefinition = $this->createMock(TableDefinition::class);
        $tableDefinition->expects($this->once())->method('getSchema')->will($this->returnValue($tableSchema));
        $database = $this->createMock(Database::class);
        $model = $this->getMockBuilder(Model::class)
                      ->disableOriginalConstructor()
                      ->onlyMethods(['getDatabase'])
                      ->getMockForAbstractClass();
        $model->expects($this->once())->method('getDatabase')->will($this->returnValue($database));
        $model->expects($this->once())->method('getTableDefinition')->will($this->returnValue($tableDefinition));
        $model->getSchema();
        $model->getSchema();
    }

    public function testSchemaMustExist(): void
    {
        $tableDefinition = $this->createMock(TableDefinition::class);
        $tableDefinition->expects($this->once())->method('getSchema')->will($this->returnValue(null));
        $database = $this->createMock(Database::class);
        $model = $this->getMockBuilder(Model::class)
                      ->disableOriginalConstructor()
                      ->onlyMethods(['getDatabase'])
                      ->getMockForAbstractClass();
        $model->expects($this->once())->method('getDatabase')->will($this->returnValue($database));
        $model->expects($this->once())->method('getTableDefinition')->will($this->returnValue($tableDefinition));
        $this->expectException(IncompatibleSchemaVersionException::class);
        $model->getSchema();
    }

    public function testAssertionDoesNothingIfCompatible(): void
    {
        $tableDefinition = $this->createMock(TableDefinition::class);
        $tableDefinition->expects($this->once())->method('areVersionsCompatible')->will($this->returnValue(true));
        $database = $this->createMock(Database::class);
        $model = $this->getMockBuilder(Model::class)
                      ->disableOriginalConstructor()
                      ->onlyMethods(['getDatabase'])
                      ->getMockForAbstractClass();
        $model->expects($this->once())->method('getDatabase')->will($this->returnValue($database));
        $model->expects($this->once())->method('getTableDefinition')->will($this->returnValue($tableDefinition));
        $model->assertTableSchemaIsCompatibleWithVersion(1);
    }

    public function testAssertionThrowsIfNotCompatible(): void
    {
        $tableDefinition = $this->createMock(TableDefinition::class);
        $tableDefinition->expects($this->once())->method('areVersionsCompatible')->will($this->returnValue(false));
        $database = $this->createMock(Database::class);
        $model = $this->getMockBuilder(Model::class)
                      ->disableOriginalConstructor()
                      ->onlyMethods(['getDatabase'])
                      ->getMockForAbstractClass();
        $model->expects($this->once())->method('getDatabase')->will($this->returnValue($database));
        $model->expects($this->once())->method('getTableDefinition')->will($this->returnValue($tableDefinition));
        $this->expectException(IncompatibleSchemaVersionException::class);
        $model->assertTableSchemaIsCompatibleWithVersion(1);
    }
}
