<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database;

use PDO;

class BasicConnectionParameters extends ConnectionParameters
{
    private string $driver;
    private array $parameters;
    private ?array $options;

    public function __construct(
        string $driver,
        array $parameters = [],
        ?array $options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    ) {
        $this->driver = $driver;
        $this->parameters = $parameters;
        $this->options = $options;
    }

    public function getDriver(): string
    {
        return $this->driver;
    }

    public function getDsnParameters(): array
    {
        return $this->parameters;
    }

    public function getOptions(): ?array
    {
        return $this->options;
    }
}
