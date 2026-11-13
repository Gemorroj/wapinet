<?php

declare(strict_types=1);

namespace App\Tests\Service\Api\Network;

use App\Service\Network\DnsLookup;
use App\Service\Network\OutboundUrlBlockedException;
use App\Service\Network\OutboundUrlGuard;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class OutboundUrlGuardTest extends TestCase
{
    public function testAllowsPublicIpLiteral(): void
    {
        $target = $this->guard()->resolve('http://8.8.8.8/path');

        static::assertSame('8.8.8.8', $target->host);
        static::assertSame('8.8.8.8', $target->ip);
        static::assertSame(80, $target->port);
        static::assertSame('http', $target->scheme);
    }

    public function testUsesHttpsDefaultPort(): void
    {
        $target = $this->guard()->resolve('https://8.8.8.8/');

        static::assertSame(443, $target->port);
        static::assertSame('https', $target->scheme);
    }

    #[DataProvider('privateAddresses')]
    public function testBlocksPrivateAndReservedAddresses(string $ip): void
    {
        $url = \sprintf('http://%s/', \str_contains($ip, ':') ? '['.$ip.']' : $ip);

        $this->expectException(OutboundUrlBlockedException::class);

        $this->guard()->resolve($url);
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function privateAddresses(): iterable
    {
        foreach ([
            '127.0.0.1',
            '10.0.0.1',
            '172.16.0.1',
            '192.168.1.1',
            '169.254.169.254',
            '100.64.0.1',
            '192.0.0.1',
            '198.51.100.1',
            '203.0.113.1',
            '224.0.0.1',
            '0.0.0.0',
            '::1',
            'fc00::1',
            'fe80::1',
            '2001:db8::1',
        ] as $ip) {
            yield $ip => [$ip];
        }
    }

    public function testBlocksResolvedPrivateAddress(): void
    {
        $guard = $this->guard(['10.0.0.1']);

        $this->expectException(OutboundUrlBlockedException::class);

        $guard->resolve('http://internal.example/');
    }

    public function testBlocksWhenAnyResolvedAddressIsPrivate(): void
    {
        $guard = $this->guard(['93.184.216.34'], ['::1']);

        $this->expectException(OutboundUrlBlockedException::class);

        $guard->resolve('http://mixed.example/');
    }

    public function testAllowsResolvedPublicAddressAndPrefersIpv4(): void
    {
        $guard = $this->guard(['93.184.216.34'], ['2606:2800:220:1:248:1893:25c8:1946']);

        $target = $guard->resolve('https://example.com/');

        static::assertSame('example.com', $target->host);
        static::assertSame('93.184.216.34', $target->ip);
        static::assertSame(443, $target->port);
    }

    public function testRejectsUnsupportedScheme(): void
    {
        $this->expectException(OutboundUrlBlockedException::class);

        $this->guard()->resolve('ftp://example.com/');
    }

    public function testRejectsMissingHost(): void
    {
        $this->expectException(OutboundUrlBlockedException::class);

        $this->guard()->resolve('http:///');
    }

    /**
     * @param list<string> $v4
     * @param list<string> $v6
     */
    private function guard(array $v4 = [], array $v6 = []): OutboundUrlGuard
    {
        $client = new MockHttpClient(static function (string $method, string $url, array $options) use ($v4, $v6): MockResponse {
            $isIpv6 = 'AAAA' === ($options['query']['type'] ?? 'A');
            $answers = [];

            foreach ($isIpv6 ? $v6 : $v4 as $ip) {
                $answers[] = ['name' => 'test', 'type' => $isIpv6 ? 28 : 1, 'data' => $ip];
            }

            return new MockResponse((string) \json_encode(['Status' => 0, 'Answer' => $answers]));
        });

        return new OutboundUrlGuard(new DnsLookup($client, new ArrayAdapter()));
    }
}
