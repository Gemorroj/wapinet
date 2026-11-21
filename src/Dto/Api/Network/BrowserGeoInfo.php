<?php

declare(strict_types=1);

namespace App\Dto\Api\Network;

final readonly class BrowserGeoInfo
{
    public function __construct(
        public ?string $continent,
        public ?string $continentCode,
        public ?string $country,
        public ?string $countryCode,
        public ?string $region,
        public ?string $city,
        public ?string $postalCode,
        public ?float $latitude,
        public ?float $longitude,
        public ?int $accuracyRadius,
        public ?string $timezone,
        public ?string $registeredCountry,
        public ?string $registeredCountryCode,
        public bool $inEuropeanUnion,
        public ?string $network,
    ) {
    }
}
