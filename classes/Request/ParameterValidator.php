<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Request;

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

    public function getString(string $name): string
    {
        return $this->string($name)->get();
    }

    public function boolean(string $name): BooleanParameter
    {
        return new BooleanParameter($name, $this->request);
    }

    public function string(string $name): StringParameter
    {
        return new StringParameter($name, $this->request);
    }
}
