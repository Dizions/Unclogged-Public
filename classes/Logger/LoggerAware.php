<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Logger;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

abstract class LoggerAware implements LoggerAwareInterface
{
    private LoggerInterface $logger;

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function setNullLogger(): void
    {
        $this->setLogger(new NullLogger());
    }
}
