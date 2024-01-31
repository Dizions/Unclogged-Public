<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Security;

use Dizions\Unclogged\Database\Database;
use Dizions\Unclogged\Http\Request\RequestFactory;
use Dizions\Unclogged\Http\Request\Request;
use Dizions\Unclogged\Security\Password\PasswordValidator;
use Dizions\Unclogged\Setup\Environment;
use Dizions\Unclogged\Setup\InvalidConfigurationException;
use Dizions\Unclogged\TestCase;

/**
 * @covers Dizions\Unclogged\Security\KeyCredentials
 */
final class KeyCredentialsTest extends TestCase
{
    public function testAclCannotBeRetrievedWithoutAuthenticating(): void
    {
        $env = new Environment();
        $credentials = $this->createKeyCredentials($env, $this->createEmptyDatabase(), true, true);
        $this->expectException(NotYetAuthenticatedException::class);
        $credentials->getAcl();
    }

    public function testDatabaseErrorGeneratesInvalidConfigurationException(): void
    {
        $env = new Environment();
        $credentials = $this->createKeyCredentials($env, $this->createEmptyDatabase(), true, false);
        $key = $credentials->createNewKey();
        $request = (new RequestFactory($env))
            ->proxiedRequestFromGlobals(['HTTP_WWW_AUTHENTICATE' => "TestKey $key"]);
        $this->expectException(InvalidConfigurationException::class);
        $credentials->authenticate($request);
    }

    public function testEmptyRequestCannotBeAuthenticated(): void
    {
        $credentials = $this->createKeyCredentials(new Environment(), $this->createEmptyDatabase(), true, false);
        $this->expectException(MissingCredentialsException::class);
        $credentials->authenticate($this->createMock(Request::class));
    }

    public function testInvalidFormatKeyCanBeFoundInDedicatedHeader(): void
    {
        $env = new Environment();
        $credentials = $this->createKeyCredentials($env, $this->createEmptyDatabase(), true, false);
        $request = (new RequestFactory($env))->proxiedRequestFromGlobals(['HTTP_TEST_KEY' => 'invalid']);
        $this->expectException(InvalidCredentialsException::class);
        $credentials->authenticate($request);
    }

    public function testInvalidFormatKeyCanBeFoundInAuthenticateHeader(): void
    {
        $env = new Environment();
        $credentials = $this->createKeyCredentials($env, $this->createEmptyDatabase(), true, false);
        $request = (new RequestFactory($env))
            ->proxiedRequestFromGlobals(['HTTP_WWW_AUTHENTICATE' => 'TestKey invalid']);
        $this->expectException(InvalidCredentialsException::class);
        $credentials->authenticate($request);
    }

    public function testIrrelevantAuthenticateHeaderIsSkipped(): void
    {
        $env = new Environment();
        $credentials = $this->createKeyCredentials($env, $this->createEmptyDatabase(), true, false);
        $request = (new RequestFactory($env))
            ->proxiedRequestFromGlobals(['HTTP_WWW_AUTHENTICATE' => 'SomeOtherScheme invalid']);
        $this->expectException(MissingCredentialsException::class);
        $credentials->authenticate($request);
    }

    public function testNewKeyCanBeCreated(): void
    {
        $credentials = $this->createKeyCredentials(new Environment(), $this->createEmptyDatabase(), true, false);
        $key = $credentials->createNewKey();
        $this->assertIsString($key);
        $parts = explode('-', $key, 2);
        $this->assertCount(2, $parts);
        $this->assertSame(12, strlen($parts[0]));
    }

    /** @depends testNewKeyCanBeCreated */
    public function testValidKeyCanBeAuthenticated(): void
    {
        $env = new Environment();
        $db = $this->createKeyDatabase('Test');
        $credentials = $this->createKeyCredentials($env, $db, true, true);
        $key = $credentials->createNewKey();
        $insert = $db->prepare(
            'INSERT INTO test_keys (key_key, key_secret_hash, key_valid_before, key_description, key_acl)
            VALUES (?, "", "3000-01-01 00:00:00", "", ?)'
        );
        $insert->execute([explode('-', $key, 2)[0], AccessControlList::ALL_PERMISSIONS]);
        $request = (new RequestFactory($env))
            ->proxiedRequestFromGlobals(['HTTP_WWW_AUTHENTICATE' => "TestKey $key"]);
        $this->assertInstanceOf(CredentialsInterface::class, $credentials->authenticate($request));
        $this->assertTrue($credentials->getAcl()->isActionPermitted('*'));
    }

