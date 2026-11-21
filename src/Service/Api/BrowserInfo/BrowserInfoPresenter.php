<?php

declare(strict_types=1);

namespace App\Service\Api\BrowserInfo;

use App\Dto\Api\BrowserInfo\BrowserInfoResponse;
use App\Dto\Api\BrowserInfo\BrowserIpGeo;
use App\Dto\Api\BrowserInfo\BrowserIpReputation;
use App\Dto\Api\BrowserInfo\BrowserVpnInfo;
use App\Dto\Api\Network\BrowserGeoInfo;
use App\Service\Network\DnsLookup;
use App\Service\Network\IpAddress;
use App\Service\Network\IpGeoLookup;
use App\Service\Network\IpInsightLookup;
use Symfony\Component\HttpFoundation\Request;

final readonly class BrowserInfoPresenter
{
    private const array SENSITIVE_HEADERS = [
        'authorization',
        'cookie',
        'proxy-authorization',
        'set-cookie',
        'x-api-key',
        'x-csrf-token',
    ];

    private const array PROXY_HEADERS = [
        'forwarded',
        'proxy-connection',
        'via',
    ];

    public function __construct(
        private IpGeoLookup $geoLookup,
        private IpInsightLookup $insightLookup,
        private DnsLookup $dnsLookup,
    ) {
    }

    /**
     * @param list<string> $webrtcIps
     */
    public function toResponse(Request $request, array $webrtcIps = []): BrowserInfoResponse
    {
        $ip = $this->string($request->server->get('REMOTE_ADDR'));
        $proxy = $this->proxy($request);
        $insight = null !== $ip ? $this->insightLookup->lookup($ip) : null;
        $geo = $insight?->geo;
        $reputation = $insight?->reputation;

        return new BrowserInfoResponse(
            ip: $ip,
            ipHost: $this->host($ip),
            proxy: $proxy,
            proxyHost: $this->proxyHost($proxy),
            phoneNumber: $this->phoneNumber($request),
            userAgent: $request->headers->get('User-Agent'),
            remotePort: $this->string($request->server->get('REMOTE_PORT')),
            remoteUser: $this->string($request->server->get('REMOTE_USER')),
            referer: $request->headers->get('Referer'),
            encoding: $request->headers->get('Accept-Encoding') ?? $request->headers->get('TE'),
            cache: $request->headers->get('Cache-Control'),
            connection: $request->headers->get('Connection'),
            via: $request->headers->get('Via'),
            httpProtocol: $this->string($request->server->get('SERVER_PROTOCOL')),
            accept: $request->headers->get('Accept'),
            language: $request->headers->get('Accept-Language'),
            charset: $request->headers->get('Accept-Charset'),
            dnt: $request->headers->get('DNT'),
            method: $request->getMethod(),
            uri: $request->getRequestUri(),
            serverName: $this->string($request->server->get('SERVER_NAME')),
            serverPort: $this->string($request->server->get('SERVER_PORT')),
            https: $request->isSecure(),
            geoDatabase: $this->geoLookup->database(),
            insight: $insight,
            proxyGeo: $this->ipGeos($this->ipList($proxy)),
            webrtcGeo: $this->ipGeos($webrtcIps),
            vpn: $this->vpn($request, $geo, $reputation),
            headers: $this->headers($request),
        );
    }

    private function proxy(Request $request): ?string
    {
        return $this->string($request->headers->get('X-Forwarded-For') ?? $request->headers->get('Client-IP'));
    }

    private function proxyHost(?string $proxy): ?string
    {
        if (null === $proxy) {
            return null;
        }

        $hosts = [];

        foreach (\explode(',', $proxy) as $address) {
            $address = \trim($address);
            $hosts[] = $this->dnsLookup->ptr($address) ?? $address;
        }

        return \implode(', ', $hosts);
    }

    private function host(?string $ip): ?string
    {
        if (null === $ip) {
            return null;
        }

        return $this->dnsLookup->ptr($ip) ?? $ip;
    }

    /**
     * @return list<string>
     */
    private function ipList(?string $value): array
    {
        if (null === $value) {
            return [];
        }

        $ips = [];

        foreach (\explode(',', $value) as $ip) {
            $ip = \trim($ip);

            if (!IpAddress::isValid($ip) || \in_array($ip, $ips, true)) {
                continue;
            }

            $ips[] = $ip;

            if (\count($ips) >= 10) {
                break;
            }
        }

        return $ips;
    }

    /**
     * @param list<string> $ips
     *
     * @return list<BrowserIpGeo>
     */
    private function ipGeos(array $ips): array
    {
        $items = [];

        foreach ($ips as $ip) {
            $items[] = new BrowserIpGeo($ip, $this->geoLookup->lookup($ip));
        }

        return $items;
    }

    private function vpn(Request $request, ?BrowserGeoInfo $geo, ?BrowserIpReputation $reputation): BrowserVpnInfo
    {
        $torExit = null !== $reputation && $reputation->torExit;
        $blacklisted = null !== $reputation && $reputation->appears;
        $proxyHeaders = $this->proxyHeaders($request);
        $countryMismatch = null !== $geo?->countryCode
            && null !== $geo->registeredCountryCode
            && $geo->countryCode !== $geo->registeredCountryCode;

        $signals = [];

        if ($torExit) {
            $signals[] = 'IP является выходной нодой Tor.';
        }

        if ($blacklisted) {
            $signals[] = 'IP отмечен в базе StopForumSpam.';
        }

        if ([] !== $proxyHeaders) {
            $signals[] = 'В запросе есть заголовки прокси: '.\implode(', ', $proxyHeaders).'.';
        }

        if ($countryMismatch) {
            $signals[] = \sprintf(
                'Страна определения (%s) не совпадает со страной регистрации сети (%s).',
                $geo->countryCode,
                $geo->registeredCountryCode,
            );
        }

        return new BrowserVpnInfo(
            suspicious: [] !== $signals,
            torExit: $torExit,
            proxyHeaders: [] !== $proxyHeaders,
            countryMismatch: $countryMismatch,
            blacklisted: $blacklisted,
            signals: $signals,
        );
    }

    /**
     * @return list<string>
     */
    private function proxyHeaders(Request $request): array
    {
        $names = [];

        foreach (self::PROXY_HEADERS as $header) {
            if ($request->headers->has($header)) {
                $names[] = \implode('-', \array_map(static fn (string $part): string => \ucfirst($part), \explode('-', $header)));
            }
        }

        return $names;
    }

    private function phoneNumber(Request $request): ?string
    {
        if ($request->server->has('HTTP_X_NETWORK_INFO')) {
            return \preg_replace('/(.*,)(11[d])(,.*)/i', '$2', $request->server->get('HTTP_X_NETWORK_INFO'));
        }
        if ($request->server->has('HTTP_X_UP_CALLING_LINE_ID')) {
            return $request->server->get('HTTP_X_UP_CALLING_LINE_ID');
        }
        if ($request->server->has('HTTP_X_UP_SUBNO')) {
            return \preg_replace('/(.*)(11[d])(.*)/i', '$2', $request->server->get('HTTP_X_UP_SUBNO'));
        }
        if ($request->server->has('DEVICEID')) {
            return $request->server->get('DEVICEID');
        }
        if ($request->server->has('HTTP_X_MSISDN')) {
            return $request->server->get('HTTP_X_MSISDN');
        }
        if ($request->server->has('HTTP_X_NOKIA_MSISDN')) {
            return $request->server->get('HTTP_X_NOKIA_MSISDN');
        }

        return null;
    }

    /**
     * @return array<string, list<string>>
     */
    private function headers(Request $request): array
    {
        $headers = [];

        foreach ($request->headers->all() as $name => $values) {
            if ('x-php-ob-level' === $name) {
                continue;
            }

            $headers[$name] = \in_array($name, self::SENSITIVE_HEADERS, true) ? ['[скрыто]'] : $values;
        }

        \ksort($headers);

        return $headers;
    }

    private function string(mixed $value): ?string
    {
        return null === $value || '' === $value ? null : (string) $value;
    }
}
