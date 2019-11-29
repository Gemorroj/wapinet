<?php

namespace App\Helper;

use GeoIp2\Database\Reader;
use GeoIp2\Model\Country;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Geoip2 хэлпер
 */
class Geoip2
{
    /**
     * @var Reader
     */
    protected $reader;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->reader = new Reader($parameterBag->get('wapinet_geoip2_country_path'), ['ru']);
    }

    /**
     * @throws \GeoIp2\Exception\AddressNotFoundException
     * @throws \MaxMind\Db\Reader\InvalidDatabaseException
     */
    public function getCountry(string $ip): Country
    {
        return $this->reader->country($ip);
    }
}
