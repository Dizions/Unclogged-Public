<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database\Schema;

use TypeError;

/**
 * @method static ColumnSchema bigint(string $name, int $displayWidth = null);
 * @method static ColumnSchema bit(string $name, int $length = null);
 * @method static ColumnSchema blob(string $name);
 * @method static ColumnSchema bool(string $name);
 * @method static ColumnSchema char(string $name, int $length = null, string $characterSet = null);
 * @method static ColumnSchema datetime(string $name);
 * @method static ColumnSchema decimal(string $name, int $totalDigits = null, int $digitsAfterDecimalPoint = null);
 * @method static ColumnSchema int(string $name, int $displayWidth = null);
 * @method static ColumnSchema json(string $name);
 * @method static ColumnSchema mediumint(string $name, int $displayWidth = null);
 * @method static ColumnSchema smallint(string $name, int $displayWidth = null);
 * @method static ColumnSchema text(string $name, string $characterSet = null);
 * @method static ColumnSchema timestamp(string $name);
 * @method static ColumnSchema tinyint(string $name, int $displayWidth = null);
 * @method static ColumnSchema varchar(string $name, int $length = null, string $characterSet = null);
 * @package Dizions\Unclogged\Database
 */
class ColumnSchema
{
    private string $name;
    private bool $autoincrement = false;
    private string $comment = '';
    private ?string $default = null;
    private array $references = [];
    private bool $nullable = false;
    private ColumnType $type;

    /**
     * Exactly the same as using the constructor directly, but more suitable for chaining since it
     * doesn't need to be wrapped in brackets.
     *
     * @param string $name
     * @return ColumnSchema
     */
    public static function new(string $name): self
    {
        return new self($name);
    }

    /**
     * Generic factory method for creating a column schema with a data type given by the method
     * name, in the same manner as ColumnType
     *
     * @see ColumnType
     * @return ColumnSchema
     * @throws TypeError
     */
    public static function __callStatic($methodName, $arguments): self
    {
        $columnName = array_shift($arguments);
        return (new self($columnName))->setType(ColumnType::$methodName(...$arguments));
    }

    public function __construct(string $name)
    {
        $this->name = $name;
        $this->type = ColumnType::undefined();
    }

    public function getAutoIncrement(): bool
    {
        return $this->autoincrement;
    }

    public function getComment(): string
    {
        return $this->comment;
    }

    public function getDefault(): ?string
    {
        return $this->default;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getNullable(): bool
    {
        return $this->nullable;
    }

    public function getReferences(): array
    {
        return $this->references;
    }

    public function getType(): ColumnType
    {
        return $this->type;
    }

    public function setAutoIncrement(): self
    {
        $this->autoincrement = true;
        return $this;
    }

    public function setComment(string $comment): self
    {
        $this->comment = $comment;
        return $this;
    }

    public function setDefault(string $default): self
    {
        $this->default = $default;
        return $this;
    }

    public function setNullable(): self
    {
        $this->nullable = true;
        return $this;
    }

    public function setReferences(string $table, string $column): self
    {
        $this->references = [$table, $column];
        return $this;
    }

    public function setType(ColumnType $type): self
    {
        $this->type = $type;
        return $this;
    }
}
