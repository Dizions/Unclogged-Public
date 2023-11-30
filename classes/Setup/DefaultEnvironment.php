<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Setup;

class DefaultEnvironment extends Environment
{
    /** @var array $defaults Fallback values for any variables not found in the environment */
    protected array $defaults = [
        'APPLICATION_NAME' => null,
        // Prepended to the names of database tables used for key-based auth.
        // Derived from application name if unset.
        'AUTHENTICATION_KEY_TABLE_NAME_PREFIX' => null,
        // See searchPaths()
        'ENVIRONMENT_SEARCH_PATHS' => null,
        'MYSQL_CHARSET' => 'utf8mb4',
        'MYSQL_TIMEOUT' => '3',
        /**
         * Used for determining the real source IP address of the request.
         * @see RequestFactory::fromGlobals()
         */
        'TRUSTED_PROXIES' => ['127.0.0.1', '::1', '10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16'],
    ];

    public function __construct(?array $env = null)
    {
        parent::__construct($env);
        $this->setDefaults($this->defaults);
    }

    public function getEnvironment(string $documentRoot): Environment
    {
        return $this->load(
            $this->get('ENVIRONMENT_SEARCH_PATHS') ?? $this->searchPaths($documentRoot)
        );
    }

    /**
     * The default list of search paths for environment files is set here because it needs to be
     * dynamically generated.
     *
     * @return string[]
     */
    private function searchPaths(string $documentRoot): array
    {
        return [
            $documentRoot,
            "$documentRoot/..",
            "$documentRoot/../environments/",
            '/run/secrets/',
        ];
    }
}
