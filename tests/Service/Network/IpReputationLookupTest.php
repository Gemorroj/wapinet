<?php

declare(strict_types=1);

namespace App\Tests\Service\Network;

use App\Service\Network\IpReputationLookup;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class IpReputationLookupTest extends TestCase
{
    public function testParsesStopForumSpamResponse(): void
    {
        $client = new MockHttpClient(new MockResponse((string) \json_encode([
            'success' => 1,
            'ip' => [[
                'value' => '8.8.8.8',
                'appears' => 1,
                'confidence' => 12.5,
                'frequency' => 80,
                'lastseen' => 1700000000,
                'torexit' => 1,
                'country' => 'US',
                'asn' => 15169,
                'delegated' => '8.8.8.0/24',
            ]],
        ])));

        $reputation = new IpReputationLookup($client, new ArrayAdapter())->lookup('8.8.8.8');

        static::assertNotNull($reputation);
        static::assertTrue($reputation->appears);
        static::assertSame(12.5, $reputation->confidence);
        static::assertSame(80.0, $reputation->frequency);
        static::assertSame('2023-11-14T22:13:20+00:00', $reputation->lastSeen);
        static::assertTrue($reputation->torExit);
        static::assertSame('US', $reputation->country);
        static::assertSame(15169, $reputation->asn);
        static::assertSame('8.8.8.0/24', $reputation->delegated);
    }

    public function testSkipsPrivateAddresses(): void
    {
        $client = new MockHttpClient(static function (): never {
            static::fail('No HTTP request expected for a private address.');
        });

        static::assertNull(new IpReputationLookup($client, new ArrayAdapter())->lookup('127.0.0.1'));
        static::assertNull(new IpReputationLookup($client, new ArrayAdapter())->lookup(null));
    }

    public function testCachesResultPerAddress(): void
    {
        $calls = 0;
        $client = new MockHttpClient(static function () use (&$calls): MockResponse {
            ++$calls;

            return new MockResponse((string) \json_encode([
                'success' => 1,
                'ip' => [['value' => '8.8.8.8', 'appears' => 0]],
            ]));
        });
        $lookup = new IpReputationLookup($client, new ArrayAdapter());

        $lookup->lookup('8.8.8.8');
        $lookup->lookup('8.8.8.8');

        static::assertSame(1, $calls);
    }
}
