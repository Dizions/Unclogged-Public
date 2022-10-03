<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database\Schema\Renderers;

use Dizions\Unclogged\Database\Schema\TableSchema;

interface SqlRendererInterface
{
    /**
     * Generate the "CREATE TABLE" statement, and any additional statements that are needed after
     * it (eg "CREATE INDEX" statements).
     * @return string[]
     */
    public function renderCreateTable(TableSchema $schema): array;
    public function quoteIdentifier(string $identifier): string;
    public function quoteString(string $string): string;

    /** Get the SQL string used to represent a boolean value */
    public function renderBoolean(bool $value): string;
    /** Get the SQL string used to retrieve the current date and time (typically 'CURRENT_TIMESTAMP') */
    public function renderCurrentTimestamp(): string;
}
