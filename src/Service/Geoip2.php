<?php

declare(strict_types=1);

namespace App\Service;

use GeoIp2\Database\Reader;
use GeoIp2\Model\Asn;
use GeoIp2\Model\City;
use GeoIp2\Model\Country;
use MaxMind\Db\Reader\Metadata;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class Geoip2
{
    private ?Reader $reader = null;
    private ?Reader $asnReader = null;

    public function __construct(
        #[Autowire('%env(GEOIP2_CITY_PATH)%')]
        private string $dbPath,
        #[Autowire('%env(GEOIP2_ASN_PATH)%')]
        private string $asnDbPath,
    ) {
    }

    /**
     * @throws \GeoIp2\Exception\AddressNotFoundException
     * @throws \MaxMind\Db\Reader\InvalidDatabaseException
     */
    public function getCountry(string $ip): Country
    {
        return $this->getDbReader()->city($ip);
    }

    /**
     * @throws \GeoIp2\Exception\AddressNotFoundException
     * @throws \MaxMind\Db\Reader\InvalidDatabaseException
     */
    public function getCity(string $ip): City
    {
        return $this->getDbReader()->city($ip);
    }

    /**
     * @throws \GeoIp2\Exception\AddressNotFoundException
     * @throws \MaxMind\Db\Reader\InvalidDatabaseException
     */
    public function getAsn(string $ip): Asn
    {
        return $this->getAsnReader()->asn($ip);
    }

    /**
     * @throws \MaxMind\Db\Reader\InvalidDatabaseException
     */
    public function getMetadata(): Metadata
    {
        return $this->getDbReader()->metadata();
    }

    /**
     * @throws \MaxMind\Db\Reader\InvalidDatabaseException
     */
    private function getDbReader(): Reader
    {
        return $this->reader ??= new Reader($this->dbPath, ['ru']);
    }

    /**
     * @throws \MaxMind\Db\Reader\InvalidDatabaseException
     */
    private function getAsnReader(): Reader
    {
        return $this->asnReader ??= new Reader($this->asnDbPath, ['ru']);
    }
}
