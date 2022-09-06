<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Security\Password;

use BadMethodCallException;

class NullPasswordValidator extends PasswordValidator
{
    public function isHashUpToDate(string $hash): bool
    {
        return false;
    }

    public function generatePasswordHash(string $password): string
    {
        throw new BadMethodCallException('A NullPasswordValidator cannot be used to generate a hash');
    }

    public function isPasswordCorrect(string $password, string $hash): bool
    {
        return false;
    }
}
