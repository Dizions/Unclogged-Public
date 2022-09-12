<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Errors;

use Throwable;

class HttpForbiddenException extends HttpException
{
    public function __construct(string $messageForUser, Throwable $previous = null)
    {
        parent::__construct($messageForUser, 403, $previous);
    }
}
