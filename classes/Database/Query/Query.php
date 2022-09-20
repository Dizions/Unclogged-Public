<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database\Query;

use Dizions\Unclogged\Database\Database;

abstract class Query
{
    public function execute(Database $database): bool
    {
        [$sql, $parameters] = $this->getSqlStringAndParameters($database);
        $query = $database->prepare($sql);
        return $query->execute($parameters);
    }

    public function executeOrThrow(Database $database): void
    {
        [$sql, $parameters] = $this->getSqlStringAndParameters($database);
        $query = $database->prepare($sql);
        if (!$query->execute($parameters)) {
            throw new QueryFailureException("Failed to execute query: $sql");
        }
    }

    /** @return array{string, string[]} */
    abstract protected function getSqlStringAndParameters(Database $database): array;
}
