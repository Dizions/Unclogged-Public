<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database\Schema;

use Dizions\Unclogged\TestCase;

/** @covers Dizions\Unclogged\Database\Schema\TableDefinition */
class TableDefinitionTest extends TestCase
{
    /** @var class-string<TableDefinition> */
    private string $class;

    public function setUp(): void
    {
        $this->class = get_class(new class () extends TableDefinition {
            public array $schemas;
            public function getSchemasByVersion(): array
            {
                return $this->schemas;
            }
        });
    }

    /** @dataProvider schemaVersionsProvider */
    public function testCorrectSchemaVersionIsFound(
        array $schemas,
        int $versionToGet,
        ?TableSchema $expected
    ) {
        $definition = new $this->class();
        $definition->schemas = $schemas;
        $this->assertSame($expected, $definition->getSchema($versionToGet));
    }

    public function testRemovedInVersionDefaultsToNever(): void
    {
        $v0 = new TableSchema('table', []);
        $definition = $this->getMockForAbstractClass(TableDefinition::class);
        $definition->expects($this->any())->method('getSchemasByVersion')->will($this->returnValue([0 => $v0]));
        $this->assertSame($v0, $definition->getSchema());
    }

    public function testVersionCheckIsCached(): void
    {
        $v0 = new TableSchema('table', []);
        $definition = $this->getMockForAbstractClass(TableDefinition::class);
        $definition->expects($this->once())->method('getSchemasByVersion')->will($this->returnValue([0 => $v0]));
        $definition->areVersionsCompatible(1, 2);
        $definition->areVersionsCompatible(1, 2);
    }

    public function testVersionIsAlwaysCompatibleWithItself(): void
    {
        $definition = $this->getMockForAbstractClass(TableDefinition::class);
        $this->assertTrue($definition->areVersionsCompatible(1, 1));
    }

    public function testVersionIsCompatibleWhenSchemaIsUnchanged(): void
    {
        $v0 = new TableSchema('table', []);
        $definition = $this->getMockForAbstractClass(TableDefinition::class);
        $definition->expects($this->any())->method('getSchemasByVersion')->will($this->returnValue([0 => $v0]));
        $this->assertTrue($definition->areVersionsCompatible(1));
    }

    public function testVersionIsIncompatibleIfTableWasAdded(): void
    {
        $v0 = new TableSchema('table', []);
        $definition = $this->getMockForAbstractClass(TableDefinition::class);
        $definition->expects($this->any())->method('getSchemasByVersion')->will($this->returnValue([2 => $v0]));
        $this->assertFalse($definition->areVersionsCompatible(1));
        $this->assertTrue($definition->areVersionsCompatible(3));
    }

    public function testVersionIsIncompatibleIfTableWasRemoved(): void
    {
        $v0 = new TableSchema('table', []);
        $definition = $this->getMockForAbstractClass(TableDefinition::class);
        $definition->expects($this->any())->method('getSchemasByVersion')->will(
            $this->returnValue([0 => $v0, 2 => null])
        );
        $this->assertFalse($definition->areVersionsCompatible(1));
        $this->assertTrue($definition->areVersionsCompatible(3));
    }

    public function schemaVersionsProvider(): array
    {
        $v1 = new TableSchema('table', []);
        $v2 = new TableSchema('table', []);
        $v3 = new TableSchema('table', []);
        $v4 = new TableSchema('table', []);
        return [
            [[1 => $v1], 0, null],
            [[1 => $v1], 1, $v1],
            [[1 => $v1], TableDefinition::LATEST, $v1],
            [[1 => $v1, 5 => null], TableDefinition::LATEST, null],
            [[1 => $v1, 4 => $v4, 7 => null], 3, $v1],
            [[1 => $v1, 2 => $v2, 3 => $v3, 4 => $v4, 7 => null], 2, $v2],
            [[1 => $v1, 2 => $v2, 3 => $v3, 4 => $v4, 7 => null], 6, $v4],
            [[1 => $v1, 2 => $v2, 3 => $v3, 4 => $v4, 7 => null], 7, null],
        ];
    }
}
