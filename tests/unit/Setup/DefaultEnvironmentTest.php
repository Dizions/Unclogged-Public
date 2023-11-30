<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Setup;

use Dizions\Unclogged\TestCase;

/**
 * @covers Dizions\Unclogged\Setup\DefaultEnvironment
 */
final class DefaultEnvironmentTest extends TestCase
{
    public function testCanBeConstructed(): void
    {
        $this->assertInstanceOf(DefaultEnvironment::class, DefaultEnvironment::fromGlobal());
    }

    public function testCanGenerateFinalEnvironment(): void
    {
        $this->assertInstanceOf(
            Environment::class,
            DefaultEnvironment::fromGlobal()->getEnvironment('')
        );
    }
}
