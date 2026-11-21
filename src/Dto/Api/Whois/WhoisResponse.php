<?php

declare(strict_types=1);

namespace App\Dto\Api\Whois;

use App\Dto\Api\Network\IpInsightResponse;

final readonly class WhoisResponse
{
    /**
     * @param list<WhoisField>   $details
     * @param list<string>       $statuses
     * @param list<WhoisEvent>   $events
     * @param list<string>       $nameservers
     * @param list<WhoisContact> $contacts
     */
    public function __construct(
        public string $query,
        public string $kind,
        public string $protocol,
        public string $server,
        public string $raw,
        public array $details,
        public array $statuses,
        public array $events,
        public array $nameservers,
        public array $contacts,
        public WhoisDnsInfo $dns,
        public ?IpInsightResponse $insight,
    ) {
    }
}
