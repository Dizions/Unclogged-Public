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
        [$sql, $parameters] = $this->getSqlStringAndParameters();
        $query = $this->database->prepare($sql);
        return $query->execute($parameters);
    }

    public function executeOrThrow(): void
    {
        [$sql, $parameters] = $this->getSqlStringAndParameters();
        $query = $this->database->prepare($sql);
        if (!$query->execute($parameters)) {
            throw new QueryFailureException("Failed to execute query: $sql");
        }
    }

    /** @return array{string, string[]} */
    abstract protected function getSqlStringAndParameters(): array;

    protected function createColumnNameFromString(string $name): ColumnName
    {
        return new ColumnName($this->database, $name);
    }

    protected function createSqlStringFromString(string $string): SqlString
    {
        return new SqlString($this->database, $string);
    }

    protected function getDatabase(): Database
    {
        return $this->database;
    }
}
