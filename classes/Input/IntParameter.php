<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Input;

class IntParameter extends Parameter
{
    public function get(): int
    {
        $value = parent::get();
        if (!$this->isInteger($value)) {
            throw new InvalidParameterException("Parameter {$this->getName()} must be an integer");
        }
        return (int)$value;
    }

    private function isInteger($in): bool
    {
        if (is_string($in)) {
            $in = preg_replace('/^[0]+(?!$)/', '', trim($in));
        }
        return is_int($in) || is_string($in) && ((string)(int)$in) === $in;
    }
}
