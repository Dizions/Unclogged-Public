<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Security\Password;

abstract class PasswordValidator
{
    abstract public function generatePasswordHash(string $password): string;
    abstract public function isHashUpToDate(string $hash): bool;
    abstract public function isPasswordCorrect(string $password, string $hash): bool;

    /**
     * Generate a cryptographically random string of a given length, suitable for use as a password.
     *
     * If the given $alphabet includes multibyte characters, the resulting string will be $numChars
     * characters long, which is likely to be more than $numChars bytes.
     *
     * @param int $numChars Length of the string to generate, in characters
     * @param string $alphabet Characters to include as random options
     * @return string
     */
    public function generateRandomString(int $numChars, string $alphabet): string
    {
        $out = '';
        $numChoices = mb_strlen($alphabet);
        for ($i = 0; $i < $numChars; $i++) {
            $out .= mb_substr($alphabet, random_int(0, $numChoices - 1), 1);
        }
        return $out;
    }
}
