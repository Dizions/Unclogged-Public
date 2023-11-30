<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Setup;

class DefaultEnvironment extends Environment
{
    /** @var $defaults Fallback values for any variables not found in the environment */
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
    private string $documentRoot;

    public function __construct(string $documentRoot)
    {
        $this->documentRoot = $documentRoot;
        parent::__construct([]);
        foreach ($this->defaults as $k => $v) {
            if ($this->get($k) === null) {
                // This variable wasn't found in the real environment
                $this->set($k, $v);
            }
        }
    }

    public function getEnvironment(): Environment
    {
        return $this->merge(
            (new Environment())->load($this->get('ENVIRONMENT_SEARCH_PATHS') ?? $this->searchPaths())
        );
    }

    /**
     * The default list of search paths for environment files is set here because it needs to be
     * dynamically generated.
     *
     * @return string[]
     */
    private function searchPaths(): array
    {
        return ["$this->documentRoot/../environments/", '/run/secrets/'];
    }
}
