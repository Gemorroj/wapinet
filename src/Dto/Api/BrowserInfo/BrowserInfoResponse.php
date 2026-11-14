<?php

declare(strict_types=1);

namespace App\Dto\Api\BrowserInfo;

use App\Dto\Api\Network\IpInsightResponse;

final readonly class BrowserInfoResponse
{
    /**
     * @param array<string, list<string>> $headers
     * @param list<BrowserIpGeo>          $proxyGeo
     * @param list<BrowserIpGeo>          $webrtcGeo
     */
    public function __construct(
        public ?string $ip,
        public ?string $ipHost,
        public ?string $proxy,
        public ?string $proxyHost,
        public ?string $phoneNumber,
        public ?string $userAgent,
        public ?string $remotePort,
        public ?string $remoteUser,
        public ?string $referer,
        public ?string $encoding,
        public ?string $cache,
        public ?string $connection,
        public ?string $via,
        public ?string $httpProtocol,
        public ?string $accept,
        public ?string $language,
        public ?string $charset,
        public ?string $dnt,
        public string $method,
        public string $uri,
        public ?string $serverName,
        public ?string $serverPort,
        public bool $https,
        public ?BrowserGeoDatabase $geoDatabase,
        public ?IpInsightResponse $insight,
        public array $proxyGeo,
        public array $webrtcGeo,
        public BrowserVpnInfo $vpn,
        public array $headers,
    ) {
    }
}
