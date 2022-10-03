<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database\Query;

use Dizions\Unclogged\Database\Schema\SqlRendererInterface;

abstract class QueryModifier
{
    abstract public function getSqlString(SqlRendererInterface $renderer): string;
    /** @return string[] */
    abstract public function getParameters(SqlRendererInterface $renderer): array;
}
