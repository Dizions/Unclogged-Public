<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Security;

use Dizions\Unclogged\Http\Request\Request;
use Dizions\Unclogged\Setup\InvalidConfigurationException;
use Dizions\Unclogged\TestCase;

/**
 * @covers Dizions\Unclogged\Security\CredentialsValidator
 */
final class CredentialsValidatorTest extends TestCase
{
    public function testAuthenticatingWithNoClassesConfiguredGeneratesInvalidConfigurationException(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $validator = new CredentialsValidator([]);
        $validator->authenticate($this->createMock(Request::class));
    }

    public function testFirstSuccessfulCredentialsClassIsUsed(): void
    {
        $validator = new CredentialsValidator([
            new AlwaysMissingCredentials(),
            new AlwaysSucceedsCredentials(),
            new AlwaysSucceedsCredentials2(),
            new AlwaysInvalidCredentials(),
        ]);
        $this->assertInstanceOf(
            AlwaysSucceedsCredentials::class,
            $validator->authenticate($this->createMock(Request::class))
        );
    }

    public function testAuthenticationStopsAfterFindingInvalidCredentials(): void
    {
        $validator = new CredentialsValidator([
            new AlwaysMissingCredentials(),
            new AlwaysInvalidCredentials(),
            new AlwaysSucceedsCredentials(),
        ]);
        $this->expectException(InvalidCredentialsException::class);
        $validator->authenticate($this->createMock(Request::class));
    }

    public function testMissingCredentialsExceptionIsThrownWhenNoCredentialsAreFound(): void
    {
        $validator = new CredentialsValidator([new AlwaysMissingCredentials()]);
        $this->expectException(MissingCredentialsException::class);
        $validator->authenticate($this->createMock(Request::class));
    }
}
