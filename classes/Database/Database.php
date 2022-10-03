<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database;

use Dizions\Unclogged\Database\Query\Query;
use Dizions\Unclogged\Database\Query\QueryFailureException;
use Dizions\Unclogged\Database\Schema\Renderers\MysqlRenderer;
use Dizions\Unclogged\Database\Schema\Renderers\SqliteRenderer;
use Dizions\Unclogged\Database\Schema\Renderers\SqlRendererInterface;
use Dizions\Unclogged\Database\Schema\TableDefinitionInterface;
use Dizions\Unclogged\Database\Schema\TableSchema;
use PDO;
use PDOException;

class Database extends PDO
{
    private const RENDERERS = ['mysql' => MysqlRenderer::class, 'sqlite' => SqliteRenderer::class];
    private ConnectionParameters $parameters;
    private SqlRendererInterface $renderer;
    private int $schemaVersion = TableDefinitionInterface::LATEST;
    /** @var array<string, object> */
    private array $cachedInstances = [];

    public function __construct(ConnectionParameters $parameters)
    {
        $this->parameters = $parameters;
        parent::__construct(
            $this->parameters->getDsn(),
            $this->parameters->getUser(),
            $this->parameters->getPassword(),
            $this->parameters->getOptions()
        );
        if ($this->parameters->getDriver() == 'sqlite') {
            $this->sqliteCreateFunction('CONCAT', fn(...$args) => implode('', $args), -1, PDO::SQLITE_DETERMINISTIC);
            $this->sqliteCreateFunction('MD5', fn($x) => md5($x), 1, PDO::SQLITE_DETERMINISTIC);
        }
    }

    /**
     * Create the table with the provided schema.
     *
     * @param TableSchema $schema
     * @return static $this
     */
    public function createTable(TableSchema $schema): self
    {
        foreach ($this->getRenderer()->renderCreateTable($schema) as $sql) {
            $this->exec($sql);
        }
        return $this;
    }

    /**
     * @param Query $query
     * @return bool
     * @throws PDOException
     */
    public function execute(Query $query): bool
    {
        return $query->execute($this);
    }

    /**
     * @param Query $query
     * @return void
     * @throws PDOException
     * @throws QueryFailureException
     */
    public function executeOrThrow(Query $query): void
    {
        $query->executeOrThrow($this);
    }

    public function getConnectionParameters(): ConnectionParameters
    {
        return $this->parameters;
    }

    public function getRenderer(): SqlRendererInterface
    {
        if (!isset($this->renderer)) {
            $rendererClass = self::RENDERERS[$this->getConnectionParameters()->getDriver()];
            $this->renderer = new $rendererClass();
        }
        return $this->renderer;
    }

    public function getSchemaVersion(): int
    {
        return $this->schemaVersion;
    }

    /**
     * Get a cached instance of the given class, using a callable to create it if not found.
     *
     * @psalm-template InstanceClass of object
     * @psalm-param class-string<InstanceClass> $class
     * @psalm-return InstanceClass
     */
    public function getCachedInstance(string $class, callable $generateDefault): object
    {
        return $this->cachedInstances[$class] ??= $generateDefault();
    }

    /** @return static $this */
    public function setSchemaVersion(int $version): self
    {
        $this->schemaVersion = $version;
        return $this;
    }

    public function quoteIdentifier(string $identifier): string
    {
        return $this->getRenderer()->quoteIdentifier($identifier);
    }
}
