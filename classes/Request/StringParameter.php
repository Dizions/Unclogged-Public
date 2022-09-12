<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Request;

class StringParameter extends Parameter
{
    public function get(): string
    {
        return (string)parent::get();
    }
}
