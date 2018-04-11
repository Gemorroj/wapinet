<?php
namespace WapinetBundle\Helper;

use GeoIp2\Database\Reader;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Geoip2 хэлпер
 */
class Geoip2
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var Reader
     */
    protected $reader;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->reader = new Reader($container->getParameter('wapinet_geoip2_country_path'), ['ru']);
    }

    /**
     * @param string $ip
     * @return \GeoIp2\Model\Country
     */
    public function getCountry($ip)
    {
        return $this->reader->country($ip);
    }
}
