<?php

declare(strict_types=1);

namespace App\Service\Api\Whois;

use App\Dto\Api\Whois\WhoisResponse;
use App\Service\Network\IpInsightLookup;

final readonly class WhoisLookup
{
    public function __construct(
        private WhoisGatewayInterface $gateway,
        private WhoisParser $parser,
        private WhoisDnsLookupInterface $dnsLookup,
        private IpInsightLookup $insightLookup,
    ) {
    }

    public function lookup(string $query, string $protocol): WhoisResponse
    {
        $query = \trim($query);

        if ('' === $query) {
            throw new WhoisException('Пустой запрос.');
        }

        $kind = $this->parser->kind($query);

        try {
            $data = $this->gateway->lookup($query, $protocol);
        } catch (\Throwable $exception) {
            throw new WhoisException($exception->getMessage(), 0, $exception);
        }

        $insight = \in_array($kind, ['ipv4', 'ipv6'], true) ? $this->insightLookup->lookup($query) : null;
        $dns = $this->dnsLookup->lookup($query, $kind);

        return $this->parser->parse(
            query: $query,
            kind: $kind,
            protocol: $data['protocol'],
            server: $data['server'],
            raw: $data['response'],
            dns: $dns,
            insight: $insight,
        );
    }
}
