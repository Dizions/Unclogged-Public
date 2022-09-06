<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Filesystem;

use ErrorException;

class FilesystemHelpers
{
    public static function isDir(string $path): bool
    {
        try {
            return is_readable($path) && is_dir($path);
            // @codeCoverageIgnoreStart
        } catch (ErrorException $e) {
            // It might be possible to get here by passing something wildly invalid (eg a path
            // containing a null byte) in a live system with the custom error handler in effect,
            // converting errors to exceptions. It doesn't appear to be possible to get here in any
            // reasonable test scenarios.
            return false;
            // @codeCoverageIgnoreEnd
        }
    }
}
