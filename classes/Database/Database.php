<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database;

use PDO;

class Database extends PDO
{
    private const RENDERERS = ['mysql' => MysqlSchemaRenderer::class, 'sqlite' => SqliteSchemaRenderer::class];
    private ConnectionParameters $parameters;

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

    public function getConnectionParameters(): ConnectionParameters
    {
        return $this->parameters;
    }

    /**
     * Create the table with the provided schema.
     *
     * @param TableSchema $schema
     * @return static $this
     */
    public function createTable(TableSchema $schema): self
    {
        $driver = $this->getConnectionParameters()->getDriver();
        $rendererClass = self::RENDERERS[$driver];
        foreach ((new $rendererClass($schema))->renderCreateTable() as $sql) {
            $this->exec($sql);
        }
        return $this;
    }
}
