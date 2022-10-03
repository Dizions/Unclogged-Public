<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database\Schema;

abstract class TableDefinition implements TableDefinitionInterface
{
    private ?int $removedInVersion;
    /** @var TableSchema[] */
    private array $schemas;
    /** @var array<int, array<int, bool>> */
    private array $versionCompatibility = [];

    /** @return array<int, TableSchema> */
    abstract protected function getSchemasByVersion(): array;

    public function areVersionsCompatible(int $targetVersion, int $currentVersion = self::LATEST): bool
    {
        if (isset($this->versionCompatibility[$targetVersion][$currentVersion])) {
            return $this->versionCompatibility[$targetVersion][$currentVersion];
        }
        if ($this->areVersionsIdentical($targetVersion, $currentVersion)) {
            return $this->versionCompatibility[$targetVersion][$currentVersion] = true;
        }
        if ($this->doesTableExistInExactlyOneVersion($targetVersion, $currentVersion)) {
            return $this->versionCompatibility[$targetVersion][$currentVersion] = false;
        }
        // Ideally we would be able to declare that two version are in fact compatible, but for now
        // we just assume they never are.
        return $this->versionCompatibility[$targetVersion][$currentVersion] = false;
    }

    public function getSchema(int $version = self::LATEST): ?TableSchema
    {
        $removedIn = $this->removedInVersion() ?? INF;
        if ($version >= $removedIn) {
            return null;
        }
        foreach (array_keys($this->getSchemasSortedByVersionDescending()) as $schemaVersion) {
            if ($schemaVersion <= $version) {
                return $this->schemas[$schemaVersion];
            }
        }
        return null;
    }

    protected function doesTableExistInExactlyOneVersion(int $targetVersion, int $currentVersion): bool
    {
        $removedIn = $this->removedInVersion();
        if ($removedIn === null) {
            return false;
        }
        $versions = [$targetVersion, $removedIn, $currentVersion];
        sort($versions);
        return $versions[1] == $removedIn;
    }

    protected function removedInVersion(): ?int
    {
        if (!isset($this->removedInVersion)) {
            $schemas = $this->getSchemasSortedByVersionDescending();
            if (empty($schemas)) {
                throw new InvalidTableDefinitionException("A TableDefinition must specify at least one schema version");
            }
            $latest = array_key_first($schemas);
            $this->removedInVersion = $schemas[$latest] === null ? $latest : null;
        }
        return $this->removedInVersion;
    }

    private function areVersionsIdentical(int $targetVersion, int $currentVersion): bool
    {
        if ($targetVersion == $currentVersion) {
            return true;
        }
        return $this->getSchema($targetVersion) === $this->getSchema($currentVersion);
    }

    private function getSchemasSortedByVersionDescending(): array
    {
        if (!isset($this->schemas)) {
            $schemas = $this->getSchemasByVersion();
            krsort($schemas);
            $this->schemas = $schemas;
        }
        return $this->schemas;
    }
}
