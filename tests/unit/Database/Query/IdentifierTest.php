<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database\Query;

use Dizions\Unclogged\TestCase;

/**
 * @covers Dizions\Unclogged\Database\Query\Identifier
 */
final class IdentifierTest extends TestCase
{
    public function testCannotBeReplacedWithPlaceholderInPreparedStatement(): void
    {
        $identifier = $this->getMockForAbstractClass(Identifier::class, ['x']);
        $this->assertFalse($identifier->canUsePlaceholderInPreparedStatement());
    }

    /** @dataProvider invalidStringsProvider */
    public function testInvalidStringsAreRejected(string $invalid): void
    {
        $this->expectException(InvalidIdentifierException::class);
        $this->getMockForAbstractClass(Identifier::class, [$invalid]);
    }

    public function invalidStringsProvider(): array
    {
        return [
            [''],
            ['"x"'],
            ["'x'"],
            ['`x`'],
            ['x`x'],
        ];
    }
}
