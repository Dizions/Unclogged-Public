<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Request;

use DomainException;
use Dizions\Unclogged\TestCase;

/** @covers Dizions\Unclogged\Request\IpAddressRange */
final class IpAddressRangeTest extends TestCase
{
    public function testInvalidIp4AddressIsRejected(): void
    {
        $this->expectException(DomainException::class);
        new IpAddressRange('10.0.0.256');
    }

    public function testInvalidIp6AddressIsRejected(): void
    {
        $this->expectException(DomainException::class);
        new IpAddressRange('::10000');
    }

    public function testInvalidIp4NetmaskIsRejected(): void
    {
        $this->expectException(DomainException::class);
        new IpAddressRange('10.0.0.1/64');
    }

    public function testInvalidIp6NetmaskIsRejected(): void
    {
        $this->expectException(DomainException::class);
        new IpAddressRange('::1/129');
    }

    /**
     * @dataProvider ip4StartAddressProvider
     * @dataProvider ip6StartAddressProvider
     */
    public function testStartAddressIsDeterminedCorrectly(string $range, string $expected): void
    {
        $this->assertEquals($expected, (new IpAddressRange($range))->getStartAddr());
    }

    /**
     * @dataProvider ip4EndAddressProvider
     * @dataProvider ip6EndAddressProvider
     */
    public function testEndAddressIsDeterminedCorrectly(string $range, string $expected): void
    {
        $this->assertEquals($expected, (new IpAddressRange($range))->getEndAddr());
    }

    public function testContainsMatchesSingleIp4Address(): void
    {
        $range = new IpAddressRange('10.0.0.1');
        $this->assertTrue($range->contains('10.0.0.1'));
        $this->assertFalse($range->contains('10.0.0.2'));
        $this->assertFalse($range->contains('9.0.0.1'));
    }

    public function testContainsMatchesSingleIp6Address(): void
    {
        $range = new IpAddressRange('::7');
        $this->assertTrue($range->contains('::7'));
        $this->assertFalse($range->contains('::6'));
        $this->assertFalse($range->contains(':1:7'));
    }

    public function testContainsMatchesIp4AddressRange(): void
    {
        $range = new IpAddressRange('10.0.0.0/16');
        $this->assertTrue($range->contains('10.0.0.1'));
        $this->assertTrue($range->contains('10.0.0.2'));
        $this->assertTrue($range->contains('10.0.1.1'));
        $this->assertFalse($range->contains('10.1.0.1'));
        $this->assertFalse($range->contains('9.0.0.1'));
        $this->assertFalse($range->contains('::10.0.1.1'));
    }

    public function testContainsMatchesIp6AddressRange(): void
    {
        $range = new IpAddressRange('1:2:3:4::/64');
        $this->assertTrue($range->contains('1:2:3:4::1'));
        $this->assertTrue($range->contains('1:2:3:4::2'));
        $this->assertTrue($range->contains('1:2:3:4::1:2'));
        $this->assertFalse($range->contains('1:2:3:5::1'));
        $this->assertFalse($range->contains(':2:3:5::1'));
        $range = new IpAddressRange('::10.0.0.0/104');
        $this->assertTrue($range->contains('::10.0.0.1'));
        $this->assertFalse($range->contains('10.0.0.1'));
    }

    public static function ip4StartAddressProvider(): array
    {
        return [
            ['0.0.0.0/0', '0.0.0.0'],
            ['0.0.0.0/32', '0.0.0.0'],
            ['10.1.2.3', '10.1.2.3'],
            ['10.1.2.3/32', '10.1.2.3'],
            ['10.1.2.3/24', '10.1.2.0'],
            ['10.1.255.3/23', '10.1.254.0'],
            ['10.1.255.3/22', '10.1.252.0'],
            ['10.1.2.3/16', '10.1.0.0'],
            ['10.1.2.3/8', '10.0.0.0'],
            ['10.1.2.3/5', '8.0.0.0'],
            ['10.1.2.3/0', '0.0.0.0'],
        ];
    }

    public static function ip6StartAddressProvider(): array
    {
        return [
            ['::/0', '::'],
            ['::/128', '::'],
            ['1:2:3:4:5:6:7:8', '1:2:3:4:5:6:7:8'],
            ['::10.0.0.1', '::10.0.0.1'],
            ['::10.0.0.1/24', '::'],
            ['1:2:3:4:5:6:7:8/128', '1:2:3:4:5:6:7:8'],
            ['1:2:3:4:5:6:7:8/64', '1:2:3:4::'],
            ['1:2:3:4:5:6:7:8/63', '1:2:3:4::'],
            ['1:2:3:4:5:6:7:8/62', '1:2:3:4::'],
            ['ffff:2:3:4:5:6:7:8/16', 'ffff::'],
            ['ffff:2:3:4:5:6:7:8/8', 'ff00::'],
            ['ffff:2:3:4:5:6:7:8/5', 'f800::'],
            ['ffff:2:3:4:5:6:7:8/0', '::'],
        ];
    }

    public static function ip4EndAddressProvider(): array
    {
        return [
            ['0.0.0.0/0', '255.255.255.255'],
            ['0.0.0.0/32', '0.0.0.0'],
            ['10.1.2.3', '10.1.2.3'],
            ['10.1.2.3/32', '10.1.2.3'],
            ['10.1.2.3/24', '10.1.2.255'],
            ['10.1.255.3/23', '10.1.255.255'],
            ['10.1.255.3/22', '10.1.255.255'],
            ['10.1.2.3/16', '10.1.255.255'],
            ['10.1.2.3/8', '10.255.255.255'],
            ['10.1.2.3/5', '15.255.255.255'],
            ['10.1.2.3/0', '255.255.255.255'],
        ];
    }

    public static function ip6EndAddressProvider(): array
    {
        return [
            ['::/0', 'ffff:ffff:ffff:ffff:ffff:ffff:ffff:ffff'],
            ['::/128', '::'],
            ['1:2:3:4:5:6:7:8', '1:2:3:4:5:6:7:8'],
            ['::10.0.0.1', '::10.0.0.1'],
            ['::10.0.0.1/24', '0:ff:ffff:ffff:ffff:ffff:ffff:ffff'],
            ['1:2:3:4:5:6:7:8/128', '1:2:3:4:5:6:7:8'],
            ['1:2:3:4:5:6:7:8/64', '1:2:3:4:ffff:ffff:ffff:ffff'],
            ['1:2:3:4:5:6:7:8/63', '1:2:3:5:ffff:ffff:ffff:ffff'],
            ['1:2:3:4:5:6:7:8/62', '1:2:3:7:ffff:ffff:ffff:ffff'],
            ['ffff:2:3:4:5:6:7:8/16', 'ffff:ffff:ffff:ffff:ffff:ffff:ffff:ffff'],
            ['ffff:2:3:4:5:6:7:8/8', 'ffff:ffff:ffff:ffff:ffff:ffff:ffff:ffff'],
            ['ffff:2:3:4:5:6:7:8/5', 'ffff:ffff:ffff:ffff:ffff:ffff:ffff:ffff'],
            ['ffff:2:3:4:5:6:7:8/0', 'ffff:ffff:ffff:ffff:ffff:ffff:ffff:ffff'],
        ];
    }
}
