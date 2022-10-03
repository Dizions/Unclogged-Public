<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database\Query;

use Dizions\Unclogged\Database\Schema\Renderers\SqlRendererInterface;

class SqlBoolean extends RawSqlString
{
    private bool $value;

    public function __construct(bool $value)
    {
        $this->value = $value;
    }

    public function render(SqlRendererInterface $renderer): string
    {
        return $renderer->renderBoolean($this->value);
    }
}
