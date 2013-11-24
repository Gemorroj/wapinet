<?php
namespace Wapinet\Bundle\Helper;

/**
 * Phpwhois хэлпер
 */
class Phpwhois
{
    public function getWhois()
    {
        return new \Whois();
    }

    public function getUtils()
    {
        return new \utils();
    }
}
