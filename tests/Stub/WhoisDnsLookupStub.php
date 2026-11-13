<?php

declare(strict_types=1);

namespace App\Tests\Stub;

use App\Dto\Api\Whois\WhoisDnsInfo;
use App\Service\Api\Whois\WhoisDnsLookupInterface;

final class WhoisDnsLookupStub implements WhoisDnsLookupInterface
{
    public function lookup(string $query, string $kind): WhoisDnsInfo
    {
        return WhoisDnsInfo::empty();
    }
}


