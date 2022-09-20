<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database\Schema;

use Dizions\Unclogged\TestCase;

/**
 * @covers Dizions\Unclogged\Database\Schema\MysqlRenderer
 */
final class MysqlRendererTest extends TestCase
{
    public function testAutoIncrementCanBeRendered(): void
    {
        $column = (new ColumnSchema('col'))->setType(ColumnType::int())->setAutoIncrement(true);
        $schema = new TableSchema('table', [$column]);
        $renderer = new MysqlRenderer();
        $this->assertSame(
            'table(col INT NOT NULL AUTO_INCREMENT)',
            $this->extractTableDefinition($renderer->renderCreateTable($schema)[0])
        );
    }

    public function testCharacterSetCanBeRendered(): void
    {
        $column = (new ColumnSchema('col'))->setType(ColumnType::char()->setCharacterSet('ascii'));
        $schema = new TableSchema('table', [$column]);
        $renderer = new MysqlRenderer();
        $this->assertSame(
            'table(col CHAR CHARACTER SET ascii NOT NULL)',
            $this->extractTableDefinition($renderer->renderCreateTable($schema)[0])
        );
    }

    public function testCommentCanBeRendered(): void
    {
        $column = (new ColumnSchema('col'))->setType(ColumnType::int())->setComment("The Comment");
        $schema = new TableSchema('table', [$column]);
        $renderer = new MysqlRenderer();
        $this->assertSame(
            'table(col INT NOT NULL COMMENT `The Comment`)',
            $this->extractTableDefinition($renderer->renderCreateTable($schema)[0])
        );
    }

    public function testDecimalDigitsCanBeRendered(): void
    {
        $column = (new ColumnSchema('col'))->setType(ColumnType::decimal(10, 2));
        $schema = new TableSchema('table', [$column]);
        $renderer = new MysqlRenderer();
        $this->assertSame(
            'table(col DECIMAL(10, 2) NOT NULL)',
            $this->extractTableDefinition($renderer->renderCreateTable($schema)[0])
        );
    }

    public function testIdentifierIsQuotedCorrectly(): void
    {
        $this->assertSame('`x`', (new MysqlRenderer())->quoteIdentifier('x'));
        $this->assertSame('`x```', (new MysqlRenderer())->quoteIdentifier('x`'));
    }

    public function testLengthCanBeRendered(): void
    {
        $column = (new ColumnSchema('col'))->setType(ColumnType::char(10));
        $schema = new TableSchema('table', [$column]);
        $renderer = new MysqlRenderer();
        $this->assertSame(
            'table(col CHAR(10) NOT NULL)',
            $this->extractTableDefinition($renderer->renderCreateTable($schema)[0])
        );
    }

    public function testReferencesIsRenderedInline(): void
    {
        $column = (new ColumnSchema('col'))->setType(ColumnType::int())->setReferences('other', 'other_id');
        $schema = new TableSchema('table', [$column]);
        $renderer = new MysqlRenderer();
        $this->assertSame(
            'table(col INT NOT NULL REFERENCES other(other_id))',
            $this->extractTableDefinition($renderer->renderCreateTable($schema)[0])
        );
    }

    public function testUnsignedCanBeRendered(): void
    {
        $column = (new ColumnSchema('col'))->setType(ColumnType::int()->setUnsigned());
        $schema = new TableSchema('table', [$column]);
        $renderer = new MysqlRenderer();
        $this->assertSame(
            'table(col INT UNSIGNED NOT NULL)',
            $this->extractTableDefinition($renderer->renderCreateTable($schema)[0])
        );
    }

    public function testSingleColumnPrimaryKeyCanBeRendered(): void
    {
        $schema = (new TableSchema('table', [new ColumnSchema('col')]))->setPrimary(['col']);
        $renderer = new MysqlRenderer();
        $this->assertSame(
            'table(col NOT NULL, PRIMARY KEY (col))',
            $this->extractTableDefinition($renderer->renderCreateTable($schema)[0])
        );
    }

