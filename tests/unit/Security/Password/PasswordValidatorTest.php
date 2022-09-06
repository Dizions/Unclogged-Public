<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Security\Password;

use Dizions\Unclogged\TestCase;

/**
 * @covers Dizions\Unclogged\Security\Password\PasswordValidator
 */
final class PasswordValidatorTest extends TestCase
{
    /** @dataProvider randomStringParametersProvider */
    public function testReturnedStringContainsOnlyListedCharacters(int $numChars, string $alphabet): void
    {
        $validator = $this->getMockForAbstractClass(PasswordValidator::class);
        $string = $validator->generateRandomString($numChars, $alphabet);
        $this->assertSame($numChars, mb_strlen($string));
        $this->assertEmpty(array_diff(mb_str_split($string), mb_str_split($alphabet)));
    }

    public function randomStringParametersProvider(): array
    {
        return [
            'normal alphabet' => [32, 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789'],
            'symbols including 4-byte unicode' => [64, '/😀!"£'],
        ];
    }
}
