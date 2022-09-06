<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Security\Password;

use BadMethodCallException;
use Dizions\Unclogged\TestCase;

/**
 * @covers Dizions\Unclogged\Security\Password\NullPasswordValidator
 */
final class NullPasswordValidatorTest extends TestCase
{
    public function testNewHashCannotBeCreated(): void
    {
        $this->expectException(BadMethodCallException::class);
        (new NullPasswordValidator())->generatePasswordHash('password1');
    }

    public function testHashIsNotUpToDate(): void
    {
        $this->assertFalse((new NullPasswordValidator())->isHashUpToDate(''));
    }

    public function testPasswordIsNotCorrect(): void
    {
        $this->assertFalse((new NullPasswordValidator())->isPasswordCorrect('password1', 'password1'));
    }
}