    /** @depends testValidKeyCanBeAuthenticated */
    public function testLastUsedDateIsUpdatedAutomatically(): void
    {
        $env = new Environment();
        $db = $this->createKeyDatabase('Test');
        $credentials = $this->createKeyCredentials($env, $db, true, true);
        $key = $credentials->createNewKey();
        $insert = $db->prepare(
            'INSERT INTO test_keys (key_key, key_secret_hash, key_valid_before, key_description)
            VALUES (?, "", "3000-01-01 00:00:00", "")'
        );
        $insert->execute([explode('-', $key, 2)[0]]);
        $request = (new RequestFactory($env))
            ->proxiedRequestFromGlobals(['HTTP_WWW_AUTHENTICATE' => "TestKey $key"]);
        $credentials->authenticate($request);
        $this->assertNotNull($db->query("SELECT key_last_used FROM test_keys")->fetch()[0]);
    }

    /** @depends testValidKeyCanBeAuthenticated */
    public function testInvalidKeyIsRejected(): void
    {
        $env = new Environment();
        $db = $this->createKeyDatabase('Test');
        $credentials = $this->createKeyCredentials($env, $db, true, false);
        $key = $credentials->createNewKey();
        $insert = $db->prepare(
            'INSERT INTO test_keys (key_key, key_secret_hash, key_valid_before, key_description)
            VALUES (?, "", "3000-01-01 00:00:00", "")'
        );
        $insert->execute([explode('-', $key, 2)[0]]);
        $request = (new RequestFactory($env))
            ->proxiedRequestFromGlobals(['HTTP_WWW_AUTHENTICATE' => "TestKey $key"]);
        $this->expectException(InvalidCredentialsException::class);
        $credentials->authenticate($request);
    }

    /** @depends testInvalidKeyIsRejected */
    public function testInvalidKeyDoesNotUpdateLastUsedDate(): void
    {
        $env = new Environment();
        $db = $this->createKeyDatabase('Test');
        $credentials = $this->createKeyCredentials($env, $db, true, false);
        $key = $credentials->createNewKey();
        $insert = $db->prepare(
            'INSERT INTO test_keys (key_key, key_secret_hash, key_valid_before, key_description)
            VALUES (?, "", "3000-01-01 00:00:00", "")'
        );
        $insert->execute([explode('-', $key, 2)[0]]);
        $request = (new RequestFactory($env))
            ->proxiedRequestFromGlobals(['HTTP_WWW_AUTHENTICATE' => "TestKey $key"]);
        try {
            $credentials->authenticate($request);
        } catch (InvalidCredentialsException $e) {
        }
        $this->assertNull($db->query("SELECT key_last_used FROM test_keys")->fetch()[0]);
    }

    /**
     * @depends testInvalidKeyIsRejected
     * @dataProvider dateRestrictionProvider
     */
    public function testDateRestrictionsAreCorrectlyApplied(
        string $after,
        ?string $before,
        bool $expected
    ): void {
        $env = new Environment();
        $db = $this->createKeyDatabase('Test');
        $credentials = $this->createKeyCredentials($env, $db, true, true);
        $key = $credentials->createNewKey();
        $insert = $db->prepare(
            'INSERT INTO test_keys
                (key_key, key_secret_hash, key_valid_after, key_valid_before, key_description)
            VALUES (?, "", ?, ?, "")'
        );
        $insert->execute([explode('-', $key, 2)[0], $after, $before]);
        $request = (new RequestFactory($env))
            ->proxiedRequestFromGlobals(['HTTP_WWW_AUTHENTICATE' => "TestKey $key"]);
        try {
            $credentials->authenticate($request);
            $success = true;
        } catch (InvalidCredentialsException $e) {
            $success = false;
        }
        $this->assertSame($expected, $success);
    }

    /**
     * @depends testInvalidKeyIsRejected
     * @dataProvider ipAddressProvider
     */
    public function testIpAddressRestrictionIsCorrectlyApplied(
        string $restriction,
        string $source,
        bool $expected
    ): void {
        $env = new Environment();
        $db = $this->createKeyDatabase('Test');
        $credentials = $this->createKeyCredentials($env, $db, true, true);
        $key = $credentials->createNewKey();
        $insert = $db->prepare(
            'INSERT INTO test_keys
                (key_key, key_secret_hash, key_valid_before, key_description, key_restrict_to_ip_addresses)
            VALUES (?, "", "3000-01-01 00:00:00", "", ?)'
        );
        $insert->execute([explode('-', $key, 2)[0], $restriction]);
        $request = (new RequestFactory($env))
            ->proxiedRequestFromGlobals(['HTTP_WWW_AUTHENTICATE' => "TestKey $key", 'REMOTE_ADDR' => $source]);
        try {
            $credentials->authenticate($request);
            $success = true;
        } catch (InvalidCredentialsException $e) {
            $success = false;
        }
        $this->assertSame($expected, $success);
    }

