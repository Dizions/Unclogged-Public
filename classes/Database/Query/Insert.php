<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database\Query;

use Dizions\Unclogged\Database\Database;

class Insert extends Query
{
    private TableName $table;
    private array $values = [];

    public function __construct(Database $database, string $table)
    {
        parent::__construct($database);
        $this->table = new TableName($database, $table);
    }

    public function values(array $values): self
    {
        $this->assertValuesArrayIsValid($values);
        $this->values = $values;
        return $this;
    }

    protected function getSqlStringAndParameters(): array
    {
        $this->assertValuesArrayIsNonEmpty($this->values);
        $columns = [];
        $values = [];
        $parameters = [];
        foreach ($this->values as $column => $value) {
            $columns[] = (string)$this->createColumnNameFromString($column);
            if (!($value instanceof SqlString)) {
                $value = $this->createSqlStringFromString((string)$value);
            }
            if ($value->canUsePlaceholderInPreparedStatement()) {
                $values[] = '?';
                $parameters[] = (string)$value;
            } else {
                $values[] = (string)$value;
            }
        }
        $columnsCsv = implode(',', $columns);
        $valuesCsv = implode(',', $values);
        return ["INSERT INTO {$this->table} ($columnsCsv) VALUES ($valuesCsv)", $parameters];
    }

    private function assertValuesArrayIsNonEmpty(array $values): void
    {
        if (count($values) == 0) {
            throw new InvalidInsertException('No insert values were given');
        }
    }

    private function assertValuesArrayIsValid(array $values): void
    {
        $this->assertValuesArrayIsNonEmpty($values);
        foreach (array_keys($values) as $key) {
            if (!is_string($key) || $key == '') {
                throw new InvalidInsertException('Insert values must have non-empty string keys');
            }
        }
    }
}
