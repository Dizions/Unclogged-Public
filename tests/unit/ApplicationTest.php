<?php

declare(strict_types=1);

namespace Dizions\Unclogged;

use Dizions\Unclogged\Database\Database;
use Dizions\Unclogged\Errors\ErrorHandler;
use Dizions\Unclogged\Errors\HttpUnauthorizedException;
use Dizions\Unclogged\Http\Request\ParameterValidator;
use Dizions\Unclogged\Http\Request\Request;
use Dizions\Unclogged\Security\AlwaysInvalidCredentials;
use Dizions\Unclogged\Security\AlwaysMissingCredentials;
use Dizions\Unclogged\Security\AlwaysSucceedsCredentials;
use Dizions\Unclogged\Security\CredentialsInterface;
use Dizions\Unclogged\Security\CredentialsValidator;
use Dizions\Unclogged\Setup\Environment;
use Dizions\Unclogged\Setup\InvalidConfigurationException;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * @covers Dizions\Unclogged\Application
 */
final class ApplicationTest extends TestCase
{
    public function testEnvironmentCanBeRetrieved(): void
    {
        $app = new Application(new Environment([]), $this->createMock(Request::class));
        $this->assertInstanceOf(Environment::class, $app->getEnvironment());
    }

    public function testErrorHandlerCanBeRetrieved(): void
    {
        $app = new Application(new Environment([]), $this->createMock(Request::class));
        $this->assertInstanceOf(ErrorHandler::class, $app->getErrorHandler());
    }

    public function testRequestCanBeRetrieved(): void
    {
        $app = new Application(new Environment([]), $this->createMock(Request::class));
        $this->assertInstanceOf(Request::class, $app->getRequest());
    }

    public function testResponseEmitterCanBeRetrieved(): void
    {
        $app = new Application(new Environment([]), $this->createMock(Request::class));
        $this->assertInstanceOf(EmitterInterface::class, $app->getResponseEmitter());
    }

    public function testParameterValidatorCanBeRetrieved(): void
    {
        $app = new Application(new Environment([]), $this->createMock(Request::class));
        $this->assertInstanceOf(ParameterValidator::class, $app->getParameterValidator());
    }

    public function testLoggerCanBeRetrieved(): void
    {
        $app = new Application(new Environment([]), $this->createMock(Request::class));
        $this->assertInstanceOf(LoggerInterface::class, $app->getLogger());

        $logger = $this->createMock(LoggerInterface::class);
        $app->setLogger($logger);
        $this->assertSame($logger, $app->getLogger());
    }

    public function testMainlogDatabaseIsOnlyCreatedOnce(): void
    {
        $callCount = 0;
        $databaseFactory = function () use (&$callCount) {
            $callCount++;
            return $this->createMock(Database::class);
        };
        $app = new Application(new Environment([]), $this->createMock(Request::class));
        $app->setFactoryFunction(Database::class, $databaseFactory);
        $app->getDatabase();
        $app->getDatabase();
        $this->assertSame(1, $callCount);
    }

    public function testErrorResponseCanBeCreated(): void
    {
        $app = new Application(new Environment([]), $this->createMock(Request::class));
        $this->assertInstanceOf(ResponseInterface::class, $app->generateErrorResponse('', 500));
    }

    public function testApplicationCanGenerateDefaultName(): void
    {
        $app = new Application(new Environment([]), $this->createMock(Request::class));
        $this->assertSame('Application', $app->getName());
    }

    public function testApplicationCanGetNameFromEnvironment(): void
    {
        $env = (new Environment([]))->withVariable('APPLICATION_NAME', 'My Project');
        $app = new Application($env, $this->createMock(Request::class));
        $this->assertSame('My Project', $app->getName());
    }
    public function testApplicationNameCanBeSetExplicitly(): void
    {
        $app = new Application(new Environment([]), $this->createMock(Request::class));
        $this->assertSame('My Project', $app->setName('My Project')->getName());
    }

    public function testGettingCredentialsWithAnInvalidProviderGeneratesInvalidConfigurationException(): void
    {
        $app = new Application(new Environment([]), $this->createMock(Request::class));
        $app->setFactoryFunction(Database::class, fn () => $app);
        $this->expectException(InvalidConfigurationException::class);
        $app->getDatabase();
    }

    public function testGettingCredentialsWithoutAConfiguredProviderGeneratesInvalidConfigurationException(): void
    {
        $app = new Application(new Environment([]), $this->createMock(Request::class));
        $this->expectException(InvalidConfigurationException::class);
        $app->getCredentials();
    }

    public function testCredentialsCanBeRetrievedWhenConnectionIsAuthenticatedSuccessfully(): void
    {
        $app = new Application(new Environment([]), $this->createMock(Request::class));
        $app->setFactoryFunction(
            CredentialsValidator::class,
            fn () => new CredentialsValidator([new AlwaysSucceedsCredentials()])
        );
        $this->assertInstanceOf(CredentialsInterface::class, $app->getCredentials());
    }

    public function testMissingCredentialsGeneratesHttpUnauthorizedException(): void
    {
        $app = new Application(new Environment([]), $this->createMock(Request::class));
        $app->setFactoryFunction(
            CredentialsValidator::class,
            fn () => new CredentialsValidator([new AlwaysMissingCredentials()])
        );
        $this->expectException(HttpUnauthorizedException::class);
        $app->getCredentials();
    }

    public function testInvalidCredentialsGeneratesHttpUnauthorizedException(): void
    {
        $app = new Application(new Environment([]), $this->createMock(Request::class));
        $app->setFactoryFunction(
            CredentialsValidator::class,
            fn () => new CredentialsValidator([new AlwaysInvalidCredentials()])
        );
        $this->expectException(HttpUnauthorizedException::class);
        $app->getCredentials();
    }
}
