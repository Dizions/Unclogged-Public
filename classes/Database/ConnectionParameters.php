<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database;

abstract class ConnectionParameters
{
    abstract public function getDriver(): string;
    /** @return string[] */
    abstract public function getDsnParameters(): array;

    /** Get the full DSN for this connection */
    public function getDsn(): string
    {
        $parameters = [];
        foreach ($this->getDsnParameters() as $k => $v) {
            if (is_int($k)) {
                $parameters[] = $v;
            } else {
                $parameters[] = "$k=$v";
            }
        }
        return $this->getDriver() . ':' . implode(';', $parameters);
    }

    /**
     * Implement this if the connection requires a $user argument outside the DSN
     */
    public function getUser(): ?string
    {
        return null;
    }

    /**
     * Implement this if the connection requires a $password argument outside the DSN
     */
    public function getPassword(): ?string
    {
        return null;
    }

    /**
     * Implement this if the connection requires an $options argument
     * @return ?string[]
     */
    public function getOptions(): ?array
    {
        return null;
    }
}
