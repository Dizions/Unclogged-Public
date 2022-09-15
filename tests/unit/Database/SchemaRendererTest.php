<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database;

use Dizions\Unclogged\TestCase;

/**
 * @covers Dizions\Unclogged\Database\SchemaRenderer
 */
final class SchemaRendererTest extends TestCase
{
    public function testSchemaCanBeRetrieved(): void
    {
        $this->assertInstanceOf(
            TableSchema::class,
            $this->createRenderer(new TableSchema('table', []))->getSchema()
        );
    }

    public function testSchemaCanBeRendered(): void
    {
        $renderer = $this->createRenderer(new TableSchema('table', [new ColumnSchema('col')]));
        $this->assertIsString($renderer->renderTableSchema());
    }

    public function testColumnCanDefaultToEmptyStringWithoutNestedQuotes(): void
    {
        $renderer = $this->createRenderer(new TableSchema('table', [(new ColumnSchema('col'))->setDefault('')]));
        $this->assertSame("table(col NOT NULL DEFAULT '')", $this->reformatSql($renderer->renderTableSchema()));
    }

    public function testNullableColumnCanBeRendered(): void
    {
        $renderer = $this->createRenderer(new TableSchema('table', [(new ColumnSchema('col'))->setNullable(true)]));
        $this->assertSame('table(col NULL)', $this->reformatSql($renderer->renderTableSchema()));
    }

    public function testSingleColumnPrimaryKeyCanBeRendered(): void
    {
        $renderer = $this->createRenderer(
            (new TableSchema('table', [new ColumnSchema('col')]))->setPrimary(['col'])
        );
        $this->assertSame('table(col NOT NULL, PRIMARY KEY (col))', $this->reformatSql($renderer->renderTableSchema()));
    }

    public function testCompositePrimaryKeyCanBeRendered(): void
    {
        $columns = [new ColumnSchema('col1'), new ColumnSchema('col2')];
        $renderer = $this->createRenderer(
            (new TableSchema('table', $columns))->setPrimary(['col1', 'col2'])
        );
        $this->assertSame(
            'table(col1 NOT NULL, col2 NOT NULL, PRIMARY KEY (col1, col2))',
            $this->reformatSql($renderer->renderTableSchema())
        );
    }

    private function createRenderer(TableSchema $schema): SchemaRenderer
    {
        return new class ($schema) extends SchemaRenderer {
            public function renderCreateTable(): array
            {
                return [];
            }
            public static function quoteIdentifier(string $identifier): string
            {
                return '';
            }
            protected function renderAutoIncrement(bool $autoincrement): string
            {
                return '';
            }
            protected function renderComment(string $comment): string
            {
                return '';
            }
            protected function renderReferences(array $references): string
            {
                return '';
            }
            protected function renderType(ColumnType $type): string
            {
                return $type->getType();
            }
        };
    }
}
