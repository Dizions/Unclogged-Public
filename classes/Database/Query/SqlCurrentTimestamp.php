<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database\Query;

use Dizions\Unclogged\Database\Schema\SqlRendererInterface;

class SqlCurrentTimestamp extends RawSqlString
{
    public function __construct()
    {
    }

    public function render(SqlRendererInterface $renderer): string
    {
        return $renderer->renderCurrentTimestamp();
    }
}
