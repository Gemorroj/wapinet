<?php

declare(strict_types=1);

namespace App\Tests\Service\Api\Network;

use App\Service\Network\PeeringDbAsnLookup;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class PeeringDbAsnLookupTest extends TestCase
{
    public function testReturnsNameAndKind(): void
    {
        $client = new MockHttpClient(new MockResponse((string) \json_encode([
            'data' => [['name' => 'Serverius', 'info_type' => 'Enterprise']],
        ])));

        $info = new PeeringDbAsnLookup($client, new ArrayAdapter())->lookup(50673);

        static::assertNotNull($info);
        static::assertSame('Serverius', $info->name);
        static::assertSame('hosting', $info->kind);
    }

    public function testMapsIsp(): void
    {
        $client = new MockHttpClient(new MockResponse((string) \json_encode([
            'data' => [['name' => 'Corbina Telecom', 'info_type' => 'Cable/DSL/ISP']],
        ])));

        $info = new PeeringDbAsnLookup($client, new ArrayAdapter())->lookup(8402);

        static::assertNotNull($info);
        static::assertSame('Corbina Telecom', $info->name);
        static::assertSame('isp', $info->kind);
    }

    public function testMapsNspToIsp(): void
    {
        $client = new MockHttpClient(new MockResponse((string) \json_encode([
            'data' => [['name' => 'Kyivstar', 'info_type' => 'NSP']],
        ])));

        $info = new PeeringDbAsnLookup($client, new ArrayAdapter())->lookup(15895);

        static::assertNotNull($info);
        static::assertSame('isp', $info->kind);
    }

    public function testKeepsNameForUnknownCategory(): void
    {
        $client = new MockHttpClient(new MockResponse((string) \json_encode([
            'data' => [['name' => 'Example Network', 'info_type' => 'Government']],
        ])));

        $info = new PeeringDbAsnLookup($client, new ArrayAdapter())->lookup(12345);

        static::assertNotNull($info);
        static::assertSame('Example Network', $info->name);
        static::assertNull($info->kind);
    }

    public function testReturnsNullForMissingNetwork(): void
    {
        $client = new MockHttpClient(new MockResponse((string) \json_encode(['data' => []])));

        static::assertNull(new PeeringDbAsnLookup($client, new ArrayAdapter())->lookup(12345));
    }

    public function testCachesResultAndMisses(): void
    {
        $calls = 0;
        $client = new MockHttpClient(static function () use (&$calls): MockResponse {
            ++$calls;

            return new MockResponse((string) \json_encode(['data' => []]));
        });
        $lookup = new PeeringDbAsnLookup($client, new ArrayAdapter());

        static::assertNull($lookup->lookup(12345));
        static::assertNull($lookup->lookup(12345));
        static::assertSame(1, $calls);
    }

    public function testCachesNetworkNotFound(): void
    {
        $calls = 0;
        $client = new MockHttpClient(static function () use (&$calls): MockResponse {
            ++$calls;

            return new MockResponse('{"detail":"Not found."}', ['http_code' => 404]);
        });
        $lookup = new PeeringDbAsnLookup($client, new ArrayAdapter());

        static::assertNull($lookup->lookup(42116));
        static::assertNull($lookup->lookup(42116));
        static::assertSame(1, $calls);
    }

    public function testDoesNotCacheTemporaryError(): void
    {
        $calls = 0;
        $client = new MockHttpClient(static function () use (&$calls): MockResponse {
            ++$calls;

            return new MockResponse('', ['http_code' => 429]);
        });
        $lookup = new PeeringDbAsnLookup($client, new ArrayAdapter());

        static::assertNull($lookup->lookup(15169));
        static::assertNull($lookup->lookup(15169));
        static::assertSame(2, $calls);
    }

    public function testSkipsEmptyAsn(): void
    {
        $client = new MockHttpClient(static function (): never {
            static::fail('No HTTP request expected without ASN.');
        });
        $lookup = new PeeringDbAsnLookup($client, new ArrayAdapter());

        static::assertNull($lookup->lookup(null));
        static::assertNull($lookup->lookup(0));
    }
}
