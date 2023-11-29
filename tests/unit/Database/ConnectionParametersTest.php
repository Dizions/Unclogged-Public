<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database;

use Dizions\Unclogged\TestCase;

/**
 * @covers Dizions\Unclogged\Database\ConnectionParameters
 */
final class ConnectionParametersTest extends TestCase
{
    /** @dataProvider driverAndParameterProvider */
    public function testDsnIsGeneratedCorrectlyFromDriverAndParameters(
        string $expected,
        string $driver,
        array $parameters
    ): void {
        $mock = $this->getMockForAbstractClass(ConnectionParameters::class);
        $mock->expects($this->any())
             ->method('getDriver')
             ->will($this->returnValue($driver));
        $mock->expects($this->any())
             ->method('getDsnParameters')
             ->will($this->returnValue($parameters));
        $this->assertSame($parameters, $mock->getDsnParameters());
        $this->assertSame($expected, $mock->getDsn());
    }

    public function testUserDefaultsToNull(): void
    {
        $mock = $this->getMockForAbstractClass(ConnectionParameters::class);
        $this->assertNull($mock->getUser());
    }

    public function testPasswordDefaultsToNull(): void
    {
        $mock = $this->getMockForAbstractClass(ConnectionParameters::class);
        $this->assertNull($mock->getPassword());
    }

    public function testOptionsDefaultsToNull(): void
    {
        $mock = $this->getMockForAbstractClass(ConnectionParameters::class);
        $this->assertNull($mock->getOptions());
    }

    public static function driverAndParameterProvider(): array
    {
        return [
            ['mysql:', 'mysql', []],
            ['sqlite:test.db', 'sqlite', ['test.db']],
            ['sqlite::memory:', 'sqlite', [':memory:']],
            ['mysql:host=localhost;dbname=testdb', 'mysql', ['host' => 'localhost', 'dbname' => 'testdb']],
        ];
    }
}
