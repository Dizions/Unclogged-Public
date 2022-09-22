<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Response;

use Dizions\Unclogged\TestCase;
use ReflectionClass;

/** @covers Dizions\Unclogged\Response\HttpStatus */
class HttpStatusTest extends TestCase
{
    public function testUnknownResponseCodeIsReturnedAsString(): void
    {
        $this->assertSame('275', HttpStatus::getMessage(275));
    }

    public function testKnownResponseCodesHaveAssociatedMessage(): void
    {
        $constants = (new ReflectionClass(HttpStatus::class))->getConstants();
        foreach ($constants as $constantValue) {
            $message = HttpStatus::getMessage($constantValue);
            $this->assertNotEmpty($message);
            $this->assertIsNotNumeric($message);
        }
    }
}
