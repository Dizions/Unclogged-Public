<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database\Query;

use Dizions\Unclogged\Database\Query\Helpers\AssocValues;
use Dizions\Unclogged\Database\Schema\SqlRendererInterface;
use Dizions\Unclogged\Database\Schema\SqlStringInterface;

class Where extends QueryModifier
{
    /** @var array{string, string[]} */
    private array $rendered;
    private SqlRendererInterface $renderer;
    private AssocValues $values;

    public function __construct()
    {
        $this->values = new AssocValues();
    }

    public function getSqlString(SqlRendererInterface $renderer): string
    {
        $this->setRenderer($renderer);
        return ($this->rendered ??= $this->getSqlStringAndParameters())[0];
    }

    public function getParameters(SqlRendererInterface $renderer): array
    {
        $this->setRenderer($renderer);
        return ($this->rendered ??= $this->getSqlStringAndParameters())[1];
    }

    /**
     * @param array<string, SqlStringInterface|string|Stringable> $values
     * @return static
     */
    public function setAssocValues(array $values): self
    {
        if (!empty($values)) {
            $this->values->setValues($values);
        }
        unset($this->rendered);
        return $this;
    }

    /** @return array{string, string[]} */
    protected function getSqlStringAndParameters(): array
    {
        if (count($this->values) == 0) {
            return ['', []];
        }
        [
            'columns' => $columns,
            'values' => $values,
            'parameters' => $parameters
        ] = $this->values->getComponents($this->renderer);
        $parts = [];
        $numCols = count($columns);
        for ($i = 0; $i < $numCols; $i++) {
            $parts[] = "{$columns[$i]} = {$values[$i]}";
        }
        return ['WHERE ' . implode(' AND ', $parts), $parameters];
    }

    private function setRenderer(SqlRendererInterface $renderer): void
    {
        if (!isset($this->renderer) || $renderer !== $this->renderer) {
            $this->renderer = $renderer;
            unset($this->rendered);
        }
    }
}
