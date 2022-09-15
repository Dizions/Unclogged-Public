<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database\Query;

use Dizions\Unclogged\Database\Database;

class SqlString
{
    private Database $database;
    private string $raw;

    public function __construct(Database $database, string $string)
    {
        $this->database = $database;
        $this->raw = $string;
    }

    public function canUsePlaceholderInPreparedStatement(): bool
    {
        return true;
    }

    public function __toString(): string
    {
        return $this->raw;
    }

    protected function getDatabase(): Database
    {
        return $this->database;
    }

    protected function getRaw(): string
    {
        return $this->raw;
    }
}
