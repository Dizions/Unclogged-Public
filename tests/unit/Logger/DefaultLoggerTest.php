<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Logger;

use Dizions\Unclogged\TestCase;

/**
 * @covers Dizions\Unclogged\Logger\DefaultLogger
 */
final class DefaultLoggerTest extends TestCase
{
    private $tmpfile;
    private $orginalLogDestination;

    public function setUp(): void
    {
        $this->tmpfile = tmpfile();
        $this->orginalLogDestination = ini_set('error_log', stream_get_meta_data($this->tmpfile)['uri']);
    }

    public function tearDown(): void
    {
        ini_set('error_log', $this->orginalLogDestination);
    }

    public function testDefaultLoggerLogsToErrorLog(): void
    {
        (new DefaultLogger())->error(__METHOD__);
        $this->assertStringContainsString(__METHOD__, stream_get_contents($this->tmpfile));
    }
}
