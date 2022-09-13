<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Request;

use DateTimeInterface;

class ParameterValidator
{
    private $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
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
        return new BooleanParameter($name, $this->request);
    }

    public function datetime(string $name): DateTimeParameter
    {
        return new DateTimeParameter($name, $this->request);
    }

    public function int(string $name): IntParameter
    {
        return new IntParameter($name, $this->request);
    }

    public function ipAddress(string $name): IpAddressParameter
    {
        return new IpAddressParameter($name, $this->request);
    }

    public function string(string $name): StringParameter
    {
        return new StringParameter($name, $this->request);
    }
}
