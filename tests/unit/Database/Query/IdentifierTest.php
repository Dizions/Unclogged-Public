<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Database\Query;

use Dizions\Unclogged\Database\Database;
use Dizions\Unclogged\TestCase;

/**
 * @covers Dizions\Unclogged\Database\Query\Identifier
 */
final class IdentifierTest extends TestCase
{
    public function testCannotBeReplacedWithPlaceholderInPreparedStatement(): void
    {
        $db = $this->createMock(Database::class);
        $identifier = $this->getMockForAbstractClass(Identifier::class, [$db, 'x']);
        $this->assertFalse($identifier->canUsePlaceholderInPreparedStatement());
    }

    /** @dataProvider invalidStringsProvider */
    public function testInvalidStringsAreRejected(string $invalid): void
    {
        $db = $this->createMock(Database::class);
        $this->expectException(InvalidIdentifierException::class);
        $this->getMockForAbstractClass(Identifier::class, [$db, $invalid]);
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
