<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database\Schema;

use Dizions\Unclogged\Database\Database;
use Dizions\Unclogged\Database\Query\TableName;
use Dizions\Unclogged\Database\Query\Where;
use PDO;

abstract class TableModel extends Model implements TableModelInterface
{
    /** @var string[] */
    private array $keyFields;
    private TableName $table;

    /** @return static */
    public static function fromDatabase(Database $database): self
    {
        return $database->getCachedInstance(static::class, fn () => new static($database));
    }

    /** Get an entire row as it is stored in the database */
    public function getRowById(int $id): ?array
    {
        return $this->getRowByPrimaryKey([$id]);
    }

    public function getRowByPrimaryKey(array $fields): ?array
    {
        $database = $this->getDatabase();
        $renderer = $database->getRenderer();
        $where = new Where();
        $keyFields = $this->getKeyFields();
        switch (count($keyFields) <=> count($fields)) {
            case -1:
                throw new InvalidPrimaryKeyException('Too many fields specified for primary key');
            case 1:
                throw new InvalidPrimaryKeyException('Not enough fields specified for primary key');
        }
        $where->setAssocValues(array_combine($keyFields, $fields));
        $table = $this->getTable()->render($renderer);
        $whereSql = $where->getSqlString($renderer);
        $select = $database->query("SELECT * FROM $table $whereSql");
        $select->execute($where->getParameters($renderer));
        return $select->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** @return string[] */
    protected function getKeyFields(): array
    {
        return $this->keyFields ??= $this->getSchema()->getPrimary();
    }

    protected function getTable(): TableName
    {
        return $this->table ??= new TableName($this->getSchema()->getName());
    }
}
