<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Filesystem;

use Dizions\Unclogged\TestCase;

/**
 * @covers Dizions\Unclogged\Filesystem\FilesystemHelpers
 */
final class FilesystemHelpersTest extends TestCase
{
    public function testDirectoryIsRecognisedAsDirectory(): void
    {
        $this->assertTrue(FilesystemHelpers::isDir(__DIR__));
    }

    public function testFileIsNotMisrecognisedAsDirectory(): void
    {
        $this->assertFalse(FilesystemHelpers::isDir(__FILE__));
    }

    public function testNonexistentPathIsNotMisrecognisedAsDirectory(): void
    {
        $this->assertFalse(FilesystemHelpers::isDir(__DIR__ . '/nonexistent/path/to/nothing'));
    }
}
