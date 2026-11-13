<?php

declare(strict_types=1);

namespace App\Tests\Service\Api\Network;

use App\Service\Network\IpAddress;
use PHPUnit\Framework\TestCase;

final class IpAddressTest extends TestCase
{
    public function testDetectsPublicAndIpv4Addresses(): void
    {
        static::assertTrue(IpAddress::isPublic('8.8.8.8'));
        static::assertFalse(IpAddress::isPublic('127.0.0.1'));
        static::assertFalse(IpAddress::isPublic('192.168.0.1'));
        static::assertFalse(IpAddress::isPublic(null));

        static::assertTrue(IpAddress::isIpv4('8.8.8.8'));
        static::assertFalse(IpAddress::isIpv4('2a00:1450:4010:c0e::64'));
    }

    public function testBuildsIpv4ReverseNames(): void
    {
        static::assertSame('61.149.175.46', IpAddress::reverseLabel('46.175.149.61'));
        static::assertSame('61.149.175.46.in-addr.arpa', IpAddress::ptrName('46.175.149.61'));
    }

    public function testBuildsIpv6ReverseNames(): void
    {
        $label = \implode('.', \array_reverse(\str_split(\bin2hex((string) \inet_pton('2a00:1450:4010:c0e::64')))));

        static::assertSame($label, IpAddress::reverseLabel('2a00:1450:4010:c0e::64'));
        static::assertSame($label.'.ip6.arpa', IpAddress::ptrName('2a00:1450:4010:c0e::64'));
    }
}
