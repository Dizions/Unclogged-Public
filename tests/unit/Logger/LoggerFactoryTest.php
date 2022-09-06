<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Logger;

use Dizions\Unclogged\Logger\DefaultLogger;
use Dizions\Unclogged\Setup\Environment;
use Dizions\Unclogged\TestCase;

/**
 * @covers Dizions\Unclogged\Logger\LoggerFactory
 */
final class LoggerFactoryTest extends TestCase
{
    public function testDefaultLoggerIsCreatedWhenNothingIsSpecified(): void
    {
        $this->assertInstanceOf(DefaultLogger::class, (new LoggerFactory(new Environment([])))->getLogger());
    }
}
