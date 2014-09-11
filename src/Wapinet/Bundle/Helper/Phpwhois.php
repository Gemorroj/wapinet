<?php
namespace Wapinet\Bundle\Helper;

/**
 * Phpwhois хэлпер
 */
class Phpwhois
{
    /**
     * @return \Whois
     */
    public function getWhois()
    {
        return new \Whois();
    }

    /**
     * @return \utils
     */
    public function getUtils()
    {
        return new \utils();
    }
}
