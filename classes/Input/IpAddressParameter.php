<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Input;

class IpAddressParameter extends Parameter
{
    public function get(): string
    {
        $value = parent::get();
        if (!filter_var($value, FILTER_VALIDATE_IP)) {
            throw new InvalidParameterException("Parameter {$this->getName()} must be an IP address");
        }
        return $value;
    }
}
