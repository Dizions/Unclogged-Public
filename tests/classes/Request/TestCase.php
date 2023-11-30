<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Request;

use Dizions\Unclogged\TestCase as BaseTestCase;
use Laminas\Diactoros\ServerRequestFactory;

class TestCase extends BaseTestCase
{
    protected function getPostRequest(array $data): Request
    {
        $server = ['CONTENT_TYPE' => 'application/x-www-form-urlencoded'];
        $requestFactory = new ServerRequestFactory();
        return new Request($requestFactory->createServerRequest('POST', '/', $server)->withParsedBody($data));
    }
}
