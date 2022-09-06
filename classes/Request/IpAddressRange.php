<?php

declare(strict_types=1);

namespace Dizions\Unclogged\Request;

use DomainException;
use TypeError;

class IpAddressRange
{
    protected string $startAddr;
    protected string $endAddr;

    public function __construct(string $range)
    {
        $parts = explode('/', $range);
        $addr = $parts[0];
        try {
            $packedIp = inet_pton($addr);
            if (count($parts) > 1) {
                [$this->startAddr, $this->endAddr] = $this->getStartAndEndOfMaskedRange($packedIp, (int)$parts[1]);
            } else {
                $this->startAddr = $packedIp;
                $this->endAddr = $packedIp;
            }
        } catch (TypeError $e) {
            throw new DomainException("'$range' is not a valid IP address or CIDR range");
        }
    }

    public function contains(string $ip): bool
    {
        $packedIp = inet_pton($ip);
        return ($packedIp >= $this->startAddr && $packedIp <= $this->endAddr);
    }

    public function getStartAddr(): string
    {
        return inet_ntop($this->startAddr);
    }

    public function getEndAddr(): string
    {
        return inet_ntop($this->endAddr);
    }

    private function getStartAndEndOfMaskedRange(string $packedIp, int $mask): array
    {
        $ipLenBits = 8 * strlen($packedIp);
        if ($mask > $ipLenBits) {
            $ipVersion = $ipLenBits == 32 ? '4' : '6';
            throw new DomainException("An IPv{$ipVersion} netmask cannot exceed {$ipLenBits} bits");
        }
        $bitmask = $this->createBitmask($mask, $ipLenBits);
        return [$packedIp & $bitmask, $packedIp | ~$bitmask];
    }

    private function createBitmask(int $mask, int $ipLenBits): string
    {
        $hexMask = str_repeat('f', $mask >> 2);
        switch ($mask & 3) {
            case 1:
                $hexMask .= '8';
                break;
            case 2:
                $hexMask .= 'c';
                break;
            case 3:
                $hexMask .= 'e';
                break;
        }
        $hexMask = str_pad($hexMask, $ipLenBits >> 2, '0');
        return pack('H*', $hexMask);
    }
}
