<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database\Schema;

/**
 * Models provide an abstraction that encapsulates a database table.
 *
 * @package Dizions\Unclogged\Database\Schema
 */
interface ModelInterface
{
    public function getSchema(): TableSchema;
}
