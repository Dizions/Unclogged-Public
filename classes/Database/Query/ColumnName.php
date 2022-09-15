<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database\Query;

class ColumnName extends Identifier
{
    private string $string;

    public function __toString(): string
    {
        if (!isset($this->string)) {
            $raw = $this->getRaw();
            $parts = explode('.', $raw);
            switch (count($parts)) {
                case 1:
                    return $this->string = $this->getDatabase()->quoteIdentifier($parts[0]);
                case 2:
                    $db = $this->getDatabase();
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
