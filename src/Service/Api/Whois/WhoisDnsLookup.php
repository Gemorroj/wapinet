<?php

declare(strict_types=1);

namespace App\Service\Api\Whois;

use App\Dto\Api\Whois\WhoisDnsInfo;
use App\Dto\Api\Whois\WhoisDnsRecord;
use App\Dto\Api\Whois\WhoisHostInfo;
use App\Dto\Api\Whois\WhoisIpInfo;
use App\Service\Network\DnsLookup;
use App\Service\Network\IpGeoLookup;

final readonly class WhoisDnsLookup implements WhoisDnsLookupInterface
{
    private const int MAX_IPS = 10;
    private const int MAX_RECORDS = 20;

    public function __construct(
        private IpGeoLookup $geoLookup,
        private DnsLookup $dnsLookup,
    ) {
    }

    public function lookup(string $query, string $kind): WhoisDnsInfo
    {
        return match ($kind) {
            'domain' => $this->domain($query),
            'ipv4', 'ipv6' => $this->ip($query),
            default => WhoisDnsInfo::empty(),
        };
    }

    private function domain(string $domain, int $depth = 0): WhoisDnsInfo
    {
        $answers = $this->dnsLookup->records($domain, ['A', 'AAAA', 'CNAME', 'MX', 'NS']);

        $dnsRecords = [];
        $cname = $answers['CNAME'][0] ?? null;

        foreach ($answers['CNAME'] as $target) {
            $this->record($dnsRecords, 'CNAME', $target);
        }

        foreach ($answers['MX'] as $target) {
            $this->record($dnsRecords, 'MX', $target);
        }

        foreach ($answers['NS'] as $target) {
            $this->record($dnsRecords, 'NS', $target);
        }

        $ips = \array_slice(\array_values(\array_unique(\array_merge($answers['A'], $answers['AAAA']))), 0, self::MAX_IPS);

        if ([] === $ips && null !== $cname && $depth < 2) {
            $nested = $this->domain($cname, $depth + 1);

            return $this->info($this->unique(\array_merge($dnsRecords, $nested->records)), $nested->ips, []);
        }

        $hostnames = $this->dnsLookup->ptrMany($ips);
        $ipInfos = [];

        foreach ($ips as $ip) {
            $ipInfos[] = new WhoisIpInfo(
                ip: $ip,
                hostname: $hostnames[$ip] ?? null,
                geo: $this->geoLookup->lookup($ip),
            );
        }

        return $this->info($this->unique($dnsRecords), $ipInfos, []);
    }

    private function ip(string $ip): WhoisDnsInfo
    {
        $hostname = $this->dnsLookup->ptr($ip);

        if (null === $hostname) {
            return WhoisDnsInfo::empty();
        }

        $answers = $this->dnsLookup->records($hostname, ['A', 'AAAA']);
        $ips = \array_values(\array_unique(\array_merge($answers['A'], $answers['AAAA'])));

        return $this->info(
            [],
            [],
            [new WhoisHostInfo($hostname, $ips, \in_array($ip, $ips, true))],
        );
    }

    /**
     * @param list<WhoisDnsRecord> $records
     * @param list<WhoisIpInfo>    $ips
     * @param list<WhoisHostInfo>  $hosts
     */
    private function info(array $records, array $ips, array $hosts): WhoisDnsInfo
    {
        return new WhoisDnsInfo($records, $ips, $hosts, $this->dnsLookup->provider());
    }

    /**
     * @param list<WhoisDnsRecord> $records
     */
    private function record(array &$records, string $type, mixed $value): void
    {
        if (\count($records) >= self::MAX_RECORDS) {
            return;
        }

        if (\is_string($value) && '' !== \trim($value)) {
            $records[] = new WhoisDnsRecord($type, \trim($value));
        }
    }

    /**
     * @param list<WhoisDnsRecord> $records
     *
     * @return list<WhoisDnsRecord>
     */
    private function unique(array $records): array
    {
        $result = [];

        foreach ($records as $record) {
            $result[$record->type.'|'.$record->value] = $record;
        }

        return \array_values($result);
    }
}
