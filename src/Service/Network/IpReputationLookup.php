<?php

declare(strict_types=1);

namespace App\Service\Network;

use App\Dto\Api\BrowserInfo\BrowserIpReputation;
use Psr\Cache\CacheItemPoolInterface;
use StopSpam\Query as StopSpamQuery;
use StopSpam\Request as StopSpamRequest;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class IpReputationLookup
{
    public function __construct(
        private HttpClientInterface $httpClient,
        #[Autowire(service: 'cache.ip_reputation')]
        private CacheItemPoolInterface $cache,
    ) {
    }

    public function lookup(?string $ip): ?BrowserIpReputation
    {
        if (!IpAddress::isPublic($ip)) {
            return null;
        }

        $key = 'ip_reputation_'.\hash('sha256', $ip);
        $item = $this->cache->getItem($key);

        if ($item->isHit()) {
            $cached = $item->get();

            return $cached instanceof BrowserIpReputation ? $cached : null;
        }

        $reputation = $this->fetch($ip);

        if (null !== $reputation) {
            $item->set($reputation);
            $this->cache->save($item);
        }

        return $reputation;
    }

    private function fetch(string $ip): ?BrowserIpReputation
    {
        $request = new StopSpamRequest($this->httpClient->withOptions([
            'timeout' => 3,
            'max_duration' => 5,
            'headers' => ['User-Agent' => 'wapinet.ru'],
        ]));

        try {
            $response = $request->send(new StopSpamQuery()->addIp($ip));
        } catch (\Throwable) {
            return null;
        }

        $item = $response->getSingleIp();

        if (null === $item || $item->isError()) {
            return null;
        }

        $lastSeen = $item->getLastSeen();
        $country = $item->getCountry();

        return new BrowserIpReputation(
            appears: $item->isAppears(),
            confidence: $item->getConfidence(),
            frequency: $item->getFrequency(),
            lastSeen: null !== $lastSeen ? new \DateTimeImmutable('@'.$lastSeen)->format(\DATE_ATOM) : null,
            torExit: $item->isTorExit(),
            country: null !== $country ? \strtoupper($country) : null,
            asn: $item->getAsn(),
            delegated: $item->getDelegated(),
        );
    }
}
