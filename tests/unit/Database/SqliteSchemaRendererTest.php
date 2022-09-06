<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database;

use Dizions\Unclogged\TestCase;

/**
 * @covers Dizions\Unclogged\Database\SqliteSchemaRenderer
 */
final class SqliteSchemaRendererTest extends TestCase
{
    public function testNonPrimaryKeysCannotAutoincrement(): void
    {
        $column = (new ColumnSchema('col'))->setType(ColumnType::int())->setAutoIncrement(true);
        $renderer = new SqliteSchemaRenderer((new TableSchema('table', [$column])));
        $this->expectException(InvalidTableSchemaException::class);
        $renderer->renderTableSchema();
    }

    public function testCharacterSetIsNotRendered(): void
    {
        $column = (new ColumnSchema('col'))->setType(ColumnType::char()->setCharacterSet('ascii'));
        $renderer = new SqliteSchemaRenderer((new TableSchema('table', [$column])));
        $this->assertSame(
            'table(col CHAR NOT NULL)',
            $this->reformatSql($renderer->renderTableSchema())
        );
    }

    public function testCommentIsNotRendered(): void
    {
        $column = (new ColumnSchema('col'))->setType(ColumnType::int())->setComment("The Comment");
        $renderer = new SqliteSchemaRenderer((new TableSchema('table', [$column])));
        $this->assertSame(
            'table(col INT NOT NULL)',
            $this->reformatSql($renderer->renderTableSchema())
        );
    }

    public function testReferencesIsRenderedAsSeparateForeignKeyConstraint(): void
    {
        $column = (new ColumnSchema('col'))->setType(ColumnType::int())->setReferences('other', 'other_id');
        $renderer = new SqliteSchemaRenderer((new TableSchema('table', [$column])));
        $this->assertSame(
            'table(col INT NOT NULL, FOREIGN KEY (col) REFERENCES other(other))',
            $this->reformatSql($renderer->renderTableSchema())
        );
    }

    public function testSingleColumnPrimaryKeyIsRenderedCorrectly(): void
    {
        $column = (new ColumnSchema('col'))->setType(ColumnType::int());
        $renderer = new SqliteSchemaRenderer((new TableSchema('table', [$column]))->setPrimary(['col']));
        $this->assertSame(
            'table(col INT NOT NULL PRIMARY KEY)',
            $this->reformatSql($renderer->renderTableSchema())
        );

        $column = (new ColumnSchema('col'))->setType(ColumnType::int())->setAutoIncrement();
        $renderer = new SqliteSchemaRenderer((new TableSchema('table', [$column]))->setPrimary(['col']));
        $this->assertSame(
            'table(col INTEGER PRIMARY KEY)',
            $this->reformatSql($renderer->renderTableSchema())
        );

        $column = (new ColumnSchema('col'))->setType(ColumnType::int()->setUnsigned())->setAutoIncrement();
        $renderer = new SqliteSchemaRenderer((new TableSchema('table', [$column]))->setPrimary(['col']));
        $this->assertSame(
            'table(col INTEGER PRIMARY KEY)',
            $this->reformatSql($renderer->renderTableSchema())
        );

        $column = (new ColumnSchema('col'))->setType(ColumnType::CHAR());
        $renderer = new SqliteSchemaRenderer((new TableSchema('table', [$column]))->setPrimary(['col']));
        $this->assertSame(
            'table(col CHAR NOT NULL PRIMARY KEY)',
            $this->reformatSql($renderer->renderTableSchema())
        );
    }

    public function testCompositePrimaryKeyIsRenderdCorrectly(): void
    {
        $columns = [new ColumnSchema('col1'), new ColumnSchema('col2')];
        $renderer = new SqliteSchemaRenderer((new TableSchema('table', $columns))->setPrimary(['col1', 'col2']));
        $this->assertSame(
            'table(col1 NOT NULL, col2 NOT NULL, PRIMARY KEY (col1, col2))',
            $this->reformatSql($renderer->renderTableSchema())
        );
    }

