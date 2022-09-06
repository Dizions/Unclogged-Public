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
            public function getLogger()
            {
                return $this->logger;
            }
        };
        $logger = new NullLogger();
        $this->assertSame($logger, $loggerAware->setLogger($logger)->getLogger());
    }

    public function testLoggerAwareReturnsItselfAfterSettingLogger(): void
    {
        $loggerAware = $this->getMockForAbstractClass(LoggerAware::class);
        $this->assertSame($loggerAware, $loggerAware->setLogger(new NullLogger()));
    }

    public function testNullLoggerIsSetCorrectly(): void
    {
        $loggerAware = new class () extends LoggerAware {
            public function getLogger()
            {
                return $this->logger;
            }
        };
        $this->assertInstanceOf(NullLogger::class, $loggerAware->setNullLogger()->getLogger());
    }

    public function testNullLoggerAwareReturnsItselfAfterSettingLogger(): void
    {
        $loggerAware = $this->getMockForAbstractClass(LoggerAware::class);
        $this->assertSame($loggerAware, $loggerAware->setNullLogger());
    }
}
