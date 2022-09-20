<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database\Query;

use Dizions\Unclogged\Database\Schema\SqlRendererInterface;

class TableName extends Identifier
{
    private string $string;

    public function render(SqlRendererInterface $renderer): string
    {
        return $this->string ??= $renderer->quoteIdentifier($this->getRaw());
    }
}
