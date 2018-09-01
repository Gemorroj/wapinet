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

    /**
     * @param ParameterBagInterface $parameterBag
     */
    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->reader = new Reader($parameterBag->get('wapinet_geoip2_country_path'), ['ru']);
    }

    /**
     * @param string $ip
     *
     * @throws \GeoIp2\Exception\AddressNotFoundException
     * @throws \MaxMind\Db\Reader\InvalidDatabaseException
     *
     * @return Country
     */
    public function getCountry(string $ip): Country
    {
        return $this->reader->country($ip);
    }
}
