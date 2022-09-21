<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database\Schema;

interface TableDefinitionInterface
{
    public const LATEST = PHP_INT_MAX;

    public function getSchema(int $version = self::LATEST): ?TableSchema;
}
