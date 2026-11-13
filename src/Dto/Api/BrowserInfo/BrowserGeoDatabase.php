<?php

declare(strict_types=1);

namespace App\Dto\Api\BrowserInfo;

final readonly class BrowserGeoDatabase
{
    public function __construct(
        public string $type,
        public string $buildDate,
    ) {
    }
}

