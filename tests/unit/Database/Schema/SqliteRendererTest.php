<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database\Schema;

use Dizions\Unclogged\TestCase;

/**
 * @covers Dizions\Unclogged\Database\Schema\SqliteRenderer
 */
final class SqliteRendererTest extends TestCase
{
    public function testNonPrimaryKeysCannotAutoincrement(): void
    {
        $column = (new ColumnSchema('col'))->setType(ColumnType::int())->setAutoIncrement(true);
        $schema = new TableSchema('table', [$column]);
        $renderer = new SqliteRenderer();
        $this->expectException(InvalidTableSchemaException::class);
        $renderer->renderCreateTable($schema);
    }

    public function testCharacterSetIsNotRendered(): void
    {
        $column = (new ColumnSchema('col'))->setType(ColumnType::char()->setCharacterSet('ascii'));
        $schema = new TableSchema('table', [$column]);
        $renderer = new SqliteRenderer();
        $this->assertSame(
            'CREATE TABLE IF NOT EXISTS table(col CHAR NOT NULL)',
            $this->reformatSql($renderer->renderCreateTable($schema)[0])
        );
    }

    public function testCommentIsNotRendered(): void
    {
        $column = (new ColumnSchema('col'))->setType(ColumnType::int())->setComment("The Comment");
        $schema = new TableSchema('table', [$column]);
        $renderer = new SqliteRenderer();
        $this->assertSame(
            'CREATE TABLE IF NOT EXISTS table(col INT NOT NULL)',
            $this->reformatSql($renderer->renderCreateTable($schema)[0])
        );
    }

    public function testIdentifierIsQuotedCorrectly(): void
    {
        $this->assertSame('`x`', (new SqliteRenderer())->quoteIdentifier('x'));
        $this->assertSame('`x```', (new SqliteRenderer())->quoteIdentifier('x`'));
    }

    public function testReferencesIsRenderedAsSeparateForeignKeyConstraint(): void
    {
        $column = (new ColumnSchema('col'))->setType(ColumnType::int())->setReferences('other', 'other_id');
        $schema = new TableSchema('table', [$column]);
        $renderer = new SqliteRenderer();
        $this->assertSame(
            'CREATE TABLE IF NOT EXISTS table(col INT NOT NULL, FOREIGN KEY (col) REFERENCES other(other))',
            $this->reformatSql($renderer->renderCreateTable($schema)[0])
        );
    }

    public function testSingleColumnPrimaryKeyIsRenderedCorrectly(): void
    {
        $column = (new ColumnSchema('col'))->setType(ColumnType::int());
        $schema = (new TableSchema('table', [$column]))->setPrimary(['col']);
        $renderer = new SqliteRenderer();
        $this->assertSame(
            'CREATE TABLE IF NOT EXISTS table(col INT NOT NULL PRIMARY KEY)',
            $this->reformatSql($renderer->renderCreateTable($schema)[0])
        );

        $column = (new ColumnSchema('col'))->setType(ColumnType::int())->setAutoIncrement();
        $schema = (new TableSchema('table', [$column]))->setPrimary(['col']);
        $renderer = new SqliteRenderer();
        $this->assertSame(
            'CREATE TABLE IF NOT EXISTS table(col INTEGER PRIMARY KEY)',
            $this->reformatSql($renderer->renderCreateTable($schema)[0])
        );

        $column = (new ColumnSchema('col'))->setType(ColumnType::int()->setUnsigned())->setAutoIncrement();
        $schema = (new TableSchema('table', [$column]))->setPrimary(['col']);
        $renderer = new SqliteRenderer();
        $this->assertSame(
            'CREATE TABLE IF NOT EXISTS table(col INTEGER PRIMARY KEY)',
            $this->reformatSql($renderer->renderCreateTable($schema)[0])
        );

        $column = (new ColumnSchema('col'))->setType(ColumnType::CHAR());
        $schema = (new TableSchema('table', [$column]))->setPrimary(['col']);
        $renderer = new SqliteRenderer();
        $this->assertSame(
            'CREATE TABLE IF NOT EXISTS table(col CHAR NOT NULL PRIMARY KEY)',
            $this->reformatSql($renderer->renderCreateTable($schema)[0])
        );
    }

    public function testCompositePrimaryKeyIsRenderdCorrectly(): void
    {
        $columns = [new ColumnSchema('col1'), new ColumnSchema('col2')];
        $schema = (new TableSchema('table', $columns))->setPrimary(['col1', 'col2']);
        $renderer = new SqliteRenderer();
        $this->assertSame(
            'CREATE TABLE IF NOT EXISTS table(col1 NOT NULL, col2 NOT NULL, PRIMARY KEY (col1, col2))',
            $this->reformatSql($renderer->renderCreateTable($schema)[0])
        );
    }

    public function testSingleColumnIndexIsRenderedCorrectly(): void
    {
        $schema = (new TableSchema('table', [new ColumnSchema('col')]))->addIndex(['col'], 'my_idx');
        $renderer = new SqliteRenderer();
        $schemaSql = $renderer->renderCreateTable($schema);
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
        $schema = (new TableSchema('table', $columns))->addIndex(['col1'], 'col1_idx')->addIndex(['col2'], 'col2_idx');
        $renderer = new SqliteRenderer();
        $schemaSql = $renderer->renderCreateTable($schema);
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
        $schema = (new TableSchema('table', $columns))->addIndex(['col1', 'col2'], 'my_idx');
        $renderer = new SqliteRenderer();
        $schemaSql = $renderer->renderCreateTable($schema);
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
        $schema = (new TableSchema('table', [new ColumnSchema('col')]))->addIndex(['col']);
        $renderer = new SqliteRenderer();
        $schemaSql = $renderer->renderCreateTable($schema);
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
        $schema = (new TableSchema('table', [new ColumnSchema('col')]))->addUnique(['col']);
        $renderer = new SqliteRenderer();
        $this->assertSame(
            'CREATE TABLE IF NOT EXISTS table(col NOT NULL, UNIQUE (col))',
            $this->reformatSql($renderer->renderCreateTable($schema)[0])
        );
        $columns = [new ColumnSchema('col1'), new ColumnSchema('col2')];
        $schema = (new TableSchema('table', $columns))->addUnique(['col1'])->addUnique(['col2']);
        $renderer = new SqliteRenderer();
        $this->assertSame(
            'CREATE TABLE IF NOT EXISTS table(col1 NOT NULL, col2 NOT NULL, UNIQUE (col1), UNIQUE (col2))',
            $this->reformatSql($renderer->renderCreateTable($schema)[0])
        );
    }

    public function testCompositeUniqueConstraintIsRenderedCorrectly(): void
    {
        $columns = [new ColumnSchema('col1'), new ColumnSchema('col2')];
        $schema = (new TableSchema('table', $columns))->addUnique(['col1', 'col2']);
        $renderer = new SqliteRenderer();
        $this->assertSame(
            'CREATE TABLE IF NOT EXISTS table(col1 NOT NULL, col2 NOT NULL, UNIQUE (col1, col2))',
            $this->reformatSql($renderer->renderCreateTable($schema)[0])
        );
    }

    public function testUniqueConstraintNameIsIgnored(): void
    {
        $schema = (new TableSchema('table', [new ColumnSchema('col')]))->addUnique(['col'], 'my_unq');
        $renderer = new SqliteRenderer();
        $this->assertSame(
            'CREATE TABLE IF NOT EXISTS table(col NOT NULL, UNIQUE (col))',
            $this->reformatSql($renderer->renderCreateTable($schema)[0])
        );
    }
}