    /** @depends testValidKeyCanBeAuthenticated */
    public function testNonexistentKeyIsRejected(): void
    {
        $env = new Environment();
        $db = $this->createKeyDatabase('Test');
        $credentials = $this->createKeyCredentials($env, $db, true, true);
        $key = $credentials->createNewKey();
        $request = (new RequestFactory($env))
            ->proxiedRequestFromGlobals(['HTTP_WWW_AUTHENTICATE' => "TestKey $key"]);
        $this->expectException(InvalidCredentialsException::class);
        $credentials->authenticate($request);
    }

    /** @depends testValidKeyCanBeAuthenticated */
    public function testOutdatedHashIsUpdatedAutomatically(): void
    {
        $validator = $this->getMockForAbstractClass(PasswordValidator::class);
        $validator->expects($this->any())->method('isHashUpToDate')->will($this->returnValue(false));
        $validator->expects($this->any())->method('isPasswordCorrect')->will($this->returnValue(true));
        $validator->expects($this->any())->method('generatePasswordHash')->will($this->returnValue('new'));
        $db = $this->createKeyDatabase('Test');
        $env = new Environment();
        $config = new KeyCredentialsConfiguration($env, 'Test', $validator);
        $credentials = new KeyCredentials($config, $db);
        $key = $credentials->createNewKey();
        $insert = $db->prepare(
            'INSERT INTO test_keys (key_key, key_secret_hash, key_valid_before, key_description)
            VALUES (?, "old", "3000-01-01 00:00:00", "")'
        );
        $insert->execute([explode('-', $key, 2)[0]]);
        $request = (new RequestFactory($env))
            ->proxiedRequestFromGlobals(['HTTP_WWW_AUTHENTICATE' => "TestKey $key"]);
        $credentials->authenticate($request);
        $this->assertSame('new', $db->query('SELECT key_secret_hash FROM test_keys')->fetch()[0]);
    }

    public static function dateRestrictionProvider(): array
    {
        return [
            ['2001-01-01 00:00:00', null, true],
            ['2001-01-01 00:00:00', '3001-01-01 00:00:00', true],
            ['2001-01-01 00:00:00', '2002-01-01 00:00:00', false],
            ['3001-01-01 00:00:00', null, false],
            ['3001-01-01 00:00:00', '4001-01-01 00:00:00', false],
            ['3001-01-01 00:00:00', '2002-01-01 00:00:00', false],
        ];
    }

    public static function ipAddressProvider(): array
    {
        return [
            ['["10.0.0.0/8"]', '10.2.3.4', true],
            ['["10.0.0.0/8"]', '11.2.3.4', false],
            ['["10.0.0.0/24"]', '10.2.3.4', false],
            ['["10.2.3.4"]', '10.2.3.4', true],
            ['["10.0.0.0/16","::1"]', '10.0.3.4', true],
            ['["2001::/20","10.2.3.4"]', '2001:0fff::1', true],
            ['["2001::/20","10.2.3.4"]', '2001:1000::1', false],
        ];
    }

    private function createKeyCredentials(
        Environment $env,
        Database $db,
        bool $isHashUpToDate,
        bool $isPasswordCorrect
    ): KeyCredentials {
        $validator = $this->getMockForAbstractClass(PasswordValidator::class);
        $validator->expects($this->any())->method('isHashUpToDate')->will($this->returnValue($isHashUpToDate));
        $validator->expects($this->any())->method('isPasswordCorrect')->will($this->returnValue($isPasswordCorrect));
        $config = new KeyCredentialsConfiguration($env, 'Test', $validator);
        return new KeyCredentials($config, $db);
    }

    private function createKeyDatabase(string $applicationName): Database
    {
        $validator = $this->getMockForAbstractClass(PasswordValidator::class);
        $db = $this->createEmptyDatabase();
        $config = new KeyCredentialsConfiguration(new Environment(), $applicationName, $validator);
        return $db->createTable($config->getKeyTableSchema());
    }
}
