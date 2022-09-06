<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Errors;

use Psr\Http\Message\ResponseInterface;
use Throwable;
use Dizions\Unclogged\Application;
use Dizions\Unclogged\Security\KeyCredentialsConfiguration;
use Dizions\Unclogged\Security\Password\NullPasswordValidator;

class HttpUnauthorizedException extends HttpException
{
    public function __construct(Application $app, string $messageForUser, Throwable $previous = null)
    {
        parent::__construct($app, $messageForUser, 401, $previous);
    }

    public function getResponse(): ResponseInterface
    {
        $app = $this->getApplication();
        $config = new KeyCredentialsConfiguration($app->getEnvironment(), $app->getName(), new NullPasswordValidator());
        $authScheme = $config->getAuthenticationScheme();
        return parent::getResponse()->withAddedHeader('WWW-Authenticate', $authScheme);
    }
}
