<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database\Query;

use Dizions\Unclogged\Database\Query\Helpers\AssocValues;
use Dizions\Unclogged\Database\Schema\Renderers\SqlRendererInterface;

class Insert extends Query
{
    private TableName $table;
    private AssocValues $values;

    public function __construct(string $table)
    {
        $this->table = new TableName($table);
        $this->values = new AssocValues();
    }

    public function values(array $values): self
    {
        $this->values->setValues($values);
        return $this;
    }

    protected function getSqlStringAndParameters(SqlRendererInterface $renderer): array
    {
        [
            'columns' => $columns,
            'values' => $values,
            'parameters' => $parameters
        ] = $this->values->getComponents($renderer);
        $columnsCsv = implode(',', $columns);
        $valuesCsv = implode(',', $values);
        return ["INSERT INTO {$this->table->render($renderer)} ($columnsCsv) VALUES ($valuesCsv)", $parameters];
    }
}
