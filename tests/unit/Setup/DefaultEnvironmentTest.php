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
        $this->assertInstanceOf(DefaultEnvironment::class, new DefaultEnvironment(''));
    }

    public function testCanGenerateFinalEnvironment(): void
    {
        $this->assertInstanceOf(Environment::class, (new DefaultEnvironment(''))->getEnvironment());
    }
}
