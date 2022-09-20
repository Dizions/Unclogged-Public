<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database\Query;

use Dizions\Unclogged\Database\Schema\SqlRendererInterface;

class Insert extends Query
{
    private TableName $table;
    private array $values = [];

    public function __construct(string $table)
    {
        $this->table = new TableName($table);
    }

    public function values(array $values): self
    {
        $this->assertValuesArrayIsValid($values);
        $this->values = $values;
        return $this;
    }

    protected function getSqlStringAndParameters(SqlRendererInterface $renderer): array
    {
        $this->assertValuesArrayIsNonEmpty($this->values);
        $columns = [];
        $values = [];
        $parameters = [];
        foreach ($this->values as $column => $value) {
            $columns[] = (new ColumnName($column))->render($renderer);
            if (!($value instanceof SqlStringInterface)) {
                $value = new SqlString((string)$value);
            }
            if ($value->canUsePlaceholderInPreparedStatement()) {
                $values[] = '?';
                $parameters[] = $value->getRaw();
            } else {
                $values[] = $value->render($renderer);
            }
        }
        $columnsCsv = implode(',', $columns);
        $valuesCsv = implode(',', $values);
        return ["INSERT INTO {$this->table->render($renderer)} ($columnsCsv) VALUES ($valuesCsv)", $parameters];
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