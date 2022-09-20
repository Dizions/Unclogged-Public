<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database\Schema;

class SqliteRenderer extends SqlRenderer
{
    public function renderCreateTable(TableSchema $schema): array
    {
        $definition = $this->renderTableSchema($schema);
        $indexes = $this->renderIndexes($schema);
        return [
            "CREATE TABLE IF NOT EXISTS {$definition}",
            ...$indexes,
        ];
    }

    protected function renderAutoIncrement(bool $autoincrement): string
    {
        if ($autoincrement) {
            throw new InvalidTableSchemaException('sqlite does not support autoincrementing non-primary keys');
        }
        return '';
    }

    protected function renderColumn(TableSchema $schema, ColumnSchema $column): string
    {
        $primary = $schema->getPrimary();
        if (count($primary) == 1 && $primary[0] == $column->getName()) {
            return $this->renderColumnAsPrimaryKey($column);
        }
        return parent::renderColumn($schema, $column);
    }

    protected function renderComment(string $comment): string
    {
        return '';
    }

    /** @return string[] */
    protected function renderConstraintsAndIndexes(TableSchema $schema): array
    {
        return array_merge(
            [$this->renderPrimaryKey($schema)],
            $this->renderUniqueConstraints($schema),
            $this->renderForeignKeys($schema),
        );
    }

    protected function renderIndexes(TableSchema $schema): array
    {
        $table = $schema->getName();
        return array_map(
            fn ($i) => "CREATE INDEX IF NOT EXISTS {$i['name']} ON $table(" . implode(', ', $i['columns']) . ')',
            $schema->getIndexes()
        );
    }

    protected function renderPrimaryKey(TableSchema $schema): string
    {
        $primary = $schema->getPrimary();
        if (count($primary) > 1) {
            return 'PRIMARY KEY (' . implode(', ', $primary) . ')';
        }
        return '';
    }

    protected function renderReferences(array $references): string
    {
        return '';
    }

    protected function renderType(ColumnType $type): string
    {
        return strtoupper($type->getType());
    }

    /** @return string[] */
    protected function renderUniqueConstraints(TableSchema $schema): array
    {
        return array_map(
            fn ($i) => "UNIQUE (" . implode(', ', $i['columns']) . ')',
            $schema->getUniqueConstraints()
        );
    }

    /** @return string[] */
    private function renderForeignKeys(TableSchema $schema): array
    {
        $keys = [];
        foreach ($schema->getColumns() as $column) {
            $references = $column->getReferences();
            if ($references) {
                [$foreignTable, $foreignColumn] = $references;
                $keys[] = [$column->getName(), $foreignTable, $foreignColumn];
            }
        }
        if (!$keys) {
            return [];
        }
        return array_map(
            fn ($k) => "FOREIGN KEY ({$k[0]}) REFERENCES {$k[1]}({$k[1]})",
            $keys
        );
    }

    private function renderColumnAsPrimaryKey(ColumnSchema $column): string
    {
        $name = $column->getName();
        $typeString = $this->renderType($column->getType());
        if (substr($typeString, 0, 3) == 'INT' && $column->getAutoIncrement()) {
            return "$name INTEGER PRIMARY KEY";
        }
        return implode(' ', array_filter([
            $name,
            $this->renderType($column->getType()),
            $this->renderNullable($column->getNullable()),
            $this->renderAutoIncrement($column->getAutoIncrement()),
            'PRIMARY KEY',
            $this->renderDefault($column->getDefault()),
            $this->renderComment($column->getComment()),
        ]));
    }
}