    public function testCompositePrimaryKeyCanBeRendered(): void
    {
        $columns = [new ColumnSchema('col1'), new ColumnSchema('col2')];
        $schema = (new TableSchema('table', $columns))->setPrimary(['col1', 'col2']);
        $renderer = new MysqlRenderer();
        $this->assertSame(
            'table(col1 NOT NULL, col2 NOT NULL, PRIMARY KEY (col1, col2))',
            $this->extractTableDefinition($renderer->renderCreateTable($schema)[0])
        );
    }

    public function testSingleColumnIndexIsRenderedCorrectly(): void
    {
        $schema = (new TableSchema('table', [new ColumnSchema('col')]))->addIndex(['col'], 'my_idx');
        $renderer = new MysqlRenderer();
        $this->assertSame(
            'table(col NOT NULL, INDEX my_idx(col))',
            $this->extractTableDefinition($renderer->renderCreateTable($schema)[0])
        );
        $columns = [new ColumnSchema('col1'), new ColumnSchema('col2')];
        $schema = (new TableSchema('table', $columns))->addIndex(['col1'], 'col1_idx')->addIndex(['col2'], 'col2_idx');
        $renderer = new MysqlRenderer();
        $this->assertSame(
            'table(col1 NOT NULL, col2 NOT NULL, INDEX col1_idx(col1), INDEX col2_idx(col2))',
            $this->extractTableDefinition($renderer->renderCreateTable($schema)[0])
        );
    }

    public function testCompositeIndexIsRenderedCorrectly(): void
    {
        $columns = [new ColumnSchema('col1'), new ColumnSchema('col2')];
        $schema = (new TableSchema('table', $columns))->addIndex(['col1', 'col2']);
        $renderer = new MysqlRenderer();
        $this->assertSame(
            'table(col1 NOT NULL, col2 NOT NULL, INDEX col1_col2_idx(col1, col2))',
            $this->extractTableDefinition($renderer->renderCreateTable($schema)[0])
        );
    }

    public function testSingleColumnUniqueConstraintIsRenderedCorrectly(): void
    {
        $schema = (new TableSchema('table', [new ColumnSchema('col')]))->addUnique(['col']);
        $renderer = new MysqlRenderer();
        $this->assertSame(
            'table(col NOT NULL, UNIQUE col_unq(col))',
            $this->extractTableDefinition($renderer->renderCreateTable($schema)[0])
        );
        $columns = [new ColumnSchema('col1'), new ColumnSchema('col2')];
        $schema = (new TableSchema('table', $columns))->addUnique(['col1'])->addUnique(['col2']);
        $renderer = new MysqlRenderer();
        $this->assertSame(
            'table(col1 NOT NULL, col2 NOT NULL, UNIQUE col1_unq(col1), UNIQUE col2_unq(col2))',
            $this->extractTableDefinition($renderer->renderCreateTable($schema)[0])
        );
    }

    public function testCompositeUniqueConstraintIsRenderedCorrectly(): void
    {
        $columns = [new ColumnSchema('col1'), new ColumnSchema('col2')];
        $schema = (new TableSchema('table', $columns))->addUnique(['col1', 'col2'], 'my_unq');
        $renderer = new MysqlRenderer();
        $this->assertSame(
            'table(col1 NOT NULL, col2 NOT NULL, UNIQUE my_unq(col1, col2))',
            $this->extractTableDefinition($renderer->renderCreateTable($schema)[0])
        );
    }

    public function testTableIsCreatedUsingInnoDb(): void
    {
        $columns = [
            (new ColumnSchema('a'))->setType(ColumnType::int()),
            (new ColumnSchema('b'))->setType(ColumnType::int()),
        ];
        $schema = new TableSchema('test', $columns);
        $renderer = new MysqlRenderer();
        $this->assertSame(
            'CREATE TABLE IF NOT EXISTS test(a INT NOT NULL, b INT NOT NULL) ENGINE=InnoDB',
            $this->reformatSql($renderer->renderCreateTable($schema)[0])
        );
    }

    private function extractTableDefinition(string $createTable): string
    {
        return preg_replace('/CREATE TABLE IF NOT EXISTS (.*) ENGINE=.*/', '$1', $this->reformatSql($createTable));
    }
}
