<?php

declare(strict_types=1);

namespace App\Dto\Api\Network;

use App\Dto\Api\BrowserInfo\BrowserIpReputation;

final readonly class IpInsightResponse
{
    public function __construct(
        public string $ip,
        public ?BrowserGeoInfo $geo,
        public ?BrowserIpReputation $reputation,
        public ?string $ptr,
        public ?int $asn,
        public ?string $network,
        public ?string $organization,
        public ?string $kind,
        public string $dnsProvider = '',
    ) {
    }
}
