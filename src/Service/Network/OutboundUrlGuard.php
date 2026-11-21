<?php

declare(strict_types=1);

namespace App\Service\Network;

use Symfony\Component\HttpFoundation\IpUtils;

final readonly class OutboundUrlGuard
{
    private const int MAX_URL_LENGTH = 2048;

    public function __construct(
        private DnsLookup $dnsLookup,
    ) {
    }

    public function resolve(string $url): OutboundTarget
    {
        if (\mb_strlen($url) > self::MAX_URL_LENGTH) {
            throw new OutboundUrlBlockedException('URL слишком длинный.');
        }

        $parts = \parse_url($url);

        if (false === $parts) {
            throw new OutboundUrlBlockedException('Некорректный URL.');
        }

        $scheme = \strtolower($parts['scheme'] ?? '');

        if (!\in_array($scheme, ['http', 'https'], true)) {
            throw new OutboundUrlBlockedException('Поддерживаются только http и https.');
        }

        $host = \trim($parts['host'] ?? '', '[]');

        if ('' === $host) {
            throw new OutboundUrlBlockedException('В URL не указан хост.');
        }

        $port = $parts['port'] ?? ('https' === $scheme ? 443 : 80);

        if ($port < 1) {
            throw new OutboundUrlBlockedException('Некорректный порт.');
        }

        $ips = $this->ips($host);

        if ([] === $ips) {
            throw new OutboundUrlBlockedException('Не удалось определить IP-адрес хоста.');
        }

        foreach ($ips as $ip) {
            if (IpUtils::isPrivateIp($ip)) {
                throw new OutboundUrlBlockedException('Запросы к приватным и служебным адресам запрещены.');
            }
        }

        return new OutboundTarget($host, $port, $ips[0], $scheme);
    }

    /**
     * @return list<string>
     */
    private function ips(string $host): array
    {
        if (false !== \filter_var($host, \FILTER_VALIDATE_IP)) {
            return [$host];
        }

        $answers = $this->dnsLookup->records($host, ['A', 'AAAA']);
        $ips = \array_values(\array_unique(\array_merge($answers['A'], $answers['AAAA'])));

        \usort($ips, static fn (string $a, string $b): int => (int) (false !== \filter_var($a, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV6))
            <=> (int) (false !== \filter_var($b, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV6)));

        return $ips;
    }
}
