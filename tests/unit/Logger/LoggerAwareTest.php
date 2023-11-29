<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Logger;

use Psr\Log\NullLogger;
use Dizions\Unclogged\TestCase;

/**
 * @covers Dizions\Unclogged\Logger\LoggerAware
 */
final class LoggerAwareTest extends TestCase
{
    public function testLoggerIsSetCorrectly(): void
    {
        $loggerAware = new class () extends LoggerAware {
        };
        $logger = new NullLogger();
        $loggerAware->setLogger($logger);
        $this->assertSame($logger, $loggerAware->getLogger());
    }

    public function testNullLoggerIsSetCorrectly(): void
    {
        $loggerAware = new class () extends LoggerAware {
        };
        $loggerAware->setNullLogger();
        $this->assertInstanceOf(NullLogger::class, $loggerAware->getLogger());
    }
}