    public function testSingleColumnIndexIsRenderedCorrectly(): void
    {
        $renderer = new SqliteSchemaRenderer(
            (new TableSchema('table', [new ColumnSchema('col')]))->addIndex(['col'], 'my_idx')
        );
        $schemaSql = $renderer->renderCreateTable();
        $this->assertCount(2, $schemaSql);
        $this->assertSame(
            [
                'CREATE TABLE IF NOT EXISTS table(col NOT NULL)',
                'CREATE INDEX IF NOT EXISTS my_idx ON table(col)',
            ],
            [
                $this->reformatSql($schemaSql[0]),
                $this->reformatSql($schemaSql[1]),
            ]
        );
        $columns = [new ColumnSchema('col1'), new ColumnSchema('col2')];
        $renderer = new SqliteSchemaRenderer(
            (new TableSchema('table', $columns))->addIndex(['col1'], 'col1_idx')->addIndex(['col2'], 'col2_idx')
        );
        $schemaSql = $renderer->renderCreateTable();
        $this->assertCount(3, $schemaSql);
        $this->assertSame(
            [
                'CREATE TABLE IF NOT EXISTS table(col1 NOT NULL, col2 NOT NULL)',
                'CREATE INDEX IF NOT EXISTS col1_idx ON table(col1)',
                'CREATE INDEX IF NOT EXISTS col2_idx ON table(col2)',
            ],
            [
                $this->reformatSql($schemaSql[0]),
                $this->reformatSql($schemaSql[1]),
                $this->reformatSql($schemaSql[2]),
            ]
        );
    }

    public function testCompositeIndexIsRenderedCorrectly(): void
    {
        $columns = [new ColumnSchema('col1'), new ColumnSchema('col2')];
        $renderer = new SqliteSchemaRenderer(
            (new TableSchema('table', $columns))->addIndex(['col1', 'col2'], 'my_idx')
        );
        $schemaSql = $renderer->renderCreateTable();
        $this->assertCount(2, $schemaSql);
        $this->assertSame(
            [
                'CREATE TABLE IF NOT EXISTS table(col1 NOT NULL, col2 NOT NULL)',
                'CREATE INDEX IF NOT EXISTS my_idx ON table(col1, col2)',
            ],
            [
                $this->reformatSql($schemaSql[0]),
                $this->reformatSql($schemaSql[1]),
            ]
        );
    }

    public function testIndexHasDefaultNameIfUnspecified(): void
    {
        $renderer = new SqliteSchemaRenderer(
            (new TableSchema('table', [new ColumnSchema('col')]))->addIndex(['col'])
        );
        $schemaSql = $renderer->renderCreateTable();
        $this->assertCount(2, $schemaSql);
        $this->assertSame(
            [
                'CREATE TABLE IF NOT EXISTS table(col NOT NULL)',
                'CREATE INDEX IF NOT EXISTS col_idx ON table(col)',
            ],
            [
                $this->reformatSql($schemaSql[0]),
                $this->reformatSql($schemaSql[1]),
            ]
        );
    }

    public function testSingleColumnUniqueConstraintIsRenderedCorrectly(): void
    {
        $renderer = new SqliteSchemaRenderer(
            (new TableSchema('table', [new ColumnSchema('col')]))->addUnique(['col'])
        );
        $this->assertSame(
            'table(col NOT NULL, UNIQUE (col))',
            $this->reformatSql($renderer->renderTableSchema())
        );
        $columns = [new ColumnSchema('col1'), new ColumnSchema('col2')];
        $renderer = new SqliteSchemaRenderer(
            (new TableSchema('table', $columns))->addUnique(['col1'])->addUnique(['col2'])
        );
        $this->assertSame(
            'table(col1 NOT NULL, col2 NOT NULL, UNIQUE (col1), UNIQUE (col2))',
            $this->reformatSql($renderer->renderTableSchema())
        );
    }

    public function testCompositeUniqueConstraintIsRenderedCorrectly(): void
    {
        $columns = [new ColumnSchema('col1'), new ColumnSchema('col2')];
        $renderer = new SqliteSchemaRenderer(
            (new TableSchema('table', $columns))->addUnique(['col1', 'col2'])
        );
        $this->assertSame(
            'table(col1 NOT NULL, col2 NOT NULL, UNIQUE (col1, col2))',
            $this->reformatSql($renderer->renderTableSchema())
        );
    }

    public function testUniqueConstraintNameIsIgnored(): void
    {
        $renderer = new SqliteSchemaRenderer(
            (new TableSchema('table', [new ColumnSchema('col')]))->addUnique(['col'], 'my_unq')
        );
        $this->assertSame(
            'table(col NOT NULL, UNIQUE (col))',
            $this->reformatSql($renderer->renderTableSchema())
        );
    }
}
