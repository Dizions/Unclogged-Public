<?php

declare(strict_types=1);

namespace Dizions\Unclogged;

use Stringable;

class TestStringable implements Stringable
{
    private string $string;

    public function __construct(string $string)
    {
        $this->string = $string;
    }

    public function __toString(): string
    {
        return $this->string;
    }
}
