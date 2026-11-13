<?php

declare(strict_types=1);

namespace App\Tests\Service\Api\BrowserInfo;

use App\Service\Api\BrowserInfo\BrowserInfoPresenter;
use App\Service\Geoip2;
use App\Service\Network\DnsLookup;
use App\Service\Network\GeoIpAsnLookup;
use App\Service\Network\IpGeoLookup;
use App\Service\Network\IpInsightLookup;
use App\Service\Network\IpReputationLookup;
use App\Service\Network\PeeringDbAsnLookup;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpFoundation\Request;

final class BrowserInfoPresenterTest extends TestCase
{
    public function testRedactsSensitiveHeaders(): void
    {
        $request = Request::create(
            '/api/browser-info',
            'GET',
            server: [
                'REMOTE_ADDR' => '127.0.0.1',
                'HTTP_AUTHORIZATION' => 'Bearer secret-token',
                'HTTP_COOKIE' => 'session=secret',
                'HTTP_USER_AGENT' => 'Test browser',
            ],
        );

        $response = $this->presenter()->toResponse($request);

        static::assertSame('127.0.0.1', $response->ip);
        static::assertSame('Test browser', $response->userAgent);
        static::assertSame(['[скрыто]'], $response->headers['authorization']);
        static::assertSame(['[скрыто]'], $response->headers['cookie']);
        static::assertStringNotContainsString('secret-token', (string) \json_encode($response));
        static::assertStringNotContainsString('session=secret', (string) \json_encode($response));
    }

    public function testSkipsGeoAndReputationForPrivateAddress(): void
    {
        $request = Request::create('/api/browser-info', 'GET', server: ['REMOTE_ADDR' => '192.168.1.10']);

        $response = $this->presenter()->toResponse($request);

        static::assertNull($response->insight);
        static::assertNull($response->geoDatabase);
        static::assertFalse($response->vpn->suspicious);
        static::assertSame([], $response->vpn->signals);
    }

    public function testReportsProxyHeadersAsVpnSignal(): void
    {
        $request = Request::create(
            '/api/browser-info',
            'GET',
            server: [
                'REMOTE_ADDR' => '203.0.113.10',
                'HTTP_VIA' => '1.1 corporate-proxy',
                'HTTP_FORWARDED' => 'for=203.0.113.10',
            ],
        );

        $response = $this->presenter()->toResponse($request);

        static::assertTrue($response->vpn->suspicious);
        static::assertTrue($response->vpn->proxyHeaders);
        static::assertContains('В запросе есть заголовки прокси: Forwarded, Via.', $response->vpn->signals);
    }

    private function presenter(): BrowserInfoPresenter
    {
        $geoip2 = new Geoip2('missing-city.mmdb', 'missing-asn.mmdb');
        $geo = new IpGeoLookup($geoip2);
        $dns = new DnsLookup(new MockHttpClient(), new ArrayAdapter());

        return new BrowserInfoPresenter(
            $geo,
            new IpInsightLookup(
                $geo,
                new IpReputationLookup(new MockHttpClient(), new ArrayAdapter()),
                $dns,
                new GeoIpAsnLookup($geoip2),
                new PeeringDbAsnLookup(new MockHttpClient(), new ArrayAdapter()),
            ),
            $dns,
        );
    }
}


