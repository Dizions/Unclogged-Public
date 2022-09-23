<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database\Query;

use Dizions\Unclogged\Database\Schema\SqlRendererInterface;

class SqlNull extends RawSqlString
{
    public function __construct()
    {
    }

    public function render(SqlRendererInterface $renderer): string
    {
        return 'NULL';
    }
}
