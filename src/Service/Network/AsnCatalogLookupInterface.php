<?php

declare(strict_types=1);

namespace App\Service\Network;

interface AsnCatalogLookupInterface
{
    public function lookup(?int $asn): ?AsnCatalogEntry;
}
