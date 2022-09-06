<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Logger;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

abstract class LoggerAware implements LoggerAwareInterface
{
    /**
     * @param LoggerInterface $logger
     * @return static $this
     */
    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * @return static $this
     */
    public function setNullLogger(): self
    {
        return $this->setLogger(new NullLogger());
    }
}
