<?php

declare(strict_types=1);

namespace App\Service\Network;

use App\Dto\Api\Network\IpInsightResponse;

final readonly class IpInsightLookup
{
    public function __construct(
        private IpGeoLookup $geoLookup,
        private IpReputationLookup $reputationLookup,
        private DnsLookup $dnsLookup,
        private IpAsnLookupInterface $ipAsnLookup,
        private AsnCatalogLookupInterface $asnCatalogLookup,
    ) {
    }

    public function lookup(string $ip): ?IpInsightResponse
    {
        if (!IpAddress::isPublic($ip)) {
            return null;
        }

        $geo = $this->geoLookup->lookup($ip);
        $reputation = $this->reputationLookup->lookup($ip);
        $ipAsn = $this->ipAsnLookup->lookup($ip);
        $ipAsnNumber = $ipAsn?->asn;
        $asn = $ipAsnNumber ?? $reputation?->asn;
        $catalog = $this->asnCatalogLookup->lookup($asn);
        $catalogName = $catalog?->name;

        return new IpInsightResponse(
            ip: $ip,
            geo: $geo,
            reputation: $reputation,
            ptr: $this->dnsLookup->ptr($ip),
            asn: $asn,
            network: $geo?->network,
            organization: $catalogName ?? $ipAsn?->organization,
            kind: $catalog?->kind,
            dnsProvider: $this->dnsLookup->provider(),
        );
    }
}
