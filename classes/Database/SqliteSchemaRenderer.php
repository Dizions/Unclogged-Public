<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database;

class SqliteSchemaRenderer extends SchemaRenderer
{
    public function renderCreateTable(): array
    {
        $definition = $this->renderTableSchema();
        $indexes = $this->renderIndexes();
        return [
            "CREATE TABLE IF NOT EXISTS {$definition}",
            ...$indexes,
        ];
    }

    public static function quoteIdentifier(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    protected function renderAutoIncrement(bool $autoincrement): string
    {
        if ($autoincrement) {
            throw new InvalidTableSchemaException('sqlite does not support autoincrementing non-primary keys');
        }
        return '';
    }

    protected function renderColumn(ColumnSchema $column): string
    {
        $primary = $this->getSchema()->getPrimary();
        if (count($primary) == 1 && $primary[0] == $column->getName()) {
            return $this->renderColumnAsPrimaryKey($column);
        }
        return parent::renderColumn($column);
    }

    protected function renderComment(string $comment): string
    {
        return '';
    }

    /** @return string[] */
    protected function renderConstraintsAndIndexes(): array
    {
        return array_merge(
            [$this->renderPrimaryKey($this->getSchema())],
            $this->renderUniqueConstraints($this->getSchema()),
            $this->renderForeignKeys($this->getSchema()),
        );
    }

    protected function renderIndexes(): array
    {
        $table = $this->getSchema()->getName();
        return array_map(
            fn ($i) => "CREATE INDEX IF NOT EXISTS {$i['name']} ON $table(" . implode(', ', $i['columns']) . ')',
            $this->getSchema()->getIndexes()
        );
    }

    protected function renderPrimaryKey(): string
    {
        $primary = $this->getSchema()->getPrimary();
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
    protected function renderUniqueConstraints(): array
    {
        return array_map(
            fn ($i) => "UNIQUE (" . implode(', ', $i['columns']) . ')',
            $this->getSchema()->getUniqueConstraints()
        );
    }

    /** @return string[] */
    private function renderForeignKeys(): array
    {
        $keys = [];
        foreach ($this->getSchema()->getColumns() as $column) {
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
            $this->renderColumnType($column->getType(), $column->getNullable(), $column->getAutoIncrement()),
            'PRIMARY KEY',
            $this->renderDefault($column->getDefault()),
            $this->renderComment($column->getComment()),
        ]));
    }
}
