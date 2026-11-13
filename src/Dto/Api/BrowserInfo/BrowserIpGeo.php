<?php

declare(strict_types=1);

namespace App\Dto\Api\BrowserInfo;

use App\Dto\Api\Network\BrowserGeoInfo;

final readonly class BrowserIpGeo
{
    public function __construct(
        public string $ip,
        public ?BrowserGeoInfo $geo,
    ) {
    }
}

