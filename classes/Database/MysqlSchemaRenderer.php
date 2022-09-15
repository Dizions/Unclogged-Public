<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database;

class MysqlSchemaRenderer extends SchemaRenderer
{
    public function renderCreateTable(): array
    {
        $definition = $this->renderTableSchema();
        return ["CREATE TABLE IF NOT EXISTS {$definition} ENGINE=InnoDB"];
    }

    public static function quoteIdentifier(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    protected function renderAutoIncrement(bool $autoincrement): string
    {
        return $autoincrement ? 'AUTO_INCREMENT' : '';
    }

    protected function renderComment(string $comment): string
    {
        return $comment ? "COMMENT `$comment`" : '';
    }

    protected function renderReferences(array $references): string
    {
        if (empty($references)) {
            return '';
        }
        [$table, $column] = $references;
        return "REFERENCES $table($column)";
    }

    protected function renderType(ColumnType $type): string
    {
        $typeString = strtoupper($type->getType());
        $typeString .= $this->renderTypeParameters($type);
        if ($type->getUnsigned()) {
            $typeString .= ' UNSIGNED';
        }
        $characterSet = $type->getCharacterSet();
        if ($characterSet) {
            $typeString .= " CHARACTER SET $characterSet";
        }
        return $typeString;
    }

    private function renderTypeParameters(ColumnType $type): string
    {
        $ret = '';
        $length = $type->getLength();
        if ($length !== null) {
            $ret .= "($length";
            $decimalDigits = $type->getDecimalDigits();
            if ($decimalDigits !== null) {
                $ret .= ", $decimalDigits";
            }
            $ret .= ')';
        }
        return $ret;
    }
}
