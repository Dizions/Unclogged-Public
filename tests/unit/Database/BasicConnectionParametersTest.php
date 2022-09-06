<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database;

use PDO;
use Dizions\Unclogged\TestCase;

/**
 * @covers Dizions\Unclogged\Database\BasicConnectionParameters
 */
final class BasicConnectionParametersTest extends TestCase
{
    public function testDsnIsConstructedAsExpected(): void
    {
        $this->assertSame(
            'sqlite:',
            (new BasicConnectionParameters('sqlite'))->getDsn()
        );
        $this->assertSame(
            'sqlite::memory:',
            (new BasicConnectionParameters('sqlite', [':memory:']))->getDsn()
        );
        $this->assertSame(
            'mysql:host=thehost;user=theuser',
            (new BasicConnectionParameters('mysql', ['host' => 'thehost', 'user' => 'theuser']))->getDsn()
        );
    }

    public function testOptionsIncludeErrorModeExceptionByDefault(): void
    {
        $options = (new BasicConnectionParameters(''))->getOptions();
        $this->assertArrayHasKey(PDO::ATTR_ERRMODE, $options);
        $this->assertSame(PDO::ERRMODE_EXCEPTION, $options[PDO::ATTR_ERRMODE]);
    }

    public function testOptionsMayBeSetToNull(): void
    {
        $this->assertNull((new BasicConnectionParameters('', [], null))->getOptions());
    }
}
