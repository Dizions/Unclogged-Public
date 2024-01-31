<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Security;

use Dizions\Unclogged\Http\Request\Request;

class AlwaysInvalidCredentials implements CredentialsInterface
{
    public function authenticate(Request $request): CredentialsInterface
    {
        throw new InvalidCredentialsException();
    }

    public function getAcl(): AccessControlList
    {
        return new AccessControlList();
    }
}
