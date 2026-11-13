<?php

declare(strict_types=1);

namespace App\Service\Network;

final class IpAddress
{
    public static function isValid(?string $ip): bool
    {
        return null !== $ip && '' !== $ip && false !== \filter_var($ip, \FILTER_VALIDATE_IP);
    }

    public static function isPublic(?string $ip): bool
    {
        if (!self::isValid($ip)) {
            return false;
        }

        return false !== \filter_var($ip, \FILTER_VALIDATE_IP, \FILTER_FLAG_NO_PRIV_RANGE | \FILTER_FLAG_NO_RES_RANGE);
    }

    public static function isIpv4(string $ip): bool
    {
        return false !== \filter_var($ip, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV4);
    }

    /**
     * Обратный порядок октетов или полубайтов — основа имён в in-addr.arpa и ip6.arpa.
     */
    public static function reverseLabel(string $ip): string
    {
        if (self::isIpv4($ip)) {
            return \implode('.', \array_reverse(\explode('.', $ip)));
        }

        $hex = \bin2hex((string) \inet_pton($ip));

        return \implode('.', \array_reverse(\str_split($hex)));
    }

    public static function ptrName(string $ip): string
    {
        return self::reverseLabel($ip).(self::isIpv4($ip) ? '.in-addr.arpa' : '.ip6.arpa');
    }
}
