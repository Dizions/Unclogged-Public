<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Logger;

use Psr\Log\LoggerInterface;
use Dizions\Unclogged\Setup\Environment;

class LoggerFactory
{
    private Environment $environment;

    public function __construct(Environment $environment)
    {
        $this->environment = $environment;
    }

    public function getLogger(): LoggerInterface
    {
        return new DefaultLogger();
    }
}
