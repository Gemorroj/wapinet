<?php

declare(strict_types=1);

namespace App\Service\Api\Whois;

use App\Dto\Api\Whois\WhoisDnsInfo;

interface WhoisDnsLookupInterface
{
    public function lookup(string $query, string $kind): WhoisDnsInfo;
}
