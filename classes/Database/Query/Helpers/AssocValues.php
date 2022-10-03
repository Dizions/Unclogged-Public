<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database\Query\Helpers;

use Countable;
use Dizions\Unclogged\Database\Query\ColumnName;
use Dizions\Unclogged\Database\Query\InvalidQueryValuesException;
use Dizions\Unclogged\Database\Query\SqlString;
use Dizions\Unclogged\Database\Query\SqlStringInterface;
use Dizions\Unclogged\Database\Schema\Renderers\SqlRendererInterface;

class AssocValues implements Countable
{
    /** @var array<string, SqlStringInterface|string|Stringable> $values */
    private array $values = [];

    public function count(): int
    {
        return count($this->values);
    }

    /** @return array{columns: string[], values: string[], parameters: string[]} */
    public function getComponents(SqlRendererInterface $renderer): array
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
        return ['columns' => $columns, 'values' => $values, 'parameters' => $parameters];
    }

    /**
     * @param array<string, SqlStringInterface|string|Stringable> $values
     * @return static
     */
    public function setValues(array $values): self
    {
        $this->assertValuesArrayIsValid($values);
        $this->values = $values;
        return $this;
    }

    private function assertValuesArrayIsNonEmpty(array $values): void
    {
        if (count($values) == 0) {
            throw new InvalidQueryValuesException('No query values were given');
        }
    }

    private function assertValuesArrayIsValid(array $values): void
    {
        $this->assertValuesArrayIsNonEmpty($values);
        foreach (array_keys($values) as $key) {
            if (!is_string($key) || $key == '') {
                throw new InvalidQueryValuesException('Query values must have non-empty string keys');
            }
        }
    }
}
