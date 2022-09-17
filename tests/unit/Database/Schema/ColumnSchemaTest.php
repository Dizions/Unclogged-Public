<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database\Schema;

use Dizions\Unclogged\TestCase;

/**
 * @covers Dizions\Unclogged\Database\Schema\ColumnSchema
 */
final class ColumnSchemaTest extends TestCase
{
    public function testNameCanBeRetrieved(): void
    {
        $this->assertSame('name', ColumnSchema::new('name')->getName());
    }

    public function testAutoIncrementCanBeSet(): void
    {
        $this->assertTrue(ColumnSchema::new('name')->setAutoIncrement()->getAutoIncrement());
    }

    public function testAutoIncrementDefaultsToFalse(): void
    {
        $this->assertFalse(ColumnSchema::new('name')->getAutoIncrement());
    }

    public function testCommentCanBeSet(): void
    {
        $this->assertSame('comment', ColumnSchema::new('name')->setComment('comment')->getComment());
    }

    public function testCommentDefaultsToEmpty(): void
    {
        $this->assertEmpty(ColumnSchema::new('name')->getComment());
    }

    public function testDefaultCanBeSet(): void
    {
        $this->assertSame('default', ColumnSchema::new('name')->setDefault('default')->getDefault());
    }

    public function testDefaultDefaultsToEmpty(): void
    {
        $this->assertEmpty(ColumnSchema::new('name')->getDefault());
    }

    public function testNullableCanBeSet(): void
    {
        $this->assertTrue(ColumnSchema::new('name')->setNullable()->getNullable());
    }

    public function testNullableDefaultsToFalse(): void
    {
        $this->assertFalse(ColumnSchema::new('name')->getNullable());
    }

    public function testReferencesCanBeSet(): void
    {
        $this->assertSame(
            ['table', 'table_id'],
            ColumnSchema::new('name')->setReferences('table', 'table_id')->getReferences()
        );
    }

    public function testReferencesDefaultsToEmpty(): void
    {
        $this->assertEmpty(ColumnSchema::new('name')->getReferences());
    }

    public function testTypeCanBeSet(): void
    {
        $this->assertSame(
            'int',
            ColumnSchema::new('name')->setType(ColumnType::int())->getType()->getType()
        );
    }

    public function testTypeCanBeSetUsingFacade(): void
    {
        $this->assertSame(
            'int',
            ColumnSchema::int('name')->getType()->getType()
        );
    }

    public function testTypeDefaultsToEmpty(): void
    {
        $this->assertEmpty(ColumnSchema::new('name')->getType()->getType());
    }
}
