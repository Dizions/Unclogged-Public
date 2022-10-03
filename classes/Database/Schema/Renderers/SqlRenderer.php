<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database\Schema\Renderers;

use Dizions\Unclogged\Database\Query\SqlStringInterface;
use Dizions\Unclogged\Database\Schema\ColumnSchema;
use Dizions\Unclogged\Database\Schema\ColumnType;
use Dizions\Unclogged\Database\Schema\TableSchema;

abstract class SqlRenderer implements SqlRendererInterface
{
    abstract protected function renderAutoIncrement(bool $autoincrement): string;
    abstract protected function renderComment(string $comment): string;
    /**
     * If the column should include an inline REFERENCES statement (as opposed to a separate foreign
     * key declaration), it's generated here.
     */
    abstract protected function renderReferences(array $references): string;
    abstract protected function renderType(ColumnType $type): string;

    public function quoteIdentifier(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    public function quoteString(string $string): string
    {
        return "'" . str_replace("'", "''", $string) . "'";
    }

    public function renderBoolean(bool $value): string
    {
        return $value ? 'true' : 'false';
    }

    public function renderCurrentTimestamp(): string
    {
        return 'CURRENT_TIMESTAMP';
    }

    protected function renderColumn(TableSchema $schema, ColumnSchema $column): string
    {
        return implode(' ', array_filter([
            $column->getName(),
            $this->renderType($column->getType()),
            $this->renderNullable($column->getNullable()),
            $this->renderAutoIncrement($column->getAutoIncrement()),
            $this->renderReferences($column->getReferences()),
            $this->renderDefault($column->getDefault()),
            $this->renderComment($column->getComment()),
        ]));
    }

    /** @return string[] */
    protected function renderConstraintsAndIndexes(TableSchema $schema): array
    {
        return array_merge(
            [$this->renderPrimaryKey($schema)],
            $this->renderUniqueConstraints($schema),
            $this->renderIndexes($schema)
        );
    }

    /** @return string[] */
    protected function renderIndexes(TableSchema $schema): array
    {
        return array_map(
            fn ($i) => "INDEX {$i['name']}(" . implode(', ', $i['columns']) . ')',
            $schema->getIndexes()
        );
    }

    protected function renderPrimaryKey(TableSchema $schema): string
    {
        $primary = $schema->getPrimary();
        if ($primary) {
            return 'PRIMARY KEY (' . implode(', ', $primary) . ')';
        }
        return '';
    }

    /** @return string[] */
    protected function renderUniqueConstraints(TableSchema $schema): array
    {
        return array_map(
            fn ($i) => "UNIQUE {$i['name']}(" . implode(', ', $i['columns']) . ')',
            $schema->getUniqueConstraints()
        );
    }

    final protected function renderDefault(?SqlStringInterface $default): string
    {
        return $default ? "DEFAULT {$default->render($this)}" : '';
    }

    final protected function renderNullable(bool $nullable): string
    {
        return $nullable ? 'NULL' : 'NOT NULL';
    }

    final protected function renderTableSchema(TableSchema $schema): string
    {
        $definitionParts = array_merge(
            $this->renderColumns($schema),
            $this->renderConstraintsAndIndexes($schema)
        );
        $definitionParts = array_filter($definitionParts);
        return $schema->getName() . "(\n    " . implode(",\n    ", $definitionParts) . "\n)";
    }

    /** @return string[] */
    private function renderColumns(TableSchema $schema): array
    {
        return array_map(fn ($col) => $this->renderColumn($schema, $col), $schema->getColumns());
    }
}
