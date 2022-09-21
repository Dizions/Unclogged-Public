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
            public ?int $removedIn;
            public function getSchemasByVersion(): array
            {
                return $this->schemas;
            }
            public function removedInVersion(): ?int
            {
                return $this->removedIn;
            }
        });
    }

    /** @dataProvider schemaVersionsProvider */
    public function testCorrectSchemaVersionIsFound(
        array $schemas,
        ?int $removedIn,
        int $versionToGet,
        ?TableSchema $expected
    ) {
        $definition = new $this->class();
        $definition->schemas = $schemas;
        $definition->removedIn = $removedIn;
        $this->assertSame($expected, $definition->getSchema($versionToGet));
    }

    public function testRemovedInVersionDefaultsToNever(): void
    {
        $v0 = new TableSchema('table', []);
        $definition = $this->getMockForAbstractClass(TableDefinition::class);
        $definition->expects($this->any())->method('getSchemasByVersion')->will($this->returnValue([0 => $v0]));
        $this->assertSame($v0, $definition->getSchema());
    }

    public function schemaVersionsProvider(): array
    {
        $v1 = new TableSchema('table', []);
        $v2 = new TableSchema('table', []);
        $v3 = new TableSchema('table', []);
        $v4 = new TableSchema('table', []);
        return [
            [[1 => $v1], null, 0, null],
            [[1 => $v1], null, 1, $v1],
            [[1 => $v1], null, TableDefinition::LATEST, $v1],
            [[1 => $v1], 5, TableDefinition::LATEST, null],
            [[1 => $v1, 4 => $v4], null, 3, $v1],
            [[1 => $v1, 2 => $v2, 3 => $v3, 4 => $v4], 7, 2, $v2],
            [[1 => $v1, 2 => $v2, 3 => $v3, 4 => $v4], 7, 6, $v4],
            [[1 => $v1, 2 => $v2, 3 => $v3, 4 => $v4], 7, 7, null],
        ];
    }
}
