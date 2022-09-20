<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database\Query;

use Dizions\Unclogged\Database\Database;

abstract class Query
{
    private Database $database;

    public function __construct(Database $database)
    {
        $this->database = $database;
    }

    public function execute(): bool
    {
        [$sql, $parameters] = $this->getSqlStringAndParameters($this->database);
        $query = $this->database->prepare($sql);
        return $query->execute($parameters);
    }

    public function executeOrThrow(): void
    {
        [$sql, $parameters] = $this->getSqlStringAndParameters($this->database);
        $query = $this->database->prepare($sql);
        if (!$query->execute($parameters)) {
            throw new QueryFailureException("Failed to execute query: $sql");
        }
    }

    /** @return array{string, string[]} */
    abstract protected function getSqlStringAndParameters(Database $database): array;

    protected function createColumnNameFromString(string $name): ColumnName
    {
        return new ColumnName($name);
    }

    protected function createSqlStringFromString(string $string): SqlString
    {
        return new SqlString($string);
    }

    protected function getDatabase(): Database
    {
        return $this->database;
    }
}
