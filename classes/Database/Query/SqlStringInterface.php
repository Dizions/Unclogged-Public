<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database\Query;

use Dizions\Unclogged\Database\Schema\SqlRendererInterface;

interface SqlStringInterface
{
    public function canUsePlaceholderInPreparedStatement(): bool;
    public function render(SqlRendererInterface $renderer): string;
}
