<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database;

class TableSchema
{
    private string $name;
    /** @var ColumnSchema[] */
    private array $columns = [];
    /** @var string[] */
    private $columnNames;
    /** @var string[] */
    private array $primary = [];
    private array $uniqueConstraints = [];
    private array $indexes = [];

    /**
     * @param iterable<ColumnSchema> $columnSchemas
     */
    public function __construct(string $name, iterable $columnSchemas)
    {
        $this->name = $name;
        foreach ($columnSchemas as $column) {
            $this->columns[] = $column;
            $this->columnNames[] = $column->getName();
        }
    }

    /**
     * @param iterable<string> $columnNames
     * @param string $indexName
     * @return self $this
     */
    public function addIndex(iterable $columnNames, string $indexName = ''): self
    {
        $this->checkColumnsAreAllDefined($columnNames);
        if (!$indexName) {
            $indexName = implode('_', $columnNames) . '_idx';
        }
        $this->indexes[] = ['name' => $indexName, 'columns' => [...$columnNames]];
        return $this;
    }

    /**
     * @param iterable<string> $columnNames
     * @param string $constraintName
     * @return self $this
     */
    public function addUnique(iterable $columnNames, string $constraintName = ''): self
    {
        $this->checkColumnsAreAllDefined($columnNames);
        if (!$constraintName) {
            $constraintName = implode('_', $columnNames) . '_unq';
        }
        $this->uniqueConstraints[] = ['name' => $constraintName, 'columns' => [...$columnNames]];
        return $this;
    }

    /** @return ColumnSchema[] */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * @return array [['name' => <index name>, 'columns' => ['column1', ...]]]
     */
    public function getIndexes(): array
    {
        return $this->indexes;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /** @return string[]  */
    public function getPrimary(): array
    {
        return $this->primary;
    }

    /**
     * @return array [['name' => <constraint name>, 'columns' => ['column1', ...]]]
     */
    public function getUniqueConstraints(): array
    {
        return $this->uniqueConstraints;
    }

    /**
     * @param iterable<string> $primary
     * @return self $this
     */
    public function setPrimary(iterable $primary): self
    {
        $this->checkColumnsAreAllDefined($primary);
        $this->primary = [...$primary];
        return $this;
    }

    private function checkColumnsAreAllDefined(iterable $columnNames): void
    {
        foreach ($columnNames as $column) {
            if (!in_array($column, $this->columnNames)) {
                throw new InvalidTableSchemaException("$column was not declared");
            }
        }
    }
}
