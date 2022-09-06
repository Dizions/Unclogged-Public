<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Security\Password;

use Dizions\Unclogged\Setup\InvalidConfigurationException;

class PepperedPasswordValidator extends PasswordValidator
{
    /** @var array<int, string> */
    private array $peppers = [];
    /** @var class-string<HashInterface>[] */
    private array $hashClasses;

    /**
     * Create or verify hashes. Multiple hash classes can be specified; in this case, the first
     * class will be used for generation, and any of them can be used for verification. The
     * validator will try all of the classes in order, until it finds one which understands the
     * given hash format. If such a class is found, it will be used to verify the password, and
     * other hashes will not be tried. If not, verification fails.
     *
     * @param class-string<HashInterface>[] $hashClasses Classes to generate and verify hashes
     */
    public function __construct(array $hashClasses)
    {
        $this->hashClasses = $hashClasses;
        if (count($this->hashClasses) == 0) {
            throw new InvalidConfigurationException('At least one hashing class must be specified');
        }
    }

    public function generatePasswordHash(string $password): string
    {
        return $this->hashClasses[0]::create($password, $this->getPeppers())->getHash();
    }

    public function isHashUpToDate(string $hash): bool
    {
        return (new $this->hashClasses[0]($hash, $this->getPeppers()))->isValidFormat();
    }

    public function isPasswordCorrect(string $password, string $hash): bool
    {
        $peppers = $this->getPeppers();
        foreach ($this->hashClasses as $hashClass) {
            /** @var HashInterface */
            $hashObject = new $hashClass($hash, $peppers);
            if ($hashObject->isValidFormat()) {
                return $hashObject->match($password);
            }
        }
        return false;
    }

    public function setPeppers(array $peppers): self
    {
        ksort($peppers);
        $this->peppers = $peppers;
        return $this;
    }

    protected function getPeppers(): array
    {
        return $this->peppers;
    }
}
