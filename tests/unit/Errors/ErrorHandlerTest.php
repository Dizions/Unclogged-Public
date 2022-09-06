<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Errors;

use ErrorException;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Dizions\Unclogged\TestCase;

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
        $handler->registerErrorHandler()->setLogger(new NullLogger());
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

    public function testExceptionHandlerReturnsResponse(): void
    {
        $handler = new ErrorHandler($this->createEmptyApplication());
        $handler->registerExceptionHandler()->setLogger(new NullLogger());
        $registeredHandler = set_exception_handler(null);
        $this->assertIsCallable($registeredHandler);
        $this->assertInstanceOf(ResponseInterface::class, $registeredHandler(new ErrorException()));
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

    public function testExceptionHandlerPassesResponseToCallback(): void
    {
        $handler = new ErrorHandler($this->createEmptyApplication());
        $response = null;
        $callback = function ($arg) use (&$response) {
            $response = $arg;
        };
        $handler->registerExceptionHandler($callback)->setLogger(new NullLogger());
        $registeredHandler = set_exception_handler(null);
        $this->assertIsCallable($registeredHandler);
        $registeredHandler(new ErrorException());
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testExceptionHandlerReturnsResponseGeneratedByHttpException(): void
    {
        $handler = new ErrorHandler($this->createEmptyApplication());
        $handler->setLogger(new NullLogger());
        $response = $this->createMock(ResponseInterface::class);
        $exception = $this->createMock(HttpException::class);
        $exception->expects($this->once())->method('getResponse')->will($this->returnValue($response));
        $this->assertSame($response, $handler->except($exception));
    }

    public function testExceptionHandlerRunsPreviousHandler(): void
    {
        $handler = new ErrorHandler($this->createEmptyApplication());
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
        $handler = new ErrorHandler($this->createEmptyApplication());
        $mockLogger = $this->createMock(LoggerInterface::class);
        $mockLogger->expects($this->once())->method('error');
        $handler->setLogger($mockLogger);
        $handler->except(new ErrorException());
    }

    public function testExceptionHandlerLogsSecondaryExceptionWhileRunningPreviousHandler(): void
    {
        $handler = new ErrorHandler($this->createEmptyApplication());
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
