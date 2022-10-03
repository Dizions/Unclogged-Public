<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database\Schema;

/**
 * Table models provide an abstraction that encapsulates a database table where there is no
 * direct one-to-one relationship between an object and a row. Typically this means a single object
 * that acts as a factory for record object instances, and/or provides functions for manipulating
 * records in bulk..
 *
 * @package Dizions\Unclogged\Database\Schema
 */
interface TableModelInterface extends ModelInterface
{
    public function getRowById(int $id): ?array;
    /** @throws IncompatibleSchemaVersionException */
    public function assertTableSchemaIsCompatibleWithVersion(int $version): void;
}
