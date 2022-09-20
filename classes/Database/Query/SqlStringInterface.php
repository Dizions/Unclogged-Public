<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database\Query;

use Dizions\Unclogged\Database\Database;

interface SqlStringInterface
{
    public function canUsePlaceholderInPreparedStatement(): bool;
    public function render(Database $database): string;
}
