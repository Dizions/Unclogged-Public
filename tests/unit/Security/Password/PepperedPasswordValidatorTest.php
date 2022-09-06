<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Security\Password;

use Dizions\Unclogged\Setup\InvalidConfigurationException;
use Dizions\Unclogged\TestCase;

/**
 * @covers Dizions\Unclogged\Security\Password\PepperedPasswordValidator
 * @group passwords
 */
final class PepperedPasswordValidatorTest extends TestCase
{
    public function testAtLeastOneHashClassMustBeSpecified(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        new PepperedPasswordValidator([]);
    }

    public function testFirstHashClassIsUsedForGeneration(): void
    {
        $first = new class ('', []) extends DummyHash {
            protected static $getHash = 'first';
        };
        $second = new class ('', []) extends DummyHash {
            protected static $getHash = 'second';
        };
        $validator = new PepperedPasswordValidator([get_class($first), get_class($second)]);
        $this->assertSame('first', $validator->generatePasswordHash(''));
    }

    public function testFirstHashClassIsUsedForUpToDateCheck(): void
    {
        $valid = new class ('', []) extends DummyHash {
            protected static $isValidFormat = true;
        };
        $invalid = new class ('', []) extends DummyHash {
            protected static $isValidFormat = false;
        };
        $validator = new PepperedPasswordValidator([get_class($valid), get_class($invalid)]);
        $this->assertTrue($validator->isHashUpToDate(''));
        $validator = new PepperedPasswordValidator([get_class($invalid), get_class($valid)]);
        $this->assertFalse($validator->isHashUpToDate(''));
    }

    public function testFirstValidHashClassIsUsedForVerification(): void
    {
        $validButFails = new class ('', []) extends DummyHash {
            protected static $isValidFormat = true;
            protected static $match = false;
        };
        $validAndSucceeds = new class ('', []) extends DummyHash {
            protected static $isValidFormat = true;
            protected static $match = true;
        };
        $validator = new PepperedPasswordValidator([get_class($validButFails), get_class($validAndSucceeds)]);
        $this->assertFalse($validator->isPasswordCorrect('', ''));
    }

    public function testInvalidFormatPasswordsAreAlwaysIncorrect(): void
    {
        $invalisButSucceeds1 = new class ('', []) extends DummyHash {
            protected static $isValidFormat = false;
            protected static $match = true;
        };
        $invalidButSucceeds2 = new class ('', []) extends DummyHash {
            protected static $isValidFormat = false;
            protected static $match = true;
        };
        $validator = new PepperedPasswordValidator([get_class($invalisButSucceeds1), get_class($invalidButSucceeds2)]);
        $this->assertFalse($validator->isPasswordCorrect('', ''));
    }

    public function testPasswordIsPassedUnmodifiedToHashObject(): void
    {
        $hash = new class ('', []) extends DummyHash {
            protected static $isValidFormat = true;
            public static string $plaintext;
            public function __construct(string $hash, array $peppers = [])
            {
            }
            public function match(string $plaintext): bool
            {
                self::$plaintext = $plaintext;
                return false;
            }
        };
        $validator = new PepperedPasswordValidator([get_class($hash)]);
        $validator->isPasswordCorrect("\nuntrimmed password\n", '');
        $this->assertSame("\nuntrimmed password\n", $hash::$plaintext);
    }

    public function testPeppersAreSortedById(): void
    {
        $hash = new class ('', []) extends DummyHash {
            private array $peppers;
            public function __construct(string $hash, array $peppers = [])
            {
                $this->peppers = $peppers;
            }
            public function getHash(): string
            {
                return (string)array_key_last($this->peppers);
            }
        };
        $validator = new PepperedPasswordValidator([get_class($hash)]);
        $validator->setPeppers([2 => 'x', 1 => 'a']);
        $this->assertSame('2', $validator->generatePasswordHash(''));
    }
}
