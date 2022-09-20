<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database\Schema;

interface SqlRendererInterface
{
    /**
     * Generate the "CREATE TABLE" statement, and any additional statements that are needed after
     * it (eg "CREATE INDEX" statements).
     * @return string[]
     */
    public function renderCreateTable(TableSchema $schema): array;
    public function quoteIdentifier(string $identifier): string;
}
