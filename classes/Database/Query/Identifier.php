<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database\Query;

abstract class Identifier extends SqlString
{
    public function __construct(string $string)
    {
        if ($string == '') {
            throw new InvalidIdentifierException('Identifier must not be empty');
        }
        if (preg_match('/[`"\']/', $string)) {
            throw new InvalidIdentifierException('Identifier must not contain any quotes');
        }
        parent::__construct($string);
    }

    public function canUsePlaceholderInPreparedStatement(): bool
    {
        return false;
    }
}
