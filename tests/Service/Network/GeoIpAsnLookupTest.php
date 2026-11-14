<?php

declare(strict_types=1);

namespace App\Tests\Service\Network;

use App\Service\Geoip2;
use App\Service\Network\GeoIpAsnLookup;
use PHPUnit\Framework\TestCase;

final class GeoIpAsnLookupTest extends TestCase
{
    public function testSkipsPrivateAddressesAndMissingDatabase(): void
    {
        $lookup = new GeoIpAsnLookup(new Geoip2('missing-city.mmdb', 'missing-asn.mmdb'));

        static::assertNull($lookup->lookup('127.0.0.1'));
        static::assertNull($lookup->lookup('192.168.1.10'));
        static::assertNull($lookup->lookup('8.8.8.8'));
    }
}
