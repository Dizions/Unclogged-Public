<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database\Schema;

use TypeError;

/**
 * Define the type of a column in a database-agnostic way.
 *
 * @method static ColumnType bigint(int $displayWidth = null);
 * @method static ColumnType bit(int $length = null);
 * @method static ColumnType blob();
 * @method static ColumnType bool();
 * @method static ColumnType datetime();
 * @method static ColumnType decimal(int $totalDigits = null, int $digitsAfterDecimalPoint = null);
 * @method static ColumnType int(int $displayWidth = null);
 * @method static ColumnType json();
 * @method static ColumnType mediumint(int $displayWidth = null);
 * @method static ColumnType smallint(int $displayWidth = null);
 * @method static ColumnType timestamp();
 * @method static ColumnType tinyint(int $displayWidth = null);
 * @package Dizions\Unclogged\Database
 */
class ColumnType
{
    private string $type;
    private ?int $length = null;
    private ?int $decimalDigits = null;
    private bool $unsigned = false;
    private string $characterSet = '';

    private function __construct($type)
    {
        $this->type = $type;
    }

    public static function __callStatic($name, $arguments): self
    {
        $name = strtolower($name);
        if ($arguments) {
            $length = $arguments[0];
            self::validateInt(__CLASS__ . "::$name()", 2, $length);
            if (count($arguments) > 1) {
                $digitsAfterDecimalPoint = $arguments[1];
                self::validateInt(__CLASS__ . "::$name()", 3, $digitsAfterDecimalPoint);
                return (new self($name))->setLength($length)->setDecimalDigits($digitsAfterDecimalPoint);
            }
            return (new self($name))->setLength($length);
        }
        return new self($name);
    }

    public static function char(?int $length = null, ?string $characterSet = null): self
    {
        $columnType = new self(__FUNCTION__);
        if ($length !== null) {
            $columnType->setLength($length);
        }
        if ($characterSet !== null) {
            $columnType->setCharacterSet($characterSet);
        }
        return $columnType;
    }

    public static function varchar(?int $length = null, ?string $characterSet = null): self
    {
        $columnType = new self(__FUNCTION__);
        if ($length !== null) {
            $columnType->setLength($length);
        }
        if ($characterSet !== null) {
            $columnType->setCharacterSet($characterSet);
        }
        return $columnType;
    }

    public static function text(?string $characterSet = ''): self
    {
        $columnType = new self(__FUNCTION__);
        if ($characterSet !== null) {
            $columnType->setCharacterSet($characterSet);
        }
        return $columnType;
    }

    public static function undefined(): self
    {
        return new self('');
    }

    public function getCharacterSet(): string
    {
        return $this->characterSet;
    }

    public function getDecimalDigits(): ?int
    {
        return $this->decimalDigits;
    }

    public function getLength(): ?int
    {
        return $this->length;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getUnsigned(): bool
    {
        return $this->unsigned;
    }

    public function setCharacterSet(string $charset): self
    {
        $this->characterSet = $charset;
        return $this;
    }

    public function setDecimalDigits(int $digits): self
    {
        $this->decimalDigits = $digits;
        return $this;
    }

    public function setUnsigned(): self
    {
        $this->unsigned = true;
        return $this;
    }


    private function setLength(int $length): self
    {
        $this->length = $length;
        return $this;
    }

    private static function validateInt(string $method, int $argument, $value): void
    {
        if (!is_int($value)) {
            $got = gettype($value);
            if ($got == 'object') {
                $got = get_class($value);
            }
            $error = "Argument $argument passed to $method must be of the type int, $got given";
            throw new TypeError($error);
        }
    }
}
