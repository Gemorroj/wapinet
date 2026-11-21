<?php

declare(strict_types=1);

namespace App\Dto\Api\Whois;

use App\Dto\Api\Network\BrowserGeoInfo;

final readonly class WhoisIpInfo
{
    public function __construct(
        public string $ip,
        public ?string $hostname,
        public ?BrowserGeoInfo $geo,
    ) {
    }
}
