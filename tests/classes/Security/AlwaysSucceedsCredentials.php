<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Security;

use Dizions\Unclogged\Request\Request;

class AlwaysSucceedsCredentials implements CredentialsInterface
{
    public function authenticate(Request $request): CredentialsInterface
    {
        return $this;
    }

    public function getAcl(): AccessControlList
    {
        return new AccessControlList();
    }
}
