<?php

declare(strict_types=1);

namespace App\Service\Network;

final readonly class AsnCatalogEntry
{
    public function __construct(
        public string $name,
        /**
         * @var 'hosting'|'isp'|null
         */
        public ?string $kind,
    ) {
    }
}
