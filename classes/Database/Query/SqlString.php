<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database\Query;

use Dizions\Unclogged\Database\Schema\SqlRendererInterface;

class SqlString implements SqlStringInterface
{
    private string $raw;

    public function __construct(string $string)
    {
        $this->raw = $string;
    }

    public function canUsePlaceholderInPreparedStatement(): bool
    {
        return true;
    }

    public function render(SqlRendererInterface $renderer): string
    {
        return $this->raw;
    }

    protected function getRaw(): string
    {
        return $this->raw;
    }
}
