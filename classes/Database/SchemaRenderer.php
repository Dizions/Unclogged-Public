<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database;

abstract class SchemaRenderer implements SchemaRendererInterface
{
    private TableSchema $schema;

    abstract protected function renderAutoIncrement(bool $autoincrement): string;
    abstract protected function renderComment(string $comment): string;
    /**
     * If the column should include an inline REFERENCES statement (as opposed to a separate foreign
     * key declaration), it's generated here.
     */
    abstract protected function renderReferences(array $references): string;
    abstract protected function renderType(ColumnType $type): string;

    public function __construct(TableSchema $schema)
    {
        $this->schema = $schema;
    }

    public function getSchema(): TableSchema
    {
        return $this->schema;
    }

    public function renderTableSchema(): string
    {
        $schema = $this->getSchema();
        $definitionParts = array_merge(
            $this->renderColumns($schema),
            $this->renderConstraintsAndIndexes()
        );
        $definitionParts = array_filter($definitionParts);
        return $schema->getName() . "(\n    " . implode(",\n    ", $definitionParts) . "\n)";
    }

    protected function renderColumn(ColumnSchema $column): string
    {
        return implode(' ', array_filter([
            $column->getName(),
            $this->renderColumnType($column->getType(), $column->getNullable(), $column->getAutoIncrement()),
            $this->renderReferences($column->getReferences()),
            $this->renderDefault($column->getDefault()),
            $this->renderComment($column->getComment()),
        ]));
    }

    protected function renderColumnType(ColumnType $type, bool $nullable, bool $autoincrement): string
    {
        return implode(' ', array_filter([
            $this->renderType($type),
            $this->renderNullable($nullable),
            $this->renderAutoIncrement($autoincrement),
        ]));
    }

    /** @return string[] */
    protected function renderColumns(): array
    {
        return array_map(fn ($col) => $this->renderColumn($col), $this->getSchema()->getColumns());
    }

    /** @return string[] */
    protected function renderConstraintsAndIndexes(): array
    {
        return array_merge(
            [$this->renderPrimaryKey($this->getSchema())],
            $this->renderUniqueConstraints($this->getSchema()),
            $this->renderIndexes($this->getSchema())
        );
    }

    protected function renderDefault(?string $default): string
    {
        if ($default === '') {
            $default = "''";
        }
        return $default !== null ? "DEFAULT $default" : '';
    }

    /** @return string[] */
    protected function renderIndexes(): array
    {
        return array_map(
            fn ($i) => "INDEX {$i['name']}(" . implode(', ', $i['columns']) . ')',
            $this->getSchema()->getIndexes()
        );
    }

    protected function renderNullable(bool $nullable): string
    {
        return $nullable ? 'NULL' : 'NOT NULL';
    }

    protected function renderPrimaryKey(): string
    {
        $primary = $this->getSchema()->getPrimary();
        if ($primary) {
            return 'PRIMARY KEY (' . implode(', ', $primary) . ')';
        }
        return '';
    }

    /** @return string[] */
    protected function renderUniqueConstraints(): array
    {
        return array_map(
            fn ($i) => "UNIQUE {$i['name']}(" . implode(', ', $i['columns']) . ')',
            $this->getSchema()->getUniqueConstraints()
        );
    }
}
