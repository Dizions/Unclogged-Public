<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Security\Password;

interface HashInterface
{
    /**
     * @param string $hash
     * @param array<int, string> $peppers
     */
    public function __construct(string $hash, array $peppers = []);
    public function getHash(): string;
    public function isValidFormat(): bool;
    public function match(string $plaintext): bool;
    public static function create(string $plaintext, array $peppers): self;
}
