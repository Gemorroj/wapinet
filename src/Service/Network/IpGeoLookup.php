<?php

declare(strict_types=1);

namespace App\Service\Network;

use App\Dto\Api\BrowserInfo\BrowserGeoDatabase;
use App\Dto\Api\Network\BrowserGeoInfo;
use App\Service\Geoip2;

final readonly class IpGeoLookup
{
    public function __construct(
        private Geoip2 $geoip2,
    ) {
    }

    public function lookup(?string $ip): ?BrowserGeoInfo
    {
        if (!IpAddress::isPublic($ip)) {
            return null;
        }

        try {
            $record = $this->geoip2->getCity($ip);
        } catch (\Throwable) {
            return null;
        }

        return new BrowserGeoInfo(
            continent: $record->continent->name,
            continentCode: $record->continent->code,
            country: $record->country->name,
            countryCode: $record->country->isoCode,
            region: $record->mostSpecificSubdivision->name,
            city: $record->city->name,
            postalCode: $record->postal->code,
            latitude: $record->location->latitude,
            longitude: $record->location->longitude,
            accuracyRadius: $record->location->accuracyRadius,
            timezone: $record->location->timeZone,
            registeredCountry: $record->registeredCountry->name,
            registeredCountryCode: $record->registeredCountry->isoCode,
            inEuropeanUnion: $record->country->isInEuropeanUnion,
            network: $record->traits->network,
        );
    }

    public function database(): ?BrowserGeoDatabase
    {
        try {
            $metadata = $this->geoip2->getMetadata();
        } catch (\Throwable) {
            return null;
        }

        return new BrowserGeoDatabase(
            type: $metadata->databaseType,
            buildDate: new \DateTimeImmutable('@'.$metadata->buildEpoch)->format('Y-m-d'),
        );
    }
}
