<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Input;

use DateTimeInterface;

class ParameterValidator
{
    private array $data = [];
    private string $source = '';

    /**
     * @param array $data
     * @param string $sourceDescription Used for generating more explicit error messages
     * @return $this
     */
    public function setData(array $data, string $sourceDescription = ''): self
    {
        $this->data = $data;
        $this->source = $sourceDescription;
        return $this;
    }

    public function getBoolean(string $name): bool
    {
        return $this->boolean($name)->get();
    }

    public function getDateTime(string $name): ?DateTimeInterface
    {
        return $this->datetime($name)->get();
    }

    public function getDateTimeString(string $name): string
    {
        return $this->datetime($name)->getString();
    }

    public function getInt(string $name): int
    {
        return $this->int($name)->get();
    }

    public function getIpAddress(string $name): string
    {
        return $this->ipAddress($name)->get();
    }

    public function getString(string $name): string
    {
        return $this->string($name)->get();
    }

    public function boolean(string $name): BooleanParameter
    {
        return (new BooleanParameter($name))->setData($this->data, $this->source);
    }

    public function datetime(string $name): DateTimeParameter
    {
        return (new DateTimeParameter($name))->setData($this->data, $this->source);
    }

    public function int(string $name): IntParameter
    {
        return (new IntParameter($name))->setData($this->data, $this->source);
    }

    public function ipAddress(string $name): IpAddressParameter
    {
        return (new IpAddressParameter($name))->setData($this->data, $this->source);
    }

    public function string(string $name): StringParameter
    {
        return (new StringParameter($name))->setData($this->data, $this->source);
    }
}
