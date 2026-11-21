<?php

declare(strict_types=1);

namespace App\Service\Network;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class DnsLookup
{
    private const string DNS_QUERY_URL = 'https://cloudflare-dns.com/dns-query';
    private const string PROVIDER = 'Cloudflare';
    private const int TIMEOUT = 1;
    private const int MAX_DURATION = 2;
    private const array TYPE_CODES = [
        'A' => 1,
        'NS' => 2,
        'CNAME' => 5,
        'PTR' => 12,
        'MX' => 15,
        'AAAA' => 28,
    ];

    public function __construct(
        private HttpClientInterface $httpClient,
        #[Autowire(service: 'cache.dns')]
        private CacheItemPoolInterface $cache,
    ) {
    }

    public function provider(): string
    {
        return self::PROVIDER;
    }

    public function ptr(string $ip): ?string
    {
        return $this->ptrMany([$ip])[$ip] ?? null;
    }

    /**
     * @param list<string> $ips
     *
     * @return array<string, string|null>
     */
    public function ptrMany(array $ips): array
    {
        $queries = [];
        $results = [];

        foreach (\array_unique($ips) as $ip) {
            if (!IpAddress::isPublic($ip)) {
                $results[$ip] = null;

                continue;
            }

            $queries[$ip] = [IpAddress::ptrName($ip), 'PTR'];
        }

        foreach ($this->answers($queries) as $ip => $answers) {
            $results[$ip] = $answers[0] ?? null;
        }

        return $results;
    }

    /**
     * @param list<string> $types
     *
     * @return array<string, list<string>>
     */
    public function records(string $name, array $types): array
    {
        $queries = [];

        foreach ($types as $type) {
            $queries[$type] = [$name, $type];
        }

        $results = $this->answers($queries);

        foreach ($types as $type) {
            $results[$type] ??= [];
        }

        return $results;
    }

    /**
     * @param array<string, array{0: string, 1: string}> $queries
     *
     * @return array<string, list<string>>
     */
    private function answers(array $queries): array
    {
        $results = [];
        $pendingItems = [];
        $pendingResponses = [];

        foreach ($queries as $key => [$name, $type]) {
            $cacheKey = 'dns_'.\hash('sha256', \mb_strtolower($name).'|'.$type);
            $item = $this->cache->getItem($cacheKey);

            if ($item->isHit()) {
                $value = $item->get();
                $results[$key] = \is_array($value) ? \array_values(\array_filter($value, '\is_string')) : [];

                continue;
            }

            try {
                $pendingItems[$key] = $item;
                $pendingResponses[$key] = $this->httpClient->request('GET', self::DNS_QUERY_URL, [
                    'query' => ['name' => $name, 'type' => $type],
                    'headers' => ['accept' => 'application/dns-json'],
                    'timeout' => self::TIMEOUT,
                    'max_duration' => self::MAX_DURATION,
                ]);
            } catch (\Throwable) {
                $results[$key] = [];
            }
        }

        foreach ($pendingResponses as $key => $response) {
            $values = [];

            try {
                $data = $response->toArray(false);
                $code = self::TYPE_CODES[$queries[$key][1]] ?? null;

                foreach ($data['Answer'] ?? [] as $answer) {
                    if (!\is_array($answer) || ($answer['type'] ?? null) !== $code) {
                        continue;
                    }

                    $value = $answer['data'] ?? null;

                    if (\is_string($value) && '' !== $value) {
                        $value = \rtrim($value, '.');
                        $values[] = $value;
                    }
                }
            } catch (\Throwable) {
                $values = [];
            }

            $results[$key] = $values;
            $item = $pendingItems[$key];
            $item->set($values);
            $this->cache->save($item);
        }

        return $results;
    }
}
