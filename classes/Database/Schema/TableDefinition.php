<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database\Schema;

abstract class TableDefinition implements TableDefinitionInterface
{
    /** @var TableSchema[] */
    private array $schemas;

    /** @return array<int, TableSchema> */
    abstract protected function getSchemasByVersion(): array;

    public function getSchema(int $version = self::LATEST): ?TableSchema
    {
        $removedIn = $this->removedInVersion() ?? INF;
        if ($version >= $removedIn) {
            return null;
        }
        $this->schemas ??= $this->getSchemasSortedByVersionDescending();
        foreach (array_keys($this->schemas) as $schemaVersion) {
            if ($schemaVersion <= $version) {
                return $this->schemas[$schemaVersion];
            }
        }
        return null;
    }

    protected function removedInVersion(): ?int
    {
        return null;
    }

    private function getSchemasSortedByVersionDescending(): array
    {
        $schemas = $this->getSchemasByVersion();
        krsort($schemas);
        return $schemas;
    }
}
