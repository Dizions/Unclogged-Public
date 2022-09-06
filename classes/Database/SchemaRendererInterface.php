<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database;

interface SchemaRendererInterface
{
    public function __construct(TableSchema $schema);
    public function getSchema(): TableSchema;
    public function renderCreateTable(): array;
    public function renderTableSchema(): string;
}
