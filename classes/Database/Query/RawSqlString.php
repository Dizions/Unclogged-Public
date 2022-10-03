<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database\Query;

use Dizions\Unclogged\Database\Schema\Renderers\SqlRendererInterface;

class RawSqlString extends SqlString
{
    public function canUsePlaceholderInPreparedStatement(): bool
    {
        return false;
    }

    public function render(SqlRendererInterface $renderer): string
    {
        return $this->getRaw();
    }
}
