<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Request;

/**
 * A request parameter which will be validated and retrieved as a bool. Rather than simply
 * evaluating the parameter in a boolean context, it supports the following pairs:
 * 1/0, true/false, Y/N, yes/no, on/off.
 *
 * Strings will be evaluated case-insensitively.
 *
 * (Note that GET and POST parameters will typically be strings, however they may be another type in
 * certain circumstances, for example if the body is provided as a JSON array, so int 1/0 and bool
 * true/false are supported.)
 *
 * @package Dizions\Unclogged\Request
 */
class BooleanParameter extends Parameter
{
    private const TRUE_OPTIONS = [1, '1', true, 'true', 'y', 'yes', 'on'];
    private const FALSE_OPTIONS = [0, '0', false, 'false', 'n', 'no', 'off'];

    public function get(): bool
    {
        return $this->toBoolean(parent::get());
    }

    private function toBoolean($value): bool
    {
        if (is_string($value)) {
            $value = strtolower($value);
        }
        if (in_array($value, self::TRUE_OPTIONS, true)) {
            return true;
        }
        if (in_array($value, self::FALSE_OPTIONS, true)) {
            return false;
        }
        throw new InvalidParameterException("Parameter {$this->getName()} could not be interpreted as a boolean");
    }
}
