<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database\Query;

class TableName extends Identifier
{
    private string $string;

    public function __toString(): string
    {
        return $this->string ??= $this->getDatabase()->quoteIdentifier($this->getRaw());
    }
}
