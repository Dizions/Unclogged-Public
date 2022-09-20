<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database\Schema;

use Dizions\Unclogged\TestCase;

/**
 * @covers Dizions\Unclogged\Database\Schema\SqlRenderer
 */
final class SqlRendererTest extends TestCase
{
    public function testSchemaCanBeRendered(): void
    {
        $schema = new TableSchema('table', [new ColumnSchema('col')]);
        $renderer = $this->createRenderer();
        $this->assertIsString($renderer->renderCreateTable($schema)[0]);
    }

    public function testColumnCanDefaultToEmptyStringWithoutNestedQuotes(): void
    {
        $schema = new TableSchema('table', [(new ColumnSchema('col'))->setDefault('')]);
        $renderer = $this->createRenderer();
        $this->assertSame(
            "table(col NOT NULL DEFAULT '')",
            $this->reformatSql($renderer->renderCreateTable($schema)[0])
        );
    }

    public function testIdentifierIsQuotedCorrectly(): void
    {
        $renderer = $this->createRenderer();
        $this->assertSame('`x`', $renderer->quoteIdentifier('x'));
        $this->assertSame('`x```', $renderer->quoteIdentifier('x`'));
    }

    public function testNullableColumnCanBeRendered(): void
    {
        $schema = new TableSchema('table', [(new ColumnSchema('col'))->setNullable(true)]);
        $renderer = $this->createRenderer();
        $this->assertSame('table(col NULL)', $this->reformatSql($renderer->renderCreateTable($schema)[0]));
    }

    public function testSingleColumnPrimaryKeyCanBeRendered(): void
    {
        $schema = (new TableSchema('table', [new ColumnSchema('col')]))->setPrimary(['col']);
        $renderer = $this->createRenderer($schema);
        $this->assertSame(
            'table(col NOT NULL, PRIMARY KEY (col))',
            $this->reformatSql($renderer->renderCreateTable($schema)[0])
        );
    }

    public function testCompositePrimaryKeyCanBeRendered(): void
    {
        $columns = [new ColumnSchema('col1'), new ColumnSchema('col2')];
        $schema = (new TableSchema('table', $columns))->setPrimary(['col1', 'col2']);
        $renderer = $this->createRenderer($schema);
        $this->assertSame(
            'table(col1 NOT NULL, col2 NOT NULL, PRIMARY KEY (col1, col2))',
            $this->reformatSql($renderer->renderCreateTable($schema)[0])
        );
    }

    private function createRenderer(): SqlRenderer
    {
        return new class () extends SqlRenderer {
            public function renderCreateTable(TableSchema $schema): array
            {
                return [$this->renderTableSchema($schema)];
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
