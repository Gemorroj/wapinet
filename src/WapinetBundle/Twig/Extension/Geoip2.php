<?php

namespace WapinetBundle\Twig\Extension;

use WapinetBundle\Helper\Geoip2 as Geoip2Helper;

class Geoip2 extends \Twig_Extension
{
    /**
     * @var Geoip2Helper
     */
    protected $geoip2;

    /**
     * @param Geoip2Helper $geoip2
     */
    public function __construct(Geoip2Helper $geoip2)
    {
        $this->geoip2 = $geoip2;
    }

    /**
     * Returns a list of global functions to add to the existing list.
     *
     * @return array An array of global functions
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('wapinet_geoip2_country', array($this, 'getCountry')),
        );
    }


    /**
     * @param string $ip
     * @return \GeoIp2\Model\Country|null
     */
    public function getCountry ($ip)
    {
        try {
            return $this->geoip2->getCountry($ip);
        } catch (\Exception $e) {
            return null;
        }
    }


    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'wapinet_geoip2';
    }
}
