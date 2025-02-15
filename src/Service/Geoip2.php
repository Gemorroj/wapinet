<?php

namespace App\Service;

use GeoIp2\Database\Reader;
use GeoIp2\Model\Country;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

final class Geoip2
{
    private ?Reader $reader = null;
    private readonly string $dbPath;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->dbPath = $parameterBag->get('wapinet_geoip2_country_path');
    }

    /**
     * @throws \GeoIp2\Exception\AddressNotFoundException
     * @throws \MaxMind\Db\Reader\InvalidDatabaseException
     */
    public function getCountry(string $ip): Country
    {
        return $this->getDbReader()->country($ip);
    }

    /**
     * @throws \MaxMind\Db\Reader\InvalidDatabaseException
     */
    private function getDbReader(): Reader
    {
        $this->reader = $this->reader ?: new Reader($this->dbPath, ['ru']);

        return $this->reader;
    }
}
