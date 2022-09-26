<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database\Schema;

interface TableDefinitionInterface
{
    public const LATEST = PHP_INT_MAX;

    public function getSchema(int $version = self::LATEST): ?TableSchema;
    /**
     * Check whether code written for $targetVersion will work correctly with $currentVersion.
     * Often this will simply mean checking the schema for this table hasn't changed, but some
     * schema changes don't require code changes.
     */
    public function areVersionsCompatible(int $targetVersion, int $currentVersion = self::LATEST): bool;
}
