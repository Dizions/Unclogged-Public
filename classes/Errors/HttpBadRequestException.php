<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Errors;

use Throwable;

class HttpBadRequestException extends HttpException
{
    public function __construct(string $messageForUser, ?Throwable $previous = null)
    {
        parent::__construct($messageForUser, 400, $previous);
    }
}
