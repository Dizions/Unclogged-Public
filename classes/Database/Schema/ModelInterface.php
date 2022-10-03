<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database\Schema;

use Dizions\Unclogged\Database\Database;

/**
 * Models provide an abstraction that encapsulates a database table.
 *
 * @package Dizions\Unclogged\Database\Schema
 */
interface ModelInterface
{
    public function getDatabase(): Database;
    public function getSchema(): TableSchema;
}
