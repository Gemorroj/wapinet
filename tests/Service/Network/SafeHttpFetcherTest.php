<?php

declare(strict_types=1);

namespace App\Tests\Service\Network;

use App\Service\Network\DnsLookup;
use App\Service\Network\OutboundUrlBlockedException;
use App\Service\Network\OutboundUrlGuard;
use App\Service\Network\SafeHttpFetcher;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class SafeHttpFetcherTest extends TestCase
{
    public function testFollowsRedirect(): void
    {
        $requests = [];
        $client = new MockHttpClient(static function (string $method, string $url) use (&$requests): MockResponse {
            $requests[] = $url;

            return match (\count($requests)) {
                1 => new MockResponse('', ['http_code' => 302, 'response_headers' => ['location: /file']]),
                default => new MockResponse('ok'),
            };
        });

        $response = $this->fetcher($client)->get('https://example.com/start');

        static::assertSame(200, $response->getStatusCode());
        static::assertSame('ok', $response->getContent());
        static::assertSame(['https://example.com/start', 'https://example.com/file'], $requests);
    }

    public function testBlocksRedirectToPrivateAddress(): void
    {
        $requests = 0;
        $client = new MockHttpClient(static function () use (&$requests): MockResponse {
            ++$requests;

            return new MockResponse('', [
                'http_code' => 302,
                'response_headers' => ['location: http://127.0.0.1/secret'],
            ]);
        });

        try {
            $this->fetcher($client)->get('https://example.com/start');
            static::fail('Expected OutboundUrlBlockedException.');
        } catch (OutboundUrlBlockedException) {
        }

        static::assertSame(1, $requests);
    }

    public function testRejectsTooManyRedirects(): void
    {
        $this->expectException(OutboundUrlBlockedException::class);

        $client = new MockHttpClient(static fn (): MockResponse => new MockResponse('', [
            'http_code' => 302,
            'response_headers' => ['location: /next'],
        ]));

        $this->fetcher($client)->get('https://example.com/start');
    }

    public function testPinsResolvedAddress(): void
    {
        $resolve = null;
        $client = new MockHttpClient(static function (string $method, string $url, array $options) use (&$resolve): MockResponse {
            $resolve = $options['resolve'] ?? null;

            return new MockResponse('ok');
        });

        $this->fetcher($client)->get('https://example.com/');

        static::assertSame(['example.com' => '93.184.216.34'], $resolve);
    }

    private function fetcher(MockHttpClient $client): SafeHttpFetcher
    {
        $dns = new MockHttpClient(static function (string $method, string $url, array $options): MockResponse {
            $isIpv6 = 'AAAA' === ($options['query']['type'] ?? 'A');

            return new MockResponse((string) \json_encode([
                'Answer' => $isIpv6 ? [] : [['name' => 'example.com', 'type' => 1, 'data' => '93.184.216.34']],
            ]));
        });

        return new SafeHttpFetcher($client, new OutboundUrlGuard(new DnsLookup($dns, new ArrayAdapter())));
    }
}
