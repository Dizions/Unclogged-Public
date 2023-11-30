<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Input;

/**
 * @covers Dizions\Unclogged\Input\IpAddressParameter
 */
final class IpAddressParameterTest extends TestCase
{
    /** @dataProvider invalidValuesProvider */
    public function testInvalidValuesAreRejected($in): void
    {
        $parameter = new IpAddressParameter('a', $this->getPostRequest(['a' => $in]));
        $this->expectException(InvalidParameterException::class);
        $parameter->get();
    }

    /** @dataProvider validValuesProvider */
    public function testValidValuesAreAccepted(string $in): void
    {
        $parameter = new IpAddressParameter('a', $this->getPostRequest(['a' => $in]));
        $this->assertSame($in, $parameter->get());
    }

    public static function invalidValuesProvider(): array
    {
        return [
            [''],
            [1],
            [null],
            [10.1],
            ['10.1'],
            [':1'],
        ];
    }

    public static function validValuesProvider(): array
    {
        return [
            ['10.0.0.1'],
            ['127.0.0.1'],
            ['::1'],
            ['fd12:3456:789a::1'],
            ['fd12:3456:789a::ffff'],
        ];
    }
}
