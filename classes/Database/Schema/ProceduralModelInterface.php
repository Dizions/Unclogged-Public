<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database\Schema;

/**
 * Procedural models provide an abstraction that encapsulates a database table where there is no
 * direct one-to-one relationship between an object and a row. Typically this means a single object
 * that represents the entire table in some way.
 *
 * @package Dizions\Unclogged\Database\Schema
 */
interface ProceduralModelInterface extends ModelInterface
{
}
