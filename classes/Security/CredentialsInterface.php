<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Security;

use Dizions\Unclogged\Request\Request;

interface CredentialsInterface
{
    /**
     * @return static
     * @throws MissingCredentialsException
     * @throws InvalidCredentialsException
     */
    public function authenticate(Request $request): self;
    public function getAcl(): AccessControlList;
}
