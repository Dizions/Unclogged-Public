<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Input;

use Dizions\Unclogged\Errors\HttpBadRequestException;

class MissingParameterException extends HttpBadRequestException
{
}
