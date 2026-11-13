<?php

declare(strict_types=1);

namespace App\Dto\Api\Whois;

final readonly class WhoisDnsInfo
{
    /**
     * @param list<WhoisDnsRecord> $records
     * @param list<WhoisIpInfo>    $ips
     * @param list<WhoisHostInfo>  $hosts
     */
    public function __construct(
        public array $records,
        public array $ips,
        public array $hosts,
        public string $provider = '',
    ) {
    }

    public static function empty(): self
    {
        return new self([], [], []);
    }
}

