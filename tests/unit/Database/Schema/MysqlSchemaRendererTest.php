<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database\Schema;

use Dizions\Unclogged\TestCase;

/**
 * @covers Dizions\Unclogged\Database\Schema\MysqlSchemaRenderer
 */
final class MysqlSchemaRendererTest extends TestCase
{
    public function testAutoIncrementCanBeRendered(): void
    {
        $column = (new ColumnSchema('col'))->setType(ColumnType::int())->setAutoIncrement(true);
        $renderer = new MysqlSchemaRenderer((new TableSchema('table', [$column])));
        $this->assertSame(
            'table(col INT NOT NULL AUTO_INCREMENT)',
            $this->reformatSql($renderer->renderTableSchema())
        );
    }

    public function testCharacterSetCanBeRendered(): void
    {
        $column = (new ColumnSchema('col'))->setType(ColumnType::char()->setCharacterSet('ascii'));
        $renderer = new MysqlSchemaRenderer((new TableSchema('table', [$column])));
        $this->assertSame(
            'table(col CHAR CHARACTER SET ascii NOT NULL)',
            $this->reformatSql($renderer->renderTableSchema())
        );
    }

    public function testCommentCanBeRendered(): void
    {
        $column = (new ColumnSchema('col'))->setType(ColumnType::int())->setComment("The Comment");
        $renderer = new MysqlSchemaRenderer((new TableSchema('table', [$column])));
        $this->assertSame(
            'table(col INT NOT NULL COMMENT `The Comment`)',
            $this->reformatSql($renderer->renderTableSchema())
        );
    }

    public function testDecimalDigitsCanBeRendered(): void
    {
        $column = (new ColumnSchema('col'))->setType(ColumnType::decimal(10, 2));
        $renderer = new MysqlSchemaRenderer((new TableSchema('table', [$column])));
        $this->assertSame(
            'table(col DECIMAL(10, 2) NOT NULL)',
            $this->reformatSql($renderer->renderTableSchema())
        );
    }

    public function testIdentifierIsQuotedCorrectly(): void
    {
        $this->assertSame('`x`', MysqlSchemaRenderer::quoteIdentifier('x'));
        $this->assertSame('`x```', MysqlSchemaRenderer::quoteIdentifier('x`'));
    }

    public function testLengthCanBeRendered(): void
    {
        $column = (new ColumnSchema('col'))->setType(ColumnType::char(10));
        $renderer = new MysqlSchemaRenderer((new TableSchema('table', [$column])));
        $this->assertSame(
            'table(col CHAR(10) NOT NULL)',
            $this->reformatSql($renderer->renderTableSchema())
        );
    }

    public function testReferencesIsRenderedInline(): void
    {
        $column = (new ColumnSchema('col'))->setType(ColumnType::int())->setReferences('other', 'other_id');
        $renderer = new MysqlSchemaRenderer((new TableSchema('table', [$column])));
        $this->assertSame(
            'table(col INT NOT NULL REFERENCES other(other_id))',
            $this->reformatSql($renderer->renderTableSchema())
        );
    }

    public function testUnsignedCanBeRendered(): void
    {
        $column = (new ColumnSchema('col'))->setType(ColumnType::int()->setUnsigned());
        $renderer = new MysqlSchemaRenderer((new TableSchema('table', [$column])));
        $this->assertSame(
            'table(col INT UNSIGNED NOT NULL)',
            $this->reformatSql($renderer->renderTableSchema())
        );
    }

    public function testSingleColumnPrimaryKeyCanBeRendered(): void
    {
        $renderer = new MysqlSchemaRenderer(
            (new TableSchema('table', [new ColumnSchema('col')]))->setPrimary(['col'])
        );
        $this->assertSame('table(col NOT NULL, PRIMARY KEY (col))', $this->reformatSql($renderer->renderTableSchema()));
    }

    public function testCompositePrimaryKeyCanBeRendered(): void
    {
        $columns = [new ColumnSchema('col1'), new ColumnSchema('col2')];
        $renderer = new MysqlSchemaRenderer((new TableSchema('table', $columns))->setPrimary(['col1', 'col2']));
        $this->assertSame(
            'table(col1 NOT NULL, col2 NOT NULL, PRIMARY KEY (col1, col2))',
            $this->reformatSql($renderer->renderTableSchema())
        );
    }

    public function testSingleColumnIndexIsRenderedCorrectly(): void
    {
        $renderer = new MysqlSchemaRenderer(
            (new TableSchema('table', [new ColumnSchema('col')]))->addIndex(['col'], 'my_idx')
        );
        $this->assertSame('table(col NOT NULL, INDEX my_idx(col))', $this->reformatSql($renderer->renderTableSchema()));
        $columns = [new ColumnSchema('col1'), new ColumnSchema('col2')];
        $renderer = new MysqlSchemaRenderer(
            (new TableSchema('table', $columns))->addIndex(['col1'], 'col1_idx')->addIndex(['col2'], 'col2_idx')
        );
        $this->assertSame(
            'table(col1 NOT NULL, col2 NOT NULL, INDEX col1_idx(col1), INDEX col2_idx(col2))',
            $this->reformatSql($renderer->renderTableSchema())
        );
    }

    public function testCompositeIndexIsRenderedCorrectly(): void
    {
        $columns = [new ColumnSchema('col1'), new ColumnSchema('col2')];
        $renderer = new MysqlSchemaRenderer(
            (new TableSchema('table', $columns))->addIndex(['col1', 'col2'])
        );
        $this->assertSame(
            'table(col1 NOT NULL, col2 NOT NULL, INDEX col1_col2_idx(col1, col2))',
            $this->reformatSql($renderer->renderTableSchema())
        );
    }

    public function testSingleColumnUniqueConstraintIsRenderedCorrectly(): void
    {
        $renderer = new MysqlSchemaRenderer(
            (new TableSchema('table', [new ColumnSchema('col')]))->addUnique(['col'])
        );
        $this->assertSame(
            'table(col NOT NULL, UNIQUE col_unq(col))',
            $this->reformatSql($renderer->renderTableSchema())
        );
        $columns = [new ColumnSchema('col1'), new ColumnSchema('col2')];
        $renderer = new MysqlSchemaRenderer(
            (new TableSchema('table', $columns))->addUnique(['col1'])->addUnique(['col2'])
        );
        $this->assertSame(
            'table(col1 NOT NULL, col2 NOT NULL, UNIQUE col1_unq(col1), UNIQUE col2_unq(col2))',
            $this->reformatSql($renderer->renderTableSchema())
        );
    }

    public function testCompositeUniqueConstraintIsRenderedCorrectly(): void
    {
        $columns = [new ColumnSchema('col1'), new ColumnSchema('col2')];
        $renderer = new MysqlSchemaRenderer(
            (new TableSchema('table', $columns))->addUnique(['col1', 'col2'], 'my_unq')
        );
        $this->assertSame(
            'table(col1 NOT NULL, col2 NOT NULL, UNIQUE my_unq(col1, col2))',
            $this->reformatSql($renderer->renderTableSchema())
        );
    }

    public function testTableIsCreatedUsingInnoDb(): void
    {
        $columns = [
            (new ColumnSchema('a'))->setType(ColumnType::int()),
            (new ColumnSchema('b'))->setType(ColumnType::int()),
        ];
        $renderer = new MysqlSchemaRenderer((new TableSchema('test', $columns)));
        $this->assertSame(
            'CREATE TABLE IF NOT EXISTS test(a INT NOT NULL, b INT NOT NULL) ENGINE=InnoDB',
            $this->reformatSql($renderer->renderCreateTable()[0])
        );
    }
}
