<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database\Query;

use Dizions\Unclogged\Database\Database;

class TableName extends Identifier
{
    private string $string;

    public function render(Database $db): string
    {
        return $this->string ??= $db->quoteIdentifier($this->getRaw());
    }
}
