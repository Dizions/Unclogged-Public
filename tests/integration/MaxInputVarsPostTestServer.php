<?php

declare(strict_types=1);

use Dizions\Unclogged\Http\Request\RequestFactory;

require_once __DIR__ . '/../../vendor/autoload.php';

try {
    RequestFactory::default()->fromGlobals()->getAllParams();
    echo "Success";
} catch (Throwable $e) {
    echo $e->getMessage();
}
