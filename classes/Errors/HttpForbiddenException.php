<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Errors;

use Throwable;
use Dizions\Unclogged\Application;

class HttpForbiddenException extends HttpException
{
    public function __construct(Application $app, string $messageForUser, Throwable $previous = null)
    {
        parent::__construct($app, $messageForUser, 403, $previous);
    }
}
