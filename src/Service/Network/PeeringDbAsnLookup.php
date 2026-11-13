<?php

declare(strict_types=1);

namespace App\Service\Network;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class PeeringDbAsnLookup implements AsnCatalogLookupInterface
{
    private const string API_URL = 'https://www.peeringdb.com/api/net';
    private const array KIND_MAP = [
        'Cable/DSL/ISP' => 'isp',
        'NSP' => 'isp',
        'Wholesale' => 'isp',
        'Content' => 'hosting',
        'Enterprise' => 'hosting',
    ];

    public function __construct(
        private HttpClientInterface $httpClient,
        #[Autowire(service: 'cache.peeringdb')]
        private CacheItemPoolInterface $cache,
    ) {
    }

    public function lookup(?int $asn): ?AsnCatalogEntry
    {
        if (null === $asn || $asn <= 0) {
            return null;
        }

        $item = $this->cache->getItem('peeringdb_net_'.$asn);

        if ($item->isHit()) {
            $cached = $item->get();

            return $cached instanceof AsnCatalogEntry ? $cached : null;
        }

        try {
            $entry = $this->fetch($asn);
        } catch (\Throwable) {
            return null;
        }

        $item->set($entry);
        $this->cache->save($item);

        return $entry;
    }

    /**
     * Возвращает null, если сети нет в PeeringDB. Временные ошибки пробрасывает,
     * чтобы они не осели в кэше на сутки.
     */
    private function fetch(int $asn): ?AsnCatalogEntry
    {
        $response = $this->httpClient->request('GET', self::API_URL, [
            'query' => ['asn' => $asn],
            'timeout' => 2,
            'max_duration' => 3,
        ]);

        if (404 === $response->getStatusCode()) {
            return null;
        }

        $data = $response->toArray();
        $net = $data['data'][0] ?? null;

        if (!\is_array($net)) {
            return null;
        }

        $name = $net['name'] ?? null;

        if (!\is_string($name) || '' === \trim($name)) {
            return null;
        }

        $infoType = $net['info_type'] ?? null;

        return new AsnCatalogEntry(
            name: \trim($name),
            kind: \is_string($infoType) ? (self::KIND_MAP[\trim($infoType)] ?? null) : null,
        );
    }
}
