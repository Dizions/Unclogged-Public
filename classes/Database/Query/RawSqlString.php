<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database\Query;

class RawSqlString extends SqlString
{
    public function canUsePlaceholderInPreparedStatement(): bool
    {
        return false;
    }
}
