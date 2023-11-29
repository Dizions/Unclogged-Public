<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Logger;

use Psr\Log\AbstractLogger;
use Dizions\Unclogged\Strings\BasicInterpolator;

class DefaultLogger extends AbstractLogger
{
    /** @var Stringable|string $message */
    public function log($level, $message, array $context = []): void
    {
        $message = (new BasicInterpolator())->interpolate($message, $context);
        error_log("[$level] $message");
    }
}
