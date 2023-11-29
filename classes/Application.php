<?php

declare(strict_types=1);

namespace Dizions\Unclogged;

use Dizions\Unclogged\Database\Database;
use Dizions\Unclogged\Errors\ErrorHandler;
use Dizions\Unclogged\Errors\HttpUnauthorizedException;
use Dizions\Unclogged\Logger\LoggerAware;
use Dizions\Unclogged\Request\ParameterValidator;
use Dizions\Unclogged\Request\Request;
use Dizions\Unclogged\Security\{CredentialsInterface, CredentialsValidator};
use Dizions\Unclogged\Security\{InvalidCredentialsException, MissingCredentialsException};
use Dizions\Unclogged\Setup\Environment;
use Dizions\Unclogged\Setup\InvalidConfigurationException;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Psr\Http\Message\ResponseInterface;

class Application extends LoggerAware
{
    private CredentialsInterface $credentials;
    private Database $database;
    private Environment $environment;
    private ErrorHandler $errorHandler;
    private string $name;
    private Request $request;
    private EmitterInterface $responseEmitter;
    /** @var array<string, callable> */
    private array $factoryFunctions = [];

    public function __construct(Environment $environment, Request $request)
    {
        $this->setNullLogger();
        $this->environment = $environment;
        $this->request = $request;
        $this->setFactoryFunction(
            ErrorHandler::class,
            function ($app) {
                $errorHandler = new ErrorHandler($app);
                $errorHandler->setLogger($app->getLogger());
                return $errorHandler;
            }
        );
        $this->setFactoryFunction(EmitterInterface::class, fn () => new SapiEmitter());
    }

    public function generateErrorResponse($content, int $code): ResponseInterface
    {
        return new JsonResponse($content, $code);
    }

    /**
     * Retrieve and validate the credentials provided in the request.
     *
     * @return CredentialsInterface
     * @throws HttpUnauthorizedException If credentials are missing or invalid
     * @throws InvalidConfigurationException If no credential providers were configured
     */
    public function getCredentials(): CredentialsInterface
    {
        try {
            return $this->credentials ??=
                $this->createNew(CredentialsValidator::class)->authenticate($this->getRequest());
        } catch (InvalidCredentialsException $e) {
            throw new HttpUnauthorizedException('Invalid credentials provided: ' . $e->getMessage(), $e);
        } catch (MissingCredentialsException $e) {
            throw new HttpUnauthorizedException('No credentials provided', $e);
        }
    }

    public function getEnvironment(): Environment
    {
        return $this->environment;
    }

    public function getErrorHandler(): ErrorHandler
    {
        return $this->errorHandler ??= $this->createNew(ErrorHandler::class);
    }

    public function getDatabase(): Database
    {
        return $this->database ??= $this->createNew(Database::class);
    }

    public function getName(): string
    {
        return $this->name ??= $this->environment->get('APPLICATION_NAME') ??
            substr(strrchr(get_class($this), '\\'), 1); // Default to short class name
    }

    public function getParameterValidator(): ParameterValidator
    {
        return new ParameterValidator($this->request);
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getResponseEmitter(): EmitterInterface
    {
        return $this->responseEmitter ??= $this->createNew(EmitterInterface::class);
    }

    /**
     * @psalm-param class-string $class The class that this function will create
     * @param callable $function A function taking $this as a parameter and returning a $class
     * @return static
     */
    public function setFactoryFunction(string $class, callable $function): self
    {
        $this->factoryFunctions[$class] = $function;
        return $this;
    }

    /** @return static */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @psalm-template Subject of object
     * @psalm-param class-string<Subject> $class
     * @psalm-return Subject
     * @throws InvalidConfigurationException If no valid factory function was registered for $class
     */
    protected function createNew(string $class): object
    {
        if (!array_key_exists($class, $this->factoryFunctions)) {
            throw new InvalidConfigurationException("No factory function registered for $class");
        }
        $object = $this->factoryFunctions[$class]($this);
        if (!is_a($object, $class)) {
            $actual = get_class($object);
            throw new InvalidConfigurationException(
                "Factory function registered for $class produced object of type $actual"
            );
        }
        return $object;
    }
}
