<?php

declare(strict_types=1);

namespace App\Service\Network;

final readonly class IpAsn
{
    public function __construct(
        public ?int $asn,
        public ?string $organization,
    ) {
    }
}
