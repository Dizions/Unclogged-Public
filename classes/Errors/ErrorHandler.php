<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Errors;

use Dizions\Unclogged\Application;
use Dizions\Unclogged\Logger\LoggerAware;
use ErrorException;
use Throwable;

class ErrorHandler extends LoggerAware
{
    private const ERROR_NAMES = [
        E_WARNING => 'E_WARNING',
        E_NOTICE => 'E_NOTICE',
        E_USER_ERROR => 'E_USER_ERROR',
        E_USER_WARNING => 'E_USER_WARNING',
        E_USER_NOTICE => 'E_USER_NOTICE',
        E_STRICT => 'E_STRICT',
        E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
        E_DEPRECATED => 'E_DEPRECATED',
        E_USER_DEPRECATED => 'E_USER_DEPRECATED',
    ];
    private Application $application;
    private bool $errorHandlerRegistered = false;
    private bool $exceptionHandlerRegistered = false;
    /** @var ?callable */
    private $previousErrorHandler = null;
    /** @var ?callable */
    private $previousExceptionHandler = null;

    public function __construct(Application $application)
    {
        $this->setNullLogger();
        $this->application = $application;
    }

    public function registerErrorHandler(): self
    {
        $this->previousErrorHandler = set_error_handler([$this, 'errorToException']);
        $this->errorHandlerRegistered = true;
        return $this;
    }

    public function registerExceptionHandler(): self
    {
        $this->previousExceptionHandler = set_exception_handler([$this, 'except']);
        $this->exceptionHandlerRegistered = true;
        return $this;
    }

    public function except(Throwable $exception): void
    {
        if ($exception instanceof HttpException) {
            $this->application->getResponseEmitter()->emit($exception->getResponse());
            return;
        }
        $message = 'Uncaught {exception}: "{message}" in {file}:{line}';
        $this->logException($message, $exception);
        $this->callPreviousExceptionHandler($exception);
        $this->application->getResponseEmitter()->emit(
            $this->application->generateErrorResponse('Internal Server Error', 500)
        );
    }

    public function errorToException(
        int $errorLevel,
        string $errorMessage,
        string $errorFile,
        int $errorLine
    ): bool {
        $message = (self::ERROR_NAMES[$errorLevel] ?? (string)$errorLevel) . ": " . $errorMessage;
        switch ($errorLevel) {
            case E_DEPRECATED:
            case E_USER_DEPRECATED:
                $message = '"{message}" in {file}:{line}';
                $context = [
                    'message' => $errorMessage,
                    'file' => $errorFile,
                    'line' => $errorLine,
                ];
                $this->logger->warning($message, $context);
                return true;
            default:
                throw new ErrorException($message, 0, $errorLevel, $errorFile, $errorLine);
        }
    }

    public function unregisterErrorHandler(): self
    {
        if ($this->errorHandlerRegistered) {
            set_error_handler($this->previousErrorHandler);
            $this->previousErrorHandler = null;
            $this->errorHandlerRegistered = false;
        }
        return $this;
    }

    public function unregisterExceptionHandler(): self
    {
        if ($this->exceptionHandlerRegistered) {
            set_exception_handler($this->previousExceptionHandler);
            $this->previousExceptionHandler = null;
            $this->exceptionHandlerRegistered = false;
        }
        return $this;
    }

    private function callPreviousExceptionHandler(Throwable $exception): void
    {
        if ($this->previousExceptionHandler) {
            try {
                ($this->previousExceptionHandler)($exception);
            } catch (Throwable $e) {
                if ($e !== $exception) {
                    $message = 'Uncaught {exception} while handling previous exception: "{message}" in {file}:{line}';
                    $this->logException($message, $e);
                } else {
                    // The previous exception handler re-threw the exception for some reason. Sentry
                    // always does this if it was the first exception handler at the point when it
                    // was registered.
                }
            }
        }
    }

    private function logException(string $message, Throwable $exception): void
    {
        $context = [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ];
        $this->logger->error($message, $context);
    }
}
