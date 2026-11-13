<?php

declare(strict_types=1);

namespace App\Tests\Service\Api\Network;

use App\Service\Network\DnsLookup;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class DnsLookupTest extends TestCase
{
    public function testResolvesAndCachesPtr(): void
    {
        $calls = 0;
        $client = new MockHttpClient(static function () use (&$calls): MockResponse {
            ++$calls;

            return new MockResponse((string) \json_encode(['Answer' => [['type' => 12, 'data' => 'dns.google.']]]));
        });
        $dnsLookup = new DnsLookup($client, new ArrayAdapter());

        static::assertSame('dns.google', $dnsLookup->ptr('8.8.8.8'));
        static::assertSame('dns.google', $dnsLookup->ptr('8.8.8.8'));
        static::assertSame(1, $calls);
    }

    public function testSkipsPrivateAddresses(): void
    {
        $client = new MockHttpClient(static function (): never {
            static::fail('No HTTP request expected for a private address.');
        });
        $dnsLookup = new DnsLookup($client, new ArrayAdapter());

        static::assertNull($dnsLookup->ptr('127.0.0.1'));
        static::assertNull($dnsLookup->ptr('192.168.1.10'));
    }

    public function testFiltersAnswersByType(): void
    {
        $client = new MockHttpClient(new MockResponse((string) \json_encode([
            'Answer' => [
                ['type' => 5, 'data' => 'alias.example.com.'],
                ['type' => 1, 'data' => '1.2.3.4'],
            ],
        ])));
        $dnsLookup = new DnsLookup($client, new ArrayAdapter());

        static::assertSame(['1.2.3.4'], $dnsLookup->records('example.com', ['A'])['A']);
    }
}
