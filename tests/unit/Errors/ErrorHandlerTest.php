<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Errors;

use Dizions\Unclogged\TestCase;
use ErrorException;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * @covers Dizions\Unclogged\Errors\ErrorHandler
 */
final class ErrorHandlerTest extends TestCase
{
    private $originalErrorHandler = null;
    private $originalExceptionHandler = null;

    public function setUp(): void
    {
        $this->originalErrorHandler = set_error_handler(null);
        $this->originalExceptionHandler = set_exception_handler(null);
    }

    public function tearDown(): void
    {
        set_error_handler($this->originalErrorHandler);
        set_exception_handler($this->originalExceptionHandler);
    }

    public function testErrorHandlerConvertsErrorToException(): void
    {
        $handler = new ErrorHandler($this->createEmptyApplication());
        $handler->registerErrorHandler();
        $registeredHandler = set_error_handler(null);
        $this->assertIsCallable($registeredHandler);
        $this->expectException(ErrorException::class);
        $registeredHandler(E_ERROR, '', __FILE__, __LINE__);
    }

    public function testErrorHandlerLogsWarningForDeprecationThenContinues(): void
    {
        $handler = new ErrorHandler($this->createEmptyApplication());

        $mockLogger = $this->createMock(LoggerInterface::class);
        $mockLogger->expects($this->once())->method('warning');
        $handler->setLogger($mockLogger);
        $handler->errorToException(E_DEPRECATED, '', __FILE__, __LINE__);

        $mockLogger = $this->createMock(LoggerInterface::class);
        $mockLogger->expects($this->once())->method('warning');
        $handler->setLogger($mockLogger);
        $handler->errorToException(E_USER_DEPRECATED, '', __FILE__, __LINE__);
    }

    public function testErrorHandlerCanBeUnregistered(): void
    {
        $handler = new ErrorHandler($this->createEmptyApplication());
        $handler->registerErrorHandler();
        $handler->unregisterErrorHandler();
        $this->assertNull(set_error_handler(null));
    }

    public function testUnregisteringErrorHandlerRestoresOriginalHandler(): void
    {
        $original = fn () => null;
        set_error_handler($original);
        $handler = new ErrorHandler($this->createEmptyApplication());
        $handler->registerErrorHandler();
        $handler->unregisterErrorHandler();
        $this->assertSame($original, set_error_handler(null));
    }

    public function testExceptionHandlerEmitsResponse(): void
    {
        $emitter = $this->createMock(EmitterInterface::class);
        $emitter->expects($this->once())->method('emit');
        $app = $this->createEmptyApplication();
        $app->setFactoryFunction(EmitterInterface::class, fn () => $emitter);
        $handler = new ErrorHandler($app);
        $handler->registerExceptionHandler();
        $registeredHandler = set_exception_handler(null);
        $this->assertIsCallable($registeredHandler);
        $registeredHandler(new ErrorException());
    }

    public function testExceptionHandlerCanBeUnregistered(): void
    {
        $handler = new ErrorHandler($this->createEmptyApplication());
        $handler->registerExceptionHandler();
        $handler->unregisterExceptionHandler();
        $this->assertNull(set_exception_handler(null));
    }

    public function testUnregisteringExceptionHandlerRestoresOriginalHandler(): void
    {
        $original = fn () => null;
        set_exception_handler($original);
        $handler = new ErrorHandler($this->createEmptyApplication());
        $handler->registerExceptionHandler();
        $handler->unregisterExceptionHandler();
        $this->assertSame($original, set_exception_handler(null));
    }

    public function testExceptionHandlerEmitsResponseGeneratedByHttpException(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $emitter = $this->createMock(EmitterInterface::class);
        $emitter->expects($this->once())->method('emit')->with($response);
        $app = $this->createEmptyApplication();
        $app->setFactoryFunction(EmitterInterface::class, fn () => $emitter);
        $handler = new ErrorHandler($app);
        $exception = $this->createMock(HttpException::class);
        $exception->expects($this->once())->method('getResponse')->will($this->returnValue($response));
        $handler->except($exception);
    }

    public function testExceptionHandlerRunsPreviousHandler(): void
    {
        $emitter = $this->createMock(EmitterInterface::class);
        $app = $this->createEmptyApplication();
        $app->setFactoryFunction(EmitterInterface::class, fn () => $emitter);
        $handler = new ErrorHandler($app);
        $run = false;
        set_exception_handler(function () use (&$run) {
            $run = true;
        });
        $handler->registerExceptionHandler();
        $registeredHandler = set_exception_handler(null);
        $registeredHandler(new ErrorException());
        $this->assertTrue($run);
    }

    public function testExceptionHandlerLogsMessage(): void
    {
        $emitter = $this->createMock(EmitterInterface::class);
        $app = $this->createEmptyApplication();
        $app->setFactoryFunction(EmitterInterface::class, fn () => $emitter);
        $handler = new ErrorHandler($app);
        $mockLogger = $this->createMock(LoggerInterface::class);
        $mockLogger->expects($this->once())->method('error');
        $handler->setLogger($mockLogger);
        $handler->except(new ErrorException());
    }

    public function testExceptionHandlerLogsSecondaryExceptionWhileRunningPreviousHandler(): void
    {
        $emitter = $this->createMock(EmitterInterface::class);
        $app = $this->createEmptyApplication();
        $app->setFactoryFunction(EmitterInterface::class, fn () => $emitter);
        $handler = new ErrorHandler($app);
        $mockLogger = $this->createMock(LoggerInterface::class);
        $mockLogger->expects($this->exactly(2))->method('error');
        set_exception_handler(function () {
            throw new ErrorException();
        });
        $handler->registerExceptionHandler()->setLogger($mockLogger);
        $registeredHandler = set_exception_handler(null);
        $registeredHandler(new ErrorException());
    }
}
