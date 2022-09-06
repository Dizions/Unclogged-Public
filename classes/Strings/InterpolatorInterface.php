<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Strings;

interface InterpolatorInterface
{
    public function interpolate(string $message, array $context): string;
}
