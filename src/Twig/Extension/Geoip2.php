<?php

namespace App\Twig\Extension;

use App\Service\Geoip2 as Geoip2Helper;
use GeoIp2\Model\Country;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class Geoip2 extends AbstractExtension
{
    public function __construct(private readonly Geoip2Helper $geoip2)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('wapinet_geoip2_country', function (string $ip): ?Country {
                try {
                    return $this->geoip2->getCountry($ip);
                } catch (\Exception $e) {
                    return null;
                }
            }),
        ];
    }
}
