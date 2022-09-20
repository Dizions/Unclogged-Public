<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database\Query;

use Dizions\Unclogged\Database\Database;

class ColumnName extends Identifier
{
    private string $string;

    public function render(Database $db): string
    {
        if (!isset($this->string)) {
            $raw = $this->getRaw();
            $parts = explode('.', $raw);
            switch (count($parts)) {
                case 1:
                    return $this->string = $db->quoteIdentifier($parts[0]);
                case 2:
                    return $this->string = implode(
                        '.',
                        [$db->quoteIdentifier($parts[0]), $db->quoteIdentifier($parts[1])]
                    );
            }
            throw new InvalidIdentifierException("'$raw' is not a valid column name");
        }
        return $this->string;
    }
}
