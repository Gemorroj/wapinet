<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\Network\IpAddress;
use PHPUnit\Framework\TestCase;

final class IpAddressTest extends TestCase
{
    public function testValidatesAddresses(): void
    {
        static::assertTrue(IpAddress::isValid('8.8.8.8'));
        static::assertTrue(IpAddress::isValid('2001:4860:4860::8888'));
        static::assertFalse(IpAddress::isValid('not-an-ip'));
        static::assertFalse(IpAddress::isValid(null));
    }

    public function testDetectsPublicAddresses(): void
    {
        static::assertTrue(IpAddress::isPublic('8.8.8.8'));
        static::assertFalse(IpAddress::isPublic('127.0.0.1'));
        static::assertFalse(IpAddress::isPublic('192.168.1.10'));
        static::assertFalse(IpAddress::isPublic('10.0.0.1'));
        static::assertFalse(IpAddress::isPublic('::1'));
    }
}
