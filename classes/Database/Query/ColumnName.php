<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database\Query;

use Dizions\Unclogged\Database\Schema\Renderers\SqlRendererInterface;

class ColumnName extends Identifier
{
    private string $string;

    public function render(SqlRendererInterface $renderer): string
    {
        if (!isset($this->string)) {
            $raw = $this->getRaw();
            $parts = explode('.', $raw);
            switch (count($parts)) {
                case 1:
                    return $this->string = $renderer->quoteIdentifier($parts[0]);
                case 2:
                    return $this->string = implode(
                        '.',
                        [$renderer->quoteIdentifier($parts[0]), $renderer->quoteIdentifier($parts[1])]
                    );
            }
            throw new InvalidIdentifierException("'$raw' is not a valid column name");
        }
        return $this->string;
    }
}
