<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Errors;

use Exception;
use Psr\Http\Message\ResponseInterface;
use Dizions\Unclogged\Application;

/**
 * HttpExceptions are sent directly to the user, and should typically indicate a problem with the
 * request that the user can fix.
 *
 * @package Dizions\Unclogged\Errors
 */
class HttpException extends Exception
{
    public function getResponse(Application $app): ResponseInterface
    {
        return $app->generateErrorResponse($this->getMessage(), $this->getCode());
    }
}
