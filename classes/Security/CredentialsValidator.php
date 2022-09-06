<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Security;

use Dizions\Unclogged\Request\Request;
use Dizions\Unclogged\Setup\InvalidConfigurationException;

class CredentialsValidator
{
    /** @var iterable<CredentialsInterface> */
    private iterable $credentialValidators;

    /**
     * Designate a set of objects to try, in order, to get the caller's credentials (eg API key)
     * from the request.
     *
     * @param iterable<CredentialsInterface> $credentialsObjects
     */
    public function __construct(iterable $credentialsObjects)
    {
        $this->credentialValidators = $credentialsObjects;
    }

    /**
     * @param Request $request
     * @return CredentialsInterface
     * @throws InvalidConfigurationException
     * @throws InvalidCredentialsException
     * @throws MissingCredentialsException
     */
    public function authenticate(Request $request): CredentialsInterface
    {
        if (count($this->credentialValidators) == 0) {
            throw new InvalidConfigurationException('No credentials providers configured');
        }
        foreach ($this->credentialValidators as $validator) {
            try {
                return $validator->authenticate($request);
            } catch (MissingCredentialsException $e) {
            }
        }
        throw new MissingCredentialsException('No credentials found');
    }
}
