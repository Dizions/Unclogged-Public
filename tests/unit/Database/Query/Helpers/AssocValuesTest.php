<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database\Query\Helpers;

use Dizions\Unclogged\Database\Query\InvalidQueryValuesException;
use Dizions\Unclogged\Database\Query\RawSqlString;
use Dizions\Unclogged\Database\Query\SqlString;
use Dizions\Unclogged\Database\Schema\SqliteRenderer;
use Dizions\Unclogged\TestCase;
use Dizions\Unclogged\TestStringable;

/** @covers Dizions\Unclogged\Database\Query\Helpers\AssocValues */
final class AssocValuesTest extends TestCase
{
    public function testValuesCanBeCounted(): void
    {
        $values = new AssocValues();
        $this->assertCount(0, $values);
        $values->setValues(['a' => 1]);
        $this->assertCount(1, $values);
    }

    public function testEmptyValuesArrayIsRejected(): void
    {
        $values = new AssocValues();
        $this->expectException(InvalidQueryValuesException::class);
        $values->setValues([]);
    }

    public function testValuesArrayWithNumericKeysIsRejected(): void
    {
        $values = new AssocValues();
        $this->expectException(InvalidQueryValuesException::class);
        $values->setValues([1]);
    }

    public function testValuesArrayWithEmptyKeyIsRejected(): void
    {
        $values = new AssocValues();
        $this->expectException(InvalidQueryValuesException::class);
        $values->setValues(['' => 1]);
    }

    public function testComponentsAreRenderedCorrectly(): void
    {
        $assocValues = new AssocValues();
        $assocValues->setValues([
            'a' => 1,
            'b' => 'b',
            'c' => new TestStringable('c'),
            'd' => new SqlString('d'),
            'e' => new RawSqlString('e'),
        ]);
        [
            'columns' => $columns,
            'values' => $values,
            'parameters' => $parameters
        ] = $assocValues->getComponents(new SqliteRenderer());
        $this->assertSame(['`a`', '`b`', '`c`', '`d`', '`e`'], $columns);
        $this->assertSame(['?', '?', '?', '?', 'e'], $values);
        $this->assertSame(['1', 'b', 'c', 'd'], $parameters);
    }
}
