<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database;

use Dizions\Unclogged\Database\Schema\MysqlSchemaRenderer;
use Dizions\Unclogged\Database\Schema\SqliteSchemaRenderer;
use Dizions\Unclogged\Database\Schema\TableSchema;
use PDO;

class Database extends PDO
{
    private const RENDERERS = ['mysql' => MysqlSchemaRenderer::class, 'sqlite' => SqliteSchemaRenderer::class];
    private ConnectionParameters $parameters;
    /** @var class-string<SchemaRendererInterface>  */
    private string $rendererClass;

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
        $rendererClass = $this->getRendererClass();
        foreach ((new $rendererClass($schema))->renderCreateTable() as $sql) {
            $this->exec($sql);
        }
        return $this;
    }

    public function quoteIdentifier(string $identifier): string
    {
        return $this->getRendererClass()::quoteIdentifier($identifier);
    }

    /** @return class-string<SchemaRendererInterface>  */
    private function getRendererClass(): string
    {
        return $this->rendererClass ??= self::RENDERERS[$this->getConnectionParameters()->getDriver()];
    }
}
