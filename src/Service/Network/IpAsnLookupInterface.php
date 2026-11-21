<?php

declare(strict_types=1);

namespace App\Service\Network;

interface IpAsnLookupInterface
{
    public function lookup(string $ip): ?IpAsn;
}
