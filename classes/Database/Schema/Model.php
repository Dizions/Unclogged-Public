<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database\Schema;

use Dizions\Unclogged\Database\Database;

abstract class Model implements ModelInterface
{
    abstract protected function getTableDefinition(): TableDefinitionInterface;

    private Database $database;
    private TableSchema $schema;

    protected function __construct(Database $database)
    {
        $this->database = $database;
    }

    /** @throws IncompatibleSchemaVersionException */
    public function assertTableSchemaIsCompatibleWithVersion(int $version): void
    {
        if (!$this->getTableDefinition()->areVersionsCompatible($version, $this->getDatabase()->getSchemaVersion())) {
            throw new IncompatibleSchemaVersionException();
        }
    }

    public function getDatabase(): Database
    {
        return $this->database;
    }

    public function getSchema(): TableSchema
    {
        if (!isset($this->schema)) {
            $version = $this->getDatabase()->getSchemaVersion();
            $schema = $this->getTableDefinition()->getSchema($version);
            if (!$schema) {
                $class = static::class;
                $version = $version == TableDefinitionInterface::LATEST ? 'the current version' : "version $version";
                throw new IncompatibleSchemaVersionException(
                    "Table definition for $class doesn't include a schema for $version"
                );
            }
            $this->schema = $schema;
        }
        return $this->schema;
    }
}
