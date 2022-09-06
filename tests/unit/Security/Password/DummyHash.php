<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Security\Password;

class DummyHash implements HashInterface
{
    protected static $getHash = '';
    protected static $isValidFormat = true;
    protected static $match = true;

    public function __construct(string $hash, array $peppers = [])
    {
    }

    public function getHash(): string
    {
        return static::$getHash;
    }

    public function isValidFormat(): bool
    {
        return static::$isValidFormat;
    }

    public function match(string $plaintext): bool
    {
        return static::$match;
    }

    public static function create(string $plaintext, array $peppers): self
    {
        return new static($plaintext, $peppers);
    }
}
