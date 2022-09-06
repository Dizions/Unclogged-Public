<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Errors;

use Exception;
use Psr\Http\Message\ResponseInterface;
use Throwable;
use Dizions\Unclogged\Application;

/**
 * HttpExceptions are sent directly to the user, and should typically indicate a problem with the
 * request that the user can fix.
 *
 * @package Dizions\Unclogged\Errors
 */
class HttpException extends Exception
{
    private Application $application;

    public function __construct(Application $app, string $messageForUser, int $statusCode, Throwable $previous = null)
    {
        $this->application = $app;
        parent::__construct($messageForUser, $statusCode, $previous);
    }

    public function getResponse(): ResponseInterface
    {
        return $this->getApplication()->createErrorResponse($this->getMessage(), $this->getCode());
    }

    protected function getApplication(): Application
    {
        return $this->application;
    }
}
