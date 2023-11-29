<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Security;

use Dizions\Unclogged\Security\Password\PasswordValidator;
use Dizions\Unclogged\Setup\Environment;
use Dizions\Unclogged\TestCase;

/**
 * @covers Dizions\Unclogged\Security\KeyCredentialsConfiguration
 */
final class KeyCredentialsConfigurationTest extends TestCase
{
    /** @dataProvider applicationNameProvider */
    public function testKeyHeaderAndAuthenticationSchemeAreCorrectlyGeneratedFromApplicationName(
        string $applicationName,
        string $keyHeader,
        string $authenticationScheme
    ): void {
        $validator = $this->getMockForAbstractClass(PasswordValidator::class);
        $config = new KeyCredentialsConfiguration(new Environment(), $applicationName, $validator);
        $this->assertSame($keyHeader, $config->getKeyHeader());
        $this->assertSame($authenticationScheme, $config->getAuthenticationScheme());
    }

    public function testKeyTableNameCanBeGeneratedAutomatically(): void
    {
        $validator = $this->getMockForAbstractClass(PasswordValidator::class);
        $env = (new Environment([]))->withVariable('AUTHENTICATION_KEY_TABLE_NAME_PREFIX', 'myapp_');
        $config = new KeyCredentialsConfiguration($env, 'Application', $validator);
        $this->assertSame('myapp_keys', $config->getKeyTableSchema()->getName());
    }

    public function testKeyTableNameCanBeSpecified(): void
    {
        $validator = $this->getMockForAbstractClass(PasswordValidator::class);
        $config = new KeyCredentialsConfiguration(new Environment(), 'my new application', $validator);
        $this->assertSame('my_new_application_keys', $config->getKeyTableSchema()->getName());
    }


    public function testPasswordValidatorCanBeRetrieved(): void
    {
        $validator = $this->getMockForAbstractClass(PasswordValidator::class);
        $config = new KeyCredentialsConfiguration(new Environment(), 'Application', $validator);
        $this->assertInstanceOf(PasswordValidator::class, $config->getPasswordValidator());
    }

    public static function applicationNameProvider(): array
    {
        return [
            ['the application', 'The-Application-Key', 'TheApplicationKey'],
            ['The application', 'The-Application-Key', 'TheApplicationKey'],
            ['The  Application', 'The-Application-Key', 'TheApplicationKey'],
            ['TheApplication', 'The-Application-Key', 'TheApplicationKey'],
            ['the-Application', 'The-Application-Key', 'TheApplicationKey'],
            ['THE_APPLICATION', 'The-Application-Key', 'TheApplicationKey'],
            ['THE APPLICATION', 'The-Application-Key', 'TheApplicationKey'],
        ];
    }
}
