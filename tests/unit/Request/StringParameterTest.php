<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Request;

/**
 * @covers Dizions\Unclogged\Request\StringParameter
 */
final class StringParameterTest extends TestCase
{
    public function testValuesAreCastToString(): void
    {
        $parameter = new StringParameter('a', $this->getPostRequest(['a' => 1]));
        $this->assertSame('1', $parameter->get());
    }

    public function testMaxLengthCanBeEnforced(): void
    {
        $parameter = new StringParameter('a', $this->getPostRequest(['a' => 'the']));
        $this->assertSame('the', $parameter->maxLength(3)->get());

        $parameter = new StringParameter('a', $this->getPostRequest(['a' => 'them']));
        $parameter->maxLength(3);
        $this->expectException(InvalidParameterException::class);
        $parameter->get();
    }
}
