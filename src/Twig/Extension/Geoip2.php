<?php

namespace App\Twig\Extension;

use App\Helper\Geoip2 as Geoip2Helper;
use Exception;
use GeoIp2\Model\Country;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class Geoip2 extends AbstractExtension
{
    /**
     * @var Geoip2Helper
     */
    protected $geoip2;

    public function __construct(Geoip2Helper $geoip2)
    {
        $this->geoip2 = $geoip2;
    }

    /**
     * Returns a list of global functions to add to the existing list.
     *
     * @return array An array of global functions
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('wapinet_geoip2_country', function (string $ip): ?Country {
                try {
                    return $this->geoip2->getCountry($ip);
                } catch (Exception $e) {
                    return null;
                }
            }),
        ];
    }
}
