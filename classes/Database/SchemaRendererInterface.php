<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database;

interface SchemaRendererInterface
{
    public function __construct(TableSchema $schema);
    public function getSchema(): TableSchema;
    /**
     * Generate the "CREATE TABLE" statement, and any additional statements that are needed after
     * it (eg "CREATE INDEX" statements).
     * @return array
     */
    public function renderCreateTable(): array;
    public function renderTableSchema(): string;
    public static function quoteIdentifier(string $identifier): string;
}
