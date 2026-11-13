<?php

declare(strict_types=1);

namespace App\Dto\Api\BrowserInfo;

final readonly class BrowserVpnInfo
{
    /**
     * @param list<string> $signals
     */
    public function __construct(
        public bool $suspicious,
        public bool $torExit,
        public bool $proxyHeaders,
        public bool $countryMismatch,
        public bool $blacklisted,
        public array $signals,
    ) {
    }
}

