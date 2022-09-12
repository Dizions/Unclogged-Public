<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Request;

use Dizions\Unclogged\Errors\HttpBadRequestException;

class UnknownContentTypeException extends HttpBadRequestException
{
}
