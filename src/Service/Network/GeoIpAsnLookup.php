<?php

declare(strict_types=1);

namespace App\Service\Network;

use App\Service\Geoip2;

final readonly class GeoIpAsnLookup implements IpAsnLookupInterface
{
    public function __construct(
        private Geoip2 $geoip2,
    ) {
    }

    public function lookup(string $ip): ?IpAsn
    {
        if (!IpAddress::isPublic($ip)) {
            return null;
        }

        try {
            $record = $this->geoip2->getAsn($ip);
        } catch (\Throwable) {
            return null;
        }

        return new IpAsn(
            asn: $record->autonomousSystemNumber,
            organization: $record->autonomousSystemOrganization,
        );
    }
}
